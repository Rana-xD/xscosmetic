<?php

namespace App\Http\Controllers;

use App\Category;
use App\IncomingShipment;
use App\IncomingShipmentItem;
use App\Product;
use App\ProductLog;
use App\Services\ProductCacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncomingProductController extends Controller
{
    protected $productCacheService;

    public function __construct(ProductCacheService $productCacheService)
    {
        $this->middleware('auth');
        $this->productCacheService = $productCacheService;
    }

    public function index()
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return redirect('/')->with('error', 'Access denied.');
        }

        return view('incoming_products.index', [
            'canManageIncoming' => $user->isSuperAdmin(),
            'categories' => Category::orderBy('name', 'asc')->get(['id', 'name']),
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));

        $baseQuery = IncomingShipmentItem::query()
            ->leftJoin('categories', 'categories.id', '=', 'incoming_shipment_items.category_id')
            ->select([
                'incoming_shipment_items.id',
                'incoming_shipment_items.created_at',
                'incoming_shipment_items.name',
                'incoming_shipment_items.barcode',
                'incoming_shipment_items.qty',
                'incoming_shipment_items.cost',
                'incoming_shipment_items.price',
                'incoming_shipment_items.expire_date',
                'incoming_shipment_items.status',
                'categories.name as category_name',
            ])
            ->where('incoming_shipment_items.status', 'pending');

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';
            $baseQuery->where(function ($builder) use ($like) {
                $builder->where('incoming_shipment_items.name', 'like', $like)
                    ->orWhere('incoming_shipment_items.barcode', 'like', $like)
                    ->orWhere('incoming_shipment_items.expire_date', 'like', $like)
                    ->orWhere('categories.name', 'like', $like);
            });
        }

        $sortableColumns = [
            0 => 'incoming_shipment_items.created_at',
            1 => 'incoming_shipment_items.name',
            2 => 'incoming_shipment_items.barcode',
            3 => 'incoming_shipment_items.qty',
            4 => 'incoming_shipment_items.cost',
            5 => 'incoming_shipment_items.price',
            6 => 'categories.name',
            7 => 'incoming_shipment_items.expire_date',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'incoming_shipment_items.created_at';

        $baseQuery->orderBy($orderColumn, $orderDirection)
            ->orderBy('incoming_shipment_items.id', 'desc');

        $recordsTotal = IncomingShipmentItem::where('status', 'pending')->count();
        $recordsFiltered = (clone $baseQuery)->count('incoming_shipment_items.id');
        $items = $baseQuery->skip($start)->take($length)->get();

        $data = $items->values()->map(function ($item, $index) use ($start) {
            return [
                'id' => $item->id,
                'row_number' => $start + $index + 1,
                'created_at_display' => $item->created_at ? Carbon::parse($item->created_at)->format('d-m-Y H:i') : '-',
                'name' => $item->name,
                'barcode' => $item->barcode,
                'qty' => (int) $item->qty,
                'cost_display' => $item->cost !== null ? number_format((float) $item->cost, 2) . '$' : '-',
                'price_display' => $item->price !== null ? number_format((float) $item->price, 2) . '$' : '-',
                'category_name' => $item->category_name ?: '-',
                'expire_date' => $item->expire_date ?: '-',
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'expire_date' => 'nullable|string|max:255',
        ]);

        try {
            $shipment = IncomingShipment::create([
                'reference_no' => $this->generateUniqueReferenceNo(),
                'status' => 'sent',
                'notes' => null,
                'created_by' => $user->id,
                'sent_at' => Carbon::now(),
            ]);

            IncomingShipmentItem::create([
                'incoming_shipment_id' => $shipment->id,
                'name' => trim((string) $request->name),
                'barcode' => trim((string) $request->barcode),
                'qty' => (int) $request->qty,
                'cost' => $request->cost,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'expire_date' => $request->expire_date,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Incoming item created successfully.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Incoming item create failed: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create incoming item.',
            ], 422);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $item = IncomingShipmentItem::with('shipment')->find((int) $id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        if ($item->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending items can be deleted.'], 422);
        }

        $shipmentId = $item->incoming_shipment_id;
        $item->delete();

        $pendingExists = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)
            ->where('status', 'pending')
            ->exists();

        if (!$pendingExists) {
            IncomingShipment::where('id', $shipmentId)->update(['status' => 'closed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Incoming item deleted.',
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        try {
            $result = $this->confirmIncomingItem((int) $id, $user);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'created_new_product' => $result['created_new_product'],
                'product_id' => $result['product_id'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Incoming product confirm failed: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function confirmByBarcode(Request $request)
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $request->validate([
            'barcode' => 'required|string|max:255',
        ]);

        $barcode = trim((string) $request->barcode);

        $itemId = IncomingShipmentItem::query()
            ->where('status', 'pending')
            ->where('barcode', $barcode)
            ->orderBy('id', 'asc')
            ->value('id');

        if (!$itemId) {
            return response()->json([
                'success' => false,
                'message' => 'No pending item found for this barcode.',
            ], 404);
        }

        try {
            $result = $this->confirmIncomingItem((int) $itemId, $user);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'created_new_product' => $result['created_new_product'],
                'product_id' => $result['product_id'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Incoming barcode confirm failed: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function history()
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return redirect('/')->with('error', 'Access denied.');
        }

        return view('incoming_products.history');
    }

    public function historyData(Request $request)
    {
        $user = Auth::user();
        if (!$this->canVerifyIncoming($user)) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));

        $baseQuery = IncomingShipmentItem::query()
            ->leftJoin('users as confirmers', 'confirmers.id', '=', 'incoming_shipment_items.confirmed_by')
            ->leftJoin('products', 'products.id', '=', 'incoming_shipment_items.linked_product_id')
            ->select([
                'incoming_shipment_items.id',
                'incoming_shipment_items.name',
                'incoming_shipment_items.barcode',
                'incoming_shipment_items.qty',
                'incoming_shipment_items.confirmed_at',
                'confirmers.username as confirmed_by_name',
                'products.name as product_name',
            ])
            ->where('incoming_shipment_items.status', 'confirmed');

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';
            $baseQuery->where(function ($builder) use ($like) {
                $builder->where('incoming_shipment_items.name', 'like', $like)
                    ->orWhere('incoming_shipment_items.barcode', 'like', $like)
                    ->orWhere('confirmers.username', 'like', $like)
                    ->orWhere('products.name', 'like', $like);
            });
        }

        $sortableColumns = [
            0 => 'incoming_shipment_items.confirmed_at',
            1 => 'incoming_shipment_items.name',
            2 => 'incoming_shipment_items.barcode',
            3 => 'incoming_shipment_items.qty',
            4 => 'confirmers.username',
            5 => 'products.name',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'incoming_shipment_items.confirmed_at';

        $baseQuery->orderBy($orderColumn, $orderDirection)
            ->orderBy('incoming_shipment_items.id', 'desc');

        $recordsTotal = IncomingShipmentItem::where('status', 'confirmed')->count();
        $recordsFiltered = (clone $baseQuery)->count('incoming_shipment_items.id');
        $items = $baseQuery->skip($start)->take($length)->get();

        $data = $items->values()->map(function ($item, $index) use ($start) {
            return [
                'id' => $item->id,
                'row_number' => $start + $index + 1,
                'confirmed_at_display' => $item->confirmed_at ? Carbon::parse($item->confirmed_at)->format('d-m-Y H:i') : '-',
                'name' => $item->name,
                'barcode' => $item->barcode,
                'qty' => (int) $item->qty,
                'confirmed_by_name' => $item->confirmed_by_name ?: '-',
                'product_name' => $item->product_name ?: '-',
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create()
    {
        return redirect()->route('incoming-products.index');
    }

    private function canVerifyIncoming($user)
    {
        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdmin() || $user->isManager() || $user->isStaff();
    }

    private function confirmIncomingItem($itemId, $actor)
    {
        return DB::transaction(function () use ($itemId, $actor) {
            $item = IncomingShipmentItem::with('shipment')
                ->where('id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                throw new \RuntimeException('Incoming item not found.');
            }

            if ($item->status === 'confirmed') {
                throw new \RuntimeException('This item has already been confirmed.');
            }

            $barcode = trim((string) $item->barcode);
            if ($barcode === '') {
                throw new \RuntimeException('Barcode is required to confirm item.');
            }

            $matchingProducts = Product::where('product_barcode', $barcode)->lockForUpdate()->get();
            if ($matchingProducts->count() > 1) {
                throw new \RuntimeException('Multiple products found with same barcode. Please resolve duplicate barcode in Product list first.');
            }

            $product = $matchingProducts->first();
            $createdNewProduct = false;

            if ($product) {
                $product = $this->updateExistingProductFromIncomingItem($product, $item);
                $message = 'Product stock updated successfully.';
            } else {
                $product = $this->createProductFromIncomingItem($item, $barcode);
                $createdNewProduct = true;
                $message = 'New product created and stock added successfully.';
            }

            $item->status = 'confirmed';
            $item->confirmed_by = $actor->id;
            $item->confirmed_at = Carbon::now();
            $item->linked_product_id = $product->id;
            $item->save();

            $remainingPending = IncomingShipmentItem::where('incoming_shipment_id', $item->incoming_shipment_id)
                ->where('status', 'pending')
                ->exists();

            if (!$remainingPending && $item->shipment) {
                $item->shipment->status = 'closed';
                $item->shipment->save();
            }

            $this->productCacheService->clearProductCache($product->id);
            $this->productCacheService->clearCache();

            return [
                'message' => $message,
                'created_new_product' => $createdNewProduct,
                'product_id' => $product->id,
            ];
        });
    }

    private function updateExistingProductFromIncomingItem(Product $product, IncomingShipmentItem $item)
    {
        $product->stock = (int) $product->stock + (int) $item->qty;

        if ((!$product->expire_date || $product->expire_date === '') && $item->expire_date) {
            $product->expire_date = $item->expire_date;
        }

        if ((is_null($product->price) || (float) $product->price == 0.0) && !is_null($item->price)) {
            $product->price = $item->price;
        }

        if ((is_null($product->cost) || (float) $product->cost == 0.0) && !is_null($item->cost)) {
            $product->cost = $item->cost;
        }

        if (!$product->category_id && $item->category_id) {
            $product->category_id = $item->category_id;
        }

        $costGroup = $product->cost_group;
        if (!is_array($costGroup)) {
            $decodedCostGroup = json_decode($costGroup ?: '[]', true);
            $costGroup = is_array($decodedCostGroup) ? $decodedCostGroup : [];
        }

        if (!is_null($item->cost)) {
            $incomingCost = (string) $item->cost;
            if (!in_array($incomingCost, array_map('strval', $costGroup), true)) {
                $costGroup[] = $item->cost;
            }
        }

        $product->cost_group = array_values($costGroup);
        $product->save();

        $this->createProductLog(
            $product,
            'edit',
            (int) $item->qty,
            $product->product_barcode,
            'incoming item confirm'
        );

        return $product;
    }

    private function createProductFromIncomingItem(IncomingShipmentItem $item, $barcode)
    {
        $categoryId = $item->category_id ?: Category::orderBy('id', 'asc')->value('id');
        if (!$categoryId) {
            throw new \RuntimeException('No category available. Please create category first.');
        }

        $cost = is_null($item->cost) ? 0 : (float) $item->cost;

        $product = Product::create([
            'name' => $item->name,
            'product_barcode' => $barcode,
            'category_id' => $categoryId,
            'stock' => (int) $item->qty,
            'expire_date' => $item->expire_date,
            'price' => is_null($item->price) ? 0 : (float) $item->price,
            'cost' => $cost,
            'cost_group' => $cost == 0.0 ? [] : [$cost],
            'photo' => 'default.jpg',
        ]);

        $this->createProductLog(
            $product,
            'create',
            (int) $item->qty,
            $product->product_barcode,
            'incoming item confirm'
        );

        return $product;
    }

    private function createProductLog($product, $action, $stock, $barcode, $additionalAction = '')
    {
        $today = Carbon::now()->format('Y-m-d');
        $productLog = ProductLog::where('date', $today)->first();

        $item = [
            'id' => $product->id,
            'name' => $product->name,
            'action' => $action,
            'stock' => $stock,
            'barcode' => $barcode,
            'additional_action' => $additionalAction,
        ];

        if (empty($productLog)) {
            ProductLog::create([
                'date' => $today,
                'items' => [$item],
            ]);
            return;
        }

        $items = $productLog->items;
        $found = false;

        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as &$existingItem) {
            if (isset($existingItem['id']) && (int) $existingItem['id'] === (int) $product->id) {
                $existingItem['name'] = $product->name;
                $existingItem['barcode'] = $product->product_barcode;
                $existingItem['stock'] = (int) ($existingItem['stock'] ?? 0) + (int) $stock;
                $existingItem['action'] = $action;
                $existingItem['additional_action'] = $additionalAction;
                $found = true;
                break;
            }
        }
        unset($existingItem);

        if (!$found) {
            $items[] = $item;
        }

        $productLog->items = $items;
        $productLog->save();
    }

    private function generateUniqueReferenceNo()
    {
        $base = 'IN-' . Carbon::now()->format('YmdHis');
        $referenceNo = $base;
        $counter = 1;

        while (IncomingShipment::where('reference_no', $referenceNo)->exists()) {
            $referenceNo = $base . '-' . $counter;
            $counter++;
        }

        return $referenceNo;
    }
}

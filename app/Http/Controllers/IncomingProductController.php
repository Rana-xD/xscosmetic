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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomingProductController extends Controller
{
    protected $productCacheService;

    public function __construct(ProductCacheService $productCacheService)
    {
        $this->middleware('auth');
        $this->productCacheService = $productCacheService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $openBatches = $this->getPendingBatches();
        $selectedBatch = $this->resolveSelectedBatch($openBatches, $request->query('shipment_id'));

        return view('incoming_products.index', [
            'openBatches' => $openBatches,
            'selectedBatch' => $selectedBatch,
            'selectedShipmentId' => optional($selectedBatch)->id,
            'categories' => Category::orderBy('name', 'ASC')->get(['id', 'name']),
            'canManageIncoming' => $this->canManageIncoming($user),
            'canSeeCost' => $this->canSeeCost($user),
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $shipmentId = (int) $request->input('shipment_id');
        if ($shipmentId <= 0) {
            return $this->emptyDataTableResponse($request);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));
        $barcodeFilter = trim((string) $request->input('barcode_filter', ''));
        $canSeeCost = $this->canSeeCost($user);

        $query = IncomingShipmentItem::query()
            ->leftJoin('categories', 'categories.id', '=', 'incoming_shipment_items.category_id')
            ->where('incoming_shipment_items.incoming_shipment_id', $shipmentId)
            ->where('incoming_shipment_items.status', 'pending')
            ->select([
                'incoming_shipment_items.id',
                'incoming_shipment_items.created_at',
                'incoming_shipment_items.name',
                'incoming_shipment_items.barcode',
                'incoming_shipment_items.qty',
                'incoming_shipment_items.cost',
                'incoming_shipment_items.price',
                'incoming_shipment_items.category_id',
                'incoming_shipment_items.expire_date',
                'categories.name as category_name',
            ]);

        if ($barcodeFilter !== '') {
            $query->where('incoming_shipment_items.barcode', 'like', '%' . $barcodeFilter . '%');
        }

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';

            $query->where(function ($builder) use ($like) {
                $builder->where('incoming_shipment_items.name', 'like', $like)
                    ->orWhere('incoming_shipment_items.barcode', 'like', $like)
                    ->orWhere('incoming_shipment_items.qty', 'like', $like)
                    ->orWhere('incoming_shipment_items.price', 'like', $like)
                    ->orWhere('incoming_shipment_items.expire_date', 'like', $like)
                    ->orWhere('categories.name', 'like', $like)
                    ->orWhereRaw("DATE_FORMAT(incoming_shipment_items.created_at, '%d-%m-%Y') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(incoming_shipment_items.created_at, '%Y-%m-%d') like ?", [$like]);

                if ($this->canSeeCost(Auth::user())) {
                    $builder->orWhere('incoming_shipment_items.cost', 'like', $like);
                }
            });
        }

        $sortableColumns = $this->pendingSortableColumns($canSeeCost);
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'incoming_shipment_items.created_at';

        $query->orderBy($orderColumn, $orderDirection)
            ->orderBy('incoming_shipment_items.id', 'desc');

        $recordsTotal = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)
            ->where('status', 'pending')
            ->count();
        $recordsFiltered = (clone $query)->count('incoming_shipment_items.id');

        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($item) use ($canSeeCost) {
            $row = [
                'id' => $item->id,
                'created_at_display' => optional($item->created_at)->format('d-m-Y'),
                'name' => $item->name,
                'barcode' => $item->barcode,
                'qty' => $item->qty,
                'price' => $item->price !== null ? (string) $item->price : '',
                'category_id' => $item->category_id,
                'expire_date_raw' => $item->expire_date ?: '',
                'price_display' => $item->price !== null ? $this->formatMoney($item->price) : '-',
                'category_name' => $item->category_name ?: '-',
                'expire_date' => $item->expire_date ?: '-',
            ];

            if ($canSeeCost) {
                $row['cost'] = $item->cost !== null ? (string) $item->cost : '';
                $row['cost_display'] = $item->cost !== null ? $this->formatMoney($item->cost) : '-';
            }

            return $row;
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function storeBatch(Request $request)
    {
        $user = Auth::user();
        if (!$this->canManageIncoming($user)) {
            return $this->jsonUnauthorizedResponse();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $shipment = IncomingShipment::create([
            'reference_no' => $this->generateUniqueReferenceNo(),
            'title' => trim($validated['title']),
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
            'sent_at' => Carbon::now(),
        ]);

        $this->syncShipmentStatus($shipment->id);

        return response()->json([
            'success' => true,
            'message' => __('messages.batch_created'),
            'shipment_id' => $shipment->id,
            'redirect_url' => route('incoming-products.index', ['shipment_id' => $shipment->id]),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$this->canManageIncoming($user)) {
            return $this->jsonUnauthorizedResponse();
        }

        $validated = $request->validate($this->incomingItemRules());

        $shipment = IncomingShipment::find($validated['incoming_shipment_id']);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => __('messages.incoming_batch_not_found'),
            ], 404);
        }

        if ($shipment->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('messages.incoming_batch_closed'),
            ], 422);
        }

        IncomingShipmentItem::create([
            'incoming_shipment_id' => $shipment->id,
            'name' => trim($validated['name']),
            'barcode' => trim($validated['barcode']),
            'qty' => (int) $validated['qty'],
            'cost' => array_key_exists('cost', $validated) && $validated['cost'] !== null ? $validated['cost'] : null,
            'price' => array_key_exists('price', $validated) && $validated['price'] !== null ? $validated['price'] : null,
            'category_id' => $validated['category_id'] ?? null,
            'expire_date' => $validated['expire_date'] ?? null,
            'status' => 'pending',
        ]);

        $this->syncShipmentStatus($shipment->id);

        return response()->json([
            'success' => true,
            'message' => __('messages.incoming_item_created'),
            'batch' => $this->getShipmentSummary($shipment->id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$this->canManageIncoming($user)) {
            return $this->jsonUnauthorizedResponse();
        }

        $validated = $request->validate($this->incomingItemRules());

        $result = DB::transaction(function () use ($id, $validated) {
            $item = IncomingShipmentItem::where('id', (int) $id)->lockForUpdate()->first();

            if (!$item || $item->status !== 'pending') {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => __('messages.pending_item_not_found'),
                ];
            }

            if ((int) $item->incoming_shipment_id !== (int) $validated['incoming_shipment_id']) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.select_batch_first'),
                ];
            }

            $shipment = IncomingShipment::where('id', (int) $validated['incoming_shipment_id'])->lockForUpdate()->first();

            if (!$shipment) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => __('messages.incoming_batch_not_found'),
                ];
            }

            if ($shipment->status === 'completed') {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.incoming_batch_closed'),
                ];
            }

            $item->name = trim($validated['name']);
            $item->barcode = trim($validated['barcode']);
            $item->qty = (int) $validated['qty'];
            $item->cost = array_key_exists('cost', $validated) && $validated['cost'] !== null ? $validated['cost'] : null;
            $item->price = array_key_exists('price', $validated) && $validated['price'] !== null ? $validated['price'] : null;
            $item->category_id = $validated['category_id'] ?? null;
            $item->expire_date = $validated['expire_date'] ?? null;
            $item->save();

            return [
                'success' => true,
                'status' => 200,
                'message' => __('messages.incoming_item_updated'),
                'batch' => $this->getShipmentSummary($shipment->id),
            ];
        });

        return response()->json($result, $result['status']);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$this->canManageIncoming($user)) {
            return $this->jsonUnauthorizedResponse();
        }

        $result = DB::transaction(function () use ($id) {
            $item = IncomingShipmentItem::where('id', (int) $id)->lockForUpdate()->first();

            if (!$item) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => __('messages.pending_item_not_found'),
                ];
            }

            if ($item->status !== 'pending') {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.pending_item_not_found'),
                ];
            }

            $shipmentId = (int) $item->incoming_shipment_id;
            $item->delete();

            $remainingItems = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)->count();
            if ($remainingItems === 0) {
                IncomingShipment::where('id', $shipmentId)->delete();

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.incoming_item_deleted'),
                    'redirect_url' => $this->pendingIndexUrl($shipmentId),
                ];
            }

            $this->syncShipmentStatus($shipmentId);

            return [
                'success' => true,
                'status' => 200,
                'message' => __('messages.incoming_item_deleted'),
                'batch' => $this->getShipmentSummary($shipmentId),
            ];
        });

        return response()->json($result, $result['status']);
    }

    public function closeBatch($id)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $result = DB::transaction(function () use ($id) {
            $shipment = IncomingShipment::where('id', (int) $id)->lockForUpdate()->first();

            if (!$shipment) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => __('messages.incoming_batch_not_found'),
                ];
            }

            $pendingCount = IncomingShipmentItem::where('incoming_shipment_id', $shipment->id)
                ->where('status', 'pending')
                ->count();
            $totalCount = IncomingShipmentItem::where('incoming_shipment_id', $shipment->id)->count();

            if ($totalCount === 0) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.batch_close_requires_items'),
                ];
            }

            if ($pendingCount > 0) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.batch_close_requires_no_pending'),
                ];
            }

            $shipment->status = 'completed';
            if (!$shipment->sent_at) {
                $shipment->sent_at = Carbon::now();
            }
            $shipment->save();

            return [
                'success' => true,
                'status' => 200,
                'message' => __('messages.batch_closed'),
                'redirect_url' => $this->pendingIndexUrl($shipment->id),
            ];
        });

        return response()->json($result, $result['status']);
    }

    public function confirm(Request $request, $id)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $result = $this->confirmIncomingItem((int) $id, (int) $request->input('shipment_id'));

        return response()->json($result, $result['status']);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $historyBatches = $this->getHistoryBatches();
        $selectedBatch = $this->resolveSelectedBatch($historyBatches, $request->query('shipment_id'));

        return view('incoming_products.history', [
            'historyBatches' => $historyBatches,
            'selectedBatch' => $selectedBatch,
            'selectedShipmentId' => optional($selectedBatch)->id,
            'canSeeCost' => $this->canSeeCost($user),
        ]);
    }

    public function historyData(Request $request)
    {
        $user = Auth::user();
        $this->abortIfUnauthorized($user);

        $shipmentId = (int) $request->input('shipment_id');
        if ($shipmentId <= 0) {
            return $this->emptyDataTableResponse($request);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));
        $canSeeCost = $this->canSeeCost($user);

        $query = IncomingShipmentItem::query()
            ->leftJoin('users', 'users.id', '=', 'incoming_shipment_items.confirmed_by')
            ->leftJoin('products', 'products.id', '=', 'incoming_shipment_items.linked_product_id')
            ->where('incoming_shipment_items.incoming_shipment_id', $shipmentId)
            ->where('incoming_shipment_items.status', 'confirmed')
            ->select([
                'incoming_shipment_items.id',
                'incoming_shipment_items.confirmed_at',
                'incoming_shipment_items.name',
                'incoming_shipment_items.barcode',
                'incoming_shipment_items.qty',
                'incoming_shipment_items.cost',
                'incoming_shipment_items.price',
                'users.username as confirmed_by_name',
                'products.name as product_name',
            ]);

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';

            $query->where(function ($builder) use ($like) {
                $builder->where('incoming_shipment_items.name', 'like', $like)
                    ->orWhere('incoming_shipment_items.barcode', 'like', $like)
                    ->orWhere('incoming_shipment_items.qty', 'like', $like)
                    ->orWhere('incoming_shipment_items.price', 'like', $like)
                    ->orWhere('users.username', 'like', $like)
                    ->orWhere('products.name', 'like', $like)
                    ->orWhereRaw("DATE_FORMAT(incoming_shipment_items.confirmed_at, '%d-%m-%Y %H:%i') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(incoming_shipment_items.confirmed_at, '%Y-%m-%d %H:%i:%s') like ?", [$like]);

                if ($this->canSeeCost(Auth::user())) {
                    $builder->orWhere('incoming_shipment_items.cost', 'like', $like);
                }
            });
        }

        $sortableColumns = $this->historySortableColumns($canSeeCost);
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'incoming_shipment_items.confirmed_at';

        $query->orderBy($orderColumn, $orderDirection)
            ->orderBy('incoming_shipment_items.id', 'desc');

        $recordsTotal = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)
            ->where('status', 'confirmed')
            ->count();
        $recordsFiltered = (clone $query)->count('incoming_shipment_items.id');
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($item) use ($canSeeCost) {
            $row = [
                'confirmed_at_display' => optional($item->confirmed_at)->format('d-m-Y H:i'),
                'name' => $item->name,
                'barcode' => $item->barcode,
                'qty' => $item->qty,
                'price_display' => $item->price !== null ? $this->formatMoney($item->price) : '-',
                'confirmed_by_name' => $item->confirmed_by_name ?: '-',
                'product_name' => $item->product_name ?: '-',
            ];

            if ($canSeeCost) {
                $row['cost_display'] = $item->cost !== null ? $this->formatMoney($item->cost) : '-';
            }

            return $row;
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

    private function confirmIncomingItem($itemId, $shipmentId = 0)
    {
        return DB::transaction(function () use ($itemId, $shipmentId) {
            $item = IncomingShipmentItem::with('shipment')
                ->where('id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$item || $item->status !== 'pending') {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => __('messages.pending_item_not_found'),
                ];
            }

            if ($shipmentId > 0 && (int) $item->incoming_shipment_id !== $shipmentId) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.select_batch_first'),
                ];
            }

            $productQuery = Product::where('product_barcode', $item->barcode)->lockForUpdate();
            $products = $productQuery->get();

            if ($products->count() > 1) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => __('messages.incoming_duplicate_barcode'),
                ];
            }

            $product = $products->count() === 1
                ? $this->updateExistingProductFromIncomingItem($products->first(), $item)
                : $this->createProductFromIncomingItem($item);

            $item->status = 'confirmed';
            $item->confirmed_by = Auth::id();
            $item->confirmed_at = Carbon::now();
            $item->linked_product_id = $product->id;
            $item->save();

            $batchSummary = $this->syncShipmentStatus($item->incoming_shipment_id);

            return [
                'success' => true,
                'status' => 200,
                'message' => __('messages.incoming_item_confirmed'),
                'batch' => $batchSummary,
                'redirect_url' => null,
            ];
        });
    }

    private function updateExistingProductFromIncomingItem(Product $product, IncomingShipmentItem $item)
    {
        $product->stock = (int) $product->stock + (int) $item->qty;

        if (!$product->category_id && $item->category_id) {
            $product->category_id = $item->category_id;
        }

        if ((!$product->expire_date || $product->expire_date === '-') && $item->expire_date) {
            $product->expire_date = $item->expire_date;
        }

        if (($product->price === null || (float) $product->price === 0.0) && $item->price !== null) {
            $product->price = $item->price;
        }

        $costGroup = $this->normalizeCostGroup($product->cost_group);
        if ($item->cost !== null) {
            if ($product->cost === null || (float) $product->cost === 0.0) {
                $product->cost = $item->cost;
            }

            if (!in_array((string) $item->cost, array_map('strval', $costGroup), true)) {
                $costGroup[] = $item->cost;
            }
        }

        $product->cost_group = $costGroup;
        $product->save();

        $this->createProductLog($product, 'edit', (int) $item->qty, $product->product_barcode, 'incoming item confirm');
        $this->productCacheService->clearProductCache($product->id);
        $this->productCacheService->clearCache();

        return $product;
    }

    private function createProductFromIncomingItem(IncomingShipmentItem $item)
    {
        $product = Product::create([
            'name' => $item->name,
            'product_barcode' => $item->barcode,
            'stock' => $item->qty,
            'price' => $item->price !== null ? $item->price : 0,
            'cost' => $item->cost !== null ? $item->cost : 0,
            'cost_group' => $item->cost !== null ? [$item->cost] : [],
            'expire_date' => $item->expire_date,
            'photo' => 'default.jpg',
            'category_id' => $item->category_id,
        ]);

        $this->createProductLog($product, 'create', (int) $item->qty, $item->barcode, 'incoming item confirm');
        $this->productCacheService->clearProductCache($product->id);
        $this->productCacheService->clearCache();

        return $product;
    }

    private function getPendingBatches()
    {
        return $this->getDecoratedShipments()->filter(function ($shipment) {
            return $shipment->status_key !== 'completed';
        })->values();
    }

    private function getHistoryBatches()
    {
        return $this->getDecoratedShipments()->filter(function ($shipment) {
            return $shipment->confirmed_items_count > 0 || $shipment->status_key === 'completed';
        })->values();
    }

    private function getDecoratedShipments()
    {
        return IncomingShipment::query()
            ->with('creator:id,username')
            ->withCount([
                'items as pending_items_count' => function ($query) {
                    $query->where('status', 'pending');
                },
                'items as confirmed_items_count' => function ($query) {
                    $query->where('status', 'confirmed');
                },
                'items as total_items_count',
            ])
            ->orderByRaw('COALESCE(sent_at, created_at) desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($shipment) {
                $statusKey = $this->determineShipmentStatus(
                    $shipment->status,
                    (int) $shipment->pending_items_count,
                    (int) $shipment->confirmed_items_count,
                    (int) $shipment->total_items_count
                );

                if ($statusKey === 'partial' && (int) $shipment->pending_items_count === 0 && (int) $shipment->confirmed_items_count > 0) {
                    $statusKey = 'ready';
                }

                $shipment->status_key = $statusKey;
                $shipment->status_label = $this->shipmentStatusLabel($statusKey);
                $shipment->status_badge_class = $this->shipmentStatusBadgeClass($statusKey);
                $shipment->created_at_display = optional($shipment->created_at)->format('d-m-Y H:i');
                $shipment->sent_at_display = optional($shipment->sent_at)->format('d-m-Y H:i');
                $shipment->creator_name = optional($shipment->creator)->username ?: '-';

                return $shipment;
            });
    }

    private function resolveSelectedBatch(Collection $batches, $selectedId)
    {
        $selectedId = (int) $selectedId;
        if ($selectedId > 0) {
            $selectedBatch = $batches->firstWhere('id', $selectedId);
            if ($selectedBatch) {
                return $selectedBatch;
            }
        }

        return $batches->first();
    }

    private function pendingSortableColumns($canSeeCost)
    {
        $columns = [
            0 => 'incoming_shipment_items.created_at',
            1 => 'incoming_shipment_items.name',
            2 => 'incoming_shipment_items.barcode',
            3 => 'incoming_shipment_items.qty',
        ];

        if ($canSeeCost) {
            $columns[4] = 'incoming_shipment_items.cost';
            $columns[5] = 'incoming_shipment_items.price';
            $columns[6] = 'categories.name';
            $columns[7] = 'incoming_shipment_items.expire_date';
        } else {
            $columns[4] = 'incoming_shipment_items.price';
            $columns[5] = 'categories.name';
            $columns[6] = 'incoming_shipment_items.expire_date';
        }

        return $columns;
    }

    private function historySortableColumns($canSeeCost)
    {
        $columns = [
            0 => 'incoming_shipment_items.confirmed_at',
            1 => 'incoming_shipment_items.name',
            2 => 'incoming_shipment_items.barcode',
            3 => 'incoming_shipment_items.qty',
        ];

        if ($canSeeCost) {
            $columns[4] = 'incoming_shipment_items.cost';
            $columns[5] = 'incoming_shipment_items.price';
            $columns[6] = 'users.username';
            $columns[7] = 'products.name';
        } else {
            $columns[4] = 'incoming_shipment_items.price';
            $columns[5] = 'users.username';
            $columns[6] = 'products.name';
        }

        return $columns;
    }

    private function formatMoney($amount)
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $formatted = number_format((float) $amount, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted . '$';
    }

    private function normalizeCostGroup($costGroup)
    {
        if (is_array($costGroup)) {
            return array_values(array_filter($costGroup, function ($value) {
                return $value !== null && $value !== '';
            }));
        }

        $decoded = json_decode($costGroup ?? '[]', true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, function ($value) {
                return $value !== null && $value !== '';
            }))
            : [];
    }

    private function syncShipmentStatus($shipmentId)
    {
        $shipment = IncomingShipment::find($shipmentId);
        if (!$shipment) {
            return null;
        }

        $pendingCount = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)
            ->where('status', 'pending')
            ->count();
        $confirmedCount = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)
            ->where('status', 'confirmed')
            ->count();
        $totalCount = IncomingShipmentItem::where('incoming_shipment_id', $shipmentId)->count();

        $shipment->status = $this->determineShipmentStatus($shipment->status, $pendingCount, $confirmedCount, $totalCount);
        if (!$shipment->sent_at) {
            $shipment->sent_at = Carbon::now();
        }
        $shipment->save();

        return $this->getShipmentSummary($shipmentId);
    }

    private function determineShipmentStatus($currentStatus, $pendingCount, $confirmedCount, $totalCount)
    {
        if ($currentStatus === 'completed') {
            return 'completed';
        }

        if ($totalCount === 0) {
            return 'open';
        }

        if ($confirmedCount > 0) {
            return 'partial';
        }

        return 'open';
    }

    private function shipmentStatusLabel($statusKey)
    {
        switch ($statusKey) {
            case 'ready':
                return __('messages.status_ready_to_close');
            case 'partial':
                return __('messages.status_partial');
            case 'completed':
                return __('messages.status_completed');
            default:
                return __('messages.status_open');
        }
    }

    private function shipmentStatusBadgeClass($statusKey)
    {
        switch ($statusKey) {
            case 'ready':
                return 'info';
            case 'partial':
                return 'warning';
            case 'completed':
                return 'success';
            default:
                return 'primary';
        }
    }

    private function getShipmentSummary($shipmentId)
    {
        $shipment = IncomingShipment::query()
            ->with('creator:id,username')
            ->withCount([
                'items as pending_items_count' => function ($query) {
                    $query->where('status', 'pending');
                },
                'items as confirmed_items_count' => function ($query) {
                    $query->where('status', 'confirmed');
                },
                'items as total_items_count',
            ])
            ->find($shipmentId);

        if (!$shipment) {
            return null;
        }

        $statusKey = $this->determineShipmentStatus(
            $shipment->status,
            (int) $shipment->pending_items_count,
            (int) $shipment->confirmed_items_count,
            (int) $shipment->total_items_count
        );

        if ($statusKey === 'partial' && (int) $shipment->pending_items_count === 0 && (int) $shipment->confirmed_items_count > 0) {
            $statusKey = 'ready';
        }

        return [
            'id' => $shipment->id,
            'title' => $shipment->title ?: $shipment->reference_no,
            'notes' => $shipment->notes,
            'status_key' => $statusKey,
            'status_label' => $this->shipmentStatusLabel($statusKey),
            'status_badge_class' => $this->shipmentStatusBadgeClass($statusKey),
            'pending_items_count' => (int) $shipment->pending_items_count,
            'confirmed_items_count' => (int) $shipment->confirmed_items_count,
            'total_items_count' => (int) $shipment->total_items_count,
            'creator_name' => optional($shipment->creator)->username ?: '-',
            'created_at_display' => optional($shipment->created_at)->format('d-m-Y H:i'),
            'sent_at_display' => optional($shipment->sent_at)->format('d-m-Y H:i'),
        ];
    }

    private function pendingIndexUrl($currentShipmentId)
    {
        $nextBatch = $this->getPendingBatches()->first(function ($batch) use ($currentShipmentId) {
            return (int) $batch->id !== (int) $currentShipmentId;
        });
        if ($nextBatch) {
            return route('incoming-products.index', ['shipment_id' => $nextBatch->id]);
        }

        return route('incoming-products.index');
    }

    private function emptyDataTableResponse(Request $request)
    {
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
    }

    private function incomingItemRules()
    {
        return [
            'incoming_shipment_id' => 'required|integer|exists:incoming_shipments,id',
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'expire_date' => 'nullable|date_format:Y-m-d',
        ];
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

        if (!$productLog) {
            ProductLog::create([
                'date' => $today,
                'items' => [$item],
            ]);

            return;
        }

        $items = $productLog->items;
        $found = false;

        foreach ($items as &$existingItem) {
            if ($existingItem['id'] === $product->id) {
                $existingItem['name'] = $product->name;
                $existingItem['barcode'] = $product->product_barcode;
                $existingItem['stock'] += $stock;
                $existingItem['action'] = $action;
                $existingItem['additional_action'] = $additionalAction;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $items[] = $item;
        }

        $productLog->items = $items;
        $productLog->save();
    }

    private function generateUniqueReferenceNo()
    {
        do {
            $referenceNo = 'INC-' . Carbon::now()->format('YmdHis') . '-' . mt_rand(1000, 9999);
        } while (IncomingShipment::where('reference_no', $referenceNo)->exists());

        return $referenceNo;
    }

    private function abortIfUnauthorized($user)
    {
        if (!$this->canAccessIncoming($user)) {
            abort(403);
        }
    }

    private function canAccessIncoming($user)
    {
        return $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isManager() || $user->isStaff());
    }

    private function canManageIncoming($user)
    {
        return $user && $user->isSuperAdmin();
    }

    private function canSeeCost($user)
    {
        return $user && $user->isSuperAdmin();
    }

    private function jsonUnauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized.',
        ], 403);
    }
}

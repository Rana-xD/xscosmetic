<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Product;
use App\ProductLog;
use App\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductCacheService;

class ProductController extends Controller
{
    protected $productCacheService;

    public function __construct(ProductCacheService $productCacheService)
    {
        $this->middleware('auth');
        $this->middleware('adminormanager');
        $this->productCacheService = $productCacheService;
    }

    public function show()
    {
        return view('product.view', [
            'categories' => Category::orderBy('name', 'ASC')->get(['id', 'name'])
        ]);
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 25);
        $length = $length > 0 ? min($length, 100) : 25;
        $searchValue = trim((string) $request->input('search.value', ''));

        $query = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'products.id',
                'products.updated_at',
                'products.name',
                'products.product_barcode',
                'products.stock',
                'products.price',
                'products.cost',
                'products.cost_group',
                'products.expire_date',
                'products.photo',
                'products.category_id',
                'categories.name as category_name',
            ]);

        if ($searchValue !== '') {
            $like = '%' . $searchValue . '%';

            $query->where(function ($builder) use ($like) {
                $builder->where('products.name', 'like', $like)
                    ->orWhere('products.product_barcode', 'like', $like)
                    ->orWhere('categories.name', 'like', $like)
                    ->orWhere('products.stock', 'like', $like)
                    ->orWhere('products.price', 'like', $like)
                    ->orWhere('products.cost', 'like', $like)
                    ->orWhere('products.expire_date', 'like', $like)
                    ->orWhereRaw("DATE_FORMAT(products.updated_at, '%d-%m-%Y') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(products.updated_at, '%Y-%m-%d') like ?", [$like]);
            });
        }

        $sortableColumns = [
            1 => 'products.updated_at',
            2 => 'products.name',
            3 => 'products.product_barcode',
            4 => 'products.stock',
            5 => 'products.price',
            6 => 'products.cost',
            7 => 'categories.name',
            8 => 'products.expire_date',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $sortableColumns[$orderColumnIndex] ?? 'products.updated_at';

        $query->orderBy($orderColumn, $orderDirection)
            ->orderBy('products.id', 'desc');

        $recordsTotal = Product::count();
        $recordsFiltered = (clone $query)->count('products.id');

        $products = $query->skip($start)->take($length)->get();

        $data = $products->values()->map(function ($product, $index) use ($start) {
            $costGroup = $product->cost_group;

            if (!is_array($costGroup)) {
                $decodedCostGroup = json_decode($costGroup ?? '[]', true);
                $costGroup = is_array($decodedCostGroup) ? $decodedCostGroup : [];
            }

            $costGroup = array_values(array_filter($costGroup, function ($value) {
                return $value !== null && $value !== '';
            }));

            return [
                'id' => $product->id,
                'row_number' => $start + $index + 1,
                'updated_at_display' => optional($product->updated_at)->format('d-m-Y'),
                'updated_at_order' => optional($product->updated_at)->format('Y-m-d H:i:s'),
                'name' => $product->name,
                'product_barcode' => $product->product_barcode ?? '',
                'stock' => $product->stock,
                'price' => $product->price,
                'price_display' => $product->price !== null ? $product->price . '$' : '-',
                'cost' => $product->cost,
                'cost_group_raw' => implode(', ', $costGroup),
                'cost_group_display' => empty($costGroup)
                    ? ($product->cost !== null ? $product->cost . '$' : '-')
                    : collect($costGroup)->map(function ($value) {
                        return $value . '$';
                    })->implode(' , '),
                'category_id' => $product->category_id,
                'category_name' => $product->category_name ?? '-',
                'expire_date' => $product->expire_date ?? '',
                'photo_url' => '/storage/product_images/' . ($product->photo ?: 'default.jpg'),
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


        if ($request->hasFile('photo')) {
            $filenameWithExt = $request->file('photo')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $path = $request->file('photo')->storeAs('public/product_images', $fileNameToStore);
        } else {
            $fileNameToStore = 'default.jpg';
        }
        $data = [
            "name" => $request->name,
            "product_barcode" => $request->product_barcode === '' ? null : $request->product_barcode,
            "category_id" => $request->category_id,
            // "unit_id" => $request->unit_id,
            "stock" => $request->stock,
            "expire_date" => $request->expire_date,
            "price" => $request->price === 0 ? 0 : $request->price,
            "cost" => $request->cost === 0 ? 0 : $request->cost,
            "cost_group" => $request->cost === 0 ? [] : [$request->cost],
            "photo" => $fileNameToStore
        ];

        $product_exist = Product::where('product_barcode', $request->product_barcode)->where('name', $request->name)->first();

        if ($product_exist) {
            return response()->json([
                'code' => 404
            ]);
        }

        $result = Product::create($data);
        $this->createProductLog($result, 'create', $result->stock, $result->product_barcode);
        
        // Clear cache after creating new product
        $this->productCacheService->clearCache();
        Log::info('Cache cleared after product creation');

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int)$request->id;
        $product = Product::find($id);
        $product->delete();

        $this->createProductLog($product, 'delete', $product->stock, $product->product_barcode);
        
        // Clear cache after deleting product
        $this->productCacheService->clearProductCache($id);
        $this->productCacheService->clearCache();
        Log::info('Cache cleared after product deletion');

        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request)
    {

        $default_img = 'default.jpg';
        $id = $request->id;
        $product = Product::find($id);
        $stock = $request->stock + intval($request->new_stock);
        $data = [
            "name" => $request->name,
            "product_barcode" => $request->product_barcode === '' ? null : $request->product_barcode,
            "category_id" => $request->category_id,
            // "unit_id" =>$request->unit_id,
            "stock" => $stock,
            "expire_date" => $request->expire_date,
            "price" => $request->price === 0 ? 0 : $request->price,
        ];
        $costGroupData = explode(', ', $request->cost);
        $data['cost_group'] = $costGroupData;
        if ($request->new_cost != 0) {
            $data['cost'] = $request->new_cost;
            $costGroupData = array_filter(explode(', ', $request->cost), function ($value) {
                return $value !== '0';
            });
            array_push($costGroupData, $request->new_cost);
            $data['cost_group'] = $costGroupData;
        }

        if ($request->hasFile('photo')) {
            if ($product->photo != $default_img) {
                unlink(storage_path('app/public/product_images/' . $product->photo));
            }

            $filenameWithExt = $request->file('photo')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $path = $request->file('photo')->storeAs('public/product_images', $fileNameToStore);
            $data['photo'] = $fileNameToStore;
        }


        $product = Product::find($id);

        $isUpdatedName = $product->name !== $request->name;
        $isUpdatedBarcode = $product->product_barcode !== $request->product_barcode;

        $product->update($data);
        
        // Clear cache after updating product
        $this->productCacheService->clearProductCache($id);
        $this->productCacheService->clearCache();
        Log::info('Cache cleared after product update');

        if ($isUpdatedName && $isUpdatedBarcode && intval($request->new_stock) != 0) {
            $this->createProductLog($product, 'edit', intval($request->new_stock), $product->product_barcode, 'edit name and product barcode and update stock');
        } else if ($isUpdatedName && $isUpdatedBarcode) {
            $this->createProductLog($product, 'edit', intval($request->new_stock), $product->product_barcode, 'edit name and product barcode');
        } else if ($isUpdatedName && intval($request->new_stock) != 0) {
            $this->createProductLog($product, 'edit', intval($request->new_stock), $product->product_barcode, 'edit name and update stock');
        } else if ($isUpdatedBarcode && intval($request->new_stock) != 0) {
            $this->createProductLog($product, 'edit', intval($request->new_stock), $product->product_barcode, 'edit product barcode and update stock');
        } else if ($isUpdatedName) {
            $this->createProductLog($product, 'edit', 0, $product->product_barcode, 'edit name');
        } else if ($isUpdatedBarcode) {
            $this->createProductLog($product, 'edit', 0, $product->product_barcode, 'edit product barcode');
        } else if (intval($request->new_stock) != 0) {
            $this->createProductLog($product, 'edit', intval($request->new_stock), $product->product_barcode, 'update stock');
        }

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    private function createProductLog($product, $action, $stock, $barcode, $additional_action = '')
    {
        $today = Carbon::now()->format('Y-m-d');
        $product_log = ProductLog::where('date', $today)->first();
        
        $item = [
            'id' => $product->id,
            'name' => $product->name,
            'action' => $action,
            'stock' => $stock,
            'barcode' => $barcode,
            'additional_action' => $additional_action
        ];

        if (empty($product_log)) {
            $data = [
                'date' => $today,
                'items' => [$item]
            ];
            ProductLog::create($data);
        } else {
            $items = $product_log->items;
            $found = false;
            
            foreach ($items as &$existing_item) {
                if ($existing_item['id'] === $product->id) {
                    $existing_item['name'] = $product->name;
                    $existing_item['barcode'] = $product->product_barcode;
                    $existing_item['stock'] += $stock;
                    $existing_item['action'] = $action;
                    $existing_item['additional_action'] = $additional_action;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $items[] = $item;
            }
            
            $product_log->items = $items;
            $product_log->save();
        }
    }
}

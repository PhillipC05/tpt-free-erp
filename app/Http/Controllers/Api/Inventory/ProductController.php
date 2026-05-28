<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Inventory\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    protected array $validationRules = [
        'sku' => 'required|string|max:50|unique:inventory_products,sku',
        'barcode' => 'nullable|string|max:100|unique:inventory_products,barcode',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'category_id' => 'nullable|exists:inventory_categories,id',
        'unit' => 'required|string|max:20',
        'unit_price' => 'numeric|min:0',
        'cost_price' => 'numeric|min:0',
        'weight' => 'numeric|min:0',
        'image_url' => 'nullable|string|max:500',
        'is_active' => 'boolean',
        'valuation_method' => 'nullable|string|in:fifo,weighted_average,standard',
        'min_stock_level' => 'numeric|min:0',
        'max_stock_level' => 'numeric|min:0',
    ];

    public function __construct()
    {
        parent::__construct(new Product());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'sku' => 'required|string|max:50|unique:inventory_products,sku',
        ]));
        if ($error) return $error;

        $product = Product::create($request->all());
        return $this->respondCreated($product, 'Product created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'sku' => 'required|string|max:50|unique:inventory_products,sku,' . $id,
            'barcode' => 'nullable|string|max:100|unique:inventory_products,barcode,' . $id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'unit' => 'required|string|max:20',
            'unit_price' => 'numeric|min:0',
            'cost_price' => 'numeric|min:0',
            'weight' => 'numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'valuation_method' => 'nullable|string|in:fifo,weighted_average,standard',
            'min_stock_level' => 'numeric|min:0',
            'max_stock_level' => 'numeric|min:0',
        ]);
        if ($error) return $error;

        $product->update($request->all());
        return $this->respondSuccess('Product updated', $product->fresh());
    }

    public function stockLevels(int $id): JsonResponse
    {
        $product = Product::with('stock.warehouse')->find($id);
        if (!$product) return $this->respondNotFound();

        $totalStock = $product->stock->sum('quantity');

        return $this->respond([
            'success' => true,
            'data' => [
                'product' => $product,
                'total_stock' => $totalStock,
                'warehouse_stock' => $product->stock,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
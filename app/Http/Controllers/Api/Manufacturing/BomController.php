<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Manufacturing\Bom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BomController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:manufacturing_boms,code',
        'name' => 'required|string|max:200',
        'product_id' => 'required|exists:inventory_products,id',
        'quantity' => 'required|numeric|min:0.01',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    protected array $validationMessages = [
        'code.required' => 'BOM code is required.',
        'code.unique' => 'This BOM code is already in use.',
        'name.required' => 'BOM name is required.',
        'product_id.required' => 'Product is required.',
        'quantity.required' => 'Quantity is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Bom());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:manufacturing_boms,code',
        ]));
        if ($error) return $error;

        $bom = Bom::create($request->all());
        return $this->respondCreated($bom, 'BOM created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bom = Bom::find($id);
        if (!$bom) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:manufacturing_boms,code,' . $id,
            'name' => 'required|string|max:200',
            'product_id' => 'required|exists:inventory_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        if ($error) return $error;

        $bom->update($request->all());
        return $this->respondSuccess('BOM updated', $bom->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $bom = Bom::with(['product', 'components'])->find($id);
        if (!$bom) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $bom]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Bom::query()->with(['product']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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

    public function components(int $id): JsonResponse
    {
        $bom = Bom::with('components')->find($id);
        if (!$bom) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $bom->components,
        ]);
    }
}
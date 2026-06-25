<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\Part;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartController extends BaseApiController
{
    protected string $cacheTag = 'fleet_parts';

    protected array $validationRules = [
        'part_number' => 'required|string|max:50|unique:fleet_parts,part_number',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'category_id' => 'nullable|exists:fleet_part_categories,id',
        'manufacturer' => 'nullable|string|max:200',
        'supplier' => 'nullable|string|max:200',
        'unit' => 'nullable|string|max:20',
        'unit_cost' => 'required|numeric|min:0',
        'sell_price' => 'nullable|numeric|min:0',
        'quantity_on_hand' => 'nullable|numeric|min:0',
        'reorder_level' => 'nullable|numeric|min:0',
        'reorder_quantity' => 'nullable|numeric|min:0',
        'bin_location' => 'nullable|string|max:100',
        'compatible_vehicles' => 'nullable|string|max:500',
        'is_active' => 'nullable|boolean',
    ];

    protected array $validationMessages = [
        'part_number.required' => 'Part number is required.',
        'part_number.unique' => 'This part number already exists.',
        'name.required' => 'Part name is required.',
        'unit_cost.required' => 'Unit cost is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Part);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['quantity_on_hand'] = $data['quantity_on_hand'] ?? 0;

        $part = Part::create($data);

        return $this->respondCreated($part->fresh(['category']), 'Part created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $part = Part::find($id);
        if (! $part) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'part_number' => 'required|string|max:50|unique:fleet_parts,part_number,'.$id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:fleet_part_categories,id',
            'manufacturer' => 'nullable|string|max:200',
            'supplier' => 'nullable|string|max:200',
            'unit' => 'nullable|string|max:20',
            'unit_cost' => 'required|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'bin_location' => 'nullable|string|max:100',
            'compatible_vehicles' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $part->update($request->all());

        return $this->respondSuccess('Part updated', $part->fresh(['category']));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Part::query()->with(['category']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($request->has('low_stock') && $request->query('low_stock') === 'true') {
            $query->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->where('reorder_level', '>', 0);
        }

        if ($request->has('is_active') && $request->boolean('is_active') === false) {
            $query->where('is_active', false);
        }

        $perPage = $request->query('per_page', 15);
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

    public function show(int $id): JsonResponse
    {
        $part = Part::with(['category', 'usages.vehicle', 'usages.maintenance'])->find($id);
        if (! $part) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $part]);
    }

    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $part = Part::find($id);
        if (! $part) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'adjustment' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $newQuantity = $part->quantity_on_hand + $request->input('adjustment');
        if ($newQuantity < 0) {
            return $this->respondError('Stock adjustment would result in negative quantity', 422);
        }

        $part->update(['quantity_on_hand' => $newQuantity]);

        return $this->respondSuccess('Stock adjusted', $part->fresh());
    }

    public function lowStock(): JsonResponse
    {
        $items = Part::query()
            ->with(['category'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->where('is_active', true)
            ->orderBy('quantity_on_hand')
            ->get();

        return $this->respond([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $part = Part::find($id);
        if (! $part) {
            return $this->respondNotFound();
        }

        if ($part->usages()->count() > 0) {
            return $this->respondError('Cannot delete part with usage records', 422);
        }

        $part->delete();

        return $this->respondSuccess('Part deleted');
    }
}

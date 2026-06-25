<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Fleet\PartCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartCategoryController extends BaseApiController
{
    protected string $cacheTag = 'fleet_part_categories';

    protected array $validationRules = [
        'name' => 'required|string|max:100|unique:fleet_part_categories,name',
        'description' => 'nullable|string',
        'parent_id' => 'nullable|exists:fleet_part_categories,id',
        'is_active' => 'nullable|boolean',
    ];

    protected array $validationMessages = [
        'name.required' => 'Category name is required.',
        'name.unique' => 'A category with this name already exists.',
    ];

    public function __construct()
    {
        parent::__construct(new PartCategory);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['slug'] = Str::slug($data['name']);

        $category = PartCategory::create($data);

        return $this->respondCreated($category, 'Part category created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = PartCategory::find($id);
        if (! $category) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:100|unique:fleet_part_categories,name,'.$id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:fleet_part_categories,id',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $data = $request->all();
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $this->respondSuccess('Category updated', $category->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = PartCategory::query()->withCount('parts');

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->query('per_page', 50);
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
        $category = PartCategory::with(['parts', 'children', 'parent'])->find($id);
        if (! $category) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $category]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = PartCategory::find($id);
        if (! $category) {
            return $this->respondNotFound();
        }

        if ($category->parts()->count() > 0) {
            return $this->respondError('Cannot delete category with existing parts', 422);
        }

        $category->delete();

        return $this->respondSuccess('Category deleted');
    }
}

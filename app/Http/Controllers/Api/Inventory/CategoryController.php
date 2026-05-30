<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Inventory\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    protected array $validationRules = [
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'parent_id' => 'nullable|exists:inventory_categories,id',
    ];

    public function __construct()
    {
        parent::__construct(new Category());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Category::query();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        } elseif ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('name')->paginate(min($perPage, 100));

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

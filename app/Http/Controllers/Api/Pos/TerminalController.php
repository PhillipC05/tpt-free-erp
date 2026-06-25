<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Pos\Terminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminalController extends BaseApiController
{
    protected string $cacheTag = 'pos_terminals';

    protected array $validationRules = [
        'terminal_code' => 'required|string|max:20|unique:pos_terminals,terminal_code',
        'name' => 'required|string|max:200',
        'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
        'assigned_to' => 'nullable|exists:users,id',
        'status' => 'sometimes|in:active,inactive,maintenance',
        'notes' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'terminal_code.required' => 'Terminal code is required.',
        'terminal_code.unique' => 'This terminal code is already in use.',
        'name.required' => 'Terminal name is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Terminal);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $terminal = Terminal::create($data);

        return $this->respondCreated($terminal, 'Terminal created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $terminal = Terminal::find($id);
        if (! $terminal) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'terminal_code' => 'required|string|max:20|unique:pos_terminals,terminal_code,'.$id,
            'name' => 'required|string|max:200',
            'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $terminal->update($request->all());

        return $this->respondSuccess('Terminal updated', $terminal->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Terminal::query()->with(['warehouse', 'assignee']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('terminal_code', 'like', "%{$search}%");
            });
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
        $terminal = Terminal::with(['warehouse', 'assignee', 'transactions'])->find($id);
        if (! $terminal) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $terminal]);
    }
}

<?php

namespace App\Http\Controllers\Api\Asset;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Assets\MaintenanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends BaseApiController
{
    protected array $validationRules = [
        'asset_id' => 'required|exists:assets,id',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string',
        'type' => 'required|in:preventive,corrective,predictive,emergency',
        'scheduled_date' => 'required|date',
        'completed_date' => 'nullable|date',
        'cost' => 'nullable|numeric|min:0',
        'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
        'notes' => 'nullable|string',
    ];

    public function __construct()
    {
        parent::__construct(new MaintenanceRecord());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'scheduled';

        $record = MaintenanceRecord::create($data);
        return $this->respondCreated($record->load('asset'), 'Maintenance record created successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRecord::query()->with(['asset']);

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->query('asset_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('scheduled_date', 'desc')->paginate(min($perPage, 100));

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

    public function byAsset(int $asset): JsonResponse
    {
        $records = MaintenanceRecord::where('asset_id', $asset)
            ->orderBy('scheduled_date', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $records]);
    }
}

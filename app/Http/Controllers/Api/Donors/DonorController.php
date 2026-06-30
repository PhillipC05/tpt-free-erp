<?php

namespace App\Http\Controllers\Api\Donors;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Donors\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorController extends BaseApiController
{
    protected string $cacheTag = 'donors';

    protected array $validationRules = [
        'name' => 'required|string|max:200',
        'type' => 'sometimes|in:individual,corporate,foundation,government',
        'email' => 'nullable|email|max:200',
        'phone' => 'nullable|string|max:50',
        'address' => 'nullable|string',
        'contact_person' => 'nullable|string|max:200',
        'status' => 'sometimes|in:active,inactive',
        'notes' => 'nullable|string',
    ];

    public function __construct()
    {
        parent::__construct(new Donor);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';
        $data['total_contributed'] = $data['total_contributed'] ?? 0;

        $donor = Donor::create($data);

        return $this->respondCreated($donor, 'Donor created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $donor = Donor::find($id);
        if (! $donor) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'type' => 'sometimes|in:individual,corporate,foundation,government',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:200',
            'status' => 'sometimes|in:active,inactive',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $donor->update($request->all());

        return $this->respondSuccess('Donor updated', $donor->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Donor::query()->withCount('grants');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
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

    public function show(int $id): JsonResponse
    {
        $donor = Donor::with(['grants'])->find($id);
        if (! $donor) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $donor]);
    }
}

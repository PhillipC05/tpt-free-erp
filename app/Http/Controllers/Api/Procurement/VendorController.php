<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Procurement\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:procurement_vendors,code',
        'name' => 'required|string|max:200',
        'email' => 'required|email|max:200|unique:procurement_vendors,email',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'tax_number' => 'nullable|string|max:50',
        'payment_terms' => 'nullable|string|max:100',
        'status' => 'sometimes|in:active,inactive,blocked',
        'current_balance' => 'nullable|numeric',
    ];

    protected array $validationMessages = [
        'code.required' => 'Vendor code is required.',
        'code.unique' => 'This vendor code is already in use.',
        'name.required' => 'Vendor name is required.',
        'email.required' => 'Email address is required.',
        'email.unique' => 'This email address is already in use.',
    ];

    public function __construct()
    {
        parent::__construct(new Vendor());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:procurement_vendors,code',
            'email' => 'required|email|max:200|unique:procurement_vendors,email',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $vendor = Vendor::create($data);
        return $this->respondCreated($vendor, 'Vendor created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vendor = Vendor::find($id);
        if (!$vendor) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:procurement_vendors,code,' . $id,
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200|unique:procurement_vendors,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,blocked',
            'current_balance' => 'nullable|numeric',
        ]);
        if ($error) return $error;

        $vendor->update($request->all());
        return $this->respondSuccess('Vendor updated', $vendor->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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
}
<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Sales\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    protected array $validationRules = [
        'code' => 'required|string|max:20|unique:sales_customers,code',
        'name' => 'required|string|max:200',
        'email' => 'required|email|max:200|unique:sales_customers,email',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'country' => 'nullable|string|max:100',
        'tax_number' => 'nullable|string|max:50',
        'payment_terms' => 'nullable|string|max:100',
        'credit_limit' => 'nullable|numeric|min:0',
        'current_balance' => 'nullable|numeric',
        'status' => 'sometimes|in:active,inactive,blocked',
        'assigned_to' => 'nullable|exists:users,id',
    ];

    protected array $validationMessages = [
        'code.required' => 'Customer code is required.',
        'code.unique' => 'This customer code is already in use.',
        'name.required' => 'Customer name is required.',
        'email.required' => 'Email address is required.',
        'email.unique' => 'This email address is already in use.',
    ];

    public function __construct()
    {
        parent::__construct(new Customer());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'code' => 'required|string|max:20|unique:sales_customers,code',
            'email' => 'required|email|max:200|unique:sales_customers,email',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'active';

        $customer = Customer::create($data);
        return $this->respondCreated($customer, 'Customer created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:20|unique:sales_customers,code,' . $id,
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200|unique:sales_customers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'status' => 'sometimes|in:active,inactive,blocked',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        if ($error) return $error;

        $customer->update($request->all());
        return $this->respondSuccess('Customer updated', $customer->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
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

    public function orders(int $id): JsonResponse
    {
        $customer = Customer::with('orders.items')->find($id);
        if (!$customer) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $customer->orders,
        ]);
    }

    public function invoices(int $id): JsonResponse
    {
        $customer = Customer::with('invoices')->find($id);
        if (!$customer) return $this->respondNotFound();

        return $this->respond([
            'success' => true,
            'data' => $customer->invoices,
        ]);
    }
}
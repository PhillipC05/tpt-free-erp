<?php

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FieldService\ServiceTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceTicketController extends BaseApiController
{
    protected array $validationRules = [
        'ticket_number' => 'required|string|max:50|unique:field_service_tickets,ticket_number',
        'customer_id' => 'required|exists:sales_customers,id',
        'title' => 'required|string|max:200',
        'description' => 'nullable|string',
        'priority' => 'required|in:low,medium,high,urgent',
        'status' => 'sometimes|in:open,assigned,in_progress,resolved,closed,cancelled',
        'assigned_to' => 'nullable|exists:hr_employees,id',
        'scheduled_date' => 'nullable|date',
        'resolution_notes' => 'nullable|string',
    ];

    protected array $validationMessages = [
        'ticket_number.required' => 'Ticket number is required.',
        'ticket_number.unique' => 'This ticket number is already in use.',
        'customer_id.required' => 'Customer is required.',
        'title.required' => 'Title is required.',
        'priority.required' => 'Priority is required.',
    ];

    public function __construct()
    {
        parent::__construct(new ServiceTicket());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'ticket_number' => 'required|string|max:50|unique:field_service_tickets,ticket_number',
        ]));
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'open';

        $ticket = ServiceTicket::create($data);
        return $this->respondCreated($ticket, 'Service ticket created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $ticket = ServiceTicket::find($id);
        if (!$ticket) return $this->respondNotFound();

        if (in_array($ticket->status, ['resolved', 'closed', 'cancelled'])) {
            return $this->respondError('Cannot update a resolved, closed, or cancelled ticket', 422);
        }

        $error = $this->validate($request->all(), [
            'ticket_number' => 'required|string|max:50|unique:field_service_tickets,ticket_number,' . $id,
            'customer_id' => 'required|exists:sales_customers,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,assigned,in_progress,resolved,closed,cancelled',
            'assigned_to' => 'nullable|exists:hr_employees,id',
            'scheduled_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string',
        ]);
        if ($error) return $error;

        $ticket->update($request->all());
        return $this->respondSuccess('Service ticket updated', $ticket->fresh());
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $ticket = ServiceTicket::find($id);
        if (!$ticket) return $this->respondNotFound();

        if (in_array($ticket->status, ['resolved', 'closed', 'cancelled'])) {
            return $this->respondError('Cannot assign a resolved, closed, or cancelled ticket', 422);
        }

        $error = $this->validate($request->all(), [
            'assigned_to' => 'required|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $ticket->update([
            'assigned_to' => $request->query('assigned_to'),
            'status' => 'assigned',
        ]);

        return $this->respondSuccess('Ticket assigned', $ticket->fresh());
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        $ticket = ServiceTicket::find($id);
        if (!$ticket) return $this->respondNotFound();

        if (!in_array($ticket->status, ['assigned', 'in_progress'])) {
            return $this->respondError('Only assigned or in-progress tickets can be resolved', 422);
        }

        $error = $this->validate($request->all(), [
            'resolution_notes' => 'required|string',
        ]);
        if ($error) return $error;

        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $request->query('resolution_notes'),
        ]);

        return $this->respondSuccess('Ticket resolved', $ticket->fresh());
    }

    public function close(int $id): JsonResponse
    {
        $ticket = ServiceTicket::find($id);
        if (!$ticket) return $this->respondNotFound();

        if ($ticket->status !== 'resolved') {
            return $this->respondError('Only resolved tickets can be closed', 422);
        }

        $ticket->update(['status' => 'closed']);
        return $this->respondSuccess('Ticket closed', $ticket->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = ServiceTicket::query()->with(['customer', 'assignedTo']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

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
        $ticket = ServiceTicket::with(['customer', 'assignedTo'])->find($id);
        if (!$ticket) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $ticket]);
    }
}
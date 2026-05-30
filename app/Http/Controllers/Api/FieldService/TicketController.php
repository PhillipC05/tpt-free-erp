<?php

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FieldService\ServiceTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends BaseApiController
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

    public function __construct()
    {
        parent::__construct(new ServiceTicket());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
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

        $error = $this->validate($request->all(), array_merge($this->validationRules, [
            'ticket_number' => 'required|string|max:50|unique:field_service_tickets,ticket_number,' . $id,
        ]));
        if ($error) return $error;

        $ticket->update($request->all());
        return $this->respondSuccess('Ticket updated', $ticket->fresh());
    }

    public function show(int $id): JsonResponse
    {
        $ticket = ServiceTicket::with(['customer', 'assignedTo'])->find($id);
        if (!$ticket) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $ticket]);
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

    public function updateStatus(Request $request, int $ticket): JsonResponse
    {
        $record = ServiceTicket::find($ticket);
        if (!$record) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'status' => 'required|in:open,assigned,in_progress,resolved,closed,cancelled',
        ]);
        if ($error) return $error;

        $updates = ['status' => $request->query('status')];
        if (in_array($request->query('status'), ['resolved', 'closed'])) {
            $updates['resolved_at'] = now();
        }

        $record->update($updates);
        return $this->respondSuccess('Status updated', $record->fresh());
    }

    public function assign(Request $request, int $ticket): JsonResponse
    {
        $record = ServiceTicket::find($ticket);
        if (!$record) return $this->respondNotFound();

        if (in_array($record->status, ['resolved', 'closed', 'cancelled'])) {
            return $this->respondError('Cannot assign a resolved, closed, or cancelled ticket', 422);
        }

        $error = $this->validate($request->all(), [
            'assigned_to' => 'required|exists:hr_employees,id',
        ]);
        if ($error) return $error;

        $record->update([
            'assigned_to' => $request->query('assigned_to'),
            'status' => 'assigned',
        ]);

        return $this->respondSuccess('Ticket assigned', $record->fresh());
    }
}

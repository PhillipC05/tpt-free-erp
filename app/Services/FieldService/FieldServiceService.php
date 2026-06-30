<?php

namespace App\Services\FieldService;

use App\Models\FieldService\ServiceTicket;

class FieldServiceService
{
    public function createTicket(array $data): ServiceTicket
    {
        $data['status'] = $data['status'] ?? 'open';

        return ServiceTicket::create($data);
    }

    public function assignTicket(ServiceTicket $ticket, int $technicianId): ServiceTicket
    {
        if (in_array($ticket->status, ['resolved', 'closed', 'cancelled'])) {
            throw new \RuntimeException('Cannot assign a resolved, closed, or cancelled ticket');
        }

        $ticket->update([
            'assigned_to' => $technicianId,
            'status' => 'assigned',
        ]);

        return $ticket->fresh();
    }

    public function resolveTicket(ServiceTicket $ticket, string $resolutionNotes): ServiceTicket
    {
        if (! in_array($ticket->status, ['assigned', 'in_progress'])) {
            throw new \RuntimeException('Only assigned or in-progress tickets can be resolved');
        }

        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);

        return $ticket->fresh();
    }

    public function closeTicket(ServiceTicket $ticket): ServiceTicket
    {
        if ($ticket->status !== 'resolved') {
            throw new \RuntimeException('Only resolved tickets can be closed');
        }

        $ticket->update(['status' => 'closed']);

        return $ticket->fresh();
    }
}

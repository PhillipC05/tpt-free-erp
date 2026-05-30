<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Finance\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends BaseApiController
{
    protected array $validationRules = [
        'entry_number' => 'required|string|max:50|unique:finance_journal_entries,entry_number',
        'entry_date' => 'required|date',
        'description' => 'required|string',
        'status' => 'sometimes|in:draft,posted,void',
    ];

    public function __construct()
    {
        parent::__construct(new JournalEntry());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = auth()->id();

        $entry = JournalEntry::create($data);
        return $this->respondCreated($entry->load('lines'), 'Journal entry created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $entry = JournalEntry::with(['lines', 'creator', 'approver'])->find($id);
        if (!$entry) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $entry]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = JournalEntry::find($id);
        if (!$entry) return $this->respondNotFound();

        if ($entry->status === 'posted') {
            return $this->respondError('Posted journal entries cannot be modified', 422);
        }

        $error = $this->validate($request->all(), [
            'entry_number' => 'required|string|max:50|unique:finance_journal_entries,entry_number,' . $id,
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'sometimes|in:draft,posted,void',
        ]);
        if ($error) return $error;

        $entry->update($request->all());
        return $this->respondSuccess('Journal entry updated', $entry->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::query()->with(['creator']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('start_date')) {
            $query->where('entry_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('entry_date', '<=', $request->query('end_date'));
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('entry_date', 'desc')->paginate(min($perPage, 100));

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

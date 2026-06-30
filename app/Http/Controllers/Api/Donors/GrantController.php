<?php

namespace App\Http\Controllers\Api\Donors;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Donors\Donor;
use App\Models\Donors\Grant;
use App\Models\Donors\GrantDisbursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrantController extends BaseApiController
{
    protected string $cacheTag = 'donors';

    protected array $validationRules = [
        'title' => 'required|string|max:200',
        'grant_number' => 'nullable|string|max:100',
        'amount' => 'required|numeric|min:0',
        'donor_id' => 'nullable|exists:donors,id',
        'status' => 'sometimes|in:draft,submitted,approved,active,closed,rejected',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'purpose' => 'nullable|string',
        'requirements' => 'nullable|string',
    ];

    public function __construct()
    {
        parent::__construct(new Grant);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) {
            return $error;
        }

        $data = $request->all();
        $data['status'] = $data['status'] ?? 'draft';
        $data['created_by'] = $request->user()->id;
        $data['funded_amount'] = $data['funded_amount'] ?? 0;
        $data['spent_amount'] = $data['spent_amount'] ?? 0;

        $grant = Grant::create($data);

        return $this->respondCreated($grant->fresh(['donor', 'creator']), 'Grant created successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $grant = Grant::find($id);
        if (! $grant) {
            return $this->respondNotFound();
        }

        if (! in_array($grant->status, ['draft', 'submitted'])) {
            return $this->respondError('Only draft or submitted grants can be edited', 422);
        }

        $error = $this->validate($request->all(), [
            'title' => 'required|string|max:200',
            'grant_number' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'donor_id' => 'nullable|exists:donors,id',
            'status' => 'sometimes|in:draft,submitted,approved,active,closed,rejected',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'purpose' => 'nullable|string',
            'requirements' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $grant->update($request->all());

        return $this->respondSuccess('Grant updated', $grant->fresh(['donor', 'creator']));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Grant::query()->with(['donor', 'creator']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('donor_id')) {
            $query->where('donor_id', $request->query('donor_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('grant_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
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
        $grant = Grant::with(['donor', 'creator', 'disbursements.creator'])->find($id);
        if (! $grant) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $grant]);
    }

    // ── DISBURSEMENTS ─────────────────────────────────────────────────────

    public function disbursements(int $grantId): JsonResponse
    {
        $grant = Grant::find($grantId);
        if (! $grant) {
            return $this->respondNotFound();
        }

        $disbursements = GrantDisbursement::with('creator')
            ->where('grant_id', $grantId)
            ->orderBy('disbursement_date', 'desc')
            ->get();

        return $this->respond(['success' => true, 'data' => $disbursements]);
    }

    public function addDisbursement(Request $request, int $grantId): JsonResponse
    {
        $grant = Grant::find($grantId);
        if (! $grant) {
            return $this->respondNotFound();
        }

        if (! in_array($grant->status, ['active'])) {
            return $this->respondError('Only active grants can receive disbursements', 422);
        }

        $error = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'disbursement_date' => 'required|date',
        ]);
        if ($error) {
            return $error;
        }

        $remaining = $grant->remainingAmount();
        if ($request->input('amount') > $remaining) {
            return $this->respondError("Disbursement amount ({$request->input('amount')}) exceeds remaining grant balance ({$remaining})", 422);
        }

        return DB::transaction(function () use ($request, $grant) {
            $disbursement = GrantDisbursement::create([
                'grant_id' => $grant->id,
                'amount' => $request->input('amount'),
                'description' => $request->input('description'),
                'disbursement_date' => $request->input('disbursement_date'),
                'created_by' => $request->user()->id,
            ]);

            $grant->increment('spent_amount', $request->input('amount'));

            // Update donor total contribution
            if ($grant->donor_id) {
                Donor::where('id', $grant->donor_id)->increment('total_contributed', $request->input('amount'));
            }

            return $this->respondCreated($disbursement->fresh(['creator']), 'Disbursement recorded');
        });
    }

    public function close(int $id): JsonResponse
    {
        $grant = Grant::find($id);
        if (! $grant) {
            return $this->respondNotFound();
        }

        if ($grant->status === 'closed') {
            return $this->respondError('Grant is already closed', 422);
        }

        $grant->update(['status' => 'closed']);

        return $this->respondSuccess('Grant closed', $grant->fresh(['donor']));
    }
}

<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Marketing\Lead;
use App\Models\Sales\CrmPipeline;
use App\Models\Sales\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadController extends BaseApiController
{
    protected string $cacheTag = 'marketing_leads';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->query('source'));
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->query('campaign_id'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $leads = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $leads->items(),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'source' => 'required|string|in:organic,referral,campaign,network,manual',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted',
            'campaign_id' => 'nullable|exists:marketing_campaigns,id',
            'interest_score' => 'nullable|integer|min:0|max:100',
        ]);

        if ($error) {
            return $error;
        }

        $lead = Lead::create($request->all());

        $this->cacheFlush();

        return $this->respondCreated($lead);
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (! $lead) {
            return $this->respondNotFound('Lead not found');
        }

        return $this->respond(['success' => true, 'data' => $lead]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (! $lead) {
            return $this->respondNotFound('Lead not found');
        }

        $error = $this->validate($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'nullable|email|max:255',
            'source' => 'sometimes|required|string|in:organic,referral,campaign,network,manual',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted',
            'interest_score' => 'nullable|integer|min:0|max:100',
        ]);

        if ($error) {
            return $error;
        }

        $lead->update($request->all());
        $this->cacheFlush();

        return $this->respondSuccess('Lead updated successfully', $lead);
    }

    public function destroy(int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (! $lead) {
            return $this->respondNotFound('Lead not found');
        }

        $lead->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Lead deleted successfully');
    }

    public function convert(int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (! $lead) {
            return $this->respondNotFound('Lead not found');
        }

        if ($lead->converted_to_customer_id) {
            return $this->respondError('Lead has already been converted');
        }

        $customer = Customer::create([
            'name' => trim($lead->first_name.' '.$lead->last_name),
            'email' => $lead->email,
            'phone' => $lead->phone,
            'code' => 'CUST-'.strtoupper(Str::random(6)),
            'status' => 'active',
        ]);

        $lead->update([
            'status' => 'converted',
            'converted_to_customer_id' => $customer->id,
            'converted_at' => now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('Lead converted to customer successfully', [
            'lead' => $lead,
            'customer' => $customer,
        ]);
    }

    public function addToPipeline(int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (! $lead) {
            return $this->respondNotFound('Lead not found');
        }

        $pipeline = CrmPipeline::create([
            'contact_name' => trim($lead->first_name.' '.$lead->last_name),
            'contact_email' => $lead->email,
            'stage' => 'lead',
            'name' => trim($lead->first_name.' '.$lead->last_name),
            'status' => 'open',
        ]);

        return $this->respondSuccess('Lead added to CRM pipeline successfully', $pipeline);
    }
}

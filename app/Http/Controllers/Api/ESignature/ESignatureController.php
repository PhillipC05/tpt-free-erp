<?php

namespace App\Http\Controllers\Api\ESignature;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Contracts\Contract;
use App\Models\Documents\Document;
use App\Models\ESignature\ESignature;
use App\Models\Recruitment\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ESignatureController extends BaseApiController
{
    protected string $cacheTag = 'esignatures';

    public function index(Request $request): JsonResponse
    {
        $query = ESignature::with(['requester:id,name,email'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('signer_email'), fn ($q) => $q->where('signer_email', $request->query('signer_email')))
            ->when($request->filled('signable_type'), fn ($q) => $q->where('signable_type', $request->query('signable_type')))
            ->when($request->filled('signable_id'), fn ($q) => $q->where('signable_id', $request->query('signable_id')));

        $perPage = min((int) $request->query('per_page', 15), 100);
        $results = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->respond([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signable_type' => 'required|string|max:100',
            'signable_id' => 'required|integer',
            'message' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($error) {
            return $error;
        }

        // Resolve the signable model and compute its hash
        $signableClass = $this->resolveSignableClass($request->input('signable_type'));
        if (! $signableClass) {
            return $this->respondError('Unknown signable type', 422);
        }

        $signable = $signableClass::find($request->input('signable_id'));
        if (! $signable) {
            return $this->respondNotFound('Signable record not found');
        }

        $documentHash = ESignature::hashSignable($signable->toArray());

        $signature = ESignature::create([
            'signable_type' => $request->input('signable_type'),
            'signable_id' => $request->input('signable_id'),
            'token' => ESignature::generateToken(),
            'status' => 'pending',
            'signer_name' => $request->input('signer_name'),
            'signer_email' => $request->input('signer_email'),
            'document_hash' => $documentHash,
            'message' => $request->input('message'),
            'expires_at' => $request->input('expires_at'),
            'requested_by' => $request->user()->id,
            'audit_log' => [[
                'event' => 'created',
                'at' => now()->toIso8601String(),
                'by' => $request->user()->email,
            ]],
        ]);

        $this->cacheFlush();

        return $this->respondCreated($signature);
    }

    public function show(int $id): JsonResponse
    {
        $signature = ESignature::with(['requester:id,name,email', 'signable'])->find($id);

        if (! $signature) {
            return $this->respondNotFound('Signature request not found');
        }

        return $this->respond(['success' => true, 'data' => $signature]);
    }

    /** Public endpoint: fetch signing page data by token (no auth required). */
    public function getByToken(string $token): JsonResponse
    {
        $signature = ESignature::where('token', $token)->first();

        if (! $signature) {
            return $this->respondNotFound('Signing request not found');
        }

        if ($signature->status !== 'pending') {
            return $this->respondError('This signing request has already been '.$signature->status, 409);
        }

        if ($signature->isExpired()) {
            $signature->update(['status' => 'expired']);
            $signature->appendAudit('expired');

            return $this->respondError('This signing request has expired', 410);
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $signature->id,
                'signer_name' => $signature->signer_name,
                'signer_email' => $signature->signer_email,
                'message' => $signature->message,
                'expires_at' => $signature->expires_at,
                'signable_type' => $signature->signable_type,
                'signable_id' => $signature->signable_id,
            ],
        ]);
    }

    /** Public endpoint: submit a signature by token (no auth required). */
    public function sign(Request $request, string $token): JsonResponse
    {
        $signature = ESignature::where('token', $token)->first();

        if (! $signature) {
            return $this->respondNotFound('Signing request not found');
        }

        if ($signature->status !== 'pending') {
            return $this->respondError('This signing request has already been '.$signature->status, 409);
        }

        if ($signature->isExpired()) {
            $signature->update(['status' => 'expired']);
            $signature->appendAudit('expired');

            return $this->respondError('This signing request has expired', 410);
        }

        $error = $this->validate($request->all(), [
            'signature_type' => 'required|in:drawn,typed',
            'signature_data' => 'required|string',
            'signer_name' => 'required|string|max:255',
        ]);

        if ($error) {
            return $error;
        }

        // Recompute hash of signable at sign time for tamper-evidence
        $signableClass = $this->resolveSignableClass($signature->signable_type);
        $signedHash = null;
        if ($signableClass) {
            $signable = $signableClass::find($signature->signable_id);
            if ($signable) {
                $signedHash = ESignature::hashSignable($signable->toArray());
            }
        }

        $ip = $request->ip();
        $ua = $request->userAgent();

        $signature->update([
            'status' => 'signed',
            'signature_type' => $request->input('signature_type'),
            'signature_data' => $request->input('signature_data'),
            'signer_name' => $request->input('signer_name'),
            'signer_ip' => $ip,
            'signer_user_agent' => $ua,
            'signed_hash' => $signedHash,
            'signed_at' => now(),
        ]);

        $signature->appendAudit('signed', ['ip' => $ip, 'signature_type' => $request->input('signature_type')]);

        // If signable is a Contract, update its status
        if ($signature->signable_type === 'App\\Models\\Contracts\\Contract' && $signableClass) {
            $signable = $signableClass::find($signature->signable_id);
            $signable?->update(['status' => 'signed', 'signed_at' => now()]);
        }

        $this->cacheFlush();

        return $this->respondSuccess('Document signed successfully', [
            'signed_at' => $signature->signed_at,
            'document_hash' => $signature->document_hash,
            'signed_hash' => $signature->signed_hash,
        ]);
    }

    /** Public endpoint: decline signing by token (no auth required). */
    public function decline(Request $request, string $token): JsonResponse
    {
        $signature = ESignature::where('token', $token)->first();

        if (! $signature) {
            return $this->respondNotFound('Signing request not found');
        }

        if ($signature->status !== 'pending') {
            return $this->respondError('This signing request has already been '.$signature->status, 409);
        }

        $reason = $request->input('reason', 'No reason given');

        $signature->update(['status' => 'declined']);
        $signature->appendAudit('declined', ['reason' => $reason, 'ip' => $request->ip()]);

        $this->cacheFlush();

        return $this->respondSuccess('Signing request declined');
    }

    /** Verify that the document has not changed since it was signed. */
    public function verify(int $id): JsonResponse
    {
        $signature = ESignature::find($id);

        if (! $signature) {
            return $this->respondNotFound('Signature request not found');
        }

        if ($signature->status !== 'signed') {
            return $this->respondError('Document has not been signed', 422);
        }

        $signableClass = $this->resolveSignableClass($signature->signable_type);
        if (! $signableClass) {
            return $this->respondError('Cannot verify: unknown signable type', 422);
        }

        $signable = $signableClass::find($signature->signable_id);
        if (! $signable) {
            return $this->respondError('Cannot verify: signable record no longer exists', 422);
        }

        $currentHash = ESignature::hashSignable($signable->toArray());
        $intact = $currentHash === $signature->signed_hash;

        return $this->respond([
            'success' => true,
            'data' => [
                'intact' => $intact,
                'signed_at' => $signature->signed_at,
                'signer_name' => $signature->signer_name,
                'signer_email' => $signature->signer_email,
                'signer_ip' => $signature->signer_ip,
                'document_hash_at_request' => $signature->document_hash,
                'document_hash_at_signing' => $signature->signed_hash,
                'document_hash_current' => $currentHash,
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $signature = ESignature::find($id);

        if (! $signature) {
            return $this->respondNotFound('Signature request not found');
        }

        if ($signature->status === 'signed') {
            return $this->respondError('Cannot delete a completed signature', 422);
        }

        $signature->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Signature request cancelled');
    }

    private function resolveSignableClass(string $type): ?string
    {
        $map = [
            'contract' => Contract::class,
            'document' => Document::class,
            'application' => Application::class,
            'App\\Models\\Contracts\\Contract' => Contract::class,
            'App\\Models\\Documents\\Document' => Document::class,
            'App\\Models\\Recruitment\\Application' => Application::class,
        ];

        return $map[$type] ?? null;
    }
}

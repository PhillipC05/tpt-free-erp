<?php

namespace Tests\Feature\ESignature;

use App\Models\Contracts\Contract;
use App\Models\ESignature\ESignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ESignatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function makeContract(): Contract
    {
        return Contract::create([
            'title' => 'Test Contract',
            'contract_number' => 'C-001',
            'type' => 'service',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
    }

    // ===== CREATE =====
    private function assignAdminRole(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Admin',
            'is_system' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');

        DB::table('user_roles')->insertOrIgnore([
            'user_id' => $this->user->id,
            'role_id' => $adminId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_signature_request(): void
    {
        $contract = $this->makeContract();

        $response = $this->postJson('/api/v1/esignatures', [
            'signer_name' => 'Jane Smith',
            'signer_email' => 'jane@example.com',
            'signable_type' => 'contract',
            'signable_id' => $contract->id,
            'message' => 'Please review and sign.',
        ], $this->auth());

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.signer_email', 'jane@example.com');

        $this->assertDatabaseHas('e_signatures', ['signer_email' => 'jane@example.com']);
    }

    public function test_create_requires_auth(): void
    {
        $this->postJson('/api/v1/esignatures', [
            'signer_name' => 'Jane',
            'signer_email' => 'jane@example.com',
            'signable_type' => 'contract',
            'signable_id' => 1,
        ])->assertStatus(401);
    }

    public function test_create_validates_unknown_signable_type(): void
    {
        $this->postJson('/api/v1/esignatures', [
            'signer_name' => 'Jane',
            'signer_email' => 'jane@example.com',
            'signable_type' => 'invoice',
            'signable_id' => 1,
        ], $this->auth())->assertStatus(422);
    }

    // ===== LIST =====

    public function test_list_signature_requests(): void
    {
        $contract = $this->makeContract();
        ESignature::factory()->count(3)->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/esignatures', $this->auth());
        $response->assertOk()->assertJsonPath('meta.total', 3);
    }

    public function test_list_filters_by_status(): void
    {
        $contract = $this->makeContract();
        ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
        ]);
        ESignature::factory()->signed()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/esignatures?status=signed', $this->auth());
        $response->assertOk()->assertJsonPath('meta.total', 1);
    }

    // ===== SHOW =====

    public function test_show_signature_request(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->getJson("/api/v1/esignatures/{$sig->id}", $this->auth())
            ->assertOk()
            ->assertJsonPath('data.id', $sig->id);
    }

    // ===== PUBLIC TOKEN ENDPOINTS =====

    public function test_get_by_token_returns_signing_info(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->getJson("/api/esignatures/sign/{$sig->token}")
            ->assertOk()
            ->assertJsonPath('data.signer_email', $sig->signer_email);
    }

    public function test_sign_by_token_with_typed_signature(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->postJson("/api/esignatures/sign/{$sig->token}", [
            'signature_type' => 'typed',
            'signature_data' => 'Jane Smith',
            'signer_name' => 'Jane Smith',
        ])->assertOk();

        $this->assertDatabaseHas('e_signatures', ['id' => $sig->id, 'status' => 'signed']);
    }

    public function test_cannot_sign_already_signed_request(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->signed()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->postJson("/api/esignatures/sign/{$sig->token}", [
            'signature_type' => 'typed',
            'signature_data' => 'Someone Else',
            'signer_name' => 'Someone Else',
        ])->assertStatus(409);
    }

    public function test_decline_by_token(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->postJson("/api/esignatures/sign/{$sig->token}/decline", [
            'reason' => 'I disagree with clause 3.',
        ])->assertOk();

        $this->assertDatabaseHas('e_signatures', ['id' => $sig->id, 'status' => 'declined']);
    }

    public function test_expired_request_cannot_be_signed(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
            'expires_at' => now()->subHour(),
        ]);

        $this->postJson("/api/esignatures/sign/{$sig->token}", [
            'signature_type' => 'typed',
            'signature_data' => 'Jane',
            'signer_name' => 'Jane',
        ])->assertStatus(410);
    }

    // ===== VERIFY =====

    public function test_verify_signed_document(): void
    {
        $contract = $this->makeContract();

        // Create sig with matching hashes — reload from DB to match controller's find()
        $freshContract = Contract::find($contract->id);
        $hash = ESignature::hashSignable($freshContract->toArray());
        $sig = ESignature::factory()->signed()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
            'document_hash' => $hash,
            'signed_hash' => $hash,
        ]);

        $response = $this->getJson("/api/v1/esignatures/{$sig->id}/verify", $this->auth());
        $response->assertOk()->assertJsonPath('data.intact', true);
    }

    public function test_verify_detects_tampered_document(): void
    {
        $contract = $this->makeContract();

        $sig = ESignature::factory()->signed()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
            'document_hash' => hash('sha256', 'original'),
            'signed_hash' => hash('sha256', 'original'),
        ]);

        // Mutate the contract after signing
        $contract->update(['title' => 'Tampered Title']);

        $response = $this->getJson("/api/v1/esignatures/{$sig->id}/verify", $this->auth());
        $response->assertOk()->assertJsonPath('data.intact', false);
    }

    // ===== DELETE =====

    public function test_cancel_pending_signature_request(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->deleteJson("/api/v1/esignatures/{$sig->id}", [], $this->auth())
            ->assertOk();

        $this->assertSoftDeleted('e_signatures', ['id' => $sig->id]);
    }

    public function test_cannot_cancel_signed_request(): void
    {
        $contract = $this->makeContract();
        $sig = ESignature::factory()->signed()->create([
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => $contract->id,
            'requested_by' => $this->user->id,
        ]);

        $this->deleteJson("/api/v1/esignatures/{$sig->id}", [], $this->auth())
            ->assertStatus(422);
    }
}

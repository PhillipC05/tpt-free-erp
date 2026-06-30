<?php

namespace Tests\Feature\HR;

use App\Models\Documents\Document;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
        $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function createDocument(array $overrides = []): Document
    {
        return Document::create(array_merge([
            'name' => 'Test Document',
            'original_filename' => 'test.pdf',
            'storage_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'documentable_type' => Employee::class,
            'documentable_id' => $this->employee->id,
            'uploaded_by' => $this->user->id,
        ], $overrides));
    }

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

    public function test_can_list_documents(): void
    {
        $this->createDocument();

        $response = $this->getJson('/api/v1/self-service/documents', $this->auth());
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJsonCount(1, 'data');
    }

    public function test_can_list_with_search(): void
    {
        $this->createDocument(['name' => 'Employment Contract']);
        $this->createDocument(['name' => 'Tax Form']);

        $response = $this->getJson('/api/v1/self-service/documents?search=Contract', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_show_own_document(): void
    {
        $doc = $this->createDocument();

        $response = $this->getJson("/api/v1/self-service/documents/{$doc->id}", $this->auth());
        $response->assertOk()->assertJson(['success' => true, 'data' => ['id' => $doc->id]]);
    }

    public function test_cannot_show_others_document(): void
    {
        $otherEmployee = Employee::factory()->create();
        $doc = $this->createDocument(['documentable_id' => $otherEmployee->id]);

        $response = $this->getJson("/api/v1/self-service/documents/{$doc->id}", $this->auth());
        $response->assertNotFound();
    }

    public function test_documents_only_show_own(): void
    {
        $otherEmployee = Employee::factory()->create();
        $this->createDocument();
        $this->createDocument(['documentable_id' => $otherEmployee->id]);

        $response = $this->getJson('/api/v1/self-service/documents', $this->auth());
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_get_categories(): void
    {
        $this->createDocument(['tags' => ['id']]);
        $this->createDocument(['tags' => ['id']]);
        $this->createDocument(['tags' => ['contract']]);

        $response = $this->getJson('/api/v1/self-service/documents/categories', $this->auth());
        $response->assertOk()->assertJsonStructure(['success', 'data']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/self-service/documents');
        $response->assertUnauthorized();
    }

    public function test_no_employee_returns_404(): void
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/self-service/documents', [
            'Authorization' => "Bearer {$token2}",
        ]);
        $response->assertStatus(404);
    }
}

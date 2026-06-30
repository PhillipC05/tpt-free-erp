<?php

namespace Tests\Feature\Documents;

use App\Models\Documents\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->assignAdminRole();
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
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

        DB::table('user_roles')->insert([
            'user_id' => $this->user->id,
            'role_id' => $adminId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_create_document_record(): void
    {
        $file = UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/documents', [
            'file' => $file,
            'name' => 'Project Proposal',
            'documentable_type' => 'App\\Models\\User',
            'documentable_id' => $this->user->id,
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'Project Proposal');

        $this->assertDatabaseHas('documents', [
            'name' => 'Project Proposal',
            'uploaded_by' => $this->user->id,
        ]);
    }

    public function test_can_list_documents(): void
    {
        Document::create([
            'name' => 'Test Doc',
            'original_filename' => 'test.pdf',
            'storage_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 512,
            'uploaded_by' => $this->user->id,
            'documentable_type' => 'App\\Models\\User',
            'documentable_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/documents', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_folder(): void
    {
        $response = $this->postJson('/api/v1/documents/folders', [
            'name' => 'Finance Documents',
        ], $this->auth());

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('document_folders', [
            'name' => 'Finance Documents',
        ]);
    }

    public function test_can_update_document(): void
    {
        $document = Document::create([
            'name' => 'Original Name',
            'original_filename' => 'original.pdf',
            'storage_path' => 'documents/original.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 256,
            'uploaded_by' => $this->user->id,
            'documentable_type' => 'App\\Models\\User',
            'documentable_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/documents/{$document->id}", [
            'name' => 'Updated Name',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_document(): void
    {
        $document = Document::create([
            'name' => 'To Delete',
            'original_filename' => 'delete-me.pdf',
            'storage_path' => 'documents/delete-me.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'uploaded_by' => $this->user->id,
            'documentable_type' => 'App\\Models\\User',
            'documentable_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/documents/{$document->id}", [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/documents');

        $response->assertUnauthorized();
    }
}

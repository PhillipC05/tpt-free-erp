<?php

namespace Tests\Feature\Lms;

use App\Models\Lms\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    private function auth(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_courses(): void
    {
        Course::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/lms/courses', $this->auth());

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_course(): void
    {
        $response = $this->postJson('/api/v1/lms/courses', [
            'code' => 'CRS-001',
            'title' => 'Laravel Fundamentals',
            'type' => 'online',
            'duration_hours' => 8,
            'is_active' => true,
        ], $this->auth());

        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lms_courses', ['code' => 'CRS-001']);
    }

    public function test_create_course_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/lms/courses', [], $this->auth());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_course_code_must_be_unique(): void
    {
        Course::factory()->create(['code' => 'CRS-001']);

        $response = $this->postJson('/api/v1/lms/courses', [
            'code' => 'CRS-001',
            'title' => 'Duplicate',
            'type' => 'online',
            'duration_hours' => 4,
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_can_show_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->getJson("/api/v1/lms/courses/{$course->id}", $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $course->id]]);
    }

    public function test_show_nonexistent_course_returns_404(): void
    {
        $response = $this->getJson('/api/v1/lms/courses/99999', $this->auth());

        $response->assertNotFound();
    }

    public function test_can_update_course(): void
    {
        $course = Course::factory()->create(['code' => 'CRS-001']);

        $response = $this->putJson("/api/v1/lms/courses/{$course->id}", [
            'code' => 'CRS-001',
            'title' => 'Updated Course',
            'type' => 'blended',
            'duration_hours' => 16,
        ], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lms_courses', ['id' => $course->id, 'title' => 'Updated Course']);
    }

    public function test_can_delete_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->deleteJson("/api/v1/lms/courses/{$course->id}", [], $this->auth());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('lms_courses', ['id' => $course->id]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/lms/courses');

        $response->assertUnauthorized();
    }
}

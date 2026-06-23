<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingTest extends TestCase
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

    private function seedPreset(): void
    {
        DB::table('onboarding_presets')->insert([
            'industry_key' => 'retail_ecommerce',
            'industry_name' => 'Retail & E-commerce',
            'icon_emoji' => '🛍️',
            'description' => 'Test preset for retail',
            'recommended_modules' => json_encode(['inventory', 'sales']),
            'chart_of_accounts_template' => json_encode([]),
            'departments_template' => json_encode([]),
            'color_theme' => '#f59e0b',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_can_list_presets(): void
    {
        $this->seedPreset();

        $response = $this->getJson('/api/v1/onboarding/presets', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_status_is_pending_for_new_user(): void
    {
        $response = $this->getJson('/api/v1/onboarding/status', $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_can_skip_onboarding(): void
    {
        $response = $this->postJson('/api/v1/onboarding/skip', [], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('onboarding_completions', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_after_skip_status_is_skipped(): void
    {
        $this->postJson('/api/v1/onboarding/skip', [], $this->auth());

        $response = $this->getJson('/api/v1/onboarding/status', $this->auth());

        $response->assertOk()
            ->assertJsonPath('data.status', 'skipped');
    }

    public function test_can_apply_a_preset(): void
    {
        $this->seedPreset();

        $response = $this->postJson('/api/v1/onboarding/apply', [
            'industry_key' => 'retail_ecommerce',
        ], $this->auth());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('onboarding_completions', [
            'user_id' => $this->user->id,
            'industry_key' => 'retail_ecommerce',
        ]);
    }

    public function test_after_apply_status_is_completed(): void
    {
        $this->seedPreset();

        $this->postJson('/api/v1/onboarding/apply', [
            'industry_key' => 'retail_ecommerce',
        ], $this->auth());

        $response = $this->getJson('/api/v1/onboarding/status', $this->auth());

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }
}

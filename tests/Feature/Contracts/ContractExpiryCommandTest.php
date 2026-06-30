<?php

namespace Tests\Feature\Contracts;

use App\Models\Contracts\Contract;
use App\Models\User;
use App\Notifications\ContractExpiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContractExpiryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_creator_when_contract_expires_in_30_days(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Contract::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->artisan('contracts:notify-expiry')->assertSuccessful();

        Notification::assertSentTo($user, ContractExpiryNotification::class, function ($n) {
            return $n->daysUntilExpiry === 30;
        });
    }

    public function test_notifies_for_7_and_1_day_thresholds(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Contract::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(7)->toDateString(),
            'created_by' => $user->id,
        ]);

        Contract::factory()->create([
            'status' => 'signed',
            'end_date' => now()->addDays(1)->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->artisan('contracts:notify-expiry')->assertSuccessful();

        Notification::assertSentTo($user, ContractExpiryNotification::class, function ($n) {
            return $n->daysUntilExpiry === 7;
        });

        Notification::assertSentTo($user, ContractExpiryNotification::class, function ($n) {
            return $n->daysUntilExpiry === 1;
        });
    }

    public function test_does_not_notify_for_draft_or_terminated_contracts(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Contract::factory()->create([
            'status' => 'draft',
            'end_date' => now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
        ]);

        Contract::factory()->create([
            'status' => 'terminated',
            'end_date' => now()->addDays(7)->toDateString(),
            'created_by' => $user->id,
        ]);

        $this->artisan('contracts:notify-expiry')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_when_no_contracts_expiring(): void
    {
        Notification::fake();

        $this->artisan('contracts:notify-expiry')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notifies_signer_as_well_as_creator(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $signer = User::factory()->create();

        Contract::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(30)->toDateString(),
            'created_by' => $creator->id,
            'signed_by' => $signer->id,
        ]);

        $this->artisan('contracts:notify-expiry')->assertSuccessful();

        Notification::assertSentTo($creator, ContractExpiryNotification::class);
        Notification::assertSentTo($signer, ContractExpiryNotification::class);
    }
}

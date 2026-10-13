<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function user(bool $twoFactor = false): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password12345'),
            'is_active' => true,
            'two_factor_enabled' => $twoFactor,
        ]);
        $user->assignRole('karyawan');

        return $user;
    }

    public function test_login_with_two_factor_redirects_to_challenge(): void
    {
        Notification::fake();
        $user = $this->user(true);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        Notification::assertSentTo($user, TwoFactorCode::class);
    }

    public function test_login_without_two_factor_logs_in_directly(): void
    {
        $user = $this->user(false);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_code_authenticates_user(): void
    {
        Notification::fake();
        $user = $this->user(true);
        $captured = null;

        $this->post('/login', ['email' => $user->email, 'password' => 'Password12345']);
        Notification::assertSentTo($user, TwoFactorCode::class, function ($notification) use (&$captured) {
            $captured = $notification->code;

            return true;
        });

        $this->withSession(['two_factor_user_id' => $user->id])
            ->post('/two-factor-challenge', ['code' => $captured])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_code_is_rejected(): void
    {
        Notification::fake();
        $user = $this->user(true);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password12345']);

        $this->withSession(['two_factor_user_id' => $user->id])
            ->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_user_can_enable_and_disable_two_factor(): void
    {
        $user = $this->user(false);

        $this->actingAs($user)->patch('/profile/two-factor/enable', ['password' => 'Password12345'])->assertRedirect();
        $this->assertTrue((bool) $user->fresh()->two_factor_enabled);

        $this->actingAs($user)->patch('/profile/two-factor/disable', ['password' => 'Password12345'])->assertRedirect();
        $this->assertFalse((bool) $user->fresh()->two_factor_enabled);
    }
}

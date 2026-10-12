<?php

namespace Tests\Feature;

use App\Http\Controllers\SessionController;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_index_lists_only_users_own_sessions(): void
    {
        $user = $this->userWithRole('karyawan');
        $other = $this->userWithRole('karyawan');

        DB::table('sessions')->insert([
            'id' => 'mine1234567890abcdefghijklmnopqrstuv',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.10',
            'user_agent' => 'Perangkat Saya',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'theirs1234567890abcdefghijklmnopqrstu',
            'user_id' => $other->id,
            'ip_address' => '10.0.0.99',
            'user_agent' => 'Perangkat Orang Lain',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->get('/sessions');

        $response->assertOk();
        $response->assertSee('10.0.0.10');
        $response->assertDontSee('10.0.0.99');
    }

    public function test_user_can_revoke_own_other_session(): void
    {
        $user = $this->userWithRole('karyawan');

        DB::table('sessions')->insert([
            'id' => 'revokeme1234567890abcdefghijklmnopqr',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.20',
            'user_agent' => 'Perangkat Lain',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->delete('/sessions/revokeme1234567890abcdefghijklmnopqr');

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'revokeme1234567890abcdefghijklmnopqr']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.session_revoked']);
    }

    public function test_user_cannot_revoke_other_users_session(): void
    {
        $user = $this->userWithRole('karyawan');
        $other = $this->userWithRole('karyawan');

        DB::table('sessions')->insert([
            'id' => 'notmine1234567890abcdefghijklmnopqrs',
            'user_id' => $other->id,
            'ip_address' => '10.0.0.30',
            'user_agent' => 'Perangkat Lain',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)->delete('/sessions/notmine1234567890abcdefghijklmnopqrs')->assertNotFound();
        $this->assertDatabaseHas('sessions', ['id' => 'notmine1234567890abcdefghijklmnopqrs']);
    }

    public function test_user_cannot_revoke_current_session(): void
    {
        $user = $this->userWithRole('karyawan');

        $id = str_pad('currentsession', 40, 'a');

        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.40',
            'user_agent' => 'Perangkat Saat Ini',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $store = app('session')->driver();
        $store->setId($id);

        $request = Request::create('/sessions/'.$id, 'DELETE', server: ['REMOTE_ADDR' => '10.0.0.40']);
        $request->setLaravelSession($store);
        $request->setUserResolver(fn () => $user);

        $response = app(SessionController::class)->destroy($request, $id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('sessions', ['id' => $id]);
    }
}

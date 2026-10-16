<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\BusinessTestCase;

class ProvisioningTest extends BusinessTestCase
{
    public function test_legacy_account_requires_explicit_supported_role_and_preserves_identity(): void
    {
        $user = $this->account(3);
        $this->artisan('dairynex:provision', ['user' => $user->id])->assertExitCode(1);
        $this->assertSame(3, $user->fresh()->user_type_id);
        $this->artisan('dairynex:provision', ['user' => $user->id, '--role' => 'client_admin'])
            ->expectsQuestion('New password (at least 12 characters)', 'provisioned-password-123')
            ->expectsQuestion('Confirm password', 'provisioned-password-123')
            ->assertExitCode(0);
        $this->assertSame(Role::ClientAdmin, $user->fresh()->role());
        $this->assertSame(1, $user->fresh()->client_id);
        $this->assertTrue(Hash::check('provisioned-password-123', $user->fresh()->password));
        $audit = DB::table('audit_logs')->where('action', 'account_provisioned_via_cli')->first();
        $this->assertSame($user->id, (int) $audit->record_id);
        $this->assertStringNotContainsString('password', $audit->new_values);
    }

    public function test_a_new_install_can_provision_an_administrator_without_a_default_password(): void
    {
        $this->artisan('dairynex:create-admin')
            ->expectsQuestion('Name', 'Platform Operator')->expectsQuestion('Mobile', '9876543210')
            ->expectsQuestion('Email (optional)', 'operator@example.test')
            ->expectsQuestion('Password (at least 12 characters)', 'operator-password-123')
            ->expectsQuestion('Confirm password', 'operator-password-123')->assertExitCode(0);
        $user = \App\Models\User::where('mobile', '9876543210')->firstOrFail();
        $this->assertSame(Role::SuperAdmin, $user->role());
        $this->assertNull($user->client_id);
        $this->assertTrue(Hash::check('operator-password-123', $user->password));
    }

    public function test_authentication_migration_is_repeatable_and_preserves_accounts(): void
    {
        $user = $this->account();
        $migration = require database_path('migrations/daatabase_migrations.php');
        $migration->up();
        $migration->down();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'mobile' => $user->mobile]);
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('sessions'));
    }

    public function test_password_changes_invalidate_previously_authenticated_sessions(): void
    {
        $user = $this->account();
        $this->actingAs($user)->get('/categories')->assertOk();
        $user->forceFill(['password' => 'changed-password-123'])->save();
        $this->get('/categories')->assertRedirect('/login');
        $this->assertGuest();
    }
}

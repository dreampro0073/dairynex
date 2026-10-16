<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\BusinessTestCase;

class AuthenticationTest extends BusinessTestCase
{
    public function test_each_supported_role_signs_in_and_redirects_to_its_own_home(): void
    {
        foreach (Role::cases() as $role) {
            $user = $this->account($role, $role === Role::SuperAdmin ? null : 1);
            $this->post('/login', ['mobile' => $user->mobile, 'password' => 'test-password-123'])
                ->assertRedirect($role->home());
            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_inactive_unsupported_empty_password_and_suspended_dairy_accounts_cannot_sign_in(): void
    {
        $users = [
            $this->account(attributes: ['status' => 0]),
            $this->account(3), $this->account(4),
            $this->account(clientId: null),
        ];
        $empty = $this->account();
        DB::table('users')->where('id', $empty->id)->update(['password' => '']);
        $users[] = $empty;
        $suspended = $this->account(clientId: 2);
        DB::table('clients')->where('id', 2)->update(['status' => 0]);
        $users[] = $suspended;
        foreach ($users as $user) {
            $this->post('/login', ['mobile' => $user->mobile, 'password' => 'test-password-123'])
                ->assertSessionHasErrors('mobile');
            $this->assertGuest();
        }
    }

    public function test_duplicate_mobile_requires_the_correct_dairy_credentials(): void
    {
        $first = $this->account(attributes: ['mobile' => '9876543210']);
        $this->account(clientId: 2, attributes: ['mobile' => $first->mobile, 'password' => 'different-password']);
        $this->post('/login', ['mobile' => $first->mobile, 'password' => 'test-password-123'])->assertSessionHasErrors('mobile');
        $this->post('/login', ['client_id' => 2, 'mobile' => $first->mobile, 'password' => 'test-password-123'])->assertSessionHasErrors('mobile');
        $this->post('/login', ['client_id' => 1, 'mobile' => $first->mobile, 'password' => 'test-password-123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($first);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['mobile' => 'unknown', 'password' => 'wrong'])->assertSessionHasErrors('mobile');
        }
        $this->post('/login', ['mobile' => 'unknown', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_password_reset_uses_a_single_use_token_and_hashes_the_new_password(): void
    {
        Notification::fake();
        $user = $this->account(attributes: ['email' => 'unique@example.test']);
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/reset-password', $data)->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_shared_email_cannot_reset_an_arbitrary_tenant_account(): void
    {
        Notification::fake();
        $user = $this->account(attributes: ['email' => 'shared@example.test']);
        $this->account(clientId: 2, attributes: ['email' => $user->email]);
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');
        Notification::assertNothingSent();
        $this->post('/reset-password', ['email' => $user->email, 'token' => 'invalid', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHasErrors('email');
    }

    public function test_active_sessions_lose_access_after_account_or_dairy_is_disabled(): void
    {
        $user = $this->account();
        $this->actingAs($user)->get('/categories')->assertOk();
        $user->forceFill(['status' => 0])->save();
        $this->get('/categories')->assertForbidden();
        $user->forceFill(['status' => 1])->save();
        DB::table('clients')->where('id', 1)->update(['status' => 0]);
        $this->actingAs($user->fresh())->get('/categories')->assertForbidden();
    }
}

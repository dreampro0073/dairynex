<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlatformSubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\BusinessTestCase;

class SuperAdminTest extends BusinessTestCase
{
    private function operator(): User
    {
        return $this->account(Role::SuperAdmin, null);
    }

    private function plan(string $name = 'Standard', string $price = '2499.00', array $extra = []): Plan
    {
        return Plan::create(['name' => $name, 'monthly_price' => $price, 'status' => 1, ...$extra]);
    }

    private function dairyData(): array
    {
        return [
            'name' => 'New Dairy',
            'city' => 'Haridwar',
            'status' => 1,
            'mobile' => '9876543210',
            'admin_name' => 'Owner',
            'admin_mobile' => '9876543211',
            'admin_email' => 'owner@example.test',
        ];
    }

    public function test_login_redirects_super_admin_to_the_real_dashboard_with_live_statistics(): void
    {
        $operator = $this->operator();
        $this->post('/login', ['mobile' => $operator->mobile, 'password' => 'test-password-123'])
            ->assertRedirect('/platform');

        $this->get('/platform')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Platform/Clients/Index')

                ->where('stats.Active Clients', 2)
                ->where('stats.Total Customers Served', 0)
                ->has('clients.data', 2)
                ->has('activities'));
    }

    public function test_all_console_pages_render_and_all_tenant_roles_are_denied_platform_actions(): void
    {
        $this->actingAs($this->operator());

        foreach ([
            'clients',
            'clients/create',
            'clients/1',
            'clients/1/edit',
            'plans',
            'billing',
            'databases',
            'usage',
            'support',
            'settings',
        ] as $path) {
            $this->get('/platform/'.$path)->assertOk();
        }

        foreach ([Role::ClientAdmin, Role::DeliveryBoy, Role::Customer] as $role) {
            $user = $this->account($role);
            $this->actingAs($user)->withSession(['password_hash_web' => $user->password]);

            foreach (['', '/clients/1/login-details', '/billing', '/databases', '/usage/export', '/settings'] as $path) {
                $this->get('/platform'.$path)->assertForbidden();
            }

            $this->post('/platform/clients', $this->dairyData())->assertForbidden();
            $this->post('/platform/clients/1/admins/1/temporary-password')->assertForbidden();
        }

        $this->assertDatabaseCount('clients', 2);
    }

    public function test_client_creation_is_atomic_with_admin_and_initial_subscription(): void
    {
        $plan = $this->plan();
        $this->actingAs($this->operator())
            ->post('/platform/clients', $this->dairyData() + ['plan_id' => $plan->id])
            ->assertRedirect('/platform/clients/3');

        $admin = User::where('client_id', 3)->firstOrFail();
        $this->assertSame(Role::ClientAdmin, $admin->role());
        $this->assertTrue($admin->must_change_password);
        $this->assertDatabaseHas('client_subscriptions', ['client_id' => 3, 'monthly_price' => '2499.00']);
        $this->assertDatabaseHas('saas_invoices', ['client_id' => 3, 'amount' => '2499.00']);
        $inactive = $this->plan('Inactive', '100.00', ['status' => 0]);
        $this->post('/platform/clients', $this->dairyData() + ['plan_id' => $inactive->id])
            ->assertSessionHasErrors('plan_id');

        $this->assertDatabaseCount('clients', 3);
        $this->assertSame(1, User::where('user_type_id', Role::ClientAdmin->value)->count());
    }

    public function test_client_search_status_plan_filters_and_pagination_use_backend_data(): void
    {
        $plan = $this->plan();
        app(PlatformSubscriptionService::class)->change(Client::find(1), $plan->id, 'assign', 1);
        $this->actingAs($this->operator())->get('/platform/clients?search=One&status=1&plan_id='.$plan->id)
            ->assertInertia(fn (Assert $p) => $p->has('clients.data', 1)->where('clients.data.0.id', 1));

        for ($i = 0; $i < 20; $i++) {
            Client::create(['name' => 'Dairy '.$i, 'status' => 1]);
        }

        $this->get('/platform/clients?page=2')
            ->assertInertia(fn (Assert $p) => $p->where('clients.current_page', 2)
                ->where('clients.total', 22)
                ->has('clients.data', 7));
    }

    public function test_editing_and_deactivating_a_dairy_preserves_tenant_business_rows(): void
    {
        $owner = $this->account();
        DB::table('customers')->insert(['client_id' => 1, 'name' => 'Preserved Customer']);
        $this->actingAs($this->operator())
            ->put('/platform/clients/1', ['name' => 'Renamed Dairy', 'status' => 1, 'city' => 'Dehradun'])
            ->assertRedirect('/platform/clients/1');

        $this->put('/platform/clients/1/status', ['status' => 0])->assertSessionHas('success');
        $this->assertDatabaseHas('customers', ['client_id' => 1, 'name' => 'Preserved Customer']);
        $this->actingAs($owner->fresh())
            ->withSession(['password_hash_web' => $owner->password])
            ->get('/dashboard')
            ->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => 2, 'status' => 1]);
    }

    public function test_client_admin_accounts_are_scoped_and_duplicate_mobile_is_rejected(): void
    {
        $other = $this->account(clientId: 2);
        $this->actingAs($this->operator())
            ->post('/platform/clients/1/admins', ['name' => 'Second owner', 'mobile' => '9990001112', 'status' => 1])
            ->assertSessionHas('success');

        $this->post('/platform/clients/1/admins', ['name' => 'Duplicate', 'mobile' => '9990001112', 'status' => 1])
            ->assertSessionHasErrors('mobile');

        $this->put('/platform/clients/1/admins/'.$other->id, ['name' => 'Attacker', 'mobile' => '9990001113', 'status' => 0])
            ->assertNotFound();

        $this->post('/platform/clients/1/admins/'.$other->id.'/temporary-password')->assertNotFound();
        $this->assertSame('Account', $other->fresh()->name);
    }

    public function test_credentials_never_expose_hashes_and_generated_password_is_returned_once_and_audited(): void
    {
        $owner = $this->account(attributes: ['email' => 'owner@example.test']);
        $this->actingAs($this->operator());
        $response = $this->getJson('/platform/clients/1/login-details')
            ->assertOk()
            ->assertJsonPath('admins.0.mobile', $owner->mobile);

        $this->assertStringContainsString('client_id=1', $response->json('login_url'));
        $this->assertStringNotContainsString('"password":', $response->getContent());
        $response->assertJsonMissing(['password' => $owner->password]);
        $result = $this->postJson('/platform/clients/1/admins/'.$owner->id.'/temporary-password')->assertOk();
        $password = $result->json('password');
        $this->assertGreaterThanOrEqual(20, strlen($password));
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
        $this->assertTrue(Hash::check($password, $owner->fresh()->password));
        $this->assertTrue($owner->fresh()->must_change_password);
        $this->assertNotNull($owner->fresh()->temporary_password_expires_at);
        $this->getJson('/platform/clients/1/login-details')->assertDontSee($password, false);
        $audit = DB::table('audit_logs')->where('action', 'client_admin_temporary_password_reset')->first();
        $this->assertSame($owner->id, (int) $audit->record_id);
        $this->assertStringNotContainsString($password, json_encode($audit));
        $this->assertStringNotContainsString($owner->fresh()->password, json_encode($audit));
        $this->assertStringNotContainsString($password, json_encode(session()->all()));
    }

    public function test_temporary_password_requires_change_before_any_tenant_access_and_then_allows_normal_login(): void
    {
        $owner = $this->account();
        $this->actingAs($this->operator());
        $password = $this->postJson('/platform/clients/1/admins/'.$owner->id.'/temporary-password')
            ->json('password');

        $this->post('/logout');
        $this->post('/login', ['client_id' => 1, 'mobile' => $owner->mobile, 'password' => $password])
            ->assertRedirect('/change-password');

        $this->get('/dashboard')->assertRedirect('/change-password');
        $this->get('/payments')->assertRedirect('/change-password');
        $this->post('/products', [])->assertRedirect('/change-password');
        $this->post('/change-password', [
            'current_password' => $password,
            'password' => $password,
            'password_confirmation' => $password,
        ])
            ->assertSessionHasErrors('password');

        $this->post('/change-password', [
            'current_password' => $password,
            'password' => 'permanent-password-456',
            'password_confirmation' => 'permanent-password-456',
        ])
            ->assertRedirect('/dashboard');

        $this->assertFalse($owner->fresh()->must_change_password);
        $this->assertNull($owner->fresh()->temporary_password_expires_at);
        $this->get('/dashboard')->assertOk();
        $this->post('/logout');
        $this->post('/login', ['mobile' => $owner->mobile, 'password' => $password])
            ->assertSessionHasErrors('mobile');

        $this->post('/login', ['mobile' => $owner->mobile, 'password' => 'permanent-password-456'])
            ->assertRedirect('/dashboard');
    }

    public function test_expired_temporary_passwords_and_disabled_accounts_cannot_be_used(): void
    {
        $owner = $this->account(attributes: [
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->post('/login', ['mobile' => $owner->mobile, 'password' => 'test-password-123'])
            ->assertSessionHasErrors('mobile');

        $this->actingAs($this->operator());
        $owner->forceFill(['status' => 0])->save();
        $this->postJson('/platform/clients/1/admins/'.$owner->id.'/temporary-password')->assertStatus(422);
    }

    public function test_reset_replaces_old_password_and_invalidates_existing_authenticated_sessions(): void
    {
        $operator = $this->operator();
        $owner = $this->account();
        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $oldHash = session('password_hash_web');
        $this->actingAs($operator)->withSession(['password_hash_web' => $operator->password]);
        $password = $this->postJson('/platform/clients/1/admins/'.$owner->id.'/temporary-password')
            ->json('password');

        $this->assertFalse(Hash::check('test-password-123', $owner->fresh()->password));
        $this->assertTrue(Hash::check($password, $owner->fresh()->password));
        $this->actingAs($owner->fresh())
            ->withSession(['password_hash_web' => $oldHash])
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_plan_crud_snapshot_pricing_activation_and_history_protection(): void
    {
        $this->actingAs($this->operator())
            ->post('/platform/plans', ['name' => 'Basic', 'monthly_price' => '999.50', 'max_customers' => 300, 'status' => 1])
            ->assertSessionHas('success');

        $plan = Plan::firstOrFail();
        $subscription = app(PlatformSubscriptionService::class)
            ->change(Client::find(1), $plan->id, 'assign', 1);

        $this->put('/platform/plans/'.$plan->id, ['name' => 'Basic', 'monthly_price' => '1099.50', 'max_customers' => 300, 'status' => 0])
            ->assertSessionHas('success');

        $this->assertSame('999.50', $subscription->fresh()->monthly_price);
        $this->assertDatabaseHas('saas_invoices', ['amount' => '999.50']);
        $this->delete('/platform/plans/'.$plan->id)->assertSessionHasErrors('plan');
        $this->post('/platform/clients/2/subscription', ['action' => 'assign', 'plan_id' => $plan->id, 'months' => 1])
            ->assertSessionHasErrors('plan_id');

        $unused = $this->plan('Unused');
        $this->delete('/platform/plans/'.$unused->id)->assertSessionHas('success');
        $this->assertDatabaseMissing('plans', ['id' => $unused->id]);
    }

    public function test_assign_renew_upgrade_downgrade_and_expire_keep_history_and_prevent_overlap(): void
    {
        $basic = $this->plan('Basic', '999.00');
        $premium = $this->plan('Premium', '4999.00');
        $this->actingAs($this->operator());
        $this->post('/platform/clients/1/subscription', ['action' => 'assign', 'plan_id' => $basic->id, 'months' => 1])
            ->assertSessionHas('success');

        $first = ClientSubscription::first();
        $this->post('/platform/clients/1/subscription', ['action' => 'assign', 'plan_id' => $basic->id, 'months' => 1])
            ->assertSessionHasErrors('action');

        $this->post('/platform/clients/1/subscription', ['action' => 'upgrade', 'plan_id' => $premium->id, 'months' => 1])
            ->assertSessionHas('success');

        $this->assertSame(2, $first->fresh()->status);
        $this->post('/platform/clients/1/subscription', ['action' => 'downgrade', 'plan_id' => $basic->id, 'months' => 1])
            ->assertSessionHas('success');

        $current = ClientSubscription::current()->firstOrFail();
        $this->post('/platform/clients/1/subscription', ['action' => 'renew', 'plan_id' => $basic->id, 'months' => 2])
            ->assertSessionHas('success');

        $renewal = ClientSubscription::orderByDesc('id')->firstOrFail();
        $this->assertSame($current->end_date->addDay()->toDateString(), $renewal->start_date->toDateString());
        $this->assertSame($current->id, ClientSubscription::current()->firstOrFail()->id);
        $this->post('/platform/clients/1/subscription', ['action' => 'renew', 'plan_id' => $basic->id, 'months' => 1])
            ->assertSessionHasErrors('plan_id');

        $this->post('/platform/clients/1/subscription', ['action' => 'expire'])->assertSessionHas('success');
        $this->assertSame(0, ClientSubscription::current()->count());
        $this->assertDatabaseCount('client_subscriptions', 4);
        $this->assertDatabaseCount('saas_invoices', 4);
        $this->assertDatabaseHas('saas_invoices', ['client_subscription_id' => $renewal->id, 'amount' => '1998.00']);
    }

    public function test_natural_expiry_and_plan_capacity_are_enforced_on_assignment(): void
    {
        $plan = $this->plan(extra: ['max_customers' => 1]);
        DB::table('customers')
            ->insert([['client_id' => 1, 'name' => 'A'], ['client_id' => 1, 'name' => 'B']]);

        $this->actingAs($this->operator())
            ->post('/platform/clients/1/subscription', ['action' => 'assign', 'plan_id' => $plan->id, 'months' => 1])
            ->assertSessionHasErrors('plan_id');

        $subscription = app(PlatformSubscriptionService::class)
            ->change(Client::find(2), $plan->id, 'assign', 1);

        $this->travelTo($subscription->end_date->copy()->addDay());
        $this->assertSame(0, ClientSubscription::current()->count());
        $this->travelBack();
    }

    public function test_billing_receipts_are_idempotent_and_do_not_touch_customer_payments(): void
    {
        $plan = $this->plan();
        app(PlatformSubscriptionService::class)->change(Client::find(1), $plan->id, 'assign', 1);
        $invoice = DB::table('saas_invoices')->first();
        $this->actingAs($this->operator())
            ->post('/platform/billing/'.$invoice->id.'/paid', ['payment_reference' => 'BANK-123'])
            ->assertSessionHas('success');

        $this->post('/platform/billing/'.$invoice->id.'/paid', ['payment_reference' => 'BANK-456'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('saas_invoices', ['id' => $invoice->id, 'payment_reference' => 'BANK-123']);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customer_ledger', 0);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'saas_invoice_paid')->count());
        $this->get('/platform/billing?status=paid')
            ->assertInertia(fn (Assert $p) => $p->has('invoices.data', 1));

        $this->get('/platform/billing/export?status=paid')->assertOk()->assertDownload('saas-billing.csv');
    }

    public function test_reports_export_actual_usage_and_escape_spreadsheet_formulas(): void
    {
        DB::table('clients')->where('id', 1)->update(['name' => '=DANGEROUS()']);
        $customer = DB::table('customers')->insertGetId(['client_id' => 1, 'name' => 'Customer']);
        DB::table('deliveries')
            ->insert([
                'client_id' => 1,
                'customer_id' => $customer,
                'delivery_date' => today()->toDateString(),
                'status_id' => 3,
            ]);

        $this->actingAs($this->operator())
            ->get('/platform/usage')
            ->assertInertia(fn (Assert $p) => $p->where('clients.data.0.deliveries', 1)
                ->where('clients.data.0.completed', 1));

        $csv = $this->get('/platform/usage/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=DANGEROUS()", $csv);
    }

    public function test_support_tickets_can_be_created_updated_closed_and_filtered(): void
    {
        $ticket = [
            'client_id' => 1,
            'subject' => 'Need help',
            'description' => 'Please check billing',
            'priority' => 'high',
            'status' => 'open',
        ];

        $this->actingAs($this->operator())->post('/platform/support', $ticket)->assertSessionHas('success');
        $this->put('/platform/support/1', array_replace($ticket, ['status' => 'closed']))
            ->assertSessionHasErrors('resolution');

        $this->put('/platform/support/1', array_replace($ticket, ['status' => 'closed', 'resolution' => 'Resolved']))
            ->assertSessionHas('success');

        $this->get('/platform/support?status=closed&priority=high')
            ->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)
                ->where('tickets.data.0.resolution', 'Resolved'));

        $this->put('/platform/support/1', array_replace($ticket, ['client_id' => 2]))->assertStatus(422);
    }

    public function test_settings_persist_and_platform_notices_are_shared_with_client_admins(): void
    {
        $settings = [
            'platform_name' => 'DairyNex Live',
            'support_email' => 'help@example.test',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'notice' => 'Service notice',
            'sms_alerts' => false,
            'whatsapp_alerts' => false,
            'welcome_email' => false,
        ];

        $this->actingAs($this->operator())->put('/platform/settings', $settings)->assertSessionHas('success');
        $this->get('/platform/settings')
            ->assertInertia(fn (Assert $p) => $p->where('settings.platform_name', 'DairyNex Live'));

        $this->put('/platform/settings', array_replace($settings, ['sms_alerts' => true]))
            ->assertSessionHasErrors('channels');

        $owner = $this->account();
        $this->actingAs($owner)
            ->withSession(['password_hash_web' => $owner->password])
            ->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->where('platform.notice', 'Service notice'));
    }

    public function test_additive_migration_repeats_without_losing_existing_data(): void
    {
        $owner = $this->account();
        $migration = require database_path('migrations/daatabase_super_admin.php');
        $migration->up();
        $migration->down();
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'mobile' => $owner->mobile]);
        $this->assertTrue(Schema::hasTable('saas_invoices'));
        $this->assertTrue(Schema::hasColumn('users', 'must_change_password'));
    }

    public function test_email_login_is_disambiguated_by_dairy_and_preserves_mobile_login(): void
    {
        $owner = $this->account(attributes: ['email' => 'owner@example.test']);
        $this->account(clientId: 2, attributes: ['email' => $owner->email]);
        $this->post('/login', ['mobile' => $owner->email, 'password' => 'test-password-123'])
            ->assertSessionHasErrors('mobile');

        $this->post('/login', ['client_id' => 1, 'mobile' => $owner->email, 'password' => 'test-password-123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($owner);
        $this->post('/logout');
        $this->get('/login?client_id=1')->assertInertia(fn (Assert $p) => $p->where('clientId', 1));
    }

    public function test_expired_subscription_can_be_renewed_from_today_without_billing_a_gap(): void
    {
        $plan = $this->plan();
        ClientSubscription::create([
            'client_id' => 1,
            'plan_id' => $plan->id,
            'start_date' => today()->subMonths(2),
            'end_date' => today()->subDays(10),
            'status' => 1,
            'monthly_price' => $plan->monthly_price,
        ]);

        $this->actingAs($this->operator())
            ->post('/platform/clients/1/subscription', ['action' => 'renew', 'plan_id' => $plan->id, 'months' => 1])
            ->assertSessionHas('success');

        $this->assertSame(today()->toDateString(), ClientSubscription::orderByDesc('id')
            ->first()->start_date->toDateString());

        $this->assertDatabaseCount('saas_invoices', 1);
    }

    public function test_an_existing_legacy_subscription_keeps_its_original_price_when_the_plan_is_edited(): void
    {
        $plan = $this->plan();
        $subscription = ClientSubscription::create([
            'client_id' => 1,
            'plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addMonth(),
            'status' => 1,
        ]);

        $this->actingAs($this->operator())
            ->put('/platform/plans/'.$plan->id, ['name' => 'Standard', 'monthly_price' => '3000.00', 'status' => 1])
            ->assertSessionHas('success');

        $this->assertSame('2499.00', $subscription->fresh()->monthly_price);
    }

    public function test_free_plans_do_not_create_a_payment_overdue_balance(): void
    {
        $plan = $this->plan('Free', '0.00');
        app(PlatformSubscriptionService::class)->change(Client::find(1), $plan->id, 'assign', 1);
        $invoice = DB::table('saas_invoices')->first();
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame('NO_CHARGE', $invoice->payment_reference);
        $this->assertEquals(0, $invoice->amount);
    }
}

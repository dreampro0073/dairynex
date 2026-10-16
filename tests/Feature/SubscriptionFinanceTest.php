<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryRoute;
use App\Models\Product;
use App\Models\Subscription;
use App\Services\DeliveryGenerationService;
use App\Services\LedgerService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\BusinessTestCase;

class SubscriptionFinanceTest extends BusinessTestCase
{
    public function test_customer_change_requires_admin_approval_and_applies_only_from_its_effective_date(): void
    {
        $admin = $this->account(); $login = $this->account(Role::Customer); $driver = $this->account(Role::DeliveryBoy);
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'delivery_pin_required' => false, 'supply_preference' => 1]);
        $customer->forceFill(['user_id' => $login->id])->save();
        $product = Product::create(['client_id' => 1, 'name' => 'Milk', 'unit_type' => 1, 'base_rate' => '56.00', 'status' => 1]);
        $route = DeliveryRoute::create(['client_id' => 1, 'name' => 'Route', 'delivery_boy_id' => $driver->id, 'status' => 1]);
        DB::table('delivery_route_customers')->insert(['client_id' => 1, 'route_id' => $route->id, 'customer_id' => $customer->id]);
        $this->actingAs($admin)->post('/subscriptions', ['customer_id' => $customer->id, 'start_date' => today()->toDateString(), 'days' => [1,2,3,4,5,6,7], 'slot' => 1, 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertSessionHasNoErrors();
        $subscription = Subscription::firstOrFail();
        $this->flushSession(); $this->actingAs($login);
        $data = ['product_id' => $product->id, 'quantity' => '2.500', 'effective_from' => today()->addDay()->toDateString(), 'reason' => 'More milk'];
        $this->post('/subscriptions/'.$subscription->id.'/quantity-change', $data)->assertSessionHasNoErrors();
        $this->post('/subscriptions/'.$subscription->id.'/quantity-change', $data)->assertSessionHasErrors('quantity');
        $change = DB::table('subscription_change_requests')->first();
        $this->put('/quantity-change-requests/'.$change->id, ['status' => 2])->assertForbidden();
        $this->assertSame(1, app(DeliveryGenerationService::class)->generate($admin, today()->addDay()->toDateString()));
        $this->flushSession(); $this->actingAs($admin);
        $this->put('/quantity-change-requests/'.$change->id, ['status' => '2'])->assertSessionHasNoErrors();
        $this->put('/quantity-change-requests/'.$change->id, ['status' => 2])->assertSessionHasErrors('status');
        $service = app(DeliveryGenerationService::class);
        $this->assertSame(1, $service->generate($admin, today()->toDateString()));
        $this->assertSame(0, $service->generate($admin, today()->addDay()->toDateString()));
        $deliveries = Delivery::orderBy('delivery_date')->get();
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $deliveries[0]->id, 'planned_quantity' => 1]);
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $deliveries[1]->id, 'planned_quantity' => 2.5]);
        $this->assertDatabaseHas('subscription_items', ['subscription_id' => $subscription->id, 'default_quantity' => 1]);
        $this->assertDatabaseHas('notifications', ['user_id' => $login->id, 'title' => 'Quantity change decision']);
    }

    public function test_payment_retries_post_one_credit_and_opening_balance_is_counted_once(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'opening_balance' => '100.50']);
        $this->actingAs($this->account());
        $payment = ['customer_id' => $customer->id, 'amount' => '40.25', 'payment_method_id' => 2, 'key' => (string) Str::uuid(), 'transaction_reference' => 'UPI-ONE'];
        $this->post('/payments', $payment)->assertSessionHasNoErrors();
        $this->post('/payments', $payment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('customer_ledger', 1);
        $this->assertSame('60.25', Decimal::format(app(LedgerService::class)->balance($customer)));
        $this->post('/payments', [...$payment, 'amount' => 41])->assertSessionHasErrors('key');
        $this->post('/payments', [...$payment, 'key' => (string) Str::uuid()])->assertSessionHasErrors('transaction_reference');
        $this->assertDatabaseCount('customer_ledger', 1);
    }

    public function test_other_tenant_and_customers_cannot_record_payments_or_read_others_ledger(): void
    {
        $customer = Customer::create(['client_id' => 2, 'name' => 'Private']);
        $this->actingAs($this->account());
        $payment = ['customer_id' => $customer->id, 'amount' => 10, 'payment_method_id' => 1, 'key' => (string) Str::uuid()];
        $this->post('/payments', $payment)->assertNotFound();
        $login = $this->account(Role::Customer);
        $this->flushSession(); $this->actingAs($login);
        $this->post('/payments', $payment)->assertForbidden();
        $this->get('/ledger?customer_id='.$customer->id)->assertInertia(fn (AssertableInertia $page) => $page->has('records.data', 0)->where('selectedBalance', null));
    }

    public function test_opening_balance_cannot_be_overwritten_after_a_financial_entry(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'opening_balance' => 100]);
        $this->actingAs($this->account());
        $this->post('/payments', ['customer_id' => $customer->id, 'amount' => 10, 'payment_method_id' => 1, 'key' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->put('/customers/'.$customer->id, ['name' => 'Customer', 'customer_type' => 1, 'supply_preference' => 1, 'address_line1' => 'Address', 'opening_balance' => 500])->assertSessionHasErrors('opening_balance');
        $this->assertSame(100.0, (float) $customer->fresh()->opening_balance);
    }
}

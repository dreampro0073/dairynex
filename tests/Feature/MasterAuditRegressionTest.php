<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\BusinessTestCase;

class MasterAuditRegressionTest extends BusinessTestCase
{
    public function test_dashboard_uses_current_ledger_collections_and_delivery_status_from_its_tenant(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'opening_balance' => 100, 'delivery_pin_required' => false]);
        Delivery::create(['client_id' => 1, 'customer_id' => $customer->id, 'delivery_date' => today()->toDateString(), 'status_id' => 3, 'total_amount' => 50]);
        DB::table('customer_ledger')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'transaction_type_id' => 3, 'debit' => 50]);
        DB::table('payments')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'payment_no' => 'PAY-ONE', 'payment_date' => now(), 'amount' => 20, 'payment_method_id' => 1, 'status' => 1]);
        DB::table('customer_ledger')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'transaction_type_id' => 8, 'credit' => 20]);
        DB::table('payments')->insert(['client_id' => 2, 'customer_id' => 99, 'payment_no' => 'PRIVATE', 'payment_date' => now(), 'amount' => 999, 'payment_method_id' => 1, 'status' => 1]);
        $this->actingAs($this->account())->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.today_delivered', 1)->where('stats.today_total', 1)->where('stats.collected_today', 20)
            ->where('stats.outstanding', 130)->where('deliveries.0.status', 3));
    }

    public function test_product_rates_have_effective_dates_and_changes_do_not_reprice_delivery_items(): void
    {
        $this->actingAs($this->account());
        $this->post('/products', ['name' => 'Milk', 'unit_type' => 1, 'base_rate' => '56.25', 'status' => 1])->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertDatabaseHas('product_rates', ['product_id' => $product->id, 'rate' => 56.25, 'effective_from' => today()->toDateString()]);
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer']);
        $delivery = Delivery::create(['client_id' => 1, 'customer_id' => $customer->id, 'delivery_date' => today()->toDateString()]);
        DB::table('delivery_items')->insert(['client_id' => 1, 'delivery_id' => $delivery->id, 'product_id' => $product->id, 'planned_quantity' => 1, 'rate' => 56.25]);
        $this->post('/products/'.$product->id.'/rates', ['rate' => '60.00', 'effective_from' => today()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $delivery->id, 'rate' => 56.25]);
        $this->get('/products/'.$product->id.'/edit')->assertInertia(fn (AssertableInertia $page) => $page->has('rates', 2));
    }

    public function test_notifications_are_private_to_the_recipient_even_inside_the_same_dairy(): void
    {
        $user = $this->account(); $other = $this->account();
        foreach ([$user, $other] as $recipient) {
            DB::table('notifications')->insert(['client_id' => 1, 'user_id' => $recipient->id, 'title' => 'Notice '.$recipient->id, 'message' => 'Private message']);
        }
        $this->actingAs($user)->get('/notifications')->assertInertia(fn (AssertableInertia $page) => $page->has('notifications.data', 1));
        $this->put('/notifications/2/read')->assertNotFound();
        $this->put('/notifications/1/read')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notifications', ['id' => 1, 'is_read' => 1]);
        $this->assertDatabaseHas('notifications', ['id' => 2, 'is_read' => 0]);
    }
}

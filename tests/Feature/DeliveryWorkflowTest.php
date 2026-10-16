<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryRoute;
use App\Models\Product;
use App\Models\Subscription;
use App\Services\DeliveryGenerationService;
use App\Services\DeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\BusinessTestCase;

class DeliveryWorkflowTest extends BusinessTestCase
{
    private function setupRound(bool $pin = false): array
    {
        $admin = $this->account();
        $driver = $this->account(Role::DeliveryBoy);
        $login = $this->account(Role::Customer);
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'mobile' => '9999999999', 'supply_preference' => 1, 'delivery_pin_required' => $pin]);
        $customer->forceFill(['user_id' => $login->id])->save();
        $route = DeliveryRoute::create(['client_id' => 1, 'name' => 'Morning', 'delivery_boy_id' => $driver->id, 'status' => 1]);
        DB::table('delivery_route_customers')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'route_id' => $route->id, 'sequence_no' => 1]);
        $product = Product::create(['client_id' => 1, 'name' => 'Milk', 'base_rate' => '56.25', 'unit_type' => 1, 'status' => 1]);
        $subscription = Subscription::create(['client_id' => 1, 'customer_id' => $customer->id, 'subscription_no' => 'SUB-TEST', 'start_date' => today()->subMonth()->toDateString(), 'status' => 1, 'auto_generate_deliveries' => 1]);
        $item = $subscription->items()->create(['client_id' => 1, 'product_id' => $product->id, 'default_quantity' => '1.000', 'rate' => '56.25']);
        return compact('admin', 'driver', 'login', 'customer', 'route', 'product', 'subscription', 'item');
    }

    private function generate(array $round): Delivery
    {
        app(DeliveryGenerationService::class)->generate($round['admin'], today()->toDateString());
        return Delivery::firstOrFail();
    }

    public function test_generation_is_idempotent_and_actual_quantity_is_not_the_subscription_quantity(): void
    {
        $r = $this->setupRound(); $delivery = $this->generate($r);
        $this->assertSame(0, app(DeliveryGenerationService::class)->generate($r['admin'], today()->toDateString()));
        $item = DB::table('delivery_items')->first();
        $this->actingAs($r['driver'])->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => '2.000']]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('delivery_items', ['id' => $item->id, 'actual_quantity' => 2, 'planned_quantity' => 1, 'rate' => 56.25, 'amount' => 112.50]);
        $this->assertDatabaseHas('subscription_items', ['id' => $r['item']->id, 'default_quantity' => 1]);
        $this->assertDatabaseHas('customer_ledger', ['reference_type' => 'delivery', 'reference_id' => $delivery->id, 'debit' => 112.50]);
        $this->assertNull($delivery->fresh()->delivery_pin_verified_at);
        $this->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => 2]]])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('customer_ledger', 1);
    }

    public function test_a_zero_price_product_can_be_delivered_without_creating_a_charge(): void
    {
        $r = $this->setupRound(); $delivery = $this->generate($r);
        DB::table('delivery_items')->where('delivery_id', $delivery->id)->update(['rate' => 0]);
        $item = DB::table('delivery_items')->first();
        $this->actingAs($r['driver'])->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => '1.000']]])->assertSessionHasNoErrors();
        $this->assertSame(3, $delivery->fresh()->status_id);
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $delivery->id, 'actual_quantity' => 1, 'amount' => 0]);
        $this->assertDatabaseCount('customer_ledger', 0);
    }

    public function test_pin_enabled_delivery_requires_valid_pin_and_records_verifying_actor(): void
    {
        $r = $this->setupRound(true); $delivery = $this->generate($r);
        $this->actingAs($r['login']);
        $response = $this->post('/deliveries/'.$delivery->id.'/pin')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $pin = $response->json('pin');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $pin);
        $this->assertTrue(Hash::check($pin, $delivery->fresh()->delivery_pin_hash));
        $this->flushSession(); $this->actingAs($r['driver']);
        $item = DB::table('delivery_items')->first();
        $payload = ['items' => [['id' => $item->id, 'quantity' => 1]]];
        $this->post('/deliveries/'.$delivery->id.'/complete', $payload)->assertSessionHasErrors('pin');
        $this->post('/deliveries/'.$delivery->id.'/complete', [...$payload, 'pin' => '000000'])->assertSessionHasErrors('pin')->assertSessionMissing('_old_input.pin');
        $this->assertDatabaseCount('customer_ledger', 0);
        $this->assertSame(2, $delivery->fresh()->pin_attempts);
        $this->post('/deliveries/'.$delivery->id.'/complete', [...$payload, 'pin' => $pin])->assertSessionHasNoErrors();
        $delivery->refresh();
        $this->assertNotNull($delivery->delivery_pin_verified_at);
        $this->assertSame($r['driver']->id, (int) $delivery->pin_verified_by_user_id);
        $this->assertNull($delivery->delivery_pin_hash);
        $this->assertDatabaseCount('delivery_pin_attempts', 3);
    }

    public function test_invalid_pin_attempts_persist_and_are_limited(): void
    {
        $r = $this->setupRound(true); $delivery = $this->generate($r);
        $item = DB::table('delivery_items')->first();
        $this->actingAs($r['driver']);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/deliveries/'.$delivery->id.'/complete', ['pin' => '000000', 'items' => [['id' => $item->id, 'quantity' => 1]]])->assertSessionHasErrors('pin');
        }
        $this->assertSame(5, $delivery->fresh()->pin_attempts);
        $this->assertDatabaseCount('delivery_pin_attempts', 5);
        $this->assertDatabaseCount('customer_ledger', 0);
    }

    public function test_skip_posts_no_charge_and_cannot_be_completed_again(): void
    {
        $r = $this->setupRound(true); $delivery = $this->generate($r);
        $this->actingAs($r['driver'])->post('/deliveries/'.$delivery->id.'/complete', ['skip' => true])->assertSessionHasNoErrors();
        $this->assertSame(4, $delivery->fresh()->status_id);
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $delivery->id, 'actual_quantity' => 0, 'amount' => 0]);
        $this->assertDatabaseCount('customer_ledger', 0);
        $this->assertNull($delivery->fresh()->delivery_pin_verified_at);
    }

    public function test_assignment_tenant_role_and_customer_ownership_prevent_pin_bypass(): void
    {
        $r = $this->setupRound(true); $delivery = $this->generate($r);
        $item = DB::table('delivery_items')->first();
        $this->actingAs($this->account(Role::DeliveryBoy))->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => 1]], 'pin_required' => false])->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->account(Role::Customer))->post('/deliveries/'.$delivery->id.'/pin')->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->account(Role::ClientAdmin, 2))->get('/deliveries/'.$delivery->id)->assertNotFound();
        $this->flushSession();
        $this->actingAs($r['admin'])->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => 1]]])->assertForbidden();
        $this->assertDatabaseCount('customer_ledger', 0);
    }

    public function test_schedule_pause_and_historical_rate_are_respected(): void
    {
        $r = $this->setupRound();
        DB::table('subscription_schedules')->insert(['client_id' => 1, 'subscription_item_id' => $r['item']->id, 'day_of_week' => today()->dayOfWeekIso,
            'delivery_slot_id' => 2, 'planned_quantity' => '1.500', 'active_from' => today()->subWeek()->toDateString()]);
        DB::table('product_rates')->insert(['client_id' => 1, 'product_id' => $r['product']->id, 'rate' => '60.50', 'effective_from' => today()->toDateString()]);
        DB::table('subscription_pauses')->insert(['client_id' => 1, 'subscription_id' => $r['subscription']->id, 'pause_from' => today(), 'pause_to' => today(), 'status' => 1]);
        $this->assertSame(0, app(DeliveryGenerationService::class)->generate($r['admin'], today()->toDateString()));
        DB::table('subscription_pauses')->update(['status' => 0]);
        $delivery = $this->generate($r);
        $this->assertSame(2, (int) $delivery->delivery_slot_id);
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $delivery->id, 'planned_quantity' => 1.5, 'rate' => 60.5]);
        $r['product']->update(['base_rate' => '999.00']);
        $this->assertDatabaseHas('delivery_items', ['delivery_id' => $delivery->id, 'rate' => 60.5]);
    }

    public function test_generation_and_completion_preserve_the_recorded_pin_policy(): void
    {
        $r = $this->setupRound(true); $delivery = $this->generate($r);
        $r['customer']->update(['delivery_pin_required' => false]);
        $item = DB::table('delivery_items')->first();
        $this->actingAs($r['driver'])->post('/deliveries/'.$delivery->id.'/complete', ['items' => [['id' => $item->id, 'quantity' => 1]], 'pin_required' => false])->assertSessionHasErrors('pin');
        $this->assertTrue($delivery->fresh()->pin_required);
    }

    public function test_customer_login_link_is_explicit_and_cannot_claim_another_dairy_account(): void
    {
        $r = $this->setupRound();
        $new = Customer::create(['client_id' => 1, 'name' => 'New Customer', 'mobile' => '7777777777']);
        $other = $this->account(Role::Customer, 2);
        $this->actingAs($r['admin'])->post('/customers/'.$new->id.'/login', ['user_id' => $other->id])->assertSessionHasErrors('user_id');
        $this->post('/customers/'.$new->id.'/login', ['password' => 'customer-password-123', 'password_confirmation' => 'customer-password-123'])->assertSessionHasNoErrors();
        $this->assertNotNull($new->fresh()->user_id);
        $this->assertDatabaseHas('users', ['id' => $new->fresh()->user_id, 'client_id' => 1, 'user_type_id' => Role::Customer->value]);
    }
}

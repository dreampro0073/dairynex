<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\BusinessTestCase;

class CustomerPinPolicyTest extends BusinessTestCase
{
    private function fields(array $overrides = []): array
    {
        return ['name' => 'Customer', 'customer_type' => 1, 'supply_preference' => 1,
            'address_line1' => 'Test address', 'action' => 'save', ...$overrides];
    }

    public function test_admin_can_create_edit_and_view_a_customer_pin_policy(): void
    {
        $this->actingAs($this->account());
        $this->post('/customers', $this->fields(['delivery_pin_required' => false]))->assertRedirect('/customers');
        $customer = Customer::firstOrFail();
        $this->assertFalse($customer->delivery_pin_required);
        $this->assertDatabaseHas('customer_addresses', ['customer_id' => $customer->id, 'client_id' => 1]);
        $this->get('/customers/'.$customer->id)->assertInertia(fn (AssertableInertia $page) => $page->where('customer.delivery_pin_required', false));
        $this->put('/customers/'.$customer->id, $this->fields(['delivery_pin_required' => true, 'status' => 0]))->assertRedirect('/customers');
        $this->assertTrue($customer->fresh()->delivery_pin_required);
        $this->assertSame(0, $customer->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['client_id' => 1, 'action' => 'customer_saved', 'record_id' => $customer->id]);
    }

    public function test_new_customer_default_is_configurable_and_omitted_update_preserves_existing_policy(): void
    {
        config(['dairynex.delivery_pin_required' => false]);
        $this->actingAs($this->account());
        $this->post('/customers', $this->fields())->assertRedirect('/customers');
        $customer = Customer::firstOrFail();
        $this->assertFalse($customer->delivery_pin_required);
        config(['dairynex.delivery_pin_required' => true]);
        $this->put('/customers/'.$customer->id, $this->fields())->assertRedirect('/customers');
        $this->assertFalse($customer->fresh()->delivery_pin_required);
    }

    public function test_invalid_policy_and_cross_tenant_edits_are_rejected(): void
    {
        $customer = Customer::create(['client_id' => 2, 'name' => 'Other customer', 'delivery_pin_required' => true]);
        $this->actingAs($this->account());
        $this->post('/customers', $this->fields(['delivery_pin_required' => 'invalid']))->assertSessionHasErrors('delivery_pin_required');
        $this->put('/customers/'.$customer->id, $this->fields(['delivery_pin_required' => false]))->assertForbidden();
        $this->get('/customers/'.$customer->id)->assertForbidden();
        $this->assertTrue($customer->fresh()->delivery_pin_required);
    }

    public function test_new_deliveries_snapshot_policy_and_future_customer_edits_do_not_rewrite_history(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'delivery_pin_required' => true]);
        $attributes = ['client_id' => 1, 'customer_id' => $customer->id, 'delivery_boy_id' => 1,
            'delivery_date' => today(), 'route_id' => 1, 'status_id' => 1];
        $first = Delivery::create($attributes);
        $this->assertTrue($first->pin_required);
        $customer->update(['delivery_pin_required' => false]);
        $second = Delivery::create([...$attributes, 'delivery_slot_id' => 2]);
        $this->assertFalse($second->pin_required);
        $this->assertTrue($first->fresh()->pin_required);
    }

    public function test_unknown_legacy_delivery_policy_is_not_invented_by_migration(): void
    {
        \Illuminate\Support\Facades\Schema::table('deliveries', fn ($table) => $table->dropColumn('pin_required'));
        DB::table('deliveries')->insert(['client_id' => 1, 'customer_id' => 1, 'delivery_boy_id' => 1, 'delivery_date' => today(), 'route_id' => 1, 'status_id' => 1]);
        (require database_path('migrations/daatabase_migrations.php'))->up();
        $this->assertNull(DB::table('deliveries')->value('pin_required'));
    }

    public function test_policy_snapshot_cannot_be_weakened_after_creation(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'delivery_pin_required' => true]);
        $delivery = Delivery::create(['client_id' => 1, 'customer_id' => $customer->id,
            'delivery_boy_id' => 1, 'delivery_date' => today(), 'route_id' => 1, 'status_id' => 1]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $delivery->forceFill(['pin_required' => false])->save();
    }

    public function test_customers_with_business_history_cannot_be_deleted(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer']);
        DB::table('customer_ledger')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'transaction_type_id' => 1, 'transaction_date' => today(), 'debit' => 50]);
        $this->actingAs($this->account())->delete('/customers/'.$customer->id)->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseCount('customer_ledger', 1);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\BusinessTestCase;

class InvoiceWorkflowTest extends BusinessTestCase
{
    public function test_invoice_uses_actual_quantity_and_historical_rate_and_does_not_post_a_second_debit(): void
    {
        $admin = $this->account();
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer', 'delivery_pin_required' => false]);
        $product = Product::create(['client_id' => 1, 'name' => 'Milk', 'unit_type' => 1, 'base_rate' => 999, 'status' => 1]);
        $lastMonth = today()->subMonthNoOverflow()->startOfMonth();
        $delivery = Delivery::create([
            'client_id' => 1,
            'customer_id' => $customer->id,
            'delivery_date' => $lastMonth->toDateString(),
            'status_id' => 3,
            'total_amount' => '112.50',
        ]);

        DB::table('delivery_items')
            ->insert([
                'client_id' => 1,
                'delivery_id' => $delivery->id,
                'product_id' => $product->id,
                'planned_quantity' => 1,
                'actual_quantity' => 2,
                'rate' => '56.25',
                'amount' => '112.50',
            ]);

        DB::table('customer_ledger')
            ->insert([
                'client_id' => 1,
                'customer_id' => $customer->id,
                'transaction_type_id' => 3,
                'reference_type' => 'delivery',
                'reference_id' => $delivery->id,
                'debit' => '112.50',
                'balance' => '112.50',
            ]);

        $this->actingAs($admin);
        $request = ['customer_id' => $customer->id, 'month' => $lastMonth->format('Y-m')];
        $this->post('/invoices', $request)->assertRedirect('/invoices/1');
        $this->post('/invoices', $request)->assertRedirect('/invoices/1');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseCount('customer_ledger', 1);
        $this->assertDatabaseHas('invoice_items', ['quantity' => 2, 'rate' => 56.25, 'amount' => 112.50]);
        $this->assertDatabaseHas('invoices', ['total' => 112.50, 'due_amount' => 112.50]);
        $this->post('/payments', [
            'customer_id' => $customer->id,
            'amount' => '100.00',
            'payment_method_id' => 1,
            'key' => (string) Str::uuid(),
        ])
            ->assertSessionHasNoErrors();

        $allocation = ['payment_id' => 1, 'amount' => '100.00'];
        $this->post('/invoices/1/allocate', $allocation)->assertSessionHasNoErrors();
        $this->post('/invoices/1/allocate', $allocation)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoice_payments', 1);
        $this->assertDatabaseCount('customer_ledger', 2);
        $this->assertDatabaseHas('invoices', ['paid_amount' => 100, 'due_amount' => 12.50, 'status' => 3]);
        $this->post('/invoices/1/allocate', ['payment_id' => 1, 'amount' => 12.50])
            ->assertSessionHasErrors('amount');
    }

    public function test_invoice_ownership_and_payment_allocation_are_tenant_scoped(): void
    {
        $login = $this->account(Role::Customer);
        $own = Customer::create(['client_id' => 1, 'name' => 'Own']);
        $own->forceFill(['user_id' => $login->id])->save();
        $other = Customer::create(['client_id' => 1, 'name' => 'Other']);

        foreach ([$own, $other] as $customer) {
            DB::table('invoices')
                ->insert([
                    'client_id' => 1,
                    'customer_id' => $customer->id,
                    'invoice_no' => 'INV-'.$customer->id,
                    'invoice_date' => today(),
                    'status' => 2,
                    'total' => 100,
                    'due_amount' => 100,
                ]);
        }

        $this->actingAs($login)->get('/invoices/1')->assertOk();
        $this->get('/invoices/2')->assertNotFound();
        $this->post('/invoices/1/allocate', ['payment_id' => 1, 'amount' => 10])->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->account(clientId: 2));
        $this->get('/invoices/1')->assertNotFound();
        $this->post('/invoices/1/allocate', ['payment_id' => 1, 'amount' => 10])->assertNotFound();
    }

    public function test_current_month_and_pending_deliveries_cannot_be_finalized_into_invoices(): void
    {
        $customer = Customer::create(['client_id' => 1, 'name' => 'Customer']);
        $this->actingAs($this->account());
        $this->post('/invoices', [
            'customer_id' => $customer->id,
            'month' => today()->format('Y-m'),
        ])
            ->assertSessionHasErrors('month');

        $month = today()->subMonthNoOverflow()->startOfMonth();
        Delivery::create([
            'client_id' => 1,
            'customer_id' => $customer->id,
            'delivery_date' => $month->toDateString(),
            'status_id' => 1,
        ]);

        $this->post('/invoices', ['customer_id' => $customer->id, 'month' => $month->format('Y-m')])
            ->assertSessionHasErrors('month');

        $this->assertDatabaseCount('invoices', 0);
    }
}

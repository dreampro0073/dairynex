<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Services\LedgerService;
use App\Services\WalkInSaleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\BusinessTestCase;

class WalkInSaleTest extends BusinessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('walk_in_sales', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('client_id');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('receipt_no');
            $t->string('customer_name')->nullable();
            $t->string('customer_mobile')->nullable();
            $t->dateTime('sold_at');
            $t->decimal('subtotal', 14, 2);
            $t->decimal('discount', 14, 2);
            $t->decimal('total', 14, 2);
            $t->unsignedTinyInteger('payment_method_id')->nullable();
            $t->decimal('paid_amount', 14, 2);
            $t->decimal('due_amount', 14, 2);
            $t->unsignedTinyInteger('status');
            $t->string('idempotency_key');
            $t->unsignedBigInteger('created_by_user_id')->nullable();
            $t->timestamps();
            $t->unique(['client_id', 'idempotency_key']);
        });

        Schema::create('walk_in_sale_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('client_id');
            $t->unsignedBigInteger('sale_id');
            $t->unsignedBigInteger('product_id');
            $t->string('product_name');
            $t->unsignedTinyInteger('unit_type');
            $t->decimal('quantity', 12, 3);
            $t->decimal('rate', 12, 2);
            $t->decimal('amount', 14, 2);
            $t->timestamp('created_at')->nullable();
        });
    }

    public function test_guest_cash_sale_stays_out_of_delivery_and_invoice_flows_and_retries_are_idempotent(): void
    {
        $admin = $this->account();
        $milk = Product::create([
            'client_id' => 1,
            'name' => 'Milk',
            'unit_type' => 1,
            'base_rate' => '56.25',
            'status' => 1,
        ]);

        $paneer = Product::create([
            'client_id' => 1,
            'name' => 'Paneer',
            'unit_type' => 3,
            'base_rate' => '320.00',
            'status' => 1,
        ]);

        $data = [
            'key' => 'counter-sale-key-00000000000001',
            'customer_name' => 'Walk-in',
            'payment_method_id' => 1,
            'items' => [
                ['product_id' => $milk->id, 'quantity' => '2.000'],
                ['product_id' => $paneer->id, 'quantity' => '0.500'],
            ],
        ];

        $sale = app(WalkInSaleService::class)->create($admin, $data);
        $retry = app(WalkInSaleService::class)->create($admin, $data);
        $this->assertSame($sale->id, $retry->id);
        $this->assertDatabaseCount('walk_in_sales', 1);
        $this->assertDatabaseCount('walk_in_sale_items', 2);
        $this->assertSame('272.50', number_format((float) $sale->total, 2));
        $this->assertSame('272.50', number_format((float) $sale->paid_amount, 2));
        $this->assertSame('0.00', number_format((float) $sale->due_amount, 2));
        $this->assertDatabaseCount('deliveries', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_credit_requires_identified_customer_and_posts_separate_walk_in_ledger_reference(): void
    {
        $admin = $this->account();
        $product = Product::create([
            'client_id' => 1,
            'name' => 'Curd',
            'unit_type' => 1,
            'base_rate' => '42.00',
            'status' => 1,
        ]);

        $customer = Customer::create(['client_id' => 1, 'name' => 'Known customer', 'mobile' => '9999999999']);
        $service = app(WalkInSaleService::class);

        try {
            $service->create($admin, [
                'key' => 'credit-anonymous-key-00000000001',
                'payment_method_id' => 0,
                'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
            ]);

            $this->fail('Anonymous credit must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_id', $e->errors());
        }

        $sale = $service->create($admin, [
            'key' => 'credit-known-key-000000000000001',
            'customer_id' => $customer->id,
            'payment_method_id' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
        ]);

        $this->assertSame('0.00', number_format((float) $sale->paid_amount, 2));
        $this->assertSame('42.00', number_format((float) $sale->due_amount, 2));
        $this->assertDatabaseHas('customer_ledger', [
            'client_id' => 1,
            'customer_id' => $customer->id,
            'reference_type' => 'walk_in_sale',
            'reference_id' => $sale->id,
            'debit' => 42,
            'credit' => 0,
        ]);

        $this->assertSame(4200, app(LedgerService::class)->balance($customer));
    }

    public function test_identified_upi_counter_payment_is_recorded_in_existing_payment_history(): void
    {
        $admin = $this->account();
        $product = Product::create([
            'client_id' => 1,
            'name' => 'Ghee',
            'unit_type' => 1,
            'base_rate' => '500.00',
            'status' => 1,
        ]);

        $customer = Customer::create(['client_id' => 1, 'name' => 'Counter customer', 'mobile' => '8888888888']);
        $sale = app(WalkInSaleService::class)
            ->create($admin, [
                'key' => 'upi-customer-key-0000000000001',
                'customer_id' => $customer->id,
                'payment_method_id' => 2,
                'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
            ]);

        $this->assertSame(0, app(LedgerService::class)->balance($customer));
        $this->assertDatabaseHas('payments', [
            'client_id' => 1,
            'customer_id' => $customer->id,
            'payment_method_id' => 2,
            'amount' => 500,
            'notes' => 'Walk-in sale receipt #'.$sale->receipt_no,
        ]);

        $this->assertDatabaseHas('customer_ledger', [
            'client_id' => 1,
            'customer_id' => $customer->id,
            'reference_type' => 'walk_in_sale',
            'reference_id' => $sale->id,
            'debit' => 500,
            'credit' => 0,
        ]);
    }

    public function test_other_tenant_product_or_customer_cannot_be_attached_to_a_sale(): void
    {
        $admin = $this->account();
        $foreign = Product::create([
            'client_id' => 2,
            'name' => 'Foreign',
            'unit_type' => 1,
            'base_rate' => '10.00',
            'status' => 1,
        ]);

        try {
            app(WalkInSaleService::class)
                ->create($admin, [
                    'key' => 'foreign-product-key-000000000001',
                    'payment_method_id' => 1,
                    'items' => [['product_id' => $foreign->id, 'quantity' => '1.000']],
                ]);

            $this->fail('Foreign product must be rejected.');
        } catch (ModelNotFoundException $e) {
            $this->assertDatabaseCount('walk_in_sales', 0);
        }
    }

    public function test_repeated_save_requests_create_one_sale_payment_and_ledger_charge(): void
    {
        $this->actingAs($this->account());
        $product = $this->counterProduct();
        $customer = Customer::create(['client_id' => 1, 'name' => 'Counter customer']);
        $data = [
            'key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'payment_method_id' => 2,
            'items' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ];

        for ($click = 0; $click < 10; $click++) {
            $this->post('/walk-in-sales', $data)
                ->assertRedirect('/walk-in-sales/1')
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('walk_in_sales', 1);
        $this->assertDatabaseCount('walk_in_sale_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('customer_ledger', 2);
        $this->assertDatabaseHas('walk_in_sales', ['total' => 112.50, 'due_amount' => 0]);
        $this->assertDatabaseHas('payments', ['amount' => 112.50]);
        $this->assertSame(0, app(LedgerService::class)->balance($customer));

        $this->get('/walk-in-sales?q=Counter')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WalkIn/Index')
                ->has('sales.data', 1));

        $this->get('/walk-in-sales?q=NoMatch')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sales.data', 0));
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_quantity_saves_nothing_and_can_be_corrected(mixed $quantity): void
    {
        $this->actingAs($this->account());
        $product = $this->counterProduct();
        $data = [
            'key' => (string) Str::uuid(),
            'payment_method_id' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ];

        $this->post('/walk-in-sales', $data)->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseCount('walk_in_sales', 0);
        $this->assertDatabaseCount('walk_in_sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customer_ledger', 0);

        $this->flushSession();
        $data['items'][0]['quantity'] = '1.000';
        $this->post('/walk-in-sales', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('walk_in_sales', 1);
    }

    public static function invalidQuantities(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'too many decimal places' => ['1.0001'],
            'over the maximum' => ['1000000'],
            'letters' => ['abc'],
            'empty field' => [''],
            'missing value' => [null],
        ];
    }

    public function test_empty_cart_and_duplicate_products_do_not_create_a_partial_sale(): void
    {
        $this->actingAs($this->account());
        $product = $this->counterProduct();
        $data = [
            'key' => (string) Str::uuid(),
            'payment_method_id' => 1,
            'items' => [],
        ];

        $this->post('/walk-in-sales', $data)->assertSessionHasErrors('items');
        $this->flushSession();
        $data['items'] = [
            ['product_id' => $product->id, 'quantity' => '1.000'],
            ['product_id' => $product->id, 'quantity' => '2.000'],
        ];

        $this->post('/walk-in-sales', $data)->assertSessionHasErrors('items.0.product_id');
        $this->assertDatabaseCount('walk_in_sales', 0);
        $this->assertDatabaseCount('walk_in_sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customer_ledger', 0);
    }

    public function test_guest_credit_and_unsupported_payment_can_be_corrected_before_saving(): void
    {
        $this->actingAs($this->account());
        $product = $this->counterProduct();
        $data = [
            'key' => (string) Str::uuid(),
            'payment_method_id' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
        ];

        $this->post('/walk-in-sales', $data)->assertSessionHasErrors('customer_id');
        $this->flushSession();
        $data['payment_method_id'] = 999;
        $this->post('/walk-in-sales', $data)->assertSessionHasErrors('payment_method_id');
        $this->flushSession();

        $foreignCustomer = Customer::create(['client_id' => 2, 'name' => 'Another dairy customer']);
        $data['customer_id'] = $foreignCustomer->id;
        $data['payment_method_id'] = 1;
        $this->post('/walk-in-sales', $data)->assertNotFound();
        unset($data['customer_id']);

        $this->assertDatabaseCount('walk_in_sales', 0);
        $this->assertDatabaseCount('walk_in_sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customer_ledger', 0);

        $this->flushSession();
        $data['payment_method_id'] = 1;
        $this->post('/walk-in-sales', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('walk_in_sales', 1);
    }

    public function test_product_deactivated_after_opening_the_cart_cannot_be_sold(): void
    {
        $this->actingAs($this->account());
        $product = $this->counterProduct();
        $data = [
            'key' => (string) Str::uuid(),
            'payment_method_id' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => '1.000']],
        ];

        $product->update(['status' => 0]);
        $this->post('/walk-in-sales', $data)->assertNotFound();
        $this->assertDatabaseCount('walk_in_sales', 0);
        $this->assertDatabaseCount('walk_in_sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('customer_ledger', 0);
    }

    private function counterProduct(): Product
    {
        return Product::create([
            'client_id' => 1,
            'name' => 'Milk',
            'unit_type' => 1,
            'base_rate' => '56.25',
            'status' => 1,
        ]);
    }
}

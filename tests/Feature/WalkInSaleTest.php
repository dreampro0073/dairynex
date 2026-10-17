<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Services\WalkInSaleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\BusinessTestCase;

class WalkInSaleTest extends BusinessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('walk_in_sales', function(Blueprint $t){$t->id();$t->unsignedBigInteger('client_id');$t->unsignedBigInteger('customer_id')->nullable();$t->string('receipt_no');$t->string('customer_name')->nullable();$t->string('customer_mobile')->nullable();$t->dateTime('sold_at');$t->decimal('subtotal',14,2);$t->decimal('discount',14,2);$t->decimal('total',14,2);$t->unsignedTinyInteger('payment_method_id')->nullable();$t->decimal('paid_amount',14,2);$t->decimal('due_amount',14,2);$t->unsignedTinyInteger('status');$t->string('idempotency_key');$t->unsignedBigInteger('created_by_user_id')->nullable();$t->timestamps();$t->unique(['client_id','idempotency_key']);});
        Schema::create('walk_in_sale_items', function(Blueprint $t){$t->id();$t->unsignedBigInteger('client_id');$t->unsignedBigInteger('sale_id');$t->unsignedBigInteger('product_id');$t->string('product_name');$t->unsignedTinyInteger('unit_type');$t->decimal('quantity',12,3);$t->decimal('rate',12,2);$t->decimal('amount',14,2);$t->timestamp('created_at')->nullable();});
    }

    public function test_guest_cash_sale_stays_out_of_delivery_and_invoice_flows_and_retries_are_idempotent(): void
    {
        $admin=$this->account();
        $milk=Product::create(['client_id'=>1,'name'=>'Milk','unit_type'=>1,'base_rate'=>'56.25','status'=>1]);
        $paneer=Product::create(['client_id'=>1,'name'=>'Paneer','unit_type'=>3,'base_rate'=>'320.00','status'=>1]);
        $data=['key'=>'counter-sale-key-00000000000001','customer_name'=>'Walk-in','payment_method_id'=>1,'items'=>[
            ['product_id'=>$milk->id,'quantity'=>'2.000'],['product_id'=>$paneer->id,'quantity'=>'0.500']]];
        $sale=app(WalkInSaleService::class)->create($admin,$data);
        $retry=app(WalkInSaleService::class)->create($admin,$data);
        $this->assertSame($sale->id,$retry->id);
        $this->assertDatabaseCount('walk_in_sales',1);$this->assertDatabaseCount('walk_in_sale_items',2);
        $this->assertSame('272.50',number_format((float)$sale->total,2));$this->assertSame('272.50',number_format((float)$sale->paid_amount,2));$this->assertSame('0.00',number_format((float)$sale->due_amount,2));
        $this->assertDatabaseCount('deliveries',0);$this->assertDatabaseCount('invoices',0);
    }

    public function test_credit_requires_identified_customer_and_posts_separate_walk_in_ledger_reference(): void
    {
        $admin=$this->account();$product=Product::create(['client_id'=>1,'name'=>'Curd','unit_type'=>1,'base_rate'=>'42.00','status'=>1]);
        $customer=Customer::create(['client_id'=>1,'name'=>'Known customer','mobile'=>'9999999999']);
        $service=app(WalkInSaleService::class);
        try{$service->create($admin,['key'=>'credit-anonymous-key-00000000001','payment_method_id'=>0,'items'=>[['product_id'=>$product->id,'quantity'=>'1.000']]]);$this->fail('Anonymous credit must be rejected.');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('customer_id',$e->errors());}
        $sale=$service->create($admin,['key'=>'credit-known-key-000000000000001','customer_id'=>$customer->id,'payment_method_id'=>0,'items'=>[['product_id'=>$product->id,'quantity'=>'1.000']]]);
        $this->assertSame('0.00',number_format((float)$sale->paid_amount,2));$this->assertSame('42.00',number_format((float)$sale->due_amount,2));
        $this->assertDatabaseHas('customer_ledger',['client_id'=>1,'customer_id'=>$customer->id,'reference_type'=>'walk_in_sale','reference_id'=>$sale->id,'debit'=>42,'credit'=>0]);
        $this->assertSame(4200,app(\App\Services\LedgerService::class)->balance($customer));
    }

    public function test_identified_upi_counter_payment_is_recorded_in_existing_payment_history(): void
    {
        $admin=$this->account();$product=Product::create(['client_id'=>1,'name'=>'Ghee','unit_type'=>1,'base_rate'=>'500.00','status'=>1]);
        $customer=Customer::create(['client_id'=>1,'name'=>'Counter customer','mobile'=>'8888888888']);
        $sale=app(WalkInSaleService::class)->create($admin,['key'=>'upi-customer-key-0000000000001','customer_id'=>$customer->id,'payment_method_id'=>2,
            'items'=>[['product_id'=>$product->id,'quantity'=>'1.000']]]);
        $this->assertSame(0,app(\App\Services\LedgerService::class)->balance($customer));
        $this->assertDatabaseHas('payments',['client_id'=>1,'customer_id'=>$customer->id,'payment_method_id'=>2,'amount'=>500,'notes'=>'Walk-in sale receipt #'.$sale->receipt_no]);
        $this->assertDatabaseHas('customer_ledger',['client_id'=>1,'customer_id'=>$customer->id,'reference_type'=>'walk_in_sale','reference_id'=>$sale->id,'debit'=>500,'credit'=>0]);
    }

    public function test_other_tenant_product_or_customer_cannot_be_attached_to_a_sale(): void
    {
        $admin=$this->account();$foreign=Product::create(['client_id'=>2,'name'=>'Foreign','unit_type'=>1,'base_rate'=>'10.00','status'=>1]);
        try{app(WalkInSaleService::class)->create($admin,['key'=>'foreign-product-key-000000000001','payment_method_id'=>1,'items'=>[['product_id'=>$foreign->id,'quantity'=>'1.000']]]);$this->fail('Foreign product must be rejected.');}
        catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$this->assertDatabaseCount('walk_in_sales',0);}
    }
}

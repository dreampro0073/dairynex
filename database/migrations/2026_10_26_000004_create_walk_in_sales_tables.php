<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('walk_in_sales')) {
            Schema::create('walk_in_sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('receipt_no', 60);
                $table->string('customer_name', 150)->nullable();
                $table->string('customer_mobile', 20)->nullable();
                $table->dateTime('sold_at');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->unsignedTinyInteger('payment_method_id')->nullable();
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('due_amount', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->uuid('idempotency_key');
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();
                $table->unique(['client_id', 'receipt_no']);
                $table->unique(['client_id', 'idempotency_key']);
                $table->index(['client_id', 'customer_id', 'sold_at']);
            });
        }

        if (! Schema::hasTable('walk_in_sale_items')) {
            Schema::create('walk_in_sale_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('sale_id');
                $table->unsignedBigInteger('product_id');
                $table->string('product_name', 150);
                $table->unsignedTinyInteger('unit_type');
                $table->decimal('quantity', 12, 3);
                $table->decimal('rate', 12, 2);
                $table->decimal('amount', 14, 2);
                $table->timestamp('created_at')->nullable();
                $table->index(['client_id', 'sale_id']);
                $table->index(['client_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        // Business data is retained; removal is intentionally not destructive.
    }
};

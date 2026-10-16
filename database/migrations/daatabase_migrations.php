<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Consolidated DairyNex business schema and additive upgrades.
// Existing tables/columns are adopted; no business rows or role upgrades are imported.
// Anonymous migrations allow Laravel to discover this filename without a date prefix.
return new class extends Migration
{
    public function up(): void
    {
        $this->createBusinessSchema();
        $this->addAuthenticationSupport();
        $this->addDeliveryPinPolicies();
        $this->addIdentityAndDeliveryControls();
        $this->protectLedgerReferences();
    }

    public function down(): void
    {
        // Preserve adopted business/authentication tables, policy history and
        // financial duplicate protection. Removal requires a reviewed migration.
    }

    private function createBusinessSchema(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 100);
                $table->string('table_name', 100)->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['user_id'], 'audit_logs_user_id');
                $table->index(['client_id', 'table_name', 'record_id', 'created_at'], 'audit_logs_client_id');
            });
        }
        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('legal_name', 200)->nullable();
                $table->string('mobile', 20)->nullable();
                $table->string('email', 150)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['status'], 'clients_status');
            });
        }
        if (!Schema::hasTable('client_subscriptions')) {
            Schema::create('client_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('plan_id');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['plan_id'], 'client_subscriptions_plan_id');
                $table->index(['client_id', 'status'], 'client_subscriptions_client_id');
            });
        }
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('customer_code', 50)->nullable();
                $table->string('name', 150);
                $table->string('mobile', 20)->nullable();
                $table->string('email', 150)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->unsignedTinyInteger('gender')->nullable()->comment('1 male, 2 female, 3 other');
                $table->decimal('opening_balance', 14,2)->default('0.00');
                $table->decimal('credit_limit', 14,2)->default('0.00');
                $table->unsignedTinyInteger('payment_mode_id')->nullable();
                $table->unsignedTinyInteger('customer_type')->default('1')->comment('1 regular, 2 walk-in, 3 both');
                $table->unsignedTinyInteger('status')->default('1');
                $table->unsignedTinyInteger('supply_preference')->default('1')->comment('1 morning, 2 evening, 3 both');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'customer_code'], 'customers_uq_customer_code');
                $table->index(['payment_mode_id'], 'customers_payment_mode_id');
                $table->index(['client_id', 'mobile'], 'customers_client_id');
                $table->index(['client_id', 'status'], 'customers_client_id_2');
            });
        }
        if (!Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedTinyInteger('address_type')->default('1');
                $table->string('address_line1', 255);
                $table->string('address_line2', 255)->nullable();
                $table->string('landmark', 150)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->decimal('latitude', 10,7)->nullable();
                $table->decimal('longitude', 10,7)->nullable();
                $table->unsignedTinyInteger('is_default')->default('0');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['customer_id'], 'customer_addresses_customer_id');
                $table->index(['client_id', 'customer_id'], 'customer_addresses_client_id');
            });
        }
        if (!Schema::hasTable('customer_ledger')) {
            Schema::create('customer_ledger', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedTinyInteger('transaction_type_id');
                $table->string('reference_type', 50)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->dateTime('transaction_date')->useCurrent();
                $table->decimal('debit', 14,2)->default('0.00');
                $table->decimal('credit', 14,2)->default('0.00');
                $table->decimal('balance', 14,2)->default('0.00');
                $table->string('description', 255)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['customer_id'], 'customer_ledger_customer_id');
                $table->index(['transaction_type_id'], 'customer_ledger_transaction_type_id');
                $table->index(['created_by_user_id'], 'customer_ledger_created_by_user_id');
                $table->index(['client_id', 'customer_id', 'transaction_date'], 'customer_ledger_client_id');
                $table->index(['client_id', 'reference_type', 'reference_id'], 'customer_ledger_client_id_2');
            });
        }
        if (!Schema::hasTable('deliveries')) {
            Schema::create('deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedBigInteger('route_id')->nullable();
                $table->unsignedBigInteger('delivery_boy_id')->nullable();
                $table->date('delivery_date');
                $table->unsignedTinyInteger('delivery_slot_id')->default('1');
                $table->unsignedTinyInteger('source_type')->default('1')->comment('1 subscription, 2 on-hand, 3 manual');
                $table->unsignedTinyInteger('status_id')->default('1');
                $table->string('delivery_pin_hash', 255)->nullable();
                $table->dateTime('delivery_pin_verified_at')->nullable();
                $table->unsignedTinyInteger('pin_attempts')->default('0');
                $table->dateTime('delivered_at')->nullable();
                $table->decimal('total_amount', 14,2)->default('0.00');
                $table->unsignedTinyInteger('payment_status')->default('1')->comment('1 unpaid, 2 partial, 3 paid');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['customer_id'], 'deliveries_customer_id');
                $table->index(['subscription_id'], 'deliveries_subscription_id');
                $table->index(['route_id'], 'deliveries_route_id');
                $table->index(['delivery_boy_id'], 'deliveries_delivery_boy_id');
                $table->index(['status_id'], 'deliveries_status_id');
                $table->index(['client_id', 'delivery_date', 'status_id'], 'deliveries_client_id');
                $table->index(['client_id', 'customer_id', 'delivery_date'], 'deliveries_client_id_2');
                $table->index(['client_id', 'delivery_boy_id', 'delivery_date'], 'deliveries_client_id_3');
            });
        }
        if (!Schema::hasTable('delivery_items')) {
            Schema::create('delivery_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('delivery_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('subscription_item_id')->nullable();
                $table->decimal('planned_quantity', 12,3)->default('0.000');
                $table->decimal('actual_quantity', 12,3)->default('0.000');
                $table->decimal('rate', 12,2)->default('0.00');
                $table->decimal('amount', 14,2)->default('0.00');
                $table->unsignedTinyInteger('quantity_change_type')->default('1')->comment('1 normal, 2 increased, 3 decreased, 4 not_required');
                $table->string('notes', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['delivery_id'], 'delivery_items_delivery_id');
                $table->index(['product_id'], 'delivery_items_product_id');
                $table->index(['subscription_item_id'], 'delivery_items_subscription_item_id');
                $table->index(['client_id', 'delivery_id'], 'delivery_items_client_id');
                $table->index(['client_id', 'product_id'], 'delivery_items_client_id_2');
            });
        }
        if (!Schema::hasTable('delivery_pin_attempts')) {
            Schema::create('delivery_pin_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('delivery_id');
                $table->unsignedBigInteger('attempted_by_user_id')->nullable();
                $table->string('entered_pin_hash', 255)->nullable();
                $table->unsignedTinyInteger('success')->default('0');
                $table->dateTime('attempted_at')->useCurrent();
                $table->string('ip_address', 45)->nullable();
                $table->index(['delivery_id'], 'delivery_pin_attempts_delivery_id');
                $table->index(['attempted_by_user_id'], 'delivery_pin_attempts_attempted_by_user_id');
                $table->index(['client_id', 'delivery_id', 'attempted_at'], 'delivery_pin_attempts_client_id');
            });
        }
        if (!Schema::hasTable('delivery_routes')) {
            Schema::create('delivery_routes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('delivery_boy_id');
                $table->string('name', 100);
                $table->string('area_locality', 255)->nullable();
                $table->unsignedInteger('expected_stops')->default('0');
                $table->date('route_date')->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['delivery_boy_id'], 'delivery_routes_delivery_boy_id');
                $table->index(['client_id', 'route_date', 'delivery_boy_id'], 'delivery_routes_client_id');
            });
        }
        if (!Schema::hasTable('delivery_route_customers')) {
            Schema::create('delivery_route_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('route_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedInteger('sequence_no')->default('0');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->unique(['route_id', 'customer_id'], 'delivery_route_customers_uq_route_customer');
                $table->index(['customer_id'], 'delivery_route_customers_customer_id');
                $table->index(['client_id', 'route_id', 'sequence_no'], 'delivery_route_customers_client_id');
            });
        }
        if (!Schema::hasTable('inventory_locations')) {
            Schema::create('inventory_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('name', 150);
                $table->unsignedTinyInteger('location_type')->default('1')->comment('1 main, 2 van, 3 shop');
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'name'], 'inventory_locations_uq_location');
            });
        }
        if (!Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('location_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedTinyInteger('transaction_type_id');
                $table->dateTime('transaction_date')->useCurrent();
                $table->decimal('quantity', 12,3);
                $table->string('reference_type', 50)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('notes', 255)->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['location_id'], 'inventory_transactions_location_id');
                $table->index(['product_id'], 'inventory_transactions_product_id');
                $table->index(['transaction_type_id'], 'inventory_transactions_transaction_type_id');
                $table->index(['created_by_user_id'], 'inventory_transactions_created_by_user_id');
                $table->index(['client_id', 'location_id', 'product_id', 'transaction_date'], 'inventory_transactions_client_id');
            });
        }
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('invoice_no', 60);
                $table->date('invoice_date');
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->decimal('subtotal', 14,2)->default('0.00');
                $table->decimal('discount', 14,2)->default('0.00');
                $table->decimal('tax', 14,2)->default('0.00');
                $table->decimal('total', 14,2)->default('0.00');
                $table->decimal('paid_amount', 14,2)->default('0.00');
                $table->decimal('due_amount', 14,2)->default('0.00');
                $table->unsignedTinyInteger('status')->default('1')->comment('1 draft, 2 issued, 3 partial, 4 paid, 5 cancelled');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'invoice_no'], 'invoices_uq_invoice_no');
                $table->index(['customer_id'], 'invoices_customer_id');
                $table->index(['client_id', 'customer_id', 'invoice_date'], 'invoices_client_id');
            });
        }
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('delivery_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->decimal('quantity', 12,3)->default('0.000');
                $table->decimal('rate', 12,2)->default('0.00');
                $table->decimal('amount', 14,2)->default('0.00');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['invoice_id'], 'invoice_items_invoice_id');
                $table->index(['delivery_id'], 'invoice_items_delivery_id');
                $table->index(['product_id'], 'invoice_items_product_id');
                $table->index(['client_id', 'invoice_id'], 'invoice_items_client_id');
            });
        }
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('payment_id');
                $table->decimal('amount', 14,2);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->unique(['invoice_id', 'payment_id'], 'invoice_payments_uq_invoice_payment');
                $table->index(['client_id'], 'invoice_payments_client_id');
                $table->index(['payment_id'], 'invoice_payments_payment_id');
            });
        }
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('title', 200);
                $table->text('message');
                $table->unsignedTinyInteger('notification_type')->default('1');
                $table->unsignedTinyInteger('is_read')->default('0');
                $table->dateTime('read_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['user_id'], 'notifications_user_id');
                $table->index(['customer_id'], 'notifications_customer_id');
                $table->index(['client_id', 'user_id', 'is_read', 'created_at'], 'notifications_client_id');
            });
        }
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('payment_no', 60);
                $table->dateTime('payment_date')->useCurrent();
                $table->decimal('amount', 14,2);
                $table->unsignedTinyInteger('payment_method_id');
                $table->string('transaction_reference', 150)->nullable();
                $table->unsignedBigInteger('collected_by_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'payment_no'], 'payments_uq_payment_no');
                $table->index(['customer_id'], 'payments_customer_id');
                $table->index(['payment_method_id'], 'payments_payment_method_id');
                $table->index(['collected_by_user_id'], 'payments_collected_by_user_id');
                $table->index(['client_id', 'customer_id', 'payment_date'], 'payments_client_id');
            });
        }
        if (!Schema::hasTable('payment_plans')) {
            Schema::create('payment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->decimal('total_amount', 14,2);
                $table->decimal('down_payment', 14,2)->default('0.00');
                $table->unsignedInteger('installment_count');
                $table->unsignedTinyInteger('frequency_id')->default('1')->comment('1 daily, 2 weekly, 3 monthly');
                $table->date('start_date');
                $table->unsignedTinyInteger('status')->default('1')->comment('1 active, 2 completed, 3 cancelled');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['customer_id'], 'payment_plans_customer_id');
                $table->index(['client_id', 'customer_id', 'status'], 'payment_plans_client_id');
            });
        }
        if (!Schema::hasTable('payment_plan_installments')) {
            Schema::create('payment_plan_installments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('payment_plan_id');
                $table->unsignedInteger('installment_no');
                $table->date('due_date');
                $table->decimal('amount', 14,2);
                $table->decimal('paid_amount', 14,2)->default('0.00');
                $table->unsignedTinyInteger('status')->default('1')->comment('1 pending, 2 partial, 3 paid, 4 overdue');
                $table->dateTime('paid_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['payment_plan_id', 'installment_no'], 'payment_plan_installments_uq_installment');
                $table->index(['client_id', 'due_date', 'status'], 'payment_plan_installments_client_id');
            });
        }
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->decimal('monthly_price', 12,2)->default('0.00');
                $table->unsignedInteger('max_customers')->nullable();
                $table->unsignedInteger('max_users')->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('name', 150);
                $table->string('sku', 80)->nullable();
                $table->unsignedTinyInteger('unit_type')->default('1')->comment('1 liter, 2 ml, 3 kg, 4 gram, 5 piece, 6 packet');
                $table->decimal('base_rate', 12,2)->default('0.00');
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'sku'], 'products_uq_sku');
                $table->index(['category_id'], 'products_category_id');
                $table->index(['client_id', 'status'], 'products_client_id');
            });
        }
        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->string('name', 100);
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'name'], 'product_categories_uq_category');
            });
        }
        if (!Schema::hasTable('product_rates')) {
            Schema::create('product_rates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('rate', 12,2);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['product_id'], 'product_rates_product_id');
                $table->index(['client_id', 'product_id', 'effective_from'], 'product_rates_client_id');
            });
        }
        if (!Schema::hasTable('ref_delivery_statuses')) {
            Schema::create('ref_delivery_statuses', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('name', 50);
                $table->unique(['name'], 'ref_delivery_statuses_name');
            });
        }
        if (!Schema::hasTable('ref_payment_methods')) {
            Schema::create('ref_payment_methods', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('name', 50);
                $table->unique(['name'], 'ref_payment_methods_name');
            });
        }
        if (!Schema::hasTable('ref_transaction_types')) {
            Schema::create('ref_transaction_types', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('name', 50);
                $table->unique(['name'], 'ref_transaction_types_name');
            });
        }
        if (!Schema::hasTable('ref_user_types')) {
            Schema::create('ref_user_types', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('name', 50);
                $table->unique(['name'], 'ref_user_types_name');
            });
        }
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('subscription_no', 60);
                $table->unsignedTinyInteger('billing_cycle_id')->default('1')->comment('1 daily, 2 weekly, 3 monthly');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->unsignedTinyInteger('status')->default('1')->comment('1 active, 2 paused, 3 expired, 4 cancelled');
                $table->unsignedTinyInteger('auto_generate_deliveries')->default('1');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'subscription_no'], 'subscriptions_uq_subscription_no');
                $table->index(['customer_id'], 'subscriptions_customer_id');
                $table->index(['client_id', 'customer_id', 'status'], 'subscriptions_client_id');
            });
        }
        if (!Schema::hasTable('subscription_change_requests')) {
            Schema::create('subscription_change_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('subscription_id');
                $table->unsignedBigInteger('requested_by_user_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->decimal('old_quantity', 12,3);
                $table->decimal('requested_quantity', 12,3);
                $table->date('effective_from');
                $table->string('reason', 255)->nullable();
                $table->unsignedTinyInteger('status')->default('1')->comment('1 pending, 2 approved, 3 rejected, 4 cancelled');
                $table->unsignedBigInteger('approved_by_user_id')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['subscription_id'], 'subscription_change_requests_subscription_id');
                $table->index(['requested_by_user_id'], 'subscription_change_requests_requested_by_user_id');
                $table->index(['product_id'], 'subscription_change_requests_product_id');
                $table->index(['approved_by_user_id'], 'subscription_change_requests_approved_by_user_id');
                $table->index(['client_id', 'status', 'effective_from'], 'subscription_change_requests_client_id');
            });
        }
        if (!Schema::hasTable('subscription_items')) {
            Schema::create('subscription_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('subscription_id');
                $table->unsignedBigInteger('product_id');
                $table->decimal('default_quantity', 12,3)->default('0.000');
                $table->decimal('rate', 12,2)->default('0.00');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['subscription_id'], 'subscription_items_subscription_id');
                $table->index(['product_id'], 'subscription_items_product_id');
                $table->index(['client_id', 'subscription_id'], 'subscription_items_client_id');
            });
        }
        if (!Schema::hasTable('subscription_pauses')) {
            Schema::create('subscription_pauses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('subscription_id');
                $table->date('pause_from');
                $table->date('pause_to');
                $table->string('reason', 255)->nullable();
                $table->unsignedTinyInteger('status')->default('1');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['subscription_id'], 'subscription_pauses_subscription_id');
                $table->index(['client_id', 'subscription_id', 'pause_from', 'pause_to'], 'subscription_pauses_client_id');
            });
        }
        if (!Schema::hasTable('subscription_schedules')) {
            Schema::create('subscription_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('subscription_item_id');
                $table->unsignedTinyInteger('day_of_week')->comment('1 Monday ... 7 Sunday');
                $table->unsignedTinyInteger('delivery_slot_id')->default('1')->comment('1 morning, 2 evening, 3 both');
                $table->decimal('planned_quantity', 12,3)->default('0.000');
                $table->date('active_from');
                $table->date('active_to')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['subscription_item_id'], 'subscription_schedules_subscription_item_id');
                $table->index(['client_id', 'subscription_item_id', 'day_of_week', 'active_from'], 'subscription_schedules_client_id');
            });
        }
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedTinyInteger('user_type_id');
                $table->string('name', 150);
                $table->string('mobile', 20);
                $table->string('email', 150)->nullable();
                $table->string('password', 255);
                $table->unsignedTinyInteger('status')->default('1');
                $table->dateTime('last_login_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['client_id', 'mobile'], 'users_uq_user_mobile_client');
                $table->index(['user_type_id', 'status'], 'users_user_type_id');
            });
        }
        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->string('role_name', 100);
                $table->json('permissions')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['user_id'], 'user_roles_user_id');
                $table->index(['client_id', 'user_id'], 'user_roles_client_id');
            });
        }
        // Add missing identity fields on an already migrated Laravel starter database.
        // Unsupported/null roles remain denied; no existing account gains privileges.
        foreach (['client_id', 'user_type_id', 'mobile', 'status', 'last_login_at'] as $column) {
            if (!Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    match ($column) {
                        'client_id' => $table->unsignedBigInteger($column)->nullable(),
                        'user_type_id' => $table->unsignedTinyInteger($column)->nullable(),
                        'mobile' => $table->string($column, 20)->nullable(),
                        'status' => $table->unsignedTinyInteger($column)->default(0),
                        'last_login_at' => $table->dateTime($column)->nullable(),
                    };
                });
            }
        }
        if (Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('email', 150)->nullable()->change());
        }
    }

    private function addAuthenticationSupport(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'user_type_id')) {
            throw new RuntimeException('DairyNex business schema is required. Reconcile the supplied SQL schema before applying authentication support.');
        }
        if (!Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', fn (Blueprint $table) => $table->rememberToken());
        }
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    private function addDeliveryPinPolicies(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasTable('deliveries')) {
            throw new RuntimeException('The existing DairyNex business schema is required.');
        }
        if (!Schema::hasColumn('customers', 'delivery_pin_required')) {
            Schema::table('customers', fn (Blueprint $table) => $table->boolean('delivery_pin_required')->default(true));
        }
        if (!Schema::hasColumn('deliveries', 'pin_required')) {
            // NULL means unknown legacy policy. Do not invent verification history.
            Schema::table('deliveries', fn (Blueprint $table) => $table->boolean('pin_required')->nullable());
        }
    }

    private function addIdentityAndDeliveryControls(): void
    {
        if (!Schema::hasColumn('customers', 'user_id')) {
            Schema::table('customers', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->unique('customers_user_login_unique'));
        }
        foreach (['generation_key', 'pin_verified_by_user_id', 'completed_by_user_id'] as $column) {
            if (!Schema::hasColumn('deliveries', $column)) {
                Schema::table('deliveries', function (Blueprint $table) use ($column) {
                    if ($column === 'generation_key') {
                        $table->string($column, 100)->nullable()->unique('deliveries_generation_key_unique');
                    } else {
                        $table->unsignedBigInteger($column)->nullable();
                    }
                });
            }
        }
    }

    private function protectLedgerReferences(): void
    {
        if (Schema::hasIndex('customer_ledger', 'ledger_reference_unique')) { return; }
        $duplicates = DB::table('customer_ledger')->whereNotNull('reference_type')->whereNotNull('reference_id')
            ->select('client_id')->groupBy('client_id', 'reference_type', 'reference_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) { throw new RuntimeException('Duplicate ledger references require manual reconciliation; no records have been removed.'); }
        Schema::table('customer_ledger', fn (Blueprint $table) => $table->unique(['client_id', 'reference_type', 'reference_id'], 'ledger_reference_unique'));
    }
};

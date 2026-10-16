<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Runs after the existing consolidated business migration. Additive and repeatable.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false);
                $table->timestamp('temporary_password_expires_at')->nullable();
            });
        }
        if (!Schema::hasColumn('client_subscriptions', 'monthly_price')) {
            Schema::table('client_subscriptions', fn (Blueprint $table) => $table->decimal('monthly_price', 12, 2)->nullable());
        }
        DB::table('client_subscriptions')->whereNull('monthly_price')->update([
            'monthly_price' => DB::raw('(SELECT monthly_price FROM plans WHERE plans.id = client_subscriptions.plan_id)'),
        ]);
        if (!Schema::hasTable('saas_invoices')) {
            Schema::create('saas_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('client_subscription_id')->unique();
                $table->string('plan_name', 100);
                $table->date('period_from');
                $table->date('period_to');
                $table->date('due_date')->index();
                $table->decimal('amount', 12, 2);
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_reference', 150)->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('subject', 200);
                $table->text('description');
                $table->string('priority', 10)->default('medium');
                $table->string('status', 15)->default('open')->index();
                $table->text('resolution')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep billing, account security, settings and support history.
    }
};

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseBaselineTest extends TestCase
{
    public function test_all_migrations_support_a_fresh_database_and_idempotent_reference_seeding(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertSame(0, Artisan::call('db:seed', ['--force' => true]));
        $this->assertSame(0, Artisan::call('db:seed', ['--force' => true]));

        foreach ([
            'customers',
            'deliveries',
            'delivery_items',
            'product_rates',
            'customer_ledger',
            'payments',
            'invoices',
            'inventory_transactions',
            'sessions',
            'jobs',
            'cache',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('ref_user_types', 4);
        $this->assertTrue(Schema::hasColumn('customers', 'user_id'));
        $this->assertDatabaseHas('migrations', ['migration' => 'daatabase_migrations']);
    }

    public function test_migrations_adopt_existing_schema_without_deleting_business_records(): void
    {
        DB::unprepared(file_get_contents(base_path('tests/Fixtures/dairynex-schema.sqlite.sql')));
        DB::table('clients')->insert(['id' => 91, 'name' => 'Existing Dairy']);
        DB::table('users')
            ->insert([
                'id' => 41,
                'client_id' => 91,
                'user_type_id' => 3,
                'name' => 'Legacy',
                'mobile' => '12345678',
                'password' => '',
            ]);

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertDatabaseHas('clients', ['id' => 91, 'name' => 'Existing Dairy']);
        $this->assertDatabaseHas('users', ['id' => 41, 'user_type_id' => 3, 'password' => '']);
    }

    public function test_consolidated_migration_preserves_databases_with_the_old_migration_history(): void
    {
        (require database_path('migrations/daatabase_migrations.php'))->up();
        DB::table('clients')->insert(['id' => 91, 'name' => 'Existing Dairy']);
        DB::table('customers')
            ->insert([
                'id' => 71,
                'client_id' => 91,
                'name' => 'Existing Customer',
                'delivery_pin_required' => false,
            ]);

        $this->assertSame(0, Artisan::call('migrate:install'));
        $oldNames = [
            '2026_10_15_000000_create_dairynex_business_schema',
            '2026_10_16_000001_add_authentication_support',
            '2026_10_16_000002_add_delivery_pin_policies',
            '2026_10_16_000003_add_customer_identity_and_delivery_controls',
            '2026_10_16_000004_protect_ledger_references',
        ];

        foreach ($oldNames as $name) {
            DB::table('migrations')->insert(['migration' => $name, 'batch' => 1]);
        }

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertDatabaseHas('customers', [
            'id' => 71,
            'client_id' => 91,
            'name' => 'Existing Customer',
            'delivery_pin_required' => false,
        ]);

        $this->assertSame(5, DB::table('migrations')->whereIn('migration', $oldNames)->count());
        $this->assertSame(1, DB::table('migrations')->where('migration', 'daatabase_migrations')->count());
        $this->assertTrue(Schema::hasIndex('customer_ledger', 'ledger_reference_unique'));
    }
}

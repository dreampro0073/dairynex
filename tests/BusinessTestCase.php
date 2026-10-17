<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;

abstract class BusinessTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // phpunit.xml forces a dedicated, in-memory SQLite connection.
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        DB::unprepared(file_get_contents(base_path('tests/Fixtures/dairynex-schema.sqlite.sql')));
        (require database_path('migrations/daatabase_migrations.php'))->up();
        (require database_path('migrations/daatabase_super_admin.php'))->up();
        (require database_path('migrations/2026_10_17_000003_create_personal_access_tokens_table.php'))->up();
        $this->seed(ReferenceDataSeeder::class);
        DB::table('clients')->insert([
            ['id' => 1, 'name' => 'Dairy One', 'status' => 1],
            ['id' => 2, 'name' => 'Dairy Two', 'status' => 1],
        ]);
    }

    protected function account(Role|int $role = Role::ClientAdmin, ?int $clientId = 1, array $attributes = []): User
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Account', 'mobile' => (string) random_int(7000000000, 9999999999),
            'client_id' => $clientId, 'user_type_id' => $role instanceof Role ? $role->value : $role,
            'status' => 1, 'password' => 'test-password-123', ...$attributes,
        ])->save();

        return $user;
    }
}

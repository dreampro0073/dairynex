<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProvisionUser extends Command
{
    protected $signature = 'dairynex:provision {user : Existing user ID} {--role= : Explicit supported role name}';

    protected $description = 'Explicitly provision credentials for an existing account; preserves its ID and relationships';

    public function handle(): int
    {
        $user = User::findOrFail($this->argument('user'));
        $role = collect(Role::cases())->first(fn ($role) => $role->label() === $this->option('role'));

        if (! $role || ($role !== Role::SuperAdmin && (! $user->client_id || (int) $user->client?->status !== 1))) {
            $this->error('Specify a supported --role; tenant accounts must belong to an active dairy.');

            return self::FAILURE;
        }

        $password = $this->secret('New password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $role, $password) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $oldRole = $locked->user_type_id;
            $locked->forceFill([
                'user_type_id' => $role->value,
                'status' => 1,
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('audit_logs')->insert([
                'client_id' => $locked->client_id,
                'user_id' => null,
                'action' => 'account_provisioned_via_cli',
                'table_name' => 'users',
                'record_id' => $locked->id,
                'old_values' => json_encode(['user_type_id' => $oldRole]),
                'new_values' => json_encode(['user_type_id' => $role->value, 'status' => 1]),
                'created_at' => now(),
            ]);
        });

        $this->info('Account provisioned. No business relationships or role reference IDs were removed.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'dairynex:create-admin {--client= : Existing active dairy ID; omit for a platform Super Admin}';
    protected $description = 'Create an administrator through an authorized operator, using private credential prompts';

    public function handle(): int
    {
        $client = $this->option('client');
        if ($client !== null && (!ctype_digit((string) $client) || !Client::where('id', $client)->where('status', 1)->exists())) {
            $this->error('Select an existing active dairy.'); return self::FAILURE;
        }
        $input = ['name' => $this->ask('Name'), 'mobile' => $this->ask('Mobile'), 'email' => $this->ask('Email (optional)'),
            'password' => $this->secret('Password (at least 12 characters)'), 'password_confirmation' => $this->secret('Confirm password')];
        $validation = Validator::make($input, ['name' => ['required', 'string', 'max:150'], 'mobile' => ['required', 'digits_between:7,20'],
            'email' => ['nullable', 'email', 'max:150'], 'password' => ['required', 'confirmed', Password::min(12)]]);
        if ($validation->fails()) { $this->error($validation->errors()->first()); return self::FAILURE; }
        $existing = User::where('client_id', $client)->where('mobile', $input['mobile'])->exists();
        if ($existing) { $this->error('An account already uses this mobile in the selected dairy context. Use explicit provisioning.'); return self::FAILURE; }
        DB::transaction(function () use ($client, $input) {
            if ($client !== null) { Client::lockForUpdate()->findOrFail($client); }
            $user = new User;
            $user->forceFill(['client_id' => $client, 'user_type_id' => $client === null ? Role::SuperAdmin->value : Role::ClientAdmin->value,
                'name' => $input['name'], 'mobile' => $input['mobile'], 'email' => $input['email'] ?: null, 'password' => $input['password'], 'status' => 1])->save();
            DB::table('audit_logs')->insert(['client_id' => $client, 'user_id' => null, 'action' => 'administrator_created_via_cli', 'table_name' => 'users', 'record_id' => $user->id, 'created_at' => now()]);
        });
        $this->info('Administrator created. Credentials were not written to output or audit records.');
        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $clientId = Tenant::id();
        abort_unless($customer->client_id === $clientId, 403);
        $data = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($q) => $q->where('client_id', $clientId)
                        ->where('user_type_id', Role::Customer->value)
                        ->where('status', 1)),
            ],
            'password' => ['required_without:user_id', 'nullable', 'confirmed', Password::min(12)],
        ]);

        DB::transaction(function () use ($customer, $clientId, $data) {
            Client::lockForUpdate()->findOrFail($clientId);
            $customer = Customer::lockForUpdate()->findOrFail($customer->id);

            if ($customer->user_id) {
                throw ValidationException::withMessages(['user_id' => 'This customer already has a linked login.']);
            }

            if (! empty($data['user_id'])) {
                $user = User::where('client_id', $clientId)->lockForUpdate()->findOrFail($data['user_id']);
            } else {
                if (
                    ! $customer->mobile
                    || User::where('client_id', $clientId)
                        ->where('mobile', $customer->mobile)
                        ->exists()
                ) {
                    throw ValidationException::withMessages(['user_id' => 'Set a unique customer mobile or select an existing customer login.']);
                }

                $user = new User;
                $user->forceFill([
                    'client_id' => $clientId,
                    'user_type_id' => Role::Customer->value,
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'email' => $customer->email,
                    'status' => 1,
                    'password' => $data['password'],
                ])
                    ->save();
            }

            if (Customer::where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'This login is already linked to a customer.']);
            }

            $customer->forceFill(['user_id' => $user->id])->save();
            DB::table('audit_logs')
                ->insert([
                    'client_id' => $clientId,
                    'user_id' => auth()->id(),
                    'action' => 'customer_login_linked',
                    'table_name' => 'customers',
                    'record_id' => $customer->id,
                    'new_values' => json_encode(['user_id' => $user->id]),
                    'created_at' => now(),
                ]);
        });

        return back()->with('success', 'Customer login linked.');
    }
}

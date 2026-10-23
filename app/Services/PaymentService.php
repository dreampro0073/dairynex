<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function record(User $actor, array $data): int
    {
        abort_unless($actor->canSignIn() && in_array($actor->role(), [Role::ClientAdmin, Role::DeliveryBoy]), 403);

        return DB::transaction(function () use ($actor, $data) {
            Client::lockForUpdate()->findOrFail($actor->client_id);
            $customer = Customer::where('client_id', $actor->client_id)
                ->lockForUpdate()
                ->findOrFail($data['customer_id']);

            if ($actor->role() === Role::DeliveryBoy) {
                abort_unless(DB::table('deliveries')
                    ->where('client_id', $actor->client_id)
                    ->where('customer_id', $customer->id)
                    ->where('delivery_boy_id', $actor->id)
                    ->whereDate('delivery_date', today())
                    ->exists(), 403);
            }

            $amount = Decimal::scaled($data['amount'], 2);

            if (
                $amount <= 0
                || ! DB::table('ref_payment_methods')
                    ->where('id', $data['payment_method_id'])
                    ->exists()
            ) {
                throw ValidationException::withMessages(['amount' => 'Enter a positive amount and a valid payment method.']);
            }

            $number = 'PAY-'.$data['key'];
            $existing = DB::table('payments')
                ->where('client_id', $actor->client_id)
                ->where('payment_no', $number)
                ->first();

            if ($existing) {
                if (
                    (int) $existing->customer_id !== $customer->id
                    || Decimal::scaled($existing->amount, 2) !== $amount
                    || (int) $existing->payment_method_id !== (int) $data['payment_method_id']
                    || (string) $existing->transaction_reference !== (string) ($data['transaction_reference'] ?? '')
                ) {
                    throw ValidationException::withMessages(['key' => 'This payment key has already been used for a different payment.']);
                }

                return $existing->id;
            }

            if (
                ! empty($data['transaction_reference'])
                && DB::table('payments')
                    ->where('client_id', $actor->client_id)
                    ->where('transaction_reference', $data['transaction_reference'])
                    ->where('status', 1)
                    ->exists()
            ) {
                throw ValidationException::withMessages(['transaction_reference' => 'This transaction reference is already recorded.']);
            }

            $id = DB::table('payments')
                ->insertGetId([
                    'client_id' => $actor->client_id,
                    'customer_id' => $customer->id,
                    'payment_no' => $number,
                    'amount' => Decimal::format($amount),
                    'payment_method_id' => $data['payment_method_id'],
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'payment_date' => now(),
                    'collected_by_user_id' => $actor->id,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            app(LedgerService::class)->post($customer, 'payment', $id, 0, $amount, $actor->id);
            DB::table('audit_logs')
                ->insert([
                    'client_id' => $actor->client_id,
                    'user_id' => $actor->id,
                    'action' => 'payment_recorded',
                    'table_name' => 'payments',
                    'record_id' => $id,
                    'new_values' => json_encode(['amount' => Decimal::format($amount), 'customer_id' => $customer->id]),
                    'created_at' => now(),
                ]);

            if ($customer->user_id) {
                DB::table('notifications')
                    ->insert([
                        'client_id' => $actor->client_id,
                        'user_id' => $customer->user_id,
                        'customer_id' => $customer->id,
                        'title' => 'Payment received',
                        'message' => 'Payment of ₹'.Decimal::format($amount).' has been recorded.',
                        'created_at' => now(),
                    ]);
            }

            return $id;
        }, 3);
    }
}

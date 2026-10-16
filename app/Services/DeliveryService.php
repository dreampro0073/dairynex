<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function issuePin(User $actor, int $id): string
    {
        return DB::transaction(function () use ($actor, $id) {
            abort_unless($actor->canSignIn() && $actor->role() === Role::Customer, 403);
            $delivery = Delivery::where('client_id', $actor->client_id)->lockForUpdate()->findOrFail($id);
            $customer = Customer::where('client_id', $actor->client_id)->where('user_id', $actor->id)->find($delivery->customer_id);
            abort_unless($customer && $customer->status === 1, 403);
            if ($delivery->pin_required !== true || !in_array($delivery->status_id, [1, 2])) {
                throw ValidationException::withMessages(['delivery' => 'This delivery cannot issue a PIN.']);
            }
            $pin = (string) random_int(100000, 999999);
            $delivery->forceFill(['delivery_pin_hash' => Hash::make($pin), 'pin_attempts' => 0])->save();
            // PIN exists only in the authorized no-store response, never a database
            // column, notification, session flash, URL, application log or audit data.
            return $pin;
        });
    }

    public function complete(User $actor, int $id, array $quantities, ?string $pin, bool $skip = false): Delivery
    {
        $result = DB::transaction(function () use ($actor, $id, $quantities, $pin, $skip) {
            abort_unless($actor->canSignIn() && $actor->role() === Role::DeliveryBoy, 403);
            $delivery = Delivery::where('client_id', $actor->client_id)->lockForUpdate()->findOrFail($id);
            abort_unless((int) $delivery->delivery_boy_id === $actor->id, 403);
            if (!in_array($delivery->status_id, [DeliveryStatus::Pending->value, DeliveryStatus::PinVerified->value])) {
                throw ValidationException::withMessages(['delivery' => 'This delivery has already been finalized or cannot be completed.']);
            }
            if ($delivery->delivery_date->isFuture()) {
                throw ValidationException::withMessages(['delivery' => 'Future deliveries cannot be completed.']);
            }
            $customer = Customer::where('client_id', $actor->client_id)->lockForUpdate()->findOrFail($delivery->customer_id);
            abort_unless($customer->status === 1, 403);
            $items = DB::table('delivery_items')->where('client_id', $actor->client_id)->where('delivery_id', $id)->orderBy('id')->get();
            if ($items->isEmpty() || (!$skip && (count($quantities) !== $items->count() || count(array_unique(array_column($quantities, 'id'))) !== $items->count()))) {
                throw ValidationException::withMessages(['items' => 'Provide each delivery item exactly once.']);
            }
            $input = collect($quantities)->keyBy('id');
            $changes = []; $total = 0; $hasQuantity = false;
            foreach ($items as $item) {
                if (!$skip && !$input->has($item->id)) {
                    throw ValidationException::withMessages(['items' => 'The supplied items do not belong to this delivery.']);
                }
                $qty = $skip ? 0 : Decimal::scaled($input[$item->id]['quantity'], 3, 'items');
                $hasQuantity = $hasQuantity || $qty > 0;
                $rate = Decimal::scaled($item->rate, 2);
                $amount = Decimal::charge($qty, $rate);
                $total += $amount;
                $planned = Decimal::scaled($item->planned_quantity, 3);
                $changes[$item->id] = ['actual_quantity' => Decimal::format($qty, 3), 'amount' => Decimal::format($amount),
                    'quantity_change_type' => $qty === 0 ? 4 : ($qty > $planned ? 2 : ($qty < $planned ? 3 : 1)), 'updated_at' => now()];
            }
            if (!$skip && !$hasQuantity) {
                throw ValidationException::withMessages(['items' => 'Use Skip delivery when nothing was delivered.']);
            }
            $limit = Decimal::scaled($customer->credit_limit ?? 0, 2);
            if (!$skip && $limit > 0 && app(LedgerService::class)->balance($customer) + $total > $limit) {
                throw ValidationException::withMessages(['delivery' => 'The customer credit limit would be exceeded. Contact the dairy administrator.']);
            }
            if (!$skip) {
                if ($delivery->pin_required === null) {
                    throw ValidationException::withMessages(['pin' => 'The legacy verification policy is unknown. Administrator review is required.']);
                }
                if ($delivery->pin_required) {
                    if ($delivery->pin_attempts >= 5) {
                        return ['error' => 'PIN attempts exhausted. Ask the customer to issue a new PIN.'];
                    }
                    $valid = $pin && preg_match('/^\d{6}$/D', $pin) && $delivery->delivery_pin_hash && Hash::check($pin, $delivery->delivery_pin_hash);
                    $delivery->pin_attempts++;
                    DB::table('delivery_pin_attempts')->insert([
                        'client_id' => $actor->client_id, 'delivery_id' => $id, 'attempted_by_user_id' => $actor->id,
                        'entered_pin_hash' => null, 'success' => (bool) $valid, 'attempted_at' => now(), 'ip_address' => request()->ip(),
                    ]);
                    if (!$valid) {
                        $delivery->save();
                        // Return instead of throwing in the transaction so failed
                        // attempts persist and cannot be erased by a rollback.
                        return ['error' => 'A valid delivery PIN is required.'];
                    }
                    $delivery->delivery_pin_verified_at = now();
                    $delivery->pin_verified_by_user_id = $actor->id;
                }
            }
            foreach ($changes as $itemId => $change) {
                DB::table('delivery_items')->where('id', $itemId)->where('client_id', $actor->client_id)->update($change);
            }
            $delivery->forceFill([
                'status_id' => $skip ? DeliveryStatus::Skipped->value : DeliveryStatus::Delivered->value,
                'total_amount' => Decimal::format($total), 'delivered_at' => $skip ? null : now(),
                'completed_by_user_id' => $actor->id, 'delivery_pin_hash' => null,
            ])->save();
            if (!$skip && $total > 0) {
                app(LedgerService::class)->post($customer, 'delivery', $id, $total, 0, $actor->id);
            }
            DB::table('audit_logs')->insert(['client_id' => $actor->client_id, 'user_id' => $actor->id,
                'action' => $skip ? 'delivery_skipped' : 'delivery_completed', 'table_name' => 'deliveries', 'record_id' => $id,
                'new_values' => json_encode(['actual_total' => $delivery->total_amount, 'pin_required' => $delivery->pin_required, 'pin_verified' => (bool) $delivery->delivery_pin_verified_at]), 'created_at' => now()]);
            return $delivery;
        }, 3);
        if (is_array($result)) {
            throw ValidationException::withMessages(['pin' => $result['error']]);
        }
        return $result;
    }
}

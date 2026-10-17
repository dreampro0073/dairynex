<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    // Call while holding the customer's row lock, inside the surrounding transaction.
    // opening_balance is included exactly once, not posted a second time as a debit.
    public function balance(Customer $customer): int
    {
        $totals = DB::table('customer_ledger')->where('client_id', $customer->client_id)
            ->where('customer_id', $customer->id)->selectRaw('COALESCE(SUM(debit),0) as debit, COALESCE(SUM(credit),0) as credit')->first();
        return Decimal::scaled($customer->opening_balance ?? 0, 2)
            + Decimal::scaled($totals->debit, 2) - Decimal::scaled($totals->credit, 2);
    }

    public function post(Customer $customer, string $reference, int $referenceId, int $debit, int $credit, int $actor): void
    {
        $query = DB::table('customer_ledger')->where('client_id', $customer->client_id)
            ->where('reference_type', $reference)->where('reference_id', $referenceId);
        if ($query->exists()) {
            return;
        }
        $balance = $this->balance($customer) + $debit - $credit;
        DB::table('customer_ledger')->insert([
            'client_id' => $customer->client_id, 'customer_id' => $customer->id,
            'transaction_type_id' => $reference === 'delivery' ? 3 : ($reference === 'walk_in_sale' ? 2 : 8),
            'reference_type' => $reference, 'reference_id' => $referenceId,
            'transaction_date' => now(), 'debit' => Decimal::format($debit), 'credit' => Decimal::format($credit),
            'balance' => Decimal::format($balance), 'description' => ucfirst($reference).' #'.$referenceId,
            'created_by_user_id' => $actor, 'created_at' => now(),
        ]);
    }
}

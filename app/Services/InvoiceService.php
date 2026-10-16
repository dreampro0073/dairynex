<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function issue(int $client, int $customerId, string $month, int $actor): int
    {
        $from = CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
        $to = $from->endOfMonth();
        if ($to >= today()) { throw ValidationException::withMessages(['month' => 'Issue invoices only for completed calendar months.']); }
        return DB::transaction(function () use ($client, $customerId, $from, $to, $actor) {
            Client::lockForUpdate()->findOrFail($client);
            $customer = Customer::where('client_id', $client)->lockForUpdate()->findOrFail($customerId);
            $number = 'INV-'.$customerId.'-'.$from->format('Ym');
            $existing = DB::table('invoices')->where('client_id', $client)->where('invoice_no', $number)->first();
            if ($existing) { return $existing->id; }
            $deliveries = DB::table('deliveries')->where('client_id', $client)->where('customer_id', $customerId)
                ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString()]);
            if ((clone $deliveries)->whereIn('status_id', [1, 2])->exists()) {
                throw ValidationException::withMessages(['month' => 'Finalize pending deliveries before issuing this monthly invoice.']);
            }
            $billable = (clone $deliveries)->whereIn('status_id', [3, 8])->whereNotIn('id', DB::table('invoice_items')->where('client_id', $client)->whereNotNull('delivery_id')->select('delivery_id'))->pluck('id');
            $lines = DB::table('delivery_items')->where('client_id', $client)->whereIn('delivery_id', $billable)->where('actual_quantity', '>', 0)->orderBy('id')->get();
            if ($lines->isEmpty()) { throw ValidationException::withMessages(['month' => 'There are no unbilled delivered items for this month.']); }
            $total = 0;
            foreach ($lines as $line) { $total += Decimal::scaled($line->amount, 2); }
            $id = DB::table('invoices')->insertGetId(['client_id' => $client, 'customer_id' => $customerId, 'invoice_no' => $number, 'invoice_date' => today(),
                'period_from' => $from->toDateString(), 'period_to' => $to->toDateString(), 'subtotal' => Decimal::format($total), 'total' => Decimal::format($total), 'due_amount' => Decimal::format($total), 'status' => 2,
                'created_at' => now(), 'updated_at' => now()]);
            foreach ($lines as $line) {
                DB::table('invoice_items')->insert(['client_id' => $client, 'invoice_id' => $id, 'delivery_id' => $line->delivery_id, 'product_id' => $line->product_id,
                    'quantity' => $line->actual_quantity, 'rate' => $line->rate, 'amount' => $line->amount, 'created_at' => now()]);
            }
            // Delivery completion already posted the debit. Invoice issuance is a
            // billing document, not a second charge in the customer's ledger.
            DB::table('audit_logs')->insert(['client_id' => $client, 'user_id' => $actor, 'action' => 'invoice_issued', 'table_name' => 'invoices', 'record_id' => $id,
                'new_values' => json_encode(['total' => Decimal::format($total)]), 'created_at' => now()]);
            if ($customer->user_id) {
                DB::table('notifications')->insert(['client_id' => $client, 'user_id' => $customer->user_id, 'customer_id' => $customerId,
                    'title' => 'Invoice issued', 'message' => $number.' for ₹'.Decimal::format($total).' is available.', 'created_at' => now()]);
            }
            return $id;
        }, 3);
    }

    public function allocate(int $client, int $invoiceId, int $paymentId, int $amount, int $actor): void
    {
        DB::transaction(function () use ($client, $invoiceId, $paymentId, $amount, $actor) {
            Client::lockForUpdate()->findOrFail($client);
            $invoice = DB::table('invoices')->where('client_id', $client)->find($invoiceId);
            $payment = DB::table('payments')->where('client_id', $client)->where('status', 1)->find($paymentId);
            abort_unless($invoice && $payment, 404);
            abort_unless((int) $invoice->customer_id === (int) $payment->customer_id, 403);
            Customer::where('client_id', $client)->lockForUpdate()->findOrFail($invoice->customer_id);
            $previous = DB::table('invoice_payments')->where('client_id', $client)->where('invoice_id', $invoiceId)->where('payment_id', $paymentId)->first();
            if ($previous) {
                if (Decimal::scaled($previous->amount, 2) !== $amount) { throw ValidationException::withMessages(['amount' => 'This invoice/payment allocation is already recorded.']); }
                return;
            }
            $allocated = 0;
            foreach (DB::table('invoice_payments')->where('client_id', $client)->where('payment_id', $paymentId)->pluck('amount') as $value) { $allocated += Decimal::scaled($value, 2); }
            $due = Decimal::scaled($invoice->due_amount, 2);
            if (!in_array((int) $invoice->status, [2, 3]) || $amount <= 0 || $amount > $due || $amount > Decimal::scaled($payment->amount, 2) - $allocated) {
                throw ValidationException::withMessages(['amount' => 'Allocation exceeds the invoice due or the unallocated payment amount.']);
            }
            DB::table('invoice_payments')->insert(['client_id' => $client, 'invoice_id' => $invoiceId, 'payment_id' => $paymentId, 'amount' => Decimal::format($amount), 'created_at' => now()]);
            DB::table('invoices')->where('id', $invoiceId)->update(['paid_amount' => Decimal::format(Decimal::scaled($invoice->paid_amount, 2) + $amount),
                'due_amount' => Decimal::format($due - $amount), 'status' => $due === $amount ? 4 : 3, 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['client_id' => $client, 'user_id' => $actor, 'action' => 'invoice_payment_allocated', 'table_name' => 'invoices', 'record_id' => $invoiceId,
                'new_values' => json_encode(['payment_id' => $paymentId, 'amount' => Decimal::format($amount)]), 'created_at' => now()]);
        }, 3);
    }
}

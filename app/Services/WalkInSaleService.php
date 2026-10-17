<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalkInSaleService
{
    public function create(User $actor, array $data): object
    {
        abort_unless($actor->canSignIn() && $actor->role() === Role::ClientAdmin, 403);

        return DB::transaction(function () use ($actor, $data) {
            DB::table('clients')->where('id', $actor->client_id)->lockForUpdate()->firstOrFail();
            $saved=DB::table('walk_in_sales')->where('client_id',$actor->client_id)->where('idempotency_key',$data['key'])->first();
            if($saved) return $saved;
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('client_id', $actor->client_id)->lockForUpdate()->findOrFail($data['customer_id']);
            } elseif ((int) ($data['payment_method_id'] ?? 0) === 0) {
                throw ValidationException::withMessages(['customer_id' => 'Select an identified customer for a credit sale.']);
            }

            $lines = [];
            $subtotal = 0;
            foreach ($data['items'] as $line) {
                $product = Product::where('client_id', $actor->client_id)->where('status', 1)->lockForUpdate()->findOrFail($line['product_id']);
                $quantity = Decimal::scaled($line['quantity'], 3, 'items');
                if ($quantity <= 0) throw ValidationException::withMessages(['items' => 'Every item quantity must be greater than zero.']);
                $rate = Decimal::scaled(DB::table('product_rates')->where('client_id', $actor->client_id)->where('product_id', $product->id)
                    ->whereDate('effective_from', '<=', today())->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()))
                    ->orderByDesc('effective_from')->orderByDesc('id')->value('rate') ?? $product->base_rate, 2);
                $amount = Decimal::charge($quantity, $rate);
                $subtotal += $amount;
                $lines[] = ['product' => $product, 'quantity' => $quantity, 'rate' => $rate, 'amount' => $amount];
            }
            if (!$lines) throw ValidationException::withMessages(['items' => 'Add at least one product.']);
            $method = (int) ($data['payment_method_id'] ?? 0);
            if (!in_array($method, [0, 1, 2], true) || ($method > 0 && !DB::table('ref_payment_methods')->where('id', $method)->exists())) {
                throw ValidationException::withMessages(['payment_method_id' => 'Choose a supported payment method.']);
            }
            $paid = in_array($method, [1, 2], true) ? $subtotal : 0;
            $receipt = 'WS-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
            $saleId = DB::table('walk_in_sales')->insertGetId([
                'client_id' => $actor->client_id, 'customer_id' => $customer?->id, 'receipt_no' => $receipt,
                'customer_name' => $customer?->name ?? (($data['customer_name'] ?? '') ?: null), 'customer_mobile' => $customer?->mobile ?? (($data['customer_mobile'] ?? '') ?: null),
                'sold_at' => now(), 'subtotal' => Decimal::format($subtotal), 'discount' => '0.00', 'total' => Decimal::format($subtotal),
                'payment_method_id' => $method ?: null, 'paid_amount' => Decimal::format($paid), 'due_amount' => Decimal::format($subtotal - $paid),
                'status' => 1, 'idempotency_key' => $data['key'], 'created_by_user_id' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($lines as $line) DB::table('walk_in_sale_items')->insert([
                'client_id' => $actor->client_id, 'sale_id' => $saleId, 'product_id' => $line['product']->id,
                'product_name' => $line['product']->name, 'unit_type' => $line['product']->unit_type,
                'quantity' => Decimal::format($line['quantity'], 3), 'rate' => Decimal::format($line['rate']),
                'amount' => Decimal::format($line['amount']), 'created_at' => now(),
            ]);
            if ($customer) {
                app(LedgerService::class)->post($customer, 'walk_in_sale', $saleId, $subtotal, 0, $actor->id);
                if ($paid > 0) app(PaymentService::class)->record($actor, ['customer_id'=>$customer->id,'amount'=>Decimal::format($paid),
                    'payment_method_id'=>$method,'key'=>$data['key'],'notes'=>'Walk-in sale receipt #'.$receipt]);
            }
            DB::table('audit_logs')->insert(['client_id' => $actor->client_id, 'user_id' => $actor->id, 'action' => 'walk_in_sale_created', 'table_name' => 'walk_in_sales', 'record_id' => $saleId,
                'new_values' => json_encode(['receipt_no' => $receipt, 'total' => Decimal::format($subtotal), 'payment_method_id' => $method ?: null]), 'created_at' => now()]);
            return DB::table('walk_in_sales')->where('id', $saleId)->first();
        }, 3);
    }
}

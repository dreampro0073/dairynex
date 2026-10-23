<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            'ref_user_types' => [1 => 'super_admin', 2 => 'client_admin', 5 => 'delivery_boy', 6 => 'customer'],
            'ref_delivery_statuses' => [
                1 => 'pending',
                2 => 'pin_verified',
                3 => 'delivered',
                4 => 'skipped',
                5 => 'cancelled',
                6 => 'failed',
                7 => 'customer_unavailable',
                8 => 'partial',
            ],
            'ref_payment_methods' => [
                1 => 'cash',
                2 => 'upi',
                3 => 'bank_transfer',
                4 => 'card',
                5 => 'online',
                6 => 'cheque',
                7 => 'wallet',
            ],
            'ref_transaction_types' => [
                1 => 'purchase',
                2 => 'sale',
                3 => 'delivery',
                4 => 'return',
                5 => 'wastage',
                6 => 'adjustment',
                7 => 'transfer',
                8 => 'payment',
                9 => 'refund',
                10 => 'opening_balance',
                11 => 'credit_note',
                12 => 'debit_note',
            ],
        ];

        foreach ($tables as $table => $values) {
            foreach ($values as $id => $name) {
                $existing = DB::table($table)->where('id', $id)->first();

                if ($existing && $existing->name !== $name) {
                    throw new \RuntimeException("Reference ID conflict in {$table}; reconcile without renumbering records.");
                }

                if (! $existing) {
                    DB::table($table)->insert(['id' => $id, 'name' => $name]);
                }
            }
        }
    }
}

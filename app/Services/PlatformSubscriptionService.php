<?php

namespace App\Services;

use App\Models\{Client, ClientSubscription, Plan};
use App\Support\PlatformAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformSubscriptionService
{
    public function change(Client $client, int $planId, string $action, int $months): ClientSubscription
    {
        return DB::transaction(function () use ($client, $planId, $action, $months) {
            Client::lockForUpdate()->findOrFail($client->id);
            $plan = Plan::lockForUpdate()->findOrFail($planId);
            if ($plan->status !== 1) {
                throw ValidationException::withMessages(['plan_id' => 'Select an active plan.']);
            }
            $current = ClientSubscription::where('client_id', $client->id)->current()->lockForUpdate()->orderByDesc('id')->first();
            $renewing = $current;
            if ($action === 'renew' && !$renewing) {
                $renewing = ClientSubscription::where('client_id', $client->id)->where('status', 1)
                    ->whereDate('end_date', '<', today())->orderByDesc('end_date')->lockForUpdate()->first();
            }
            $scheduled = ClientSubscription::where('client_id', $client->id)->where('status', 1)->whereDate('start_date', '>', today())->exists();
            if ($scheduled) {
                throw ValidationException::withMessages(['plan_id' => 'A renewal is already scheduled. Expire it before changing the subscription.']);
            }
            if ($action === 'assign' && $current) {
                throw ValidationException::withMessages(['action' => 'This dairy already has a current plan. Choose renew, upgrade or downgrade.']);
            }
            if ((in_array($action, ['upgrade', 'downgrade']) && !$current) || ($action === 'renew' && !$renewing)) {
                throw ValidationException::withMessages(['action' => 'Assign a plan first; the previous subscription may have expired.']);
            }
            $oldPrice = $current ? (float) ($current->monthly_price ?? $current->plan?->monthly_price ?? 0) : 0;
            if (($action === 'upgrade' && (float) $plan->monthly_price <= $oldPrice) || ($action === 'downgrade' && (float) $plan->monthly_price >= $oldPrice)) {
                throw ValidationException::withMessages(['plan_id' => 'Choose a '.($action === 'upgrade' ? 'higher' : 'lower').' priced plan.']);
            }
            if ($action === 'renew' && ($renewing->plan_id != $planId || !$renewing->end_date)) {
                throw ValidationException::withMessages(['plan_id' => 'Renew the current plan with a finite end date.']);
            }
            $customers = DB::table('customers')->where('client_id', $client->id)->where('status', 1)->count();
            $users = DB::table('users')->where('client_id', $client->id)->where('status', 1)->count();
            if (($plan->max_customers !== null && $customers > $plan->max_customers) || ($plan->max_users !== null && $users > $plan->max_users)) {
                throw ValidationException::withMessages(['plan_id' => 'Current customer or user usage exceeds this plan’s limits.']);
            }
            $start = $action === 'renew' ? $renewing->end_date->copy()->addDay()->max(today()) : today();
            if ($current && in_array($action, ['upgrade', 'downgrade'])) {
                ClientSubscription::where('client_id', $client->id)->current()->update(['status' => 2, 'updated_at' => now()]);
            }
            $end = $start->copy()->addMonthsNoOverflow($months)->subDay();
            $subscription = ClientSubscription::create([
                'client_id' => $client->id, 'plan_id' => $planId, 'start_date' => $start, 'end_date' => $end,
                'status' => 1, 'monthly_price' => $plan->monthly_price,
            ]);
            $amount = (int) round((float) $plan->monthly_price * 100) * $months;
            DB::table('saas_invoices')->insert([
                'client_id' => $client->id, 'client_subscription_id' => $subscription->id,
                'plan_name' => $plan->name, 'period_from' => $start->toDateString(), 'period_to' => $end->toDateString(),
                'due_date' => $start->toDateString(), 'amount' => number_format($amount / 100, 2, '.', ''),
                'paid_at' => $amount === 0 ? now() : null, 'payment_reference' => $amount === 0 ? 'NO_CHARGE' : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            PlatformAudit::record('saas_subscription_'.$action, 'client_subscriptions', $subscription->id, $client->id, ['plan_id' => $planId, 'months' => $months]);
            return $subscription;
        });
    }
}

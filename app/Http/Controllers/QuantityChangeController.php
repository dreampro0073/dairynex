<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Subscription;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QuantityChangeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::Customer]), 403);
        $query = DB::table('subscription_change_requests as changes')->join('subscriptions', 'subscriptions.id', '=', 'changes.subscription_id')
            ->where('changes.client_id', $user->client_id)->where('subscriptions.client_id', $user->client_id);
        if ($user->role() === Role::Customer) {
            $query->whereIn('subscriptions.customer_id', Customer::where('client_id', $user->client_id)->where('user_id', $user->id)->select('id'));
        }
        return Inertia::render('Subscriptions/Changes', ['changes' => $query->select('changes.*', 'subscriptions.subscription_no')->orderByDesc('changes.id')->paginate(20)]);
    }

    public function store(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless($user->role() === Role::Customer, 403);
        $data = $request->validate(['product_id' => ['required', 'integer'], 'quantity' => ['required', 'numeric', 'min:0', 'max:999999.999', 'decimal:0,3'],
            'effective_from' => ['required', 'date_format:Y-m-d', 'after:today'], 'reason' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($user, $id, $data) {
            Client::lockForUpdate()->findOrFail($user->client_id);
            $subscription = Subscription::where('client_id', $user->client_id)->where('status', 1)->findOrFail($id);
            abort_unless(Customer::where('client_id', $user->client_id)->where('user_id', $user->id)->where('id', $subscription->customer_id)->exists(), 403);
            $item = $subscription->items()->where('client_id', $user->client_id)->where('product_id', $data['product_id'])->firstOrFail();
            if (DB::table('subscription_change_requests')->where('client_id', $user->client_id)->where('subscription_id', $id)->where('product_id', $item->product_id)->where('status', 1)->exists()) {
                throw ValidationException::withMessages(['quantity' => 'A pending request already exists for this product.']);
            }
            DB::table('subscription_change_requests')->insert(['client_id' => $user->client_id, 'subscription_id' => $id, 'requested_by_user_id' => $user->id,
                'product_id' => $item->product_id, 'old_quantity' => $item->default_quantity, 'requested_quantity' => $data['quantity'],
                'effective_from' => $data['effective_from'], 'reason' => $data['reason'], 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        });
        return back()->with('success', 'Quantity change sent for approval.');
    }

    public function decide(Request $request, int $id)
    {
        $client = Tenant::id();
        $data = $request->validate(['status' => ['required', 'integer', Rule::in([2, 3])]]);
        $data['status'] = (int) $data['status'];
        DB::transaction(function () use ($client, $id, $data) {
            Client::lockForUpdate()->findOrFail($client);
            $change = DB::table('subscription_change_requests')->where('client_id', $client)->lockForUpdate()->find($id);
            abort_unless($change, 404);
            if ((int) $change->status !== 1 || $change->effective_from <= today()->toDateString()) {
                throw ValidationException::withMessages(['status' => 'Only pending requests with a future effective date may be decided.']);
            }
            if ($data['status'] === 2 && DB::table('subscription_change_requests')->where('client_id', $client)->where('subscription_id', $change->subscription_id)
                ->where('product_id', $change->product_id)->where('effective_from', $change->effective_from)->where('status', 2)->exists()) {
                throw ValidationException::withMessages(['status' => 'An approved change already exists for this effective date.']);
            }
            DB::table('subscription_change_requests')->where('id', $id)->update(['status' => $data['status'], 'approved_by_user_id' => auth()->id(), 'approved_at' => now(), 'updated_at' => now()]);
            if ($data['status'] === 2) {
                app(\App\Services\QuantityPlanService::class)->refreshFuture($client, $change->subscription_id, $change->product_id);
            }
            DB::table('audit_logs')->insert(['client_id' => $client, 'user_id' => auth()->id(), 'action' => 'quantity_change_decided', 'table_name' => 'subscription_change_requests', 'record_id' => $id,
                'new_values' => json_encode($data), 'created_at' => now()]);
            DB::table('notifications')->insert(['client_id' => $client, 'user_id' => $change->requested_by_user_id, 'title' => 'Quantity change decision',
                'message' => 'Your request #'.$id.' was '.($data['status'] === 2 ? 'approved' : 'rejected').'.', 'created_at' => now()]);
        });
        return back()->with('success', 'Quantity change decision recorded.');
    }
}

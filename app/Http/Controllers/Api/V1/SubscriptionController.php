<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Subscription::where('client_id', $user->client_id)->with(['items.product:id,name', 'pauses']);
        if ($user->role() === Role::Customer) {
            $query->whereIn('customer_id', Customer::where('client_id', $user->client_id)->where('user_id', $user->id)->select('id'));
        } else {
            abort_unless($user->role() === Role::ClientAdmin, 403);
        }

        return response()->json($query->latest('id')->paginate(20));
    }

    public function store(Request $request, SubscriptionService $subscriptions)
    {
        $clientId = $request->user()->client_id;
        $data = $request->validate(['customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where(fn ($query) => $query->where('client_id', $clientId)->where('status', 1))],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1', 'max:7'], 'days.*' => ['required', 'integer', 'distinct', 'between:1,7'], 'slot' => ['required', 'integer', Rule::in([1, 2, 3])],
            'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where(fn ($query) => $query->where('client_id', $clientId)->where('status', 1))],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999', 'decimal:0,3']]);
        $subscription = $subscriptions->create($request->user(), $data);

        return response()->json(['data' => $subscription], 201);
    }

    public function pause(Request $request, int $subscription, SubscriptionService $subscriptions)
    {
        $data = $request->validate(['pause_from' => ['required', 'date_format:Y-m-d', 'after:today'], 'pause_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:pause_from'], 'reason' => ['nullable', 'string', 'max:255']]);
        $subscriptions->pause($request->user(), $subscription, $data);

        return response()->json(['message' => 'Pause scheduled.']);
    }
}

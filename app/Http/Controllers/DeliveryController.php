<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Models\Delivery;
use App\Services\DeliveryGenerationService;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    private function query(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::DeliveryBoy, Role::Customer]), 403);
        return Delivery::query()->join('customers', function ($join) {
            $join->on('customers.id', '=', 'deliveries.customer_id')->on('customers.client_id', '=', 'deliveries.client_id');
        })->where('deliveries.client_id', $user->client_id)
            ->when($user->role() === Role::DeliveryBoy, fn ($q) => $q->where('deliveries.delivery_boy_id', $user->id))
            ->when($user->role() === Role::Customer, fn ($q) => $q->where('customers.user_id', $user->id));
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'status' => ['nullable', 'integer', Rule::in(array_column(DeliveryStatus::cases(), 'value'))], 'q' => ['nullable', 'string', 'max:150']]);
        $query = $this->query($request)
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('deliveries.delivery_date', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('deliveries.status_id', $status))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('customers.name', 'like', '%'.$search.'%'));
        return Inertia::render('Deliveries/Index', [
            'deliveries' => $query->select('deliveries.id', 'deliveries.delivery_date', 'deliveries.status_id', 'deliveries.total_amount', 'deliveries.pin_required', 'customers.name as customer_name')
                ->orderByDesc('deliveries.delivery_date')->orderByDesc('deliveries.id')->paginate(20)->withQueryString()
                ->through(fn ($row) => [...$row->toArray(), 'status' => DeliveryStatus::tryFrom($row->status_id)?->label() ?? 'Unknown']),
            'filters' => $filters, 'today' => today()->toDateString(),
            'statuses' => collect(DeliveryStatus::cases())->map(fn ($status) => ['id' => $status->value, 'name' => $status->label()]),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $delivery = $this->query($request)->where('deliveries.id', $id)->select('deliveries.*', 'customers.name as customer_name')->firstOrFail();
        $items = DB::table('delivery_items as items')->join('products', function ($join) {
            $join->on('products.id', '=', 'items.product_id')->on('products.client_id', '=', 'items.client_id');
        })->where('items.delivery_id', $id)->where('items.client_id', $request->user()->client_id)
            ->get(['items.id', 'products.name', 'items.planned_quantity', 'items.actual_quantity', 'items.rate', 'items.amount']);
        return Inertia::render('Deliveries/Show', ['delivery' => [
            ...$delivery->only(['id', 'delivery_date', 'customer_name', 'total_amount', 'pin_required', 'delivery_pin_verified_at', 'completed_by_user_id']),
            'status' => DeliveryStatus::tryFrom($delivery->status_id)?->label() ?? 'Unknown',
            'pending' => in_array($delivery->status_id, [1, 2]), 'items' => $items,
        ]]);
    }

    public function generate(Request $request, DeliveryGenerationService $service)
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(31)->toDateString()]]);
        $count = $service->generate($request->user(), $data['date']);
        return back()->with('success', "{$count} deliveries generated. Existing deliveries were preserved.");
    }

    public function complete(Request $request, int $id, DeliveryService $service)
    {
        $data = $request->validate([
            'skip' => ['sometimes', 'boolean'], 'pin' => ['nullable', 'string', 'max:6'],
            'items' => ['required_unless:skip,true', 'array', 'max:100'], 'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999.999', 'decimal:0,3'],
        ]);
        $service->complete($request->user(), $id, $data['items'] ?? [], $data['pin'] ?? null, (bool) ($data['skip'] ?? false));
        return back()->with('success', !empty($data['skip']) ? 'Delivery skipped. No delivery charge was posted.' : 'Delivery completed and ledger updated.');
    }

    public function pin(Request $request, int $id, DeliveryService $service)
    {
        return response()->json(['pin' => $service->issuePin($request->user(), $id)])
            ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }
}

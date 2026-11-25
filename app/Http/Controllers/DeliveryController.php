<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Services\DeliveryGenerationService;
use App\Services\DeliveryReadService;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    public function index(
        Request $request,
        DeliveryReadService $deliveries,
        DeliveryGenerationService $generation
    )
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'integer', Rule::in(array_column(DeliveryStatus::cases(), 'value'))],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $today = today()->toDateString();
        $filters['date'] ??= $today;
        $generationError = null;

        if ($request->user()->role() === Role::ClientAdmin && $filters['date'] === $today) {
            try {
                $generation->generate($request->user(), $today);
            } catch (ValidationException $exception) {
                $generationError = collect($exception->errors())->flatten()->first();
            }
        }

        $deliveryQuery = $deliveries->scopedQuery($request->user())
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('deliveries.delivery_date', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('deliveries.status_id', $status))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('customers.name', 'like', '%'.$search.'%'))
            ->selectRaw("'delivery' as record_type")
            ->addSelect(
                'deliveries.id',
                'deliveries.delivery_date as history_date',
                'deliveries.status_id',
                'deliveries.total_amount',
                'deliveries.pin_required',
                'customers.name as customer_name',
                'customers.delivery_pin_required as customer_pin_required'
            );

        $historyQuery = $deliveryQuery->toBase();

        if ($request->user()->role() === \App\Enums\Role::ClientAdmin && empty($filters['status'])) {
            $salesQuery = DB::table('walk_in_sales as sales')
                ->leftJoin('customers', function ($join) {
                    $join->on('customers.id', '=', 'sales.customer_id')
                        ->on('customers.client_id', '=', 'sales.client_id');
                })
                ->where('sales.client_id', $request->user()->client_id)
                ->where('sales.status', 1)
                ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('sales.sold_at', $date))
                ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereRaw(
                    "COALESCE(customers.name, sales.customer_name, 'Guest') like ?",
                    ['%'.$search.'%']
                ))
                ->selectRaw("'counter_sale' as record_type")
                ->addSelect(
                    'sales.id',
                    'sales.sold_at as history_date',
                    DB::raw('NULL as status_id'),
                    'sales.total as total_amount',
                    DB::raw('NULL as pin_required'),
                    DB::raw('NULL as customer_pin_required'),
                    DB::raw("COALESCE(customers.name, sales.customer_name, 'Guest') as customer_name")
                );

            $historyQuery->unionAll($salesQuery);
        }

        $history = DB::query()->fromSub($historyQuery, 'history')
            ->orderByDesc('history_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($row) => [
                ...((array) $row),
                'status' => $row->record_type === 'counter_sale'
                    ? 'Counter Sale'
                    : (DeliveryStatus::tryFrom((int) $row->status_id)?->label() ?? 'Unknown'),
                'pin_required' => $row->record_type === 'delivery' && in_array((int) $row->status_id, [1, 2])
                    && $row->pin_required !== null
                    ? (bool) $row->customer_pin_required
                    : $row->pin_required,
            ]);

        return Inertia::render('Deliveries/Index', [
            'deliveries' => $history,
            'filters' => $filters,
            'today' => $today,
            'generationError' => $generationError,
            'statuses' => collect(DeliveryStatus::cases())
                ->map(fn ($status) => ['id' => $status->value, 'name' => $status->label()]),
        ]);
    }

    public function show(Request $request, int $id, DeliveryReadService $deliveries)
    {
        return Inertia::render('Deliveries/Show', ['delivery' => $deliveries->find($request->user(), $id)]);
    }

    public function generate(Request $request, DeliveryGenerationService $service)
    {
        $data = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.today()->addDays(31)
                    ->toDateString(),
            ],
        ]);

        $count = $service->generate($request->user(), $data['date']);

        return back()->with('success', "{$count} deliveries generated. Existing deliveries were preserved.");
    }

    public function complete(Request $request, int $id, DeliveryService $service)
    {
        $data = $request->validate([
            'skip' => ['sometimes', 'boolean'],
            'pin' => ['nullable', 'string', 'max:6'],
            'items' => ['required_unless:skip,true', 'array', 'max:100'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999.999', 'decimal:0,3'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
        ]);

        $service->complete($request->user(), $id, $data['items'] ?? [], $data['pin'] ?? null, (bool) ($data['skip'] ?? false));

        return back()
            ->with(
                'success',
                ! empty($data['skip']) ? 'Delivery skipped. No delivery charge was posted.' : 'Delivery completed and ledger updated.'
            );
    }

    public function pin(Request $request, int $id, DeliveryService $service)
    {
        return response()->json(['pin' => $service->issuePin($request->user(), $id)])
            ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }
}

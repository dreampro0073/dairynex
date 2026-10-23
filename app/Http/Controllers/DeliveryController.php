<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Services\DeliveryGenerationService;
use App\Services\DeliveryReadService;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    public function index(Request $request, DeliveryReadService $deliveries)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'integer', Rule::in(array_column(DeliveryStatus::cases(), 'value'))],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $query = $deliveries->scopedQuery($request->user())
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('deliveries.delivery_date', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('deliveries.status_id', $status))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('customers.name', 'like', '%'.$search.'%'));

        return Inertia::render('Deliveries/Index', [
            'deliveries' => $query->select(
                'deliveries.id',
                'deliveries.delivery_date',
                'deliveries.status_id',
                'deliveries.total_amount',
                'deliveries.pin_required',
                'customers.name as customer_name'
            )
                ->orderByDesc('deliveries.delivery_date')
                ->orderByDesc('deliveries.id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn ($row) => [
                    ...$row->toArray(),
                    'status' => DeliveryStatus::tryFrom($row->status_id)?->label() ?? 'Unknown',
                ]),
            'filters' => $filters,
            'today' => today()->toDateString(),
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

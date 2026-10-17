<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Services\DeliveryReadService;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function index(Request $request, DeliveryReadService $deliveries)
    {
        $filters = $request->validate(['date' => ['nullable', 'date_format:Y-m-d'], 'status' => ['nullable', 'integer', Rule::in(array_column(DeliveryStatus::cases(), 'value'))]]);
        $data = $deliveries->scopedQuery($request->user())->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('deliveries.delivery_date', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('deliveries.status_id', $status))
            ->select('deliveries.id', 'deliveries.delivery_date', 'deliveries.status_id', 'deliveries.total_amount', 'deliveries.pin_required', 'customers.name as customer_name')
            ->orderByDesc('deliveries.delivery_date')->orderByDesc('deliveries.id')->paginate(20);
        $data->through(fn ($row) => [...$row->toArray(), 'status' => DeliveryStatus::tryFrom($row->status_id)?->label()]);

        return response()->json($data);
    }

    public function show(Request $request, int $delivery, DeliveryReadService $deliveries)
    {
        return response()->json(['data' => $deliveries->find($request->user(), $delivery)]);
    }

    public function complete(Request $request, int $delivery, DeliveryService $service)
    {
        $data = $request->validate(['skip' => ['sometimes', 'boolean'], 'pin' => ['nullable', 'string', 'max:6'],
            'items' => ['required_unless:skip,true', 'array', 'max:100'], 'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:999999.999', 'decimal:0,3']]);
        $result = $service->complete($request->user(), $delivery, $data['items'] ?? [], $data['pin'] ?? null, (bool) ($data['skip'] ?? false));

        return response()->json(['data' => ['id' => $result->id, 'status' => DeliveryStatus::tryFrom($result->status_id)?->label(), 'total_amount' => $result->total_amount]]);
    }

    public function issuePin(Request $request, int $delivery, DeliveryService $service)
    {
        return response()->json(['pin' => $service->issuePin($request->user(), $delivery)])
            ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }
}

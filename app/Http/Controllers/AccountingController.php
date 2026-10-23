<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Customer;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::DeliveryBoy, Role::Customer]), 403);
        $filters = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                ...($request->filled('from') ? ['after_or_equal:from'] : []),
            ],
        ]);

        $customers = Customer::where('client_id', $user->client_id)
            ->when($user->role() === Role::Customer, fn ($q) => $q->where('user_id', $user->id))
            ->when($user->role() === Role::DeliveryBoy, fn ($q) => $q->whereIn('id', DB::table('deliveries')
                ->where('client_id', $user->client_id)

                ->where('delivery_boy_id', $user->id)
                ->whereDate('delivery_date', today())
                ->select('customer_id')));

        $owned = (clone $customers)->select('id');
        $ledger = ! str_contains($request->path(), 'payments') && $user->role() !== Role::DeliveryBoy;
        $table = $ledger ? 'customer_ledger' : 'payments';
        $date = $ledger ? 'transaction_date' : 'payment_date';
        $query = DB::table($table)->where('client_id', $user->client_id)
            ->whereIn('customer_id', $owned)
            ->when($user->role() === Role::DeliveryBoy, fn ($q) => $q->where('collected_by_user_id', $user->id))
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $dateValue) => $q->whereDate($date, '>=', $dateValue))
            ->when($filters['to'] ?? null, fn ($q, $dateValue) => $q->whereDate($date, '<=', $dateValue));

        $selected = isset($filters['customer_id']) ? (clone $customers)->find($filters['customer_id']) : null;

        return Inertia::render('Finance/Index', [
            'title' => $ledger ? 'Customer Ledger' : 'Payments',
            'ledger' => $ledger,
            'records' => $query->orderByDesc($date)->orderByDesc('id')->paginate(20)->withQueryString(),
            'customers' => $customers->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'selectedBalance' => $selected ? Decimal::format(app(LedgerService::class)
                ->balance($selected)) : null,
            'openingBalance' => $selected?->opening_balance,
            'methods' => DB::table('ref_payment_methods')
                ->orderBy('id')
                ->get(['id', 'name']),
            'paymentKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, PaymentService $service)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'decimal:0,2'],
            'key' => ['required', 'uuid'],
            'payment_method_id' => ['required', 'integer'],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $service->record($request->user(), $data);

        return back()->with('success', 'Payment recorded and customer ledger updated.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Customer;
use App\Services\InvoiceService;
use App\Support\Decimal;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    private function query(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::Customer]), 403);

        return DB::table('invoices')->where('client_id', $user->client_id)
            ->when($user->role() === Role::Customer, fn ($q) => $q->whereIn('customer_id', Customer::where('client_id', $user->client_id)
                ->where('user_id', $user->id)
                ->select('id')));
    }

    public function index(Request $request)
    {
        return Inertia::render('Finance/Invoices', [
            'invoices' => $this->query($request)
                ->orderByDesc('id')
                ->paginate(20),
            'customers' => $request->user()
                ->role() === Role::ClientAdmin ? Customer::where('client_id', $request->user()->client_id)
                ->orderBy('name')
                ->get(['id', 'name']) : [],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $invoice = $this->query($request)->where('id', $id)->first();
        abort_unless($invoice, 404);

        return Inertia::render('Finance/Invoice', [
            'invoice' => $invoice,
            'items' => DB::table('invoice_items as items')->join('products', function ($join) {
                $join->on('products.id', '=', 'items.product_id')
                    ->on('products.client_id', '=', 'items.client_id');
            })
                ->where('items.client_id', $request->user()->client_id)
                ->where('items.invoice_id', $id)
                ->get(['items.id', 'products.name', 'items.quantity', 'items.rate', 'items.amount']),
            'payments' => $request->user()
                ->role() === Role::ClientAdmin ? DB::table('payments')
                ->where('client_id', $request->user()->client_id)
                ->where('customer_id', $invoice->customer_id)
                ->where('status', 1)
                ->get(['id', 'payment_no', 'amount']) : [],
        ]);
    }

    public function store(Request $request, InvoiceService $service)
    {
        $client = Tenant::id();
        $data = $request->validate(['customer_id' => ['required', 'integer'], 'month' => ['required', 'date_format:Y-m']]);
        $id = $service->issue($client, $data['customer_id'], $data['month'], $request->user()->id);

        return redirect('/invoices/'.$id)
            ->with('success', 'Monthly invoice issued from actual delivered quantities.');
    }

    public function allocate(Request $request, int $id, InvoiceService $service)
    {
        $client = Tenant::id();
        $data = $request->validate([
            'payment_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'decimal:0,2'],
        ]);

        $service->allocate($client, $id, $data['payment_id'], Decimal::scaled($data['amount'], 2), $request->user()->id);

        return back()
            ->with('success', 'Existing payment allocated. No additional ledger charge or credit was posted.');
    }
}

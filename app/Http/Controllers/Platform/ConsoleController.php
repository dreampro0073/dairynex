<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\{Client, ClientSubscription, Plan};
use App\Services\PlatformSubscriptionService;
use App\Support\{PlatformAudit, PlatformSettings};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ConsoleController extends Controller
{
    public function plans(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'status' => 'nullable|in:0,1']);
        return Inertia::render('Platform/Plans', ['plans' => Plan::withCount('subscriptions')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%'))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('monthly_price')->paginate(12)->withQueryString(), 'filters' => $filters]);
    }

    public function savePlan(Request $request, ?Plan $plan = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('plans', 'name')->ignore($plan?->id)],
            'description' => 'nullable|string|max:5000', 'monthly_price' => 'required|numeric|min:0|max:99999999.99|decimal:0,2',
            'max_customers' => 'nullable|integer|min:1|max:4294967295', 'max_users' => 'nullable|integer|min:1|max:4294967295', 'status' => 'required|boolean',
        ]);
        DB::transaction(function () use ($plan, $data) {
            $model = $plan ? Plan::lockForUpdate()->findOrFail($plan->id) : new Plan;
            if ($model->exists) {
                ClientSubscription::where('plan_id', $model->id)->whereNull('monthly_price')->update(['monthly_price' => $model->monthly_price]);
            }
            $model->fill($data)->save();
            PlatformAudit::record('saas_plan_saved', 'plans', $model->id);
        });
        return back()->with('success', 'Plan saved. Existing subscriptions retain their agreed prices.');
    }

    public function deletePlan(Plan $plan)
    {
        DB::transaction(function () use ($plan) {
            $plan = Plan::lockForUpdate()->findOrFail($plan->id);
            if ($plan->subscriptions()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['plan' => 'This plan has subscription history. Deactivate it to preserve records.']);
            }
            $plan->delete();
            PlatformAudit::record('saas_plan_deleted', 'plans', $plan->id);
        });
        return back()->with('success', 'Unused plan deleted.');
    }

    public function subscription(Request $request, Client $client, PlatformSubscriptionService $service)
    {
        $data = $request->validate(['action' => 'required|in:assign,renew,upgrade,downgrade,expire',
            'plan_id' => 'required_unless:action,expire|nullable|integer|exists:plans,id', 'months' => 'required_unless:action,expire|nullable|integer|min:1|max:36']);
        if ($data['action'] === 'expire') {
            DB::transaction(function () use ($client) {
                Client::lockForUpdate()->findOrFail($client->id);
                ClientSubscription::where('client_id', $client->id)->where('status', 1)->update(['status' => 0, 'updated_at' => now()]);
                PlatformAudit::record('saas_subscription_expired', 'clients', $client->id, $client->id);
            });
        } else {
            $service->change($client, $data['plan_id'], $data['action'], $data['months']);
        }
        return back()->with('success', 'Subscription updated.');
    }

    private function invoiceQuery(Request $request)
    {
        return DB::table('saas_invoices as invoices')->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->when($request->input('search'), fn ($q, $s) => $q->where('clients.name', 'like', '%'.$s.'%'))
            ->when($request->input('status') === 'paid', fn ($q) => $q->whereNotNull('paid_at'))
            ->when($request->input('status') === 'pending', fn ($q) => $q->whereNull('paid_at')->whereDate('due_date', '>=', today()))
            ->when($request->input('status') === 'overdue', fn ($q) => $q->whereNull('paid_at')->whereDate('due_date', '<', today()))
            ->when($request->input('from'), fn ($q, $s) => $q->whereDate('invoices.period_from', '>=', $s))
            ->when($request->input('to'), fn ($q, $s) => $q->whereDate('invoices.period_from', '<=', $s));
    }

    private function billingFilters(Request $request): array
    {
        return $request->validate(['search' => 'nullable|string|max:150', 'status' => 'nullable|in:paid,pending,overdue',
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
    }

    public function billing(Request $request)
    {
        $filters = $this->billingFilters($request);
        $month = DB::table('saas_invoices')->where('created_at', '>=', today()->startOfMonth());
        return Inertia::render('Platform/Billing', [
            'invoices' => $this->invoiceQuery($request)->select('invoices.*', 'clients.name as client')->orderByDesc('invoices.id')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'stats' => ['Invoiced this month' => '₹'.number_format((clone $month)->sum('amount'), 2),
                'Collected this month' => '₹'.number_format(DB::table('saas_invoices')->where('paid_at', '>=', today()->startOfMonth())->sum('amount'), 2),
                'Pending' => '₹'.number_format(DB::table('saas_invoices')->whereNull('paid_at')->sum('amount'), 2),
                'Overdue > 15 days' => DB::table('saas_invoices')->whereNull('paid_at')->whereDate('due_date', '<', today()->subDays(15))->count()],
        ]);
    }

    public function paid(Request $request, int $invoice)
    {
        $data = $request->validate(['payment_reference' => 'required|string|max:150']);
        DB::transaction(function () use ($invoice, $data) {
            $row = DB::table('saas_invoices')->where('id', $invoice)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($row->paid_at) { return; }
            DB::table('saas_invoices')->where('id', $invoice)->update(['paid_at' => now(), 'payment_reference' => $data['payment_reference'], 'updated_at' => now()]);
            PlatformAudit::record('saas_invoice_paid', 'saas_invoices', $invoice, $row->client_id);
        });
        return back()->with('success', 'SaaS payment recorded.');
    }

    public function exportBilling(Request $request)
    {
        $this->billingFilters($request);
        return $this->csv('saas-billing.csv', ['Invoice', 'Client', 'Plan', 'From', 'To', 'Due', 'Amount', 'Paid At', 'Reference'],
            $this->invoiceQuery($request)->select('invoices.*', 'clients.name as client')->orderBy('invoices.id')->cursor()
                ->map(fn ($r) => ['SAAS-'.$r->id, $r->client, $r->plan_name, $r->period_from, $r->period_to, $r->due_date, $r->amount, $r->paid_at, $r->payment_reference]));
    }

    public function databases(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:150']);
        return Inertia::render('Platform/Databases', [
            'clients' => Client::withCount(['customers', 'deliveryBoys'])->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.$s.'%'))
                ->orderBy('name')->paginate(12)->withQueryString(), 'filters' => $filters,
            'health' => ['driver' => DB::connection()->getDriverName(), 'connected' => (bool) DB::select('select 1'),
                'tenancy' => 'Shared database with client_id isolation', 'backup_status' => 'No backup provider is configured'],
        ]);
    }

    private function usageQuery(Request $request)
    {
        $from = $request->input('from', today()->toDateString());
        $to = $request->input('to', today()->toDateString());
        $deliveries = DB::table('deliveries')->whereBetween('delivery_date', [$from, $to])->selectRaw('client_id, COUNT(*) as deliveries, SUM(CASE WHEN status_id IN (3, 8) THEN 1 ELSE 0 END) as completed')->groupBy('client_id');
        return DB::table('clients')->leftJoinSub($deliveries, 'activity', 'activity.client_id', '=', 'clients.id')
            ->when($request->input('search'), fn ($q, $s) => $q->where('clients.name', 'like', '%'.$s.'%'))
            ->selectRaw('clients.id, clients.name, COALESCE(activity.deliveries, 0) as deliveries, COALESCE(activity.completed, 0) as completed')
            ->orderByDesc('deliveries')->orderBy('clients.id');
    }

    private function usageFilters(Request $request): array
    {
        return $request->validate(['search' => 'nullable|string|max:150', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
    }

    public function usage(Request $request)
    {
        $filters = $this->usageFilters($request);
        return Inertia::render('Platform/Usage', [
            'clients' => $this->usageQuery($request)->paginate(15)->withQueryString(),
            'filters' => array_replace(['from' => today()->toDateString(), 'to' => today()->toDateString()], $filters),
            'stats' => [
                'Daily active dairies' => DB::table('users')->where('last_login_at', '>=', today())->whereNotNull('client_id')->distinct()->count('client_id'),
                'Deliveries logged today' => DB::table('deliveries')->whereDate('delivery_date', today())->count(),
                'PIN verifications today' => DB::table('deliveries')->whereDate('delivery_pin_verified_at', today())->count(),
                'Support tickets open' => DB::table('support_tickets')->where('status', '!=', 'closed')->count(),
            ],
        ]);
    }

    public function exportUsage(Request $request)
    {
        $this->usageFilters($request);
        return $this->csv('platform-usage.csv', ['Dairy ID', 'Dairy', 'Deliveries', 'Completed'],
            $this->usageQuery($request)->cursor()->map(fn ($r) => [$r->id, $r->name, $r->deliveries, $r->completed]));
    }

    public function support(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:open,in_progress,closed', 'priority' => 'nullable|in:low,medium,high', 'search' => 'nullable|string|max:150']);
        return Inertia::render('Platform/Support', [
            'tickets' => DB::table('support_tickets as tickets')->join('clients', 'clients.id', '=', 'tickets.client_id')
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('tickets.status', $s))
                ->when($filters['priority'] ?? null, fn ($q, $s) => $q->where('tickets.priority', $s))
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('subject', 'like', '%'.$s.'%')->orWhere('clients.name', 'like', '%'.$s.'%')))
                ->select('tickets.*', 'clients.name as client')->orderByDesc('tickets.id')->paginate(15)->withQueryString(),
            'clients' => Client::orderBy('name')->get(['id', 'name']), 'filters' => $filters,
        ]);
    }

    public function saveTicket(Request $request, ?int $ticket = null)
    {
        $data = $request->validate(['client_id' => 'required|integer|exists:clients,id', 'subject' => 'required|string|max:200',
            'description' => 'required|string|max:10000', 'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:open,in_progress,closed', 'resolution' => 'required_if:status,closed|nullable|string|max:10000']);
        DB::transaction(function () use ($ticket, $data) {
            if ($ticket) {
                $existing = DB::table('support_tickets')->where('id', $ticket)->lockForUpdate()->first();
                abort_unless($existing, 404);
                abort_unless((int) $existing->client_id === (int) $data['client_id'], 422, 'Ticket ownership cannot be changed.');
                DB::table('support_tickets')->where('id', $ticket)->update($data + ['updated_at' => now()]);
                $id = $ticket;
            } else {
                $id = DB::table('support_tickets')->insertGetId($data + ['created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            }
            PlatformAudit::record('support_ticket_saved', 'support_tickets', $id, $data['client_id'], ['status' => $data['status']]);
        });
        return back()->with('success', 'Support ticket saved.');
    }

    public function settings()
    {
        return Inertia::render('Platform/Settings', ['settings' => PlatformSettings::values(), 'channels' => [
            'sms' => 'No SMS provider configured', 'whatsapp' => 'No WhatsApp provider configured',
            'email' => in_array(config('mail.default'), ['log', 'array']) ? 'Email delivery is not configured' : 'Transport: '.config('mail.default'),
        ]]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['platform_name' => 'required|string|max:150', 'support_email' => 'nullable|email|max:150',
            'currency' => 'required|in:INR', 'timezone' => 'required|in:Asia/Kolkata', 'notice' => 'nullable|string|max:2000',
            'sms_alerts' => 'required|boolean', 'whatsapp_alerts' => 'required|boolean', 'welcome_email' => 'required|boolean']);
        // Provider-less channels cannot be falsely marked as connected/enabled.
        if ($data['sms_alerts'] || $data['whatsapp_alerts'] || $data['welcome_email']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['channels' => 'External channel integrations are not configured. Leave these channels off.']);
        }
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                DB::table('platform_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]);
            }
            PlatformAudit::record('platform_settings_saved', 'platform_settings', 0);
        });
        return back()->with('success', 'Platform settings saved.');
    }

    private function csv(string $filename, array $headers, iterable $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn ($value) => preg_match('/^[\s]*[=+@-]/u', (string) $value) ? "'".$value : $value, $row), ',', '"', '');
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }
}

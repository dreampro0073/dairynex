<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\{Client, ClientSubscription, Plan, User};
use App\Services\PlatformSubscriptionService;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:150', 'status' => 'nullable|in:0,1', 'plan_id' => 'nullable|integer|exists:plans,id']);
        $clients = Client::with('currentSubscription.plan')->withCount(['customers', 'deliveryBoys'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('city', 'like', '%'.$search.'%')->orWhere('mobile', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['plan_id'] ?? null, fn ($q, $plan) => $q->whereHas('currentSubscription', fn ($q) => $q->where('plan_id', $plan)))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $current = ClientSubscription::current()->whereIn('client_id', Client::where('status', 1)->select('id'))->with('plan')->orderByDesc('id')->get();

        return Inertia::render('Platform/Clients/Index', [
            'clients' => $clients, 'filters' => $filters, 'plans' => Plan::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'Active Clients' => Client::where('status', 1)->count(),
                'Total Customers Served' => DB::table('customers')->count(),
                'MRR' => '₹'.number_format($current->unique('client_id')->sum(fn ($s) => (float) ($s->monthly_price ?? $s->plan?->monthly_price ?? 0)), 2),
                'Payment Overdue' => DB::table('saas_invoices')->whereNull('paid_at')->whereDate('due_date', '<', today())->distinct()->count('client_id'),
            ],
            'total' => Client::count(), 'newThisMonth' => Client::where('created_at', '>=', today()->startOfMonth())->count(),
            'activities' => DB::table('audit_logs as logs')->leftJoin('clients', 'clients.id', '=', 'logs.client_id')->leftJoin('users', 'users.id', '=', 'logs.user_id')
                ->orderByDesc('logs.id')->limit(10)->get(['logs.id', 'logs.action', 'logs.created_at', 'clients.name as client', 'users.name as actor']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Platform/Clients/Form', ['client' => null, 'plans' => Plan::where('status', 1)->get(['id', 'name', 'monthly_price'])]);
    }

    public function store(Request $request, PlatformSubscriptionService $subscriptions)
    {
        $data = $request->validate($this->rules() + [
            'admin_name' => 'required|string|max:150', 'admin_mobile' => 'required|digits_between:7,20',
            'admin_email' => 'nullable|email|max:150', 'plan_id' => 'nullable|integer|exists:plans,id',
        ]);
        $client = DB::transaction(function () use ($data, $subscriptions) {
            $client = new Client;
            $client->forceFill(collect($data)->only(array_keys($this->rules()))->all())->save();
            $user = new User;
            $user->forceFill(['client_id' => $client->id, 'user_type_id' => Role::ClientAdmin->value,
                'name' => $data['admin_name'], 'mobile' => $data['admin_mobile'], 'email' => $data['admin_email'] ?? null,
                'status' => 1, 'password' => Str::random(48), 'must_change_password' => true])->save();
            if (!empty($data['plan_id'])) {
                $subscriptions->change($client, $data['plan_id'], 'assign', 1);
            }
            PlatformAudit::record('dairy_created', 'clients', $client->id, $client->id);
            return $client;
        });
        return redirect('/platform/clients/'.$client->id)->with('success', 'Dairy and Client Admin created. Generate a temporary password from View Login Details.');
    }

    public function show(Client $client)
    {
        return Inertia::render('Platform/Clients/Show', [
            'client' => $client->loadCount(['customers', 'deliveryBoys'])->load('currentSubscription.plan'),
            'admins' => $client->admins()->orderBy('id')->get(['id', 'name', 'mobile', 'email', 'status', 'last_login_at', 'must_change_password']),
            'subscriptions' => ClientSubscription::where('client_id', $client->id)->with('plan')->orderByDesc('id')->paginate(10)->withQueryString(),
            'plans' => Plan::where('status', 1)->orderBy('monthly_price')->get(),
        ]);
    }

    public function edit(Client $client)
    {
        return Inertia::render('Platform/Clients/Form', ['client' => $client, 'plans' => []]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate($this->rules());
        DB::transaction(function () use ($client, $data) {
            $client = Client::lockForUpdate()->findOrFail($client->id);
            $client->forceFill($data)->save();
            PlatformAudit::record('dairy_updated', 'clients', $client->id, $client->id, ['status' => $client->status]);
        });
        return redirect('/platform/clients/'.$client->id)->with('success', 'Dairy updated.');
    }

    public function status(Request $request, Client $client)
    {
        $data = $request->validate(['status' => 'required|boolean']);
        DB::transaction(function () use ($client, $data) {
            $client = Client::lockForUpdate()->findOrFail($client->id);
            $client->forceFill(['status' => (int) $data['status']])->save();
            PlatformAudit::record('dairy_status_changed', 'clients', $client->id, $client->id, ['status' => $client->status]);
        });
        return back()->with('success', 'Dairy status updated.');
    }

    public function admin(Request $request, Client $client, ?int $admin = null)
    {
        $user = $admin === null ? new User : $client->admins()->findOrFail($admin);
        $data = $request->validate([
            'name' => 'required|string|max:150', 'status' => 'required|boolean',
            'mobile' => ['required', 'digits_between:7,20', Rule::unique('users', 'mobile')->where('client_id', $client->id)->ignore($user->id)],
            'email' => 'nullable|email|max:150',
        ]);
        DB::transaction(function () use ($client, $user, $data) {
            Client::lockForUpdate()->findOrFail($client->id);
            // Recheck under the tenant lock: no duplicate identities during concurrent creation.
            if (User::where('client_id', $client->id)->where('mobile', $data['mobile'])->when($user->exists, fn ($q) => $q->where('id', '!=', $user->id))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['mobile' => 'This mobile is already in use in the dairy.']);
            }
            if (!$user->exists) {
                $user->forceFill(['client_id' => $client->id, 'user_type_id' => Role::ClientAdmin->value, 'password' => Str::random(48), 'must_change_password' => true]);
            }
            $user->forceFill($data)->save();
            PlatformAudit::record('client_admin_saved', 'users', $user->id, $client->id, ['status' => (int) $user->status]);
        });
        return back()->with('success', 'Client Admin saved. New accounts need a temporary password.');
    }

    public function loginDetails(Client $client)
    {
        return response()->json([
            'dairy' => $client->only(['id', 'name', 'status']),
            'login_url' => url('/login').'?client_id='.$client->id,
            'admins' => $client->admins()->get(['id', 'name', 'email', 'mobile', 'status', 'must_change_password']),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function temporaryPassword(Client $client, int $admin)
    {
        $password = Str::password(20);
        $expires = now()->addHours(24);
        DB::transaction(function () use ($client, $admin, $password, $expires) {
            Client::lockForUpdate()->findOrFail($client->id);
            $user = $client->admins()->lockForUpdate()->findOrFail($admin);
            abort_unless($client->status === 1 && $user->status === 1, 422, 'Activate the dairy and account before resetting credentials.');
            $user->forceFill(['password' => $password, 'must_change_password' => true,
                'temporary_password_expires_at' => $expires, 'remember_token' => Str::random(60)])->save();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            PlatformAudit::record('client_admin_temporary_password_reset', 'users', $user->id, $client->id);
        });
        // Plaintext never enters an Inertia prop, flash, URL, database, audit or log.
        return response()->json(['password' => $password, 'expires_at' => $expires->toIso8601String()])
            ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    private function rules(): array
    {
        return ['name' => 'required|string|max:150', 'legal_name' => 'nullable|string|max:200',
            'mobile' => 'nullable|digits_between:7,20', 'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:2000', 'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100', 'pincode' => 'nullable|string|max:20', 'status' => 'required|boolean'];
    }
}

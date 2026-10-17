<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Product;
use App\Services\WalkInSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WalkInSaleController extends Controller
{
    private function authorizeAdmin(Request $request): void { abort_unless($request->user()->role() === Role::ClientAdmin, 403); }

    public function quick(Request $request)
    {
        $this->authorizeAdmin($request); $client = $request->user()->client_id;
        $products=Product::where('client_id', $client)->where('status', 1)->orderBy('name')->get(['id','name','unit_type','base_rate']);
        $products->each(function($product)use($client){$product->current_rate=DB::table('product_rates')->where('client_id',$client)->where('product_id',$product->id)->whereDate('effective_from','<=',today())
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',today()))->orderByDesc('effective_from')->orderByDesc('id')->value('rate')??$product->base_rate;});
        return Inertia::render('WalkIn/QuickSale', [
            'products' => $products,
            'customers' => Customer::where('client_id', $client)->where('status', 1)->orderBy('name')->get(['id','name','mobile']),
            'methods' => DB::table('ref_payment_methods')->whereIn('id',[1,2])->orderBy('id')->get(['id','name']), 'key' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, WalkInSaleService $sales)
    {
        $data = $request->validate(['key' => ['required','uuid'], 'customer_id' => ['nullable','integer'], 'customer_name' => ['nullable','string','max:150'],
            'customer_mobile' => ['nullable','string','max:20'], 'payment_method_id' => ['required','integer'], 'items' => ['required','array','min:1','max:50'],
            'items.*.product_id' => ['required','integer','distinct'], 'items.*.quantity' => ['required','numeric','gt:0','max:999999.999','decimal:0,3']]);
        abort_unless($request->user()->role() === Role::ClientAdmin, 403);
        if ($sale = DB::table('walk_in_sales')->where('client_id',$request->user()->client_id)->where('idempotency_key',$data['key'])->first()) {
            return redirect('/walk-in-sales/'.$sale->id)->with('success','Sale was already saved.');
        }
        $sale = $sales->create($request->user(), $data);
        return redirect('/walk-in-sales/'.$sale->id)->with('success','Walk-in sale saved.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['from'=>['nullable','date_format:Y-m-d'],'to'=>['nullable','date_format:Y-m-d','after_or_equal:from'],'q'=>['nullable','string','max:100']]);
        $query = DB::table('walk_in_sales')->where('client_id',$request->user()->client_id)
            ->when($filters['from']??null,fn($q,$d)=>$q->whereDate('sold_at','>=',$d))->when($filters['to']??null,fn($q,$d)=>$q->whereDate('sold_at','<=',$d))
            ->when($filters['q']??null,fn($q,$s)=>$q->where(fn($w)=>$w->where('receipt_no','like','%'.$s.'%')->orWhere('customer_name','like','%'.$s.'%')->orWhere('customer_mobile','like','%'.$s.'%')));
        $summary = DB::table('walk_in_sales')->where('client_id',$request->user()->client_id)->where('status',1)
            ->when($filters['from']??null,fn($q,$d)=>$q->whereDate('sold_at','>=',$d),fn($q)=>$q->whereDate('sold_at',today()))
            ->when($filters['to']??null,fn($q,$d)=>$q->whereDate('sold_at','<=',$d));
        return Inertia::render('WalkIn/Index',['title'=>$request->attributes->get('walk_in_report')?'Daily Sales Report':'Walk-in Sales','sales'=>$query->orderByDesc('sold_at')->paginate(25)->withQueryString(),'filters'=>$filters,
            'summary'=>['total'=>(clone $summary)->sum('total'),'cash'=>(clone $summary)->where('payment_method_id',1)->sum('paid_amount'),'upi'=>(clone $summary)->where('payment_method_id',2)->sum('paid_amount'),
                'credit'=>(clone $summary)->whereNull('payment_method_id')->sum('total'),'outstanding'=>(clone $summary)->sum('due_amount'),'count'=>(clone $summary)->count(),'period'=>($filters['from']??today()->toDateString()).' — '.($filters['to']??today()->toDateString())]]);
    }

    public function report(Request $request) { $request->attributes->set('walk_in_report',true); $request->merge(['from'=>$request->query('from',today()->toDateString()),'to'=>$request->query('to',today()->toDateString())]); return $this->index($request); }

    public function show(Request $request, int $id)
    {
        $this->authorizeAdmin($request); $sale=DB::table('walk_in_sales')->where('client_id',$request->user()->client_id)->where('id',$id)->firstOrFail();
        $sale->items=DB::table('walk_in_sale_items')->where('client_id',$request->user()->client_id)->where('sale_id',$id)->get();
        $sale->payment_method=$sale->payment_method_id ? DB::table('ref_payment_methods')->where('id',$sale->payment_method_id)->value('name') : 'Credit';
        return Inertia::render('WalkIn/Receipt',['sale'=>$sale]);
    }
}

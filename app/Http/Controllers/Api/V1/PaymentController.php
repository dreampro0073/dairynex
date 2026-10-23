<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, PaymentService $payments)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'decimal:0,2'],
            'key' => ['required', 'uuid'],
            'payment_method_id' => ['required', 'integer'],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $id = $payments->record($request->user(), $data);

        return response()->json(['data' => ['id' => $id]], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'mobile' => $user->mobile,
            'email' => $user->email, 'client_id' => $user->client_id, 'role' => $user->role()?->label()]]);
    }
}

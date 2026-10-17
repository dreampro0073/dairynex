<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiTokenService;
use Illuminate\Http\Request;

class AuthTokenController extends Controller
{
    public function store(Request $request, ApiTokenService $tokens)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $result = $tokens->issue($data, $data['device_name']);

        return response()->json(['token' => $result['token'], 'token_type' => 'Bearer', 'abilities' => $result['abilities'],
            'user' => $this->user($result['user'])], 201);
    }

    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    private function user($user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'client_id' => $user->client_id,
            'role' => $user->role()?->label()];
    }
}

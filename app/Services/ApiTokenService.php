<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiTokenService
{
    /** @return array{token: string, user: User, abilities: array<int, string>} */
    public function issue(array $credentials, string $deviceName): array
    {
        $users = User::query()
            ->where(fn ($query) => $query->where('mobile', $credentials['login'])
                ->orWhere('email', $credentials['login']))
            ->when($credentials['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->limit(2)->get();

        $user = $users->count() === 1 ? $users->first() : null;
        $valid = false;

        try {
            $valid = $user && $user->canSignIn() && ! $user->must_change_password && Hash::check($credentials['password'], $user->password);
        } catch (\RuntimeException) {
            // Invalid legacy password hashes must not be distinguishable to API clients.
        }

        if (! $valid) {
            throw ValidationException::withMessages(['login' => 'Unable to sign in with these credentials.']);
        }

        $abilities = ['role:'.$user->role()->label(), 'client:'.($user->client_id ?? 'platform')];
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;
        $user->forceFill(['last_login_at' => now()])->save();

        return compact('token', 'user', 'abilities');
    }
}

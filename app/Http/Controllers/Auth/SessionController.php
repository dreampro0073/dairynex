<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // The dairy ID disambiguates credentials; it never establishes authorization.
        $users = User::where('mobile', $data['mobile'])
            ->when($data['client_id'] ?? null,
                fn ($query, $id) => $query->where('client_id', $id))
            ->limit(2)->get();
        $user = $users->count() === 1 ? $users->first() : null;
        $valid = false;
        try {
            $valid = $user && $user->canSignIn() && Hash::check($data['password'], $user->password);
        } catch (\RuntimeException $exception) {
            // Unsupported legacy hashes cannot authenticate. Do not expose them.
        }

        if (!$valid) {
            throw ValidationException::withMessages(['mobile' => 'Unable to sign in. Check your dairy ID, mobile and password, or contact your administrator.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect($user->role()->home());
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function edit()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(12)]]);
        if (Hash::check($data['password'], $request->user()->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'Choose a different permanent password.']);
        }
        DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            $user->forceFill(['password' => $data['password'], 'must_change_password' => false,
                'temporary_password_expires_at' => null, 'remember_token' => Str::random(60)])->save();
            PlatformAudit::record('account_password_changed', 'users', $user->id, $user->client_id);
        });
        $request->session()->regenerate();
        // Keep this session; auth.session invalidates all other sessions on their next request.
        $request->session()->put('password_hash_web', $request->user()->getAuthPassword());
        return redirect($request->user()->role()->home())->with('success', 'Password changed.');
    }
}

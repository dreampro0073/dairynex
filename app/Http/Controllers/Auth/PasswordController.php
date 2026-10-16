<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function request()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        $users = User::where('email', $data['email'])->limit(2)->get();

        // The legacy schema allows duplicate emails. Never reset an ambiguous identity.
        if ($users->count() === 1 && $users->first()->canSignIn() && config('mail.default') !== 'log') {
            Password::sendResetLink($data);
        }

        return back()->with('success', 'If an eligible account has a unique matching email and email delivery is configured, a reset link will be sent. Otherwise contact your dairy administrator.');
    }

    public function form(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);
        $users = User::where('email', $data['email'])->limit(2)->get();
        if ($users->count() !== 1 || !$users->first()->canSignIn()) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or expired.']);
        }

        $result = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($result !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($result)]);
        }

        return redirect()->route('login')->with('success', 'Password reset. Please sign in.');
    }
}

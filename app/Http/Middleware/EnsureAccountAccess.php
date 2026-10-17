<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canSignIn(), 403, 'Your account or dairy is inactive, or its role is unsupported.');
        abort_if($role !== null && $user->role()?->label() !== $role, 403);

        if ($user->must_change_password && ! $request->routeIs('password.change', 'password.change.update')) {
            if ($request->expectsJson()) {
                abort(403, 'Change your temporary password before using the API.');
            }

            return redirect()->route('password.change');
        }

        return $next($request);
    }
}

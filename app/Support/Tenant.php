<?php

namespace App\Support;

use App\Enums\Role;

final class Tenant
{
    public static function id(): int
    {
        $user = request()->user();
        abort_unless($user && $user->role() === Role::ClientAdmin && $user->client_id, 403);

        return (int) $user->client_id;
    }
}

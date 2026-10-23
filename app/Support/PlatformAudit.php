<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PlatformAudit
{
    public static function record(string $action, string $table, int $id, ?int $clientId = null, array $metadata = []): void
    {
        DB::table('audit_logs')->insert([
            'client_id' => $clientId,
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $id,
            'new_values' => $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}

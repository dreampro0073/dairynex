<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    private function query(Request $request)
    {
        return DB::table('notifications')
            ->where('client_id', $request->user()->client_id)
            ->where('user_id', $request->user()->id);
    }

    public function index(Request $request)
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $this->query($request)
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function read(Request $request, int $id)
    {
        abort_unless($this->query($request)->where('id', $id)->exists(), 404);
        $this->query($request)
            ->where('id', $id)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => now()]);

        return back();
    }
}

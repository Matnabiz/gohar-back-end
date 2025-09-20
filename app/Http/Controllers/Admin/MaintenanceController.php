<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\AuditLog;

class MaintenanceController extends Controller
{
    public function toggle(Request $request)
    {
        // Very simple auth: expects header X-Admin-Key == env('ADMIN_KEY')
        $adminKey = $request->header('X-Admin-Key');
        $expected = env('ADMIN_KEY');

        if (! $adminKey || $adminKey !== $expected) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->validate([
            'action' => 'required|in:down,up',
            'reason' => 'nullable|string|max:500',
            'actor' => 'nullable|string|max:255'
        ]);

        $action = $payload['action'];
        $reason = $payload['reason'] ?? null;
        $actor = $payload['actor'] ?? $adminKey;
        $ip = $request->ip();

        if ($action === 'down') {
            Artisan::call('down', ['--message' => $reason ?? 'Maintenance']);
        } else {
            Artisan::call('up');
        }

        AuditLog::create([
            'actor' => $actor,
            'action' => 'maintenance_'.$action,
            'ip' => $ip,
            'meta' => json_encode(['reason' => $reason]),
        ]);

        return response()->json(['message' => 'OK', 'action' => $action]);
    }
}


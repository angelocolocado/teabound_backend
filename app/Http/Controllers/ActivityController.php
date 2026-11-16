<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function loginHistory(Request $request): JsonResponse
    {
        $activities = LoginActivity::query()
            ->latest('occurred_at')
            ->limit($this->limit($request))
            ->get()
            ->map(fn (LoginActivity $activity): array => [
                'id' => $activity->id,
                'name' => $activity->name,
                'role' => $activity->role,
                'date' => $activity->occurred_at->toDateString(),
                'time' => $activity->occurred_at->format('H:i:s'),
                'activity_type' => $activity->activity_type,
                'occurred_at' => $activity->occurred_at,
            ]);

        return response()->json($activities);
    }

    public function auditActivity(Request $request): JsonResponse
    {
        $activities = AuditLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit($this->limit($request))
            ->get()
            ->map(fn (AuditLog $activity): array => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'status' => $activity->status,
                'user' => $activity->user?->name,
                'timestamp' => $activity->created_at,
                'metadata' => $activity->metadata,
            ]);

        return response()->json($activities);
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 100), 1), 500);
    }
}

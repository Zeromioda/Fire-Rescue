<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    // Emergency Response Tracking board: live progress of every open incident
    public function index()
    {
        $active = Incident::active()
            ->with([
                'apparatuses' => fn ($q) => $q->wherePivotNull('released_at'),
                'personnel' => fn ($q) => $q->wherePivotNull('released_at'),
                'updates' => fn ($q) => $q->with('user')->limit(50),
            ])
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        // Performance over the last 30 days
        $recent = Incident::where('created_at', '>=', now()->subDays(30))->get();
        $avg = function ($values) {
            $values = $values->filter(fn ($v) => $v !== null);

            return $values->isEmpty() ? null : (int) round($values->avg());
        };

        $metrics = [
            'active' => $active->count(),
            'units_deployed' => $active->sum(fn ($i) => $i->apparatuses->count()),
            'personnel_deployed' => $active->sum(fn ($i) => $i->personnel->count()),
            'avg_dispatch' => $avg($recent->map->dispatchMinutes()),
            'avg_response' => $avg($recent->map->responseMinutes()),
            'avg_control' => $avg($recent->map(fn ($i) => $i->controlled_at && $i->arrived_at ? $i->arrived_at->diffInMinutes($i->controlled_at) : null)),
        ];

        $feed = IncidentUpdate::with(['incident', 'user'])->latest()->latest('id')->take(15)->get();

        return view('tracking.index', compact('active', 'metrics', 'feed'));
    }

    // Record a stage change and/or field note for an incident
    public function update(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'stage' => 'nullable|in:'.implode(',', array_slice(Incident::STAGES, 1)),
            'note' => 'nullable|string|max:1000',
        ]);

        $stage = $validated['stage'] ?? null;
        $note = $validated['note'] ?? null;

        if (! $stage && blank($note)) {
            return back()->with('error', 'Choose a stage or write a field note.');
        }

        if ($stage) {
            if (! in_array($stage, $incident->nextStages(), true)) {
                return back()->with('error', "This incident is already {$incident->stage()}.");
            }

            $incident->advanceTo($stage, Auth::user(), $note);
        } else {
            $incident->log(null, $note, Auth::user());
        }

        return back()->with('success', $stage ? "{$incident->title}: marked {$stage}." : 'Field note added.');
    }
}

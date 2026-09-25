<?php

namespace App\Http\Controllers;

use App\Models\Apparatus;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public const ROLES = ['Team Leader', 'Driver/Operator', 'Nozzleman', 'Rescuer', 'EMS/First Aider', 'Responder'];

    // Rescue Operation Dispatch board: open incidents and the resources available to send
    public function index(Request $request)
    {
        $incidents = Incident::active()
            ->with(['apparatuses' => fn ($q) => $q->wherePivotNull('released_at'), 'personnel' => fn ($q) => $q->wherePivotNull('released_at')])
            ->orderByRaw("CASE status WHEN 'Pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        $selected = $incidents->firstWhere('id', $request->integer('incident')) ?? $incidents->first();

        $apparatuses = Apparatus::orderBy('call_sign')->get();
        $availableUnits = $apparatuses->where('status', 'available')->values();

        $responders = User::role('Firefighter')
            ->withCount('activeAssignments')
            ->orderBy('name')
            ->get();
        $availableResponders = $responders->filter(fn ($u) => $u->is_available && $u->active_assignments_count === 0)->values();

        return view('dispatch.index', [
            'incidents' => $incidents,
            'selected' => $selected,
            'apparatuses' => $apparatuses,
            'availableUnits' => $availableUnits,
            'responders' => $responders,
            'availableResponders' => $availableResponders,
            'roles' => self::ROLES,
        ]);
    }

    // Assign apparatus and personnel to an incident and mark it dispatched
    public function store(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'apparatus_ids' => 'array',
            'apparatus_ids.*' => 'integer|exists:apparatuses,id',
            'personnel' => 'array',
            'personnel.*' => 'nullable|in:'.implode(',', self::ROLES),
            'note' => 'nullable|string|max:1000',
        ]);

        $unitIds = collect($validated['apparatus_ids'] ?? []);
        $crew = collect($validated['personnel'] ?? [])->filter(); // [user_id => role]

        if (! $incident->isActive()) {
            return back()->with('error', 'This incident is already resolved.');
        }
        if ($unitIds->isEmpty() && $crew->isEmpty()) {
            return back()->with('error', 'Select at least one unit or responder to dispatch.');
        }

        $units = Apparatus::whereIn('id', $unitIds)->where('status', 'available')->get();
        $people = User::role('Firefighter')->whereIn('id', $crew->keys())->where('is_available', true)
            ->whereDoesntHave('activeAssignments')->get();

        if ($units->count() !== $unitIds->count() || $people->count() !== $crew->count()) {
            return back()->with('error', 'Some selected units or responders are no longer available. Please review and try again.');
        }

        DB::transaction(function () use ($incident, $units, $people, $crew, $validated) {
            $now = now();

            foreach ($units as $unit) {
                $incident->apparatuses()->attach($unit->id, ['dispatched_at' => $now]);
                $unit->update(['status' => 'dispatched']);
            }

            foreach ($people as $person) {
                $incident->personnel()->attach($person->id, ['role' => $crew[$person->id], 'dispatched_at' => $now]);
                $person->forceFill(['is_available' => false])->save();
            }

            $summary = collect([
                $units->isNotEmpty() ? $units->pluck('call_sign')->implode(', ') : null,
                $people->isNotEmpty() ? $people->count().' '.str('responder')->plural($people->count()) : null,
            ])->filter()->implode(' with ');

            $note = 'Dispatched '.$summary.'.'.(filled($validated['note'] ?? null) ? ' '.trim($validated['note']) : '');

            if ($incident->status === 'Pending') {
                $incident->advanceTo('Dispatched', Auth::user(), $note);
            } else {
                $incident->log(null, 'Reinforcement: '.$note, Auth::user());
            }
        });

        return redirect()->route('dispatch.index', ['incident' => $incident->id])
            ->with('success', "Resources dispatched to {$incident->title}.");
    }

    // Return a single unit to service before the incident is closed
    public function releaseUnit(Incident $incident, Apparatus $apparatus)
    {
        $incident->apparatuses()->wherePivotNull('released_at')->updateExistingPivot($apparatus->id, ['released_at' => now()]);

        if (! $apparatus->activeIncidents()->exists()) {
            $apparatus->update(['status' => 'available']);
        }

        $incident->log(null, "{$apparatus->call_sign} released and returned to service.", Auth::user());

        return back()->with('success', "{$apparatus->call_sign} returned to service.");
    }
}

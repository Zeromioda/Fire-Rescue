<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class PostIncidentController extends Controller
{
    // Post-Incident Reporting: closed incidents awaiting or holding after-action reports
    public function index(Request $request)
    {
        $tab = $request->input('tab') === 'filed' ? 'filed' : 'pending';
        $term = trim((string) $request->input('q'));

        $pending = Incident::whereNull('after_action_report')->where('status', '!=', 'Pending');
        $filed = Incident::whereNotNull('after_action_report');

        $incidents = ($tab === 'filed' ? $filed->clone() : $pending->clone())
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereLike('title', "%{$term}%")
                ->orWhereLike('location_address', "%{$term}%")))
            ->withCount(['apparatuses', 'personnel'])
            ->orderByDesc('resolved_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'pending' => $pending->count(),
            'filed' => $filed->count(),
            'with_ai' => Incident::whereNotNull('ai_summary')->count(),
            'casualty_reports' => Incident::whereNotNull('ai_summary')
                ->whereNotLike('ai_summary', '%Casualties: No casualties%')
                ->whereNotLike('ai_summary', '%Casualties: Not specified%')
                ->count(),
        ];

        return view('post-incident.index', compact('incidents', 'counts', 'tab', 'term'));
    }

    // Printable after-action report for one incident
    public function show(Incident $incident)
    {
        $incident->load(['reporter', 'apparatuses', 'personnel', 'updates.user']);

        return view('post-incident.show', compact('incident'));
    }
}

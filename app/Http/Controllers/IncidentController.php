<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    // Redirect user to their role-specific dashboard
    public function dashboard()
    {
        $user = Auth::user();

        // 1. Firefighter View
        if ($user->hasRole('Firefighter')) {
            $activeIncidents = Incident::whereIn('status', ['Dispatched', 'Under Control'])->with(['apparatuses', 'personnel'])->latest()->get();
            $resolvedIncidents = Incident::where('status', 'Resolved')->latest()->take(5)->get();
            return view('dashboards.firefighter', compact('activeIncidents', 'resolvedIncidents'));
        }

        // 2. Admin / Dispatcher View
        // Open incidents first, then the most recent closed ones (full history lives in Fire Incident Reporting)
        $incidents = Incident::with('reporter')->active()->latest()->get()
            ->concat(Incident::with('reporter')->where('status', 'Resolved')->latest()->take(10)->get());
        $stats = [
            'total' => Incident::count(),
            'active' => Incident::active()->count(),
            'resolved' => Incident::where('status', 'Resolved')->count(),
        ];

        return view('dashboards.admin', compact('incidents', 'stats'));
    }

    // Toggle Duty Availability Status for Responders
    public function toggleAvailability(Request $request)
    {
        $user = Auth::user();
        $user->is_available = !$user->is_available;
        $user->save();

        return redirect()->back()->with('success', 'Duty availability status updated.');
    }

    // Fire Incident Reporting: searchable log of every incident
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'stage' => 'nullable|in:'.implode(',', Incident::STAGES),
            'severity' => 'nullable|in:'.implode(',', Incident::SEVERITIES),
            'category' => 'nullable|string|max:50',
        ]);

        $incidents = Incident::query()
            ->withCount(['apparatuses', 'personnel'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->whereLike('title', "%{$term}%")
                ->orWhereLike('location_address', "%{$term}%")
                ->orWhereLike('caller_name', "%{$term}%")))
            ->when($filters['severity'] ?? null, fn ($q, $v) => $q->where('severity', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['stage'] ?? null, fn ($q, $stage) => match ($stage) {
                'Reported' => $q->where('status', 'Pending'),
                'Dispatched' => $q->where('status', 'Dispatched')->whereNull('arrived_at'),
                'On Scene' => $q->where('status', 'Dispatched')->whereNotNull('arrived_at'),
                default => $q->where('status', $stage),
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'total' => Incident::count(),
            'active' => Incident::active()->count(),
            'awaiting_dispatch' => Incident::where('status', 'Pending')->count(),
            'today' => Incident::where('created_at', '>=', now('Asia/Manila')->startOfDay()->utc())->count(),
        ];

        return view('incidents.index', compact('incidents', 'counts', 'filters'));
    }

    // Incident detail: overview, timeline, deployed resources and report
    public function show(Incident $incident)
    {
        $incident->load(['reporter', 'apparatuses', 'personnel', 'updates.user']);

        return view('incidents.show', compact('incident'));
    }

    // Admin: Create New Emergency Call View
    public function create()
    {
        return view('incidents.create');
    }

    // Admin: Store Incident Call (awaits unit assignment in Rescue Operation Dispatch)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|in:'.implode(',', Incident::CATEGORIES),
            'severity' => 'required|in:'.implode(',', Incident::SEVERITIES),
            'location_address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'required|string|max:5000',
            'caller_name' => 'nullable|string|max:255',
            'caller_contact' => 'nullable|string|max:50',
        ]);

        $incident = Incident::create($validated + [
            'user_id' => Auth::id(),
            'category' => $validated['category'] ?? 'Structural Fire',
            'status' => 'Pending',
        ]);

        $incident->log('Reported', 'Emergency call logged.', Auth::user());

        return redirect()->route('dispatch.index', ['incident' => $incident->id])
            ->with('success', 'Incident logged. Assign units and personnel to dispatch.');
    }

    // Firefighter / Admin: Update response stage
    public function updateStatus(Request $request, Incident $incident)
    {
        // Legacy status values from older forms map onto response stages
        $request->merge(['status' => match ($request->input('status')) {
            'Pending' => 'Reported',
            default => $request->input('status'),
        }]);

        $request->validate([
            'status' => 'required|in:'.implode(',', array_slice(Incident::STAGES, 1)),
            'note' => 'nullable|string|max:1000',
        ]);

        if (! in_array($request->status, $incident->nextStages(), true)) {
            return redirect()->back()->with('error', "This incident is already {$incident->stage()}.");
        }

        $incident->advanceTo($request->status, Auth::user(), $request->input('note'));

        return redirect()->back()->with('success', "Incident marked {$request->status}.");
    }

    // Firefighter: Submit Final After-Action Incident Report with AI summary
    public function submitFinalReport(Request $request, Incident $incident, AiService $ai)
    {
        $request->validate([
            'after_action_report' => 'required|string|min:5|max:10000',
        ]);

        $rawReport = trim($request->input('after_action_report'));

        $aiSummary = [
            'cause_of_fire' => 'Not specified',
            'time_of_arrival' => 'Not specified',
            'time_fire_subdued' => 'Not specified',
            'casualties' => 'Not specified',
            'operational_overview' => 'No operational details were provided.',
        ];

        $decoded = $ai->json(
            "You are an information extraction assistant for a Fire and Rescue Services Management System.\n" .
            "Extract ONLY explicitly stated facts from the firefighter's report. Do not guess, infer, or invent missing information.\n" .
            "Return a JSON object with exactly these string keys: cause_of_fire, time_of_arrival, time_fire_subdued, casualties, operational_overview.\n" .
            "If a fact is missing, use 'Not specified' (or 'No operational details were provided.' for operational_overview).\n" .
            "operational_overview must be 1-2 sentences.",
            "FIREFIGHTER REPORT:\n\"{$rawReport}\"",
            1000
        );

        if (is_array($decoded)) {
            foreach ($aiSummary as $key => $default) {
                if (isset($decoded[$key]) && is_scalar($decoded[$key]) && trim((string) $decoded[$key]) !== '') {
                    $aiSummary[$key] = trim((string) $decoded[$key]);
                }
            }
        }

        $formattedSummary =
            "• Cause of Fire: " . $aiSummary['cause_of_fire'] . "\n" .
            "• Time of Arrival: " . $aiSummary['time_of_arrival'] . "\n" .
            "• Time of Fire Subdue: " . $aiSummary['time_fire_subdued'] . "\n" .
            "• Casualties: " . $aiSummary['casualties'] . "\n" .
            "• Operational Overview: " . $aiSummary['operational_overview'];

        $incident->update([
            'after_action_report' => $rawReport,
            'ai_summary' => $formattedSummary,
        ]);

        if ($incident->status !== 'Resolved') {
            $incident->advanceTo('Resolved', Auth::user(), 'After-action report filed.');
        } else {
            $incident->log(null, 'After-action report updated.', Auth::user());
        }

        return redirect()->back()->with('success', 'After-action report submitted.');
    }
}
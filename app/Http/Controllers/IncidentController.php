<?php

namespace App\Http\Controllers;

use App\Models\Apparatus;
use App\Models\Backlog;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        return view('dashboards.admin', $this->commandCenter());
    }

    // Everything the admin command center shows: KPIs, charts, readiness and activity
    private function commandCenter(): array
    {
        $tz = 'Asia/Manila';
        $now = now($tz);

        $all = Incident::get(['id', 'category', 'severity', 'status', 'created_at', 'dispatched_at', 'arrived_at', 'controlled_at', 'resolved_at']);
        $local = fn ($i) => $i->created_at->timezone($tz);

        $active = Incident::active()
            ->with([
                'apparatuses' => fn ($q) => $q->wherePivotNull('released_at'),
                'personnel' => fn ($q) => $q->wherePivotNull('released_at'),
            ])
            ->orderByRaw("CASE severity WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        $avg = function ($values) {
            $values = $values->filter(fn ($v) => $v !== null);

            return $values->isEmpty() ? null : (int) round($values->avg());
        };

        // Fleet: apparatus statuses are free text, so fold them into four states
        $unitState = fn ($status) => match (strtolower((string) $status)) {
            'available', 'in service' => 'Available',
            'dispatched', 'deployed' => 'Deployed',
            'maintenance', 'under maintenance' => 'Maintenance',
            default => 'Out of Service',
        };
        $fleet = Apparatus::orderBy('call_sign')->get()->each(fn ($unit) => $unit->state = $unitState($unit->status));

        $crew = User::role('Firefighter');
        $resolved = $all->where('status', 'Resolved')->count();
        $last30 = $all->filter(fn ($i) => $i->created_at->gte(now()->subDays(30)))->count();

        $stats = [
            'total' => $all->count(),
            'today' => $all->filter(fn ($i) => $local($i)->isSameDay($now))->count(),
            'last_30' => $last30,
            'prev_30' => $all->filter(fn ($i) => $i->created_at->between(now()->subDays(60), now()->subDays(30)))->count(),
            'active' => $active->count(),
            'awaiting' => $active->where('status', 'Pending')->count(),
            'resolved' => $resolved,
            'resolution_rate' => $all->count() ? (int) round($resolved / $all->count() * 100) : 0,
            'avg_dispatch' => $avg($all->map->dispatchMinutes()),
            'avg_response' => $avg($all->map->responseMinutes()),
            'avg_control' => $avg($all->map(fn ($i) => $i->controlled_at && $i->arrived_at ? (int) round($i->arrived_at->diffInMinutes($i->controlled_at)) : null)),
            'units_total' => $fleet->count(),
            'units_available' => $fleet->where('state', 'Available')->count(),
            'crew_total' => (clone $crew)->count(),
            'crew_available' => (clone $crew)->where('is_available', true)->count(),
            'crew_deployed' => (clone $crew)->whereHas('activeAssignments')->count(),
            'crew_standby' => (clone $crew)->where('is_available', true)->whereDoesntHave('activeAssignments')->count(),
        ];

        // Call volume: last 12 weeks and last 12 months
        $trend = [
            'weekly' => collect(range(11, 0))->map(function ($w) use ($now, $all, $local) {
                $start = $now->copy()->startOfWeek()->subWeeks($w);
                $count = $all->filter(fn ($i) => $local($i)->between($start, $start->copy()->endOfWeek()))->count();

                return ['label' => $start->format('M j'), 'tip' => 'Week of '.$start->format('M j, Y'), 'value' => $count];
            })->all(),
            'monthly' => collect(range(11, 0))->map(function ($m) use ($now, $all, $local) {
                $start = $now->copy()->startOfMonth()->subMonths($m);
                $count = $all->filter(fn ($i) => $local($i)->isSameMonth($start))->count();

                return ['label' => $start->format('M'), 'tip' => $start->format('F Y'), 'value' => $count];
            })->all(),
        ];

        $byHour = $all->groupBy(fn ($i) => (int) $local($i)->format('G'))->map->count();
        $hourly = collect(range(0, 23))->map(fn ($h) => [
            'label' => Carbon::createFromTime($h)->format('ga'),
            'tip' => Carbon::createFromTime($h)->format('g:00 A').' – '.Carbon::createFromTime($h)->format('g:59 A'),
            'value' => $byHour->get($h, 0),
        ])->all();

        $severity = collect(Incident::SEVERITIES)->reverse()
            ->mapWithKeys(fn ($s) => [$s => $all->where('severity', $s)->count()])->all();

        // Top categories, the long tail folded into "Other"
        $categories = $all->groupBy(fn ($i) => $i->category ?: 'Other')->map->count()->sortDesc();
        if ($categories->count() > 6) {
            $top = $categories->except('Other')->take(5);
            $categories = $top->put('Other', $categories->sum() - $top->sum());
        }

        $pipeline = collect(array_slice(Incident::STAGES, 0, 4))
            ->mapWithKeys(fn ($stage) => [$stage => $active->filter(fn ($i) => $i->stage() === $stage)->count()])->all();

        $equipment = collect(['Available', 'Assigned', 'In Maintenance', 'Decommissioned'])
            ->mapWithKeys(fn ($s) => [$s => (int) Equipment::where('status', $s)->sum('quantity')])->all();

        $backlog = [
            'open' => Backlog::where('status', '!=', 'Resolved')->count(),
            'by_priority' => collect(['Critical', 'High', 'Medium', 'Low'])
                ->mapWithKeys(fn ($p) => [$p => Backlog::where('status', '!=', 'Resolved')->where('priority', $p)->count()])->all(),
        ];

        return [
            'stats' => $stats,
            'active' => $active,
            'trend' => $trend,
            'hourly' => $hourly,
            'severity' => $severity,
            'categories' => $categories->all(),
            'pipeline' => $pipeline,
            'fleet' => $fleet,
            'equipment' => $equipment,
            'backlog' => $backlog,
            'feed' => IncidentUpdate::with(['incident', 'user'])->latest()->latest('id')->take(8)->get(),
            'recentResolved' => Incident::where('status', 'Resolved')->latest()->take(6)->get(),
            'tz' => $tz,
        ];
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
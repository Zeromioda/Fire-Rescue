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
            $activeIncidents = Incident::whereIn('status', ['Dispatched', 'Under Control'])->latest()->get();
            $resolvedIncidents = Incident::where('status', 'Resolved')->latest()->take(5)->get();
            return view('dashboards.firefighter', compact('activeIncidents', 'resolvedIncidents'));
        }

        // 2. Admin / Dispatcher View
        $incidents = Incident::with('reporter')->latest()->get();
        $stats = [
            'total' => Incident::count(),
            'active' => Incident::whereIn('status', ['Pending', 'Dispatched', 'Under Control'])->count(),
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

    // Admin: Create New Emergency Call View
    public function create()
    {
        return view('incidents.create');
    }

    // Admin: Store Incident Call
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'severity' => 'required|string',
            'location_address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string',
        ]);

        Incident::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'severity' => $request->severity,
            'location_address' => $request->location_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'status' => 'Dispatched',
        ]);

        return redirect()->route('dashboard')->with('success', '🚨 Incident dispatched to response teams!');
    }

    // Firefighter / Admin: Update On-Scene Status
    public function updateStatus(Request $request, Incident $incident)
    {
        $request->validate(['status' => 'required|string']);
        $incident->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Incident status updated.');
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
            'status' => 'Resolved',
            'after_action_report' => $rawReport,
            'ai_summary' => $formattedSummary,
        ]);

        return redirect()->back()->with('success', '🚨 Report submitted successfully!');
    }
}
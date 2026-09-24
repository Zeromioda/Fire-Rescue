<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    // Firefighter: Submit Final After-Action Incident Report using Gemini AI
    public function submitFinalReport(Request $request, Incident $incident)
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

        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');

        if (!empty($apiKey)) {
            try {
                $prompt = "You are an information extraction assistant for a Fire and Rescue Services Management System.\n" .
                    "Extract ONLY explicitly stated facts from this report. Do not guess, infer, or invent missing information.\n\n" .
                    "Return valid JSON only with these keys: cause_of_fire, time_of_arrival, time_fire_subdued, casualties, operational_overview.\n" .
                    "If a fact is missing, set the value to 'Not specified' or 'No operational details were provided.'\n\n" .
                    "FIRE FIGHTER REPORT:\n\"{$rawReport}\"";

                $response = Http::timeout(30)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $apiKey,
                    ])
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                        [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt],
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'temperature' => 0.0,
                                'maxOutputTokens' => 1000,
                                'responseMimeType' => 'application/json',
                                'responseSchema' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        'cause_of_fire' => ['type' => 'STRING', 'description' => 'Explicit cause or "Not specified"'],
                                        'time_of_arrival' => ['type' => 'STRING', 'description' => 'Explicit arrival time or "Not specified"'],
                                        'time_fire_subdued' => ['type' => 'STRING', 'description' => 'Explicit subdue time or "Not specified"'],
                                        'casualties' => ['type' => 'STRING', 'description' => 'Explicit casualties or "Not specified"'],
                                        'operational_overview' => ['type' => 'STRING', 'description' => '1-2 sentence overview or "No operational details were provided."'],
                                    ],
                                    'required' => [
                                        'cause_of_fire',
                                        'time_of_arrival',
                                        'time_fire_subdued',
                                        'casualties',
                                        'operational_overview',
                                    ],
                                ],
                            ],
                        ]
                    );

                if ($response->successful()) {
                    $parts = $response->json('candidates.0.content.parts', []);
                    $generatedText = '';

                    if (is_array($parts)) {
                        foreach ($parts as $part) {
                            if (is_array($part) && isset($part['text'])) {
                                $generatedText .= $part['text'];
                            }
                        }
                    }

                    if (empty($generatedText)) {
                        $generatedText = (string) $response->json('candidates.0.content.parts.0.text', '');
                    }

                    if (!empty($generatedText)) {
                        $cleanText = preg_replace('/^```(?:json)?\s*/i', '', trim($generatedText));
                        $cleanText = preg_replace('/\s*```$/', '', $cleanText);

                        $decoded = json_decode($cleanText, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            foreach ($aiSummary as $key => $default) {
                                if (isset($decoded[$key]) && $decoded[$key] !== null && trim((string) $decoded[$key]) !== '') {
                                    $aiSummary[$key] = trim((string) $decoded[$key]);
                                }
                            }
                        } else {
                            Log::warning('Gemini summary response was not valid JSON.', ['raw_response' => $generatedText]);
                        }
                    }
                } else {
                    Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
                }
            } catch (\Throwable $e) {
                Log::error('Gemini Exception', ['message' => $e->getMessage()]);
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
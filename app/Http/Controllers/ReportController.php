<?php

namespace App\Http\Controllers;

use App\Models\AiReport;
use App\Models\Incident;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public const PERIODS = [
        '7' => 'Last 7 Days',
        '30' => 'Last 30 Days',
        '90' => 'Last 90 Days',
        '365' => 'Last 12 Months',
        'all' => 'All Time',
    ];

    private const TIMEZONE = 'Asia/Manila';

    // Admin: Analysis & Reporting dashboard
    public function index(Request $request, AiService $ai)
    {
        $period = $this->period($request);
        [$start, $end] = $this->range($period);

        $incidents = $this->incidents($start);
        $stats = $this->stats($incidents);

        $report = $request->filled('report')
            ? AiReport::with('generatedBy')->find($request->integer('report'))
            : AiReport::with('generatedBy')->where('period', $period)->latest()->first();

        $history = AiReport::with('generatedBy')->latest()->take(10)->get();

        return view('reports.index', [
            'period' => $period,
            'periods' => self::PERIODS,
            'start' => $start,
            'end' => $end,
            'stats' => $stats,
            'report' => $report,
            'history' => $history,
            'aiReady' => $ai->isConfigured(),
            'timezone' => self::TIMEZONE,
        ]);
    }

    // Admin: Ask the AI to analyse the selected period and store the result
    public function generate(Request $request, AiService $ai)
    {
        $period = $this->period($request);
        [$start, $end] = $this->range($period);

        $incidents = $this->incidents($start);

        if ($incidents->isEmpty()) {
            return redirect()->route('reports.index', ['period' => $period])
                ->with('error', 'There are no incidents in this period to analyse.');
        }

        if (! $ai->isConfigured()) {
            return redirect()->route('reports.index', ['period' => $period])
                ->with('error', 'AI analysis is not configured. Please set AI_API_KEY.');
        }

        $stats = $this->stats($incidents);

        $analysis = $ai->json(
            $this->systemPrompt(),
            json_encode([
                'station' => 'BFAD Station 178, Camarin, Zone 15, District III, Caloocan City',
                'reporting_period' => [
                    'label' => self::PERIODS[$period],
                    'from' => $start?->timezone(self::TIMEZONE)->toDateString(),
                    'to' => $end->timezone(self::TIMEZONE)->toDateString(),
                ],
                'statistics' => $stats,
                'incidents' => $this->digest($incidents),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            3000
        );

        $analysis = $this->normalise($analysis);

        if ($analysis === null) {
            return redirect()->route('reports.index', ['period' => $period])
                ->with('error', 'The AI analysis could not be generated right now. Please try again in a moment.');
        }

        $report = AiReport::create([
            'user_id' => Auth::id(),
            'period' => $period,
            'period_start' => $start,
            'period_end' => $end,
            'incident_count' => $incidents->count(),
            'stats' => $stats,
            'analysis' => $analysis,
        ]);

        return redirect()->route('reports.index', ['period' => $period, 'report' => $report->id])
            ->with('success', 'AI analysis report generated.');
    }

    private function period(Request $request): string
    {
        $period = (string) $request->input('period', '30');

        return array_key_exists($period, self::PERIODS) ? $period : '30';
    }

    /** @return array{0: ?Carbon, 1: Carbon} */
    private function range(string $period): array
    {
        $end = now();
        $start = $period === 'all' ? null : now()->subDays((int) $period)->startOfDay();

        return [$start, $end];
    }

    private function incidents(?Carbon $start): Collection
    {
        return Incident::query()
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->latest()
            ->get();
    }

    private function stats(Collection $incidents): array
    {
        $total = $incidents->count();
        $resolved = $incidents->where('status', 'Resolved');

        // Approximation: a resolved incident's last update is when its final report was filed
        $resolutionHours = $resolved
            ->map(fn ($i) => $i->created_at && $i->updated_at ? $i->created_at->diffInMinutes($i->updated_at) / 60 : null)
            ->filter(fn ($h) => $h !== null);

        $byHour = $incidents
            ->groupBy(fn ($i) => $i->created_at->timezone(self::TIMEZONE)->format('H'))
            ->map->count()
            ->sortDesc();

        $byDay = $incidents
            ->groupBy(fn ($i) => $i->created_at->timezone(self::TIMEZONE)->format('l'))
            ->map->count()
            ->sortDesc();

        return [
            'total' => $total,
            'active' => $incidents->whereIn('status', ['Pending', 'Dispatched', 'Under Control'])->count(),
            'resolved' => $resolved->count(),
            'resolution_rate' => $total ? round($resolved->count() / $total * 100, 1) : 0,
            'avg_resolution_hours' => $resolutionHours->isNotEmpty() ? round($resolutionHours->avg(), 1) : null,
            'with_after_action_report' => $incidents->whereNotNull('after_action_report')->count(),
            'by_status' => $this->countBy($incidents, 'status', ['Pending', 'Dispatched', 'Under Control', 'Resolved']),
            'by_severity' => $this->countBy($incidents, 'severity', ['Critical', 'High', 'Medium', 'Low']),
            'by_category' => $incidents->groupBy(fn ($i) => $i->category ?: 'Uncategorized')->map->count()->sortDesc()->all(),
            'top_locations' => $incidents->groupBy('location_address')->map->count()->sortDesc()->take(5)->all(),
            'busiest_hour' => $byHour->isNotEmpty() ? $byHour->keys()->first().':00' : null,
            'busiest_day' => $byDay->keys()->first(),
        ];
    }

    private function countBy(Collection $incidents, string $field, array $order): array
    {
        $counts = $incidents->groupBy(fn ($i) => $i->{$field} ?: 'Unspecified')->map->count();

        // Known values first in a fixed order, then anything unexpected
        return collect($order)
            ->mapWithKeys(fn ($key) => [$key => $counts->get($key, 0)])
            ->merge($counts->except($order))
            ->all();
    }

    // Compact per-incident context for the AI (capped to keep the prompt small)
    private function digest(Collection $incidents): array
    {
        return $incidents->take(60)->map(fn ($i) => [
            'id' => $i->id,
            'reported_at' => $i->created_at->timezone(self::TIMEZONE)->format('Y-m-d H:i'),
            'title' => $i->title,
            'category' => $i->category,
            'severity' => $i->severity,
            'status' => $i->status,
            'location' => $i->location_address,
            'description' => Str::limit((string) $i->description, 300),
            'after_action_summary' => $i->ai_summary ?: ($i->after_action_report ? Str::limit($i->after_action_report, 400) : null),
        ])->values()->all();
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior fire and rescue operations analyst preparing an official analysis report for a Philippine Bureau of Fire Protection station commander.
Analyse ONLY the statistics and incident records provided. Never invent incidents, numbers, times, casualties, or causes. If the data is too thin to support a conclusion, say so.
Write in clear, professional English suitable for a command briefing.

Return a JSON object with exactly these keys:
- "executive_summary": string, 3 to 5 sentences summarising the period.
- "key_findings": array of 3 to 6 short strings, each citing the supporting numbers.
- "risk_patterns": array of strings describing recurring causes, locations, times, or severities worth watching (empty array if none are supported by the data).
- "operational_performance": string, 2 to 4 sentences on response and resolution performance, including open cases and missing after-action reports.
- "recommendations": array of objects {"priority": "High" | "Medium" | "Low", "action": string}, 3 to 6 concrete, actionable items.
- "data_limitations": array of strings noting gaps in the data that limit this analysis.
PROMPT;
    }

    // Guard against missing or mistyped keys so the view can rely on the shape
    private function normalise(?array $analysis): ?array
    {
        if (! $analysis || ! is_string($analysis['executive_summary'] ?? null)) {
            return null;
        }

        $strings = fn ($value) => collect(is_array($value) ? $value : [])
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->values()
            ->all();

        return [
            'executive_summary' => trim($analysis['executive_summary']),
            'key_findings' => $strings($analysis['key_findings'] ?? []),
            'risk_patterns' => $strings($analysis['risk_patterns'] ?? []),
            'operational_performance' => is_string($analysis['operational_performance'] ?? null) ? trim($analysis['operational_performance']) : '',
            'recommendations' => collect(is_array($analysis['recommendations'] ?? null) ? $analysis['recommendations'] : [])
                ->map(fn ($r) => is_array($r) ? [
                    'priority' => in_array($r['priority'] ?? '', ['High', 'Medium', 'Low'], true) ? $r['priority'] : 'Medium',
                    'action' => trim((string) ($r['action'] ?? '')),
                ] : ['priority' => 'Medium', 'action' => trim((string) $r)])
                ->filter(fn ($r) => $r['action'] !== '')
                ->values()
                ->all(),
            'data_limitations' => $strings($analysis['data_limitations'] ?? []),
        ];
    }
}

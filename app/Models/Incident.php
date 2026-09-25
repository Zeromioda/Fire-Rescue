<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Incident extends Model
{
    use HasFactory;

    /** Response stages in order. Stored status values are Pending, Dispatched, Under Control, Resolved. */
    public const STAGES = ['Reported', 'Dispatched', 'On Scene', 'Under Control', 'Resolved'];

    public const ACTIVE_STATUSES = ['Pending', 'Dispatched', 'Under Control'];

    public const CATEGORIES = [
        'Structural Fire', 'Electrical Fire', 'Commercial Fire', 'Vehicle Fire', 'Grass Fire',
        'Rubbish Fire', 'Gas Leak', 'Rescue', 'Other',
    ];

    public const SEVERITIES = ['Low', 'Medium', 'High', 'Critical'];

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'severity',
        'location_address',
        'latitude',
        'longitude',
        'description',
        'caller_name',
        'caller_contact',
        'status',
        'after_action_report',
        'ai_summary',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'arrived_at' => 'datetime',
            'controlled_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function apparatuses()
    {
        return $this->belongsToMany(Apparatus::class, 'incident_apparatus')
            ->withPivot(['dispatched_at', 'released_at'])
            ->withTimestamps();
    }

    public function personnel()
    {
        return $this->belongsToMany(User::class, 'incident_personnel')
            ->withPivot(['role', 'dispatched_at', 'released_at'])
            ->withTimestamps();
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class)->latest()->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** Current response stage, including "On Scene" which is tracked by arrived_at. */
    public function stage(): string
    {
        return match (true) {
            $this->status === 'Resolved' => 'Resolved',
            $this->status === 'Under Control' => 'Under Control',
            $this->arrived_at !== null => 'On Scene',
            $this->status === 'Dispatched' => 'Dispatched',
            default => 'Reported',
        };
    }

    public function stageIndex(): int
    {
        return array_search($this->stage(), self::STAGES, true);
    }

    /** Stages this incident can still move to, in order. */
    public function nextStages(): array
    {
        return array_slice(self::STAGES, $this->stageIndex() + 1);
    }

    /**
     * Move the incident forward to a response stage, filling in any skipped
     * milestones, logging the change, and releasing resources when resolved.
     */
    public function advanceTo(string $stage, ?User $by = null, ?string $note = null): void
    {
        $now = now();

        if ($stage === 'Dispatched' || $stage === 'On Scene' || $stage === 'Under Control' || $stage === 'Resolved') {
            $this->dispatched_at ??= $now;
            $this->status = $this->status === 'Pending' ? 'Dispatched' : $this->status;
        }
        if ($stage === 'On Scene' || $stage === 'Under Control' || $stage === 'Resolved') {
            $this->arrived_at ??= $now;
        }
        if ($stage === 'Under Control' || $stage === 'Resolved') {
            $this->controlled_at ??= $now;
            $this->status = 'Under Control';
        }
        if ($stage === 'Resolved') {
            $this->resolved_at ??= $now;
            $this->status = 'Resolved';
        }

        $this->save();
        $this->log($stage, $note, $by);

        if ($stage === 'Resolved') {
            $this->releaseResources();
        }
    }

    public function log(?string $stage, ?string $note, ?User $by = null): IncidentUpdate
    {
        return $this->updates()->create([
            'user_id' => $by?->id,
            'stage' => $stage,
            'note' => filled($note) ? trim($note) : null,
        ]);
    }

    /** Return assigned apparatus and personnel to service once the incident is closed. */
    public function releaseResources(): void
    {
        $now = now();

        foreach ($this->apparatuses()->wherePivotNull('released_at')->get() as $unit) {
            $this->apparatuses()->updateExistingPivot($unit->id, ['released_at' => $now]);

            if (! $unit->activeIncidents()->exists()) {
                $unit->update(['status' => 'available']);
            }
        }

        foreach ($this->personnel()->wherePivotNull('released_at')->get() as $person) {
            $this->personnel()->updateExistingPivot($person->id, ['released_at' => $now]);

            if (! $person->activeAssignments()->exists()) {
                $person->forceFill(['is_available' => true])->save();
            }
        }
    }

    /** Minutes from the call being logged to the first unit arriving. */
    public function responseMinutes(): ?int
    {
        return $this->arrived_at ? (int) round($this->created_at->diffInMinutes($this->arrived_at)) : null;
    }

    public function dispatchMinutes(): ?int
    {
        return $this->dispatched_at ? (int) round($this->created_at->diffInMinutes($this->dispatched_at)) : null;
    }

    public static function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        return $minutes >= 60 ? intdiv($minutes, 60).'h '.($minutes % 60).'m' : $minutes.'m';
    }

    public function severityClasses(): string
    {
        return match ($this->severity) {
            'Critical', 'High' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
            'Medium' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            default => 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
        };
    }

    public static function stageClasses(string $stage): string
    {
        return match ($stage) {
            'Resolved' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'Under Control' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
            'On Scene' => 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
            'Dispatched' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            default => 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        };
    }

    public function milestone(string $stage): ?Carbon
    {
        return match ($stage) {
            'Reported' => $this->created_at,
            'Dispatched' => $this->dispatched_at,
            'On Scene' => $this->arrived_at,
            'Under Control' => $this->controlled_at,
            'Resolved' => $this->resolved_at,
        };
    }
}

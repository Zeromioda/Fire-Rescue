<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apparatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_sign',
        'plate_number',
        'type',
        'status',
        'fuel_level_percent',
    ];

    public function incidents()
    {
        return $this->belongsToMany(Incident::class, 'incident_apparatus')
            ->withPivot(['dispatched_at', 'released_at'])
            ->withTimestamps();
    }

    /** Incidents this unit is currently committed to. */
    public function activeIncidents()
    {
        return $this->incidents()->wherePivotNull('released_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'period_start',
        'period_end',
        'incident_count',
        'stats',
        'analysis',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'stats' => 'array',
            'analysis' => 'array',
        ];
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

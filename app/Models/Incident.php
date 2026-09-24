<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id',
    'title',
    'severity',
    'location_address',
    'latitude',
    'longitude',
    'description',
    'status',
    'after_action_report',
    'ai_summary',
];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
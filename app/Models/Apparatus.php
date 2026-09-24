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
}
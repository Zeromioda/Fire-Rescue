<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'category',
        'status',
        'quantity',
        'asset_tag',
        'serial_number',
        'condition',
        'assigned_to_user_id',
        'expiration_date',
    ];
}
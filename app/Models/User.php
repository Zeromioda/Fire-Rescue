<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // 1. Add this import

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // 2. Add HasRoles trait here

    protected $fillable = [
        'name',
        'email',
        'badge_number',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function assignments()
    {
        return $this->belongsToMany(Incident::class, 'incident_personnel')
            ->withPivot(['role', 'dispatched_at', 'released_at'])
            ->withTimestamps();
    }

    /** Incidents this responder is currently deployed to. */
    public function activeAssignments()
    {
        return $this->assignments()->wherePivotNull('released_at');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
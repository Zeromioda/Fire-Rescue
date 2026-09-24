<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backlog extends Model
{
    protected $guarded = [];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
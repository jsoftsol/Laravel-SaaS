<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Project;
use App\Models\User;

class Task extends Model
{
    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser() {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

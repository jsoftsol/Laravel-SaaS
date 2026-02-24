<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Project;

class Company extends Model
{
    public function users(){
        return $this->hasMany(User::class);
    }

    public function projects() {
        return $this->hasMany(Project::class);
    }
}

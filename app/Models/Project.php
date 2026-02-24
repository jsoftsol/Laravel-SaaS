<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Company;
use App\Models\Task;

class Project extends Model
{
    public function company() {
        return $this->belongsTo(Company::class);
    }

     public function tasks() {
        return $this->hasMany(Task::class);
     }
}

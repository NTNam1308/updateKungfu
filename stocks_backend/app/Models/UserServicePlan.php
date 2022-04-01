<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserServicePlan extends Model
{
    protected $table = 'user_service_plan';
    protected $guarded = ['id']; 
    public $timestamps = false; 
}

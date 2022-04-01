<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Watchlist extends Model
{
    use HasFactory;

    public $timestamps = false; 
    protected $fillable = [
        'mack', 'user_id', 'my_watchlist_id', 'note','index'
    ];

    public function user() {
        return $this->belongsTo(User::class, "user_id");
    }
    
}

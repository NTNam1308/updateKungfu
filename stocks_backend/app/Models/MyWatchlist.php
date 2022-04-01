<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Watchlist;

class MyWatchlist extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function macks() {
        return $this->hasMany(Watchlist::class, 'my_watchlist_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KungfuNews extends Model
{
    protected $table = 'kungfu_news';
    public $timestamps = false; 
    protected $fillable = [
        'mack', 'title', 'slug', 'content', 'category_id', 'date', 'thumbnail'
    ];

    public function category()
    {
        return $this->hasMany(CategoryNews::class,'category_id','id');
    }
}

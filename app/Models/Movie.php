<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;
    protected $casts = [
        'id_categories' => 'array',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'categories_movies');
    }
    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}

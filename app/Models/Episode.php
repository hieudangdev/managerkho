<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;
    protected $fillable = ['movie_id', 'title', 'video_url', 'episode_number'];
    
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}

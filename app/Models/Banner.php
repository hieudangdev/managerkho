<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'redirect_url',
        'img_url',
        'status',
        'start_day',
        'end_day',
        'duration',
        'bookmaker_id'
    ];

    protected $casts = [
        'position' => 'array',
        'redirect_url' => 'array',
        'img_url' => 'array'
    ];

    public function domains()   
    {
        return $this->belongsToMany(Domain::class, 'banner_domain');
    }

    public function bookmaker()
    {
        return $this->belongsTo(Bookmaker::class, 'bookmaker_id');
    }
}

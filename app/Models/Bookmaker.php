<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmaker extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Quan hệ: Một Bookmaker có nhiều Banners
    public function banners()
    {
        return $this->hasMany(Banner::class, 'bookmaker_id');
    }

    public function tvcs()
    {
        return $this->hasMany(Tvc::class, 'bookmaker_id');
    }
}

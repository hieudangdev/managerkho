<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'campaign_domain');
    }
}

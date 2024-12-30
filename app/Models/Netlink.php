<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Netlink extends Model
{
    use HasFactory;
    protected $table = 'netlink';
    protected $casts = [
        'redirect_url' => 'array',
    ];

    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'netlink_domain');
    }
}

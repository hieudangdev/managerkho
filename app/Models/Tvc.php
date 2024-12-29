<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tvc extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'redirect_url',
        'video_url',
        'time_skip',
        'status',
        'start_day',
        'end_day',
        'duration'
    ];

    // Định nghĩa mối quan hệ với bảng 'domains' (many-to-many)
    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_tvc');
    }

    public function bookmaker()
    {
        return $this->belongsTo(Bookmaker::class, 'bookmaker_id');
    }
}

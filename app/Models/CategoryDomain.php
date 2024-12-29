<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    public $timestamps = true;

    /**
     * Quan hệ với Domain (hasMany).
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'category_id');
    }
}

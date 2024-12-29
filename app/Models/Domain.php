<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;

class Domain extends Model
{
    use HasFactory;
    protected $fillable = [
        'domain_url',
        'owner',
        'status',
    ];

    // Thêm quan hệ với AdminUser
    public function adminUser()
    {
        return $this->belongsTo(Administrator::class, 'owner', 'id');
    }

    /**
     * Quan hệ với CategoryDomain.
     */
    public function category()
    {
        return $this->belongsTo(CategoryDomain::class, 'category_id');
    }
}

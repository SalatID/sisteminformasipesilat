<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ts extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'alias',
        'multiplier',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
    ];

    // Relations
    public function coaches()
    {
        return $this->hasMany(Coach::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContributionDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'contribution_id',
        'coach_id',
        'multiplier',
        'attendance',
        'is_pj',
        'final_value',
        'amount_per_attendance',
        'total',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'attendance' => 'integer',
        'is_pj' => 'integer',
        'final_value' => 'decimal:2',
        'amount_per_attendance' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relations
    public function contribution()
    {
        return $this->belongsTo(Contribution::class);
    }

    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contribution extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'contribution_receipt_img',
        'periode',
        'contribution_amount',
        'pj_share',
        'pj_percentage',
        'kas_share',
        'kas_percentage',
        'saving_share',
        'saving_percentage',
        'difference',
        'revision_count',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'contribution_amount' => 'decimal:2',
        'pj_share' => 'decimal:2',
        'pj_percentage' => 'decimal:2',
        'kas_share' => 'decimal:2',
        'kas_percentage' => 'decimal:2',
        'saving_share' => 'decimal:2',
        'saving_percentage' => 'decimal:2',
        'difference' => 'decimal:2',
        'revision_count' => 'integer',
    ];

    // Relations
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function contributionDetails()
    {
        return $this->hasMany(ContributionDetail::class);
    }
}

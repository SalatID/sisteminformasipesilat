<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberPerformanceRecord extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $table = 'member_performance_records';

    protected $fillable = [
        'member_id',
        'training_center_id',
        'training_date',
        'training_type',
        'endurance',
        'strength',
        'technique',
        'attended',
        'kas',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'training_date' => 'date',
        'attended' => 'boolean',
        'kas' => 'boolean',
    ];

    // Relations
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function trainingCenter()
    {
        return $this->belongsTo(TrainingCenter::class);
    }

    // Scopes
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('training_date', $date);
    }

    public function scopeForUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    // Get average performance
    public function scopeAveragePerformance($query)
    {
        return $query->selectRaw('
            ROUND(AVG(endurance), 2) as avg_endurance,
            ROUND(AVG(strength), 2) as avg_strength,
            ROUND(AVG(technique), 2) as avg_technique,
            COUNT(*) as total_records
        ');
    }
}

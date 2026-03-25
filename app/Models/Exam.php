<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'examinee_id',
        'examinee_type',
        'exam_date',
        'exam_end_date',
        'exam_location',
        'organizer',
        'ts_before',
        'ts_after',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'exam_end_date' => 'date',
    ];

    // Polymorphic relationship
    public function examinee()
    {
        return $this->morphTo();
    }

    // Relationship to ts_before
    public function tsBefore()
    {
        return $this->belongsTo(Ts::class, 'ts_before');
    }

    // Relationship to ts_after
    public function tsAfter()
    {
        return $this->belongsTo(Ts::class, 'ts_after');
    }

    // Scope for coach exams
    public function scopeForCoach($query, $coachId)
    {
        return $query->where('examinee_type', 'coach')
                     ->where('examinee_id', $coachId);
    }

    // Scope for member exams
    public function scopeForMember($query, $memberId)
    {
        return $query->where('examinee_type', 'member')
                     ->where('examinee_id', $memberId);
    }
}

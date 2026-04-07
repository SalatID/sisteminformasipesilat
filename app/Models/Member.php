<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $table = 'members';

    protected $fillable = [
        'name',
        'birth_date',
        'birth_place',
        'ts_id',
        'joined_date',
        'member_id',
        'unit_id',
        'gender',
        'school_level',
        'picture',
        'citizen_number',
        'family_card_number',
        'bpjs_number',
        'citizen_img',
        'family_card_img',
        'bpjs_img',
        'registration_status',
        'is_self_registered',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'joined_date' => 'date',
        'approved_at' => 'datetime',
        'is_self_registered' => 'boolean',
    ];

    // Relations
    public function ts()
    {
        return $this->belongsTo(Ts::class, 'ts_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function attendanceDetails()
    {
        return $this->hasMany(MemberAttendanceDetail::class);
    }

    public function performanceRecords()
    {
        return $this->hasMany(MemberPerformanceRecord::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'examinee_id')->where('examinee_type', 'member');
    }

    public function trainingCenters()
    {
        return $this->belongsToMany(TrainingCenter::class, 'member_training_centers')
            ->withTimestamps()
            ->withPivot(['joined_date']);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes for filtering
    public function scopePending($query)
    {
        return $query->where('registration_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('registration_status', 'approved');
    }

    public function scopeSelfRegistered($query)
    {
        return $query->where('is_self_registered', true);
    }

    // Methods for approval/rejection
    public function approve($approvedBy)
    {
        $this->update([
            'registration_status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject($approvedBy, $reason)
    {
        $this->update([
            'registration_status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function isPending()
    {
        return $this->registration_status === 'pending';
    }

    public function isApproved()
    {
        return $this->registration_status === 'approved';
    }

    public function isRejected()
    {
        return $this->registration_status === 'rejected';
    }
}

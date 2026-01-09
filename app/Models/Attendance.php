<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'attendance_status',
        'attendance_date',
        'new_member_cnt',
        'old_member_cnt',
        'report_maker_id',
        'attendance_image',
        'is_notif_send',
        'created_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'new_member_cnt' => 'integer',
        'old_member_cnt' => 'integer',
    ];

    // Relations
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function reportMaker()
    {
        return $this->belongsTo(Coach::class, 'report_maker_id');
    }
}
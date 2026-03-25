<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberAttendanceDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $table = 'member_attendance_details';

    protected $fillable = [
        'member_id',
        'attendance_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relations
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

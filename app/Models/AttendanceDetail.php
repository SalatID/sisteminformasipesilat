<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'attendance_id',
        'coach_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relations
    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
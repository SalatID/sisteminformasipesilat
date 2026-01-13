<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coach extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $table='coachs';

    protected $fillable = [
        'name',
        'ts_id',
        'coach_exam_date',
        'coach_exam_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'coach_exam_date' => 'date',
    ];

    // Relations
    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function ts()
    {
        return $this->belongsTo(Ts::class, 'ts_id');
    }

    public function coachs()
    {
        return $this->belongsTo(Coach::class);
    }
}
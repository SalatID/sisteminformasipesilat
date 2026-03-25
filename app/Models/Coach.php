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
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [];

    // Relations
    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function contributionDetails()
    {
        return $this->hasMany(ContributionDetail::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'examinee_id')->where('examinee_type', 'coach');
    }

    public function ts()
    {
        return $this->belongsTo(Ts::class, 'ts_id');
    }

    public function coachs()
    {
        return $this->belongsTo(Coach::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'coach_id');
    }
}
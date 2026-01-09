<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'training_day',
        'pj_id',
        'training_hours_start',
        'training_hours_end',
        'paid_fee_type',
        'paid_periode',
        'school_pic_name',
        'school_pic_number',
        'school_level',
        'school_pic_occupation',
        'joined_date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'training_hours_start' => 'datetime:H:i',
        'training_hours_end' => 'datetime:H:i',
        'joined_date' => 'date',
    ];

    // Relations
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function pj()
    {
        return $this->belongsTo(Coach::class, 'pj_id');
    }
}
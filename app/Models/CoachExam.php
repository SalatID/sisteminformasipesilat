<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoachExam extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'coach_id',
        'exam_date',
        'exam_location',
        'result',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public static $resultMap = [
        'passed' => 'Lulus',
        'failed' => 'Gagal',
        'pending' => 'Tertunda',
    ];

    // Relations
    public function coach()
    {
        return $this->belongsTo(Coach::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCenter extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $table = 'training_centers';

    protected $fillable = [
        'name',
        'address',
        'contact_person',
        'phone',
        'email',
        'training_days',
        'training_time',
    ];

    protected $casts = [
        'training_days' => 'array',
    ];

    // Relations
    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_training_centers')
            ->withTimestamps()
            ->withPivot(['joined_date']);
    }
}

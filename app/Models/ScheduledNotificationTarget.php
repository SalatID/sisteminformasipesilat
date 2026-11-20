<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu chat tujuan milik sebuah jadwal notifikasi.
 *
 * Satu jadwal boleh punya banyak target; pesan yang sama dikirim ke semuanya.
 */
class ScheduledNotificationTarget extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'scheduled_notification_id',
        'chat_id',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduledNotification::class, 'scheduled_notification_id');
    }

    public function displayLabel(): string
    {
        return (string) (($this->label ?? '') !== '' ? $this->label : $this->chat_id);
    }
}

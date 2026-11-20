<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotificationRun extends Model
{
    use HasFactory, HasUuids;

    /**
     * Primary key eksplisit: `id` (uuid) bertipe string. Meski HasUuids sudah
     * menangani ini, deklarasi eksplisit mencegah query builder generik
     * memperlakukan `id` sebagai integer (mis. whereIn/whereIntegerInRaw).
     */
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_MISSED = 'missed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_MISSED,
    ];

    protected $fillable = [
        'scheduled_notification_id',
        'occurrence_at',
        'public_code',
        'status',
        'message_snapshot',
        'attempts',
        'next_attempt_at',
        'last_error',
        'provider_message_id',
        'sent_targets',
        'error_targets',
        'sent_at',
        'cancelled_at',
        'cancel_reason',
        'cancel_source',
        'cancelled_by',
        'announced_at',
        'announcement_message_id',
    ];

    protected $casts = [
        'occurrence_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'announced_at' => 'datetime',
        'attempts' => 'integer',
        'sent_targets' => 'array',
        'error_targets' => 'array',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduledNotification::class, 'scheduled_notification_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_SENT => 'Terkirim',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_MISSED => 'Terlewat',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'secondary',
            self::STATUS_SENT => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            self::STATUS_MISSED => 'dark',
            default => 'secondary',
        };
    }
}

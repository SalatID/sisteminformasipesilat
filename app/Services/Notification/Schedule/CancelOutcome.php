<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotificationRun;

/**
 * Hasil eksekusi pembatalan.
 */
class CancelOutcome
{
    public function __construct(
        public readonly string $status,
        public readonly ?ScheduledNotificationRun $run = null,
        public readonly bool $announcementSent = false,
        public readonly ?string $error = null,
    ) {}

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public static function cancelled(ScheduledNotificationRun $run, bool $announcementSent): self
    {
        return new self('cancelled', $run, $announcementSent);
    }

    public static function fromResolution(CancelResolution $resolution): self
    {
        return new self($resolution->status, $resolution->run);
    }

    public static function error(?string $error = null): self
    {
        return new self('error', null, false, $error);
    }
}

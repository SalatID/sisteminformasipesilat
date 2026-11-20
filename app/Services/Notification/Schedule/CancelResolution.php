<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;

/**
 * Hasil resolusi kode/reply menjadi satu run.
 */
class CancelResolution
{
    /**
     * @param  array<int, ScheduledNotificationRun>  $runs
     */
    public function __construct(
        public readonly string $status,
        public readonly ?ScheduledNotificationRun $run = null,
        public readonly array $runs = [],
        public readonly ?string $detail = null,
    ) {}

    public static function ok(ScheduledNotificationRun $run): self
    {
        return new self('ok', $run);
    }

    public static function notFound(): self
    {
        return new self('not_found');
    }

    public static function missingQuote(): self
    {
        return new self('missing_quote');
    }

    /**
     * @param  array<int, ScheduledNotificationRun>  $runs
     */
    public static function scheduleOnly(array $runs): self
    {
        return new self('schedule_only', null, $runs);
    }

    public static function mismatch(): self
    {
        return new self('mismatch');
    }

    public static function already(ScheduledNotificationRun $run): self
    {
        return new self('already', $run);
    }

    public static function past(ScheduledNotificationRun $run): self
    {
        return new self('past', $run);
    }

    public static function inactive(ScheduledNotificationRun $run): self
    {
        return new self('inactive', $run);
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }

    public function schedule(): ?ScheduledNotification
    {
        return $this->run?->schedule;
    }
}

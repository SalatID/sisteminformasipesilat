<?php

namespace App\Services\Notification\Schedule;

/**
 * Hasil parsing perintah `/cancel-schedule`.
 */
class CancelScheduleCommand
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $target,
        public readonly ?string $reason,
        public readonly ?string $error = null,
    ) {}

    public static function invalid(string $error, ?string $target = null): self
    {
        return new self(false, $target, null, $error);
    }

    public static function valid(?string $target, string $reason): self
    {
        return new self(true, $target, $reason);
    }
}

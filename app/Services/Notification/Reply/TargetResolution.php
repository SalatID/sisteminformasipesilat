<?php

namespace App\Services\Notification\Reply;

/**
 * Hasil pemetaan "nomor N" pada pesan notifikasi → sesi latihan.
 */
class TargetResolution
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $type = null,
        public readonly ?HolidayTarget $target = null,
        public readonly int $totalLines = 0,
        public readonly ?string $error = null,
    ) {}

    public static function found(string $type, HolidayTarget $target, int $totalLines): self
    {
        return new self(ok: true, type: $type, target: $target, totalLines: $totalLines);
    }

    /**
     * @param  string  $error  not_notification|not_unit_line|number_not_found|unit_not_found|date_unreadable
     */
    public static function failed(string $error, int $totalLines = 0): self
    {
        return new self(ok: false, error: $error, totalLines: $totalLines);
    }
}

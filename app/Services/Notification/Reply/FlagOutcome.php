<?php

namespace App\Services\Notification\Reply;

/**
 * Hasil eksekusi penandaan libur ke database.
 */
class FlagOutcome
{
    private function __construct(
        public readonly bool $ok,
        public readonly string $action,
        public readonly ?string $error = null,
    ) {}

    public static function done(string $action): self
    {
        return new self(ok: true, action: $action);
    }

    /**
     * @param  string  $error  reject_training|reject_inactive_unit|no_system_actor|write_failed
     */
    public static function rejected(string $error, string $action = 'rejected'): self
    {
        return new self(ok: false, action: $action, error: $error);
    }
}

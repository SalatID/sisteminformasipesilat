<?php

namespace App\Services\Notification\Reply;

/**
 * Aturan keputusan penandaan libur, dipisah dari database agar bisa diuji
 * murni (lihat HolidayFlagServiceTest).
 *
 * Referensi aturan: docs/rencana-implementasi-libur-via-balasan-wa.md bab 6.
 */
class HolidayDecision
{
    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const UNCHANGED = 'unchanged';

    public const REJECT_INACTIVE_UNIT = 'reject_inactive_unit';

    public const REJECT_TRAINING = 'reject_training';

    /**
     * @param  string|null  $existingStatus  status baris absensi yang sudah ada (null = belum ada)
     * @param  string|null  $existingReason  alasan yang sudah tersimpan
     * @param  bool  $unitActive  `units.is_active`
     */
    public static function decide(
        ?string $existingStatus,
        ?string $existingReason,
        string $status,
        string $reason,
        bool $unitActive,
    ): string {
        // V15: unit nonaktif tidak boleh diubah.
        if (! $unitActive) {
            return self::REJECT_INACTIVE_UNIT;
        }

        // V10: latihan yang sudah dilaporkan tidak boleh diubah menjadi libur.
        if ($existingStatus === 'training') {
            return self::REJECT_TRAINING;
        }

        // V11: status & alasan sama → konfirmasi ulang tanpa menulis DB.
        if ($existingStatus === $status && $existingReason === $reason) {
            return self::UNCHANGED;
        }

        if ($existingStatus === null) {
            return self::CREATE;
        }

        // V12: koreksi status/alasan.
        return self::UPDATE;
    }
}

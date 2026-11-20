<?php

namespace App\Services\Notification\Schedule;

/**
 * Mem-parse perintah pembatalan jadwal:
 *
 *   /cancel-schedule
 *   Alasan : hujan deras
 *
 *   /cancel-schedule TCGOR-20261125
 *   Alasan : hujan deras
 *
 *   /cancel-schedule TCGOR-20261125 hujan deras
 *
 * Semua kata kunci TIDAK case-sensitive (K14). Awalan wajib; pesan tanpa
 * awalan diabaikan oleh webhook sebelum parser dipanggil.
 *
 * Urutan baris tidak dipaksakan agar admin yang menulis alasan lebih dulu
 * tidak ditolak sia-sia. Hanya satu alasan dan satu kode per pesan.
 */
class CancelScheduleParser
{
    public const PREFIX = '/cancel-schedule';

    private const PREFIX_PATTERN = '/^\/cancel-schedule(?:@[^\s]+)?(?=\s|$)/iu';

    private const REASON_PATTERN = '/^alasan\s*:(.*)$/iu';

    private const CODE_PATTERN = '/^([a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*)$/u';

    private int $reasonMin;

    private int $reasonMax;

    public function __construct(?int $reasonMin = null, ?int $reasonMax = null)
    {
        $this->reasonMin = max(1, $reasonMin ?? 3);
        $this->reasonMax = max($this->reasonMin, $reasonMax ?? 255);
    }

    public static function fromConfig(): self
    {
        return new self(
            (int) config('notifications.cancel_schedule.reason_min', 3),
            (int) config('notifications.cancel_schedule.reason_max', 255),
        );
    }

    public function hasPrefix(string $text): bool
    {
        return preg_match(self::PREFIX_PATTERN, trim($text)) === 1;
    }

    public function parse(string $text): CancelScheduleCommand
    {
        $text = trim($text);

        if (! $this->hasPrefix($text)) {
            return CancelScheduleCommand::invalid('missing_prefix');
        }

        $rest = preg_replace(self::PREFIX_PATTERN, '', $text, 1) ?? '';
        $lines = preg_split('/\R/u', $rest) ?: [];

        // Baris pertama setelah awalan: jika tidak berlabel, dianggap kode.
        // Bentuk satu baris "KODE alasan..." dipecah di sini.
        $firstLine = trim((string) ($lines[0] ?? ''));
        $inlineReason = '';

        if ($firstLine !== '' && preg_match(self::REASON_PATTERN, $firstLine, $matches) !== 1) {
            $parts = preg_split('/\s+/u', $firstLine, 2) ?: [$firstLine];
            $firstLine = trim((string) ($parts[0] ?? ''));
            $inlineReason = trim((string) ($parts[1] ?? ''));
            $lines[0] = $firstLine;
        }

        $target = null;
        $targetSeen = false;

        if ($firstLine !== '' && preg_match(self::REASON_PATTERN, $firstLine) !== 1) {
            if (preg_match(self::CODE_PATTERN, $firstLine) !== 1) {
                return CancelScheduleCommand::invalid('unknown_line');
            }

            $target = mb_strtoupper($firstLine, 'UTF-8');
            $targetSeen = true;
        }

        $reason = null;
        $pendingLabel = false;

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($index === 0 && $targetSeen) {
                continue;
            }

            if (preg_match(self::REASON_PATTERN, $line, $matches) === 1) {
                if ($reason !== null || $pendingLabel) {
                    return CancelScheduleCommand::invalid('duplicate_reason', $target);
                }

                $value = trim($matches[1]);

                if ($value === '') {
                    // "Alasan :" kosong: alasan ada di baris berikutnya.
                    $pendingLabel = true;

                    continue;
                }

                $reason = $value;

                continue;
            }

            if (! $targetSeen && preg_match(self::CODE_PATTERN, $line) === 1) {
                $target = mb_strtoupper($line, 'UTF-8');
                $targetSeen = true;

                continue;
            }

            // Baris bebas hanya boleh menjadi alasan (satu kali).
            if ($reason === null && ! $pendingLabel) {
                $reason = $line;

                continue;
            }

            if ($pendingLabel && $reason === null) {
                $reason = $line;
                $pendingLabel = false;

                continue;
            }

            return CancelScheduleCommand::invalid('unknown_line', $target);
        }

        if ($inlineReason !== '') {
            if ($reason !== null) {
                return CancelScheduleCommand::invalid('duplicate_reason', $target);
            }

            $reason = $inlineReason;
        }

        if ($reason === null || $reason === '') {
            return CancelScheduleCommand::invalid('missing_reason', $target);
        }

        if (mb_strlen($reason) < $this->reasonMin) {
            return CancelScheduleCommand::invalid('reason_too_short', $target);
        }

        if (mb_strlen($reason) > $this->reasonMax) {
            return CancelScheduleCommand::invalid('reason_too_long', $target);
        }

        return CancelScheduleCommand::valid($target, $reason);
    }
}

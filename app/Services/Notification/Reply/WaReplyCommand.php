<?php

namespace App\Services\Notification\Reply;

use Illuminate\Support\Facades\Log;

/**
 * Mem-parse balasan admin di grup WhatsApp menjadi perintah penandaan libur.
 *
 * Format baku (3 baris):
 *
 *   nomor 1
 *   Diliburkan Sekolah
 *   Alasan : Sedang ujian
 *
 * Aturan (keputusan pemilik proses, 18 Nov 2026):
 *   - `nomor` harus huruf kecil (menulis `Nomor 1` ditolak).
 *   - Status TIDAK case-sensitive: `Diliburkan Sekolah`, `DILIBURKAN SEKOLAH`,
 *     `Diliburkan sekolah` semuanya diterima. Spasi berlebih juga ditoleransi.
 *   - `Alasan :` wajib, minimal 3 karakter.
 *   - Baris kosong diabaikan, tetapi baris asing menyebabkan penolakan.
 *   - Satu balasan hanya untuk SATU nomor.
 *
 * Urutan baris tidak dipaksakan: baris dikenali dari bentuknya masing-masing
 * agar admin yang menulis alasan lebih dulu (atau menyisipkan baris kosong)
 * tidak ditolak sia-sia.
 */
class WaReplyCommand
{
    /**
     * Label status yang diterima (sudah dinormalisasi) → nilai
     * `attendances.attendance_status`.
     *
     * @var array<string, array{status: string, label: string}>
     */
    public const STATUS_MAP = [
        'diliburkan sekolah' => ['status' => 'school_holiday', 'label' => 'Diliburkan Sekolah'],
        'diliburkan pelatih' => ['status' => 'cancelled', 'label' => 'Diliburkan Pelatih'],
    ];

    public const MIN_REASON_LENGTH = 3;

    public function parse(string $text): ParsedReply
    {
        $lines = $this->lines($text);

        if ($lines === []) {
            return ParsedReply::invalid('empty');
        }

        $number = null;
        $status = null;
        $statusLabel = null;
        $reason = null;

        foreach ($lines as $line) {
            if (preg_match('/^nomor\s+(\d+)$/u', $line, $match) === 1) {
                if ($number !== null) {
                    return ParsedReply::invalid('duplicate_number');
                }

                $number = (int) $match[1];

                continue;
            }

            if (preg_match('/^alasan\s*:\s*(.*)$/ui', $line, $match) === 1) {
                if ($reason !== null) {
                    return ParsedReply::invalid('duplicate_reason');
                }

                $reason = trim($match[1]);

                continue;
            }

            $normalized = $this->normalizeStatusLabel($line);

            if (isset(self::STATUS_MAP[$normalized])) {
                if ($status !== null) {
                    return ParsedReply::invalid('duplicate_status');
                }

                $status = self::STATUS_MAP[$normalized]['status'];
                $statusLabel = self::STATUS_MAP[$normalized]['label'];

                continue;
            }

            return ParsedReply::invalid('unknown_line', ['line' => $this->sanitizeLine($line)]);
        }

        if ($number === null) {
            return ParsedReply::invalid('missing_number');
        }

        if ($number < 1) {
            return ParsedReply::invalid('number_not_positive');
        }

        if ($status === null) {
            return ParsedReply::invalid('missing_status');
        }

        if ($reason === null || $reason === '') {
            return ParsedReply::invalid('missing_reason');
        }

        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            return ParsedReply::invalid('reason_too_short', ['reason' => $reason]);
        }

        [$reason, $truncated] = $this->limitReason($reason);

        return ParsedReply::valid($number, $status, $statusLabel, $reason, $truncated);
    }

    /**
     * Teks balasan ini "berpotensi" sebuah perintah? Dipakai agar bot tidak
     * membalas obrolan biasa di grup.
     */
    public function looksLikeCommand(string $text): bool
    {
        return preg_match('/^nomor\s+\d+/mi', $text) === 1
            || preg_match('/^alasan\s*:/mi', $text) === 1
            || $this->containsStatusLabel($text);
    }

    public function containsStatusLabel(string $text): bool
    {
        foreach ($this->lines($text) as $line) {
            if (isset(self::STATUS_MAP[$this->normalizeStatusLabel($line)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Daftar label status untuk pesan bantuan.
     *
     * @return array<int, string>
     */
    public function statusLabels(): array
    {
        return array_values(array_map(fn (array $item) => $item['label'], self::STATUS_MAP));
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        $lines = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function normalizeStatusLabel(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function limitReason(string $reason): array
    {
        $max = (int) config('notifications.reply.reason_max', 255);

        if ($max <= 0 || mb_strlen($reason) <= $max) {
            return [$reason, false];
        }

        return [mb_substr($reason, 0, $max), true];
    }

    private function sanitizeLine(string $line): string
    {
        Log::channel('cron')->debug('Balasan libur tidak dikenali', ['line' => mb_substr($line, 0, 120)]);

        return mb_substr($line, 0, 60);
    }
}

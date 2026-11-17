<?php

namespace App\Services\Notification\Reply;

use Illuminate\Support\Facades\Log;

/**
 * Mem-parse balasan admin di grup WhatsApp menjadi perintah penandaan libur.
 *
 * Format baku (4 baris):
 *
 *   /libur
 *   Nomor 1
 *   Diliburkan Sekolah
 *   Alasan : Sedang ujian
 *
 * Aturan (revisi setelah uji produksi 17 Nov 2026):
 *   - Semua kata kunci TIDAK case-sensitive: `/libur`, `/LIBUR`, `Nomor 1`,
 *     `NOMOR 1`, `Alasan :`, `ALASAN:` semuanya diterima.
 *   - Awalan `/libur` WAJIB: pesan tanpa awalan dianggap obrolan biasa dan
 *     tidak pernah diproses (lihat `hasPrefix()`).
 *   - `Alasan :` wajib, minimal 3 karakter.
 *   - Baris kosong diabaikan, tetapi baris asing menyebabkan penolakan.
 *   - Satu balasan hanya untuk SATU nomor.
 *
 * Urutan baris tidak dipaksakan: baris dikenali dari bentuknya masing-masing
 * agar admin yang menulis alasan lebih dulu tidak ditolak sia-sia.
 */
class WaReplyCommand
{
    public const PREFIX = '/libur';

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

    /**
     * `/libur`, `/Libur`, `/libur@628123456789` — harus berdiri sendiri
     * (bukan bagian dari kata lain seperti `/liburan`).
     */
    private const PREFIX_PATTERN = '/^\/libur(?:@[^\s]+)?(?=\s|$)/iu';

    public function parse(string $text): ParsedReply
    {
        // Awalan wajib: tanpa `/libur`, pesan bukan perintah apa pun isinya.
        if (! $this->hasPrefix($text)) {
            return ParsedReply::invalid('missing_prefix');
        }

        $lines = $this->stripPrefix($this->lines($text));

        if ($lines === []) {
            return ParsedReply::invalid('empty');
        }

        $number = null;
        $status = null;
        $statusLabel = null;
        $reason = null;

        foreach ($lines as $line) {
            if (preg_match('/^nomor\s+(\d+)$/iu', $line, $match) === 1) {
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
     * Apakah pesan dibuka dengan awalan `/libur`? Pesan tanpa awalan bukan
     * perintah dan tidak boleh diproses maupun dibalas.
     */
    public function hasPrefix(string $text): bool
    {
        $lines = $this->lines($text);

        return $lines !== [] && preg_match(self::PREFIX_PATTERN, $lines[0]) === 1;
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
     * Buang awalan `/libur` pada baris pertama. Sisa baris yang sama tetap
     * dipakai, sehingga `/libur nomor 1` juga bekerja.
     *
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function stripPrefix(array $lines): array
    {
        if ($lines === [] || preg_match(self::PREFIX_PATTERN, $lines[0]) !== 1) {
            return $lines;
        }

        $rest = trim((string) preg_replace(self::PREFIX_PATTERN, '', $lines[0], 1));

        if ($rest === '') {
            array_shift($lines);

            return $lines;
        }

        $lines[0] = $rest;

        return $lines;
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

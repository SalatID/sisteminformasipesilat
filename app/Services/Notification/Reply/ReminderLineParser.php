<?php

namespace App\Services\Notification\Reply;

use Carbon\Carbon;

/**
 * Membaca teks pesan notifikasi yang dikutip admin menjadi daftar baris
 * bernomor (unit + tanggal).
 *
 * Dua format didukung, mengikuti `AttendanceMessageBuilder`:
 *
 *  1. Pengingat gabungan
 *       1. *SDN Pegadungan 01*, Tanggal Latihan : Selasa, 17 November 2026, Pukul : 13:00–15:00 — PJ: ...
 *
 *  2. Laporan absensi satu unit
 *       *Unit*: SDN Pegadungan 01
 *       *Tanggal Latihan*: 17/11/2026
 *
 * Nama hari SENGAJA tidak dipakai untuk menghitung tanggal: tanggal diambil
 * dari angka + nama bulan, sehingga pesan lama yang nama harinya keliru
 * (mis. karena locale berbeda) tetap terbaca benar.
 */
class ReminderLineParser
{
    private const REMINDER_TITLE = '/^\*Pengingat Absensi Latihan\*/mu';

    private const REPORT_TITLE = '/^\*Absensi Latihan\*/mu';

    /**
     * Baris pengingat: `1. *Unit*, Tanggal Latihan : Senin, 17 November 2026, Pukul : 13:00–15:00 — PJ: ...`
     */
    private const REMINDER_LINE = '/^\s*(\d+)\.\s*\*(.+?)\*,\s*Tanggal Latihan\s*:\s*(.+?),\s*Pukul\s*:\s*(.+?)\s*[—–-]\s*PJ\s*:\s*(.+?)\s*$/mu';

    private const REPORT_UNIT = '/^\*Unit\*\s*:\s*(.+?)\s*$/mu';

    private const REPORT_DATE = '/^\*Tanggal Latihan\*\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/mu';

    /**
     * Nama bulan (Indonesia, Inggris, dan singkatan) → nomor bulan 1–12.
     *
     * @var array<string, int>
     */
    private const MONTHS = [
        'januari' => 1, 'january' => 1, 'jan' => 1,
        'februari' => 2, 'february' => 2, 'feb' => 2,
        'maret' => 3, 'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'june' => 6, 'jun' => 6,
        'juli' => 7, 'july' => 7, 'jul' => 7,
        'agustus' => 8, 'august' => 8, 'agu' => 8, 'agt' => 8, 'aug' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'october' => 10, 'okt' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11,
        'desember' => 12, 'december' => 12, 'des' => 12, 'dec' => 12,
    ];

    public function isReminder(string $text): bool
    {
        return preg_match(self::REMINDER_TITLE, $text) === 1;
    }

    public function isReport(string $text): bool
    {
        return preg_match(self::REPORT_TITLE, $text) === 1;
    }

    /**
     * Apakah teks ini salah satu pesan notifikasi bot?
     */
    public function isNotification(string $text): bool
    {
        return $this->isReminder($text) || $this->isReport($text);
    }

    /**
     * Baris bernomor pada pesan pengingat.
     *
     * @return array<int, array{number: int, unit: string, date: ?Carbon, dateLabel: string, start: ?string, end: ?string, pj: ?string}>
     */
    public function parseReminder(string $text): array
    {
        if (preg_match_all(self::REMINDER_LINE, $text, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $entries = [];

        foreach ($matches as $match) {
            [$start, $end] = $this->splitTime($match[4]);
            $dateLabel = trim($match[3]);

            $entries[] = [
                'number' => (int) $match[1],
                'unit' => trim($match[2]),
                'date' => $this->parseDate($dateLabel),
                'dateLabel' => $dateLabel,
                'start' => $start,
                'end' => $end,
                'pj' => trim($match[5]),
            ];
        }

        return $entries;
    }

    /**
     * Laporan absensi: satu unit, satu tanggal.
     *
     * @return array{unit: string, date: ?Carbon, dateLabel: string}|null
     */
    public function parseReport(string $text): ?array
    {
        $unit = null;
        $dateRaw = null;

        if (preg_match(self::REPORT_UNIT, $text, $match) === 1) {
            $unit = trim($match[1]);
        }

        if (preg_match(self::REPORT_DATE, $text, $match) === 1) {
            $dateRaw = trim($match[1]);
        }

        if ($unit === null || $unit === '' || $dateRaw === null) {
            return null;
        }

        return [
            'unit' => $unit,
            'date' => $this->parseDate($dateRaw),
            'dateLabel' => $dateRaw,
        ];
    }

    /**
     * "Selasa, 17 November 2026" atau "17/11/2026" → Carbon (00:00).
     *
     * Tanggal divalidasi dengan `checkdate()` supaya "31 Februari" ditolak,
     * bukan dibalap menjadi 3 Maret.
     */
    public function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $match) === 1) {
            return $this->safeCreate((int) $match[3], (int) $match[2], (int) $match[1]);
        }

        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/u', $value, $match) === 1) {
            $month = self::MONTHS[mb_strtolower($match[2], 'UTF-8')] ?? null;

            if ($month === null) {
                return null;
            }

            return $this->safeCreate((int) $match[3], $month, (int) $match[1]);
        }

        return null;
    }

    private function safeCreate(int $year, int $month, int $day): ?Carbon
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::create($year, $month, $day, 0, 0, 0, (string) config('app.timezone', 'Asia/Jakarta'));
    }

    /**
     * "13:00–15:00" → ['13:00', '15:00'].
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function splitTime(string $value): array
    {
        $value = trim($value);

        if ($value === '' || $value === '-') {
            return [null, null];
        }

        $parts = preg_split('/\s*[–—]\s*|\s+-\s+/u', $value) ?: [];

        $start = isset($parts[0]) && $parts[0] !== '' ? trim($parts[0]) : null;
        $end = isset($parts[1]) && $parts[1] !== '' ? trim($parts[1]) : null;

        return [$start, $end];
    }
}

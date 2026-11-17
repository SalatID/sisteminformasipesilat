<?php

namespace App\Services\Notification;

use App\Models\Attendance;
use App\Models\Unit;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;

/**
 * Menyusun teks notifikasi dalam format kanonik channel-agnostic:
 *
 *   *bold*            → Telegram: <b>bold</b>  | WhatsApp: *bold*
 *   _italic_          → Telegram: <i>italic</i>| WhatsApp: _italic_
 *   [label](url)      → Telegram: <a href>     | WhatsApp: label (url)
 *
 * Adapter per channel bertanggung jawab atas konversi akhirnya.
 */
class AttendanceMessageBuilder
{
    private const MAX_TELEGRAM_CHARS = 4000;

    private const MAX_PHOTO_LINKS = 4;

    public function __construct(
        private readonly bool $mentionEnabled = true,
        private readonly int $mentionMax = 10,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            (bool) config('notifications.mention.enabled', true),
            (int) config('notifications.mention.max', 10),
        );
    }

    /**
     * Pengingat absensi untuk SEMUA unit yang belum absen — satu pesan.
     *
     * @param  bool  $supportMentions  channel menerima field `mentions` (WhatsApp/WAHA)
     * @param  bool  $supportTags  teks boleh memuat `@<nomor>`; Telegram selalu `false`
     *                              supaya nomor WA tidak tampil sebagai tag yang mati
     * @param  array<int, array{unit: Unit, date: Carbon, type: string}>  $items
     * @return array{text: string, mentions: array<int, string>}|null  null bila tidak ada unit
     */
    public function buildConsolidatedReminder(
        array $items,
        bool $supportMentions = true,
        ?bool $supportTags = null,
    ): ?array {
        $supportTags ??= $supportMentions;
        if ($items === []) {
            return null;
        }

        // Urutkan stabil: jam mulai latihan, lalu nama unit.
        usort($items, function (array $a, array $b) {
            $startA = $a['unit']->training_hours_start?->format('H:i') ?? '99:99';
            $startB = $b['unit']->training_hours_start?->format('H:i') ?? '99:99';

            return [$startA, $a['unit']->name] <=> [$startB, $b['unit']->name];
        });

        $mentionBudget = ($supportMentions && $supportTags && $this->mentionsAllowed()) ? max(0, $this->mentionMax) : 0;
        $mentions = [];
        $lines = [];
        $index = 1;

        foreach ($items as $item) {
            $unit = $item['unit'];
            $pj = $unit->pj;

            $schedule = '';
            if ($unit->training_hours_start && $unit->training_hours_end) {
                $schedule = ' (' . $unit->training_hours_start->format('H:i') . '–' . $unit->training_hours_end->format('H:i') . ')';
            }

            $pjLabel = $this->buildPjLabel($pj, $mentionBudget, count($mentions), $mentions);

            $lines[] = sprintf('%d. *%s*%s — PJ: %s', $index++, $unit->name, $schedule, $pjLabel);
        }

        $tanggal = Carbon::now()->locale('id')->translatedFormat('d/m/Y');

        $text = "*Pengingat Absensi Latihan*\n\n" .
            "Tanggal: {$tanggal}\n" .
            "Unit yang belum mengirim laporan absensi:\n\n" .
            implode("\n", $lines) . "\n\n" .
            'Total: ' . count($items) . ' unit.' . "\n" .
            'Mohon lengkapi laporan absensi melalui aplikasi.';

        return [
            'text' => $text,
            'mentions' => array_values(array_unique($mentions)),
        ];
    }

    /**
     * Laporan absensi satu unit (struktur sama dengan baseline, 13 field).
     */
    public function buildAttendanceReport(
        Attendance $attendance,
        bool $supportMentions = false,
        ?bool $supportTags = null,
    ): array {
        $supportTags ??= $supportMentions;
        $unit = $attendance->unit;
        $unitName = $unit->name ?? 'Unknown';

        $trainingTime = ($unit && $unit->training_hours_start && $unit->training_hours_end)
            ? $unit->training_hours_start->format('H:i') . ' - ' . $unit->training_hours_end->format('H:i')
            : 'Unknown';

        $pjLabel = 'Unknown';
        $mentions = [];

        if ($unit && $unit->pj) {
            $pjLabel = $unit->pj->name;
            $phone = $this->validPhone($unit->pj->nomor_whatsapp);

            if ($supportMentions && $supportTags && $this->mentionsAllowed()) {
                $pjLabel .= ' (@' . $phone . ')';
                $mentions[] = $phone . '@c.us';
            }
        }

        $trainingDate = $attendance->attendance_date
            ? Carbon::parse($attendance->attendance_date)->format('d/m/Y')
            : 'Unknown';

        $createdAt = $attendance->created_at
            ? Carbon::parse($attendance->created_at)->format('d/m/Y')
            : 'Unknown';

        $delayDays = ($attendance->created_at && $attendance->attendance_date)
            ? (int) Carbon::parse($attendance->attendance_date)->startOfDay()
                ->diffInDays(Carbon::parse($attendance->created_at)->startOfDay())
            : 0;

        $reportMaker = $attendance->reportMaker->name ?? 'Unknown';

        [$coachesList, $assCoachesList] = $this->buildCoachLists($attendance);

        $photoLinks = $this->buildPhotoLinks($attendance->attendance_image);

        $text = "*Absensi Latihan*\n\n" .
            "*Unit*: {$unitName}\n" .
            "*PJ Unit*: {$pjLabel}\n" .
            "*Tanggal Latihan*: {$trainingDate}\n" .
            "*Tanggal Absen*: {$createdAt}\n" .
            "*Keterlambatan Absen*: {$delayDays} hari\n" .
            "*Jam Latihan*: {$trainingTime}\n" .
            "*Pembuat Laporan*: {$reportMaker}\n" .
            "*Anggota Lama*: {$attendance->old_member_cnt}\n" .
            "*Anggota Baru*: {$attendance->new_member_cnt}\n\n" .
            "*Pelatih*:\n{$coachesList}\n\n" .
            "*Asisten Pelatih*:\n{$assCoachesList}\n" .
            "*Foto Latihan*:\n{$photoLinks}";

        return ['text' => $text, 'mentions' => $mentions];
    }

    /**
     * Pecah teks panjang (mis. Telegram limit 4096) pada batas baris.
     *
     * @return array<int, string>
     */
    public function chunkForTelegram(string $text, int $limit = self::MAX_TELEGRAM_CHARS): array
    {
        if (mb_strlen($text) <= $limit) {
            return [$text];
        }

        $chunks = [];
        $current = '';

        foreach (explode("\n", $text) as $line) {
            if ($current !== '' && mb_strlen($current . "\n" . $line) > $limit) {
                $chunks[] = $current;
                $current = $line;
                continue;
            }

            $current = $current === '' ? $line : $current . "\n" . $line;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @param  array<int, string>  $mentions
     */
    private function buildPjLabel(?object $pj, int $budget, int $used, array &$mentions): string
    {
        if (!$pj) {
            return 'Unknown';
        }

        $phone = $this->validPhone($pj->nomor_whatsapp);

        if ($phone === null || $used >= $budget) {
            return $pj->name;
        }

        $mentions[] = $phone . '@c.us';

        return '@' . $phone . ' (' . $pj->name . ')';
    }

    /**
     * Mention aktif bila config mengizinkan saat pemanggilan (bukan hanya saat
     * builder dibuat).
     */
    private function mentionsAllowed(): bool
    {
        return $this->mentionEnabled && (bool) config('notifications.mention.enabled', true);
    }

    private function validPhone(?string $phone): ?string
    {
        $normalized = PhoneNormalizer::normalize($phone);

        if ($normalized === null || !PhoneNormalizer::isValidInput($phone)) {
            return null;
        }

        return $normalized;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildCoachLists(Attendance $attendance): array
    {
        $coaches = [];
        $assCoaches = [];
        $details = $attendance->relationLoaded('attendanceDetails')
            ? $attendance->attendanceDetails
            : $attendance->attendanceDetails()->with('coach.ts')->get();

        foreach ($details as $detail) {
            $coach = $detail->coach;

            if (!$coach) {
                continue;
            }

            $alias = $coach->ts->alias ?? '';
            $seq = (int) ($coach->ts->ts_seq ?? 0);
            $label = $coach->name . ($alias !== '' ? " ({$alias})" : '');

            if ($seq > 4) {
                $coaches[] = $label;
            } else {
                $assCoaches[] = $label;
            }
        }

        $coachesList = $this->numberedList($coaches, 'Tidak ada pelatih');
        $assCoachesList = $this->numberedList($assCoaches, 'Tidak ada asisten pelatih');

        return [$coachesList, $assCoachesList];
    }

    /**
     * @param  array<int, string>  $items
     */
    private function numberedList(array $items, string $emptyLabel): string
    {
        if ($items === []) {
            return $emptyLabel;
        }

        $lines = [];
        foreach ($items as $i => $label) {
            $lines[] = ($i + 1) . '. ' . $label;
        }

        return implode("\n", $lines);
    }

    private function buildPhotoLinks(?string $images): string
    {
        if (!$images) {
            return 'Tidak ada';
        }

        $list = array_values(array_filter(array_map('trim', explode(',', $images))));
        $shown = array_slice($list, 0, self::MAX_PHOTO_LINKS);
        $lines = [];

        foreach ($shown as $i => $url) {
            $lines[] = ($i + 1) . '. [Foto ' . ($i + 1) . '](' . $url . ')';
        }

        $remaining = count($list) - count($shown);

        if ($remaining > 0) {
            $lines[] = "... dan {$remaining} foto lainnya";
        }

        return $lines === [] ? 'Tidak ada' : implode("\n", $lines);
    }
}

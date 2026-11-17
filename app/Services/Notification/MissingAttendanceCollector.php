<?php

namespace App\Services\Notification;

use App\Models\Attendance;
use App\Models\Unit;
use Carbon\Carbon;

/**
 * Mengumpulkan unit yang BELUM melaporkan absensi berdasarkan jadwal latihan
 * (`units.training_day` + `units.training_hours_start/end`) selama N hari
 * terakhir — default 30 hari (keputusan pemilik proses, 17 Nov 2026).
 *
 * Aturan:
 *   - Hanya sesi yang JADWALNYA SUDAH BERLALU yang diperiksa (tanggal lampau,
 *     atau hari ini setelah training_hours_end). Sesi hari ini yang belum
 *     selesai tidak dicela.
 *   - Sesi dianggap terpenuhi bila ada baris `attendances` dengan
 *     `attendance_date` = tanggal sesi tersebut, apa pun statusnya
 *     (termasuk `school_holiday` / `cancelled` / `ramadhan_break`, karena
 *     unit sudah mengirim laporan).
 *   - Batas bawah: `now - days + 1` hari (30 hari terakhir termasuk hari ini).
 *   - Batas atas tidak pernah melewati `training_hours_end` hari ini.
 *
 * Satu unit bisa muncul lebih dari sekali bila jadwalnya lebih dari satu hari
 * per pekan (mis. `Tuesday,Saturday`) dan kedua sesi belum dilaporkan.
 */
class MissingAttendanceCollector
{
    public const DEFAULT_DAYS = 30;

    /**
     * Nama hari (Inggris & Indonesia) → nomor hari Carbon (0=Minggu … 6=Sabtu).
     *
     * @var array<string, int>
     */
    private const DAY_NAMES = [
        'sunday' => 0, 'minggu' => 0, 'ahad' => 0,
        'monday' => 1, 'senin' => 1,
        'tuesday' => 2, 'selasa' => 2,
        'wednesday' => 3, 'rabu' => 3,
        'thursday' => 4, 'kamis' => 4,
        'friday' => 5, 'jumat' => 5, 'jum\'at' => 5,
        'saturday' => 6, 'sabtu' => 6,
    ];

    /**
     * @return array<int, array{unit: Unit, date: Carbon, type: string, start: ?string, end: ?string}>
     */
    public function collect(int $days = self::DEFAULT_DAYS, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();
        $days = max(1, $days);

        $from = $now->copy()->startOfDay()->subDays($days - 1);
        $today = $now->copy()->startOfDay();

        $units = Unit::with('pj')
            ->where('is_active', true)
            ->whereNotNull('training_day')
            ->orderBy('name')
            ->get();

        if ($units->isEmpty()) {
            return [];
        }

        // Sekali kueri: semua tanggal absensi dalam rentang.
        $reported = Attendance::query()
            ->whereIn('unit_id', $units->pluck('id'))
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $today->toDateString())
            ->get(['unit_id', 'attendance_date'])
            ->groupBy('unit_id')
            ->map(fn ($rows) => $rows
                ->map(fn ($row) => Carbon::parse($row->attendance_date)->toDateString())
                ->unique()
                ->flip());

        $items = [];

        foreach ($units as $unit) {
            $dayOfWeeks = $this->dayOfWeeks($unit->training_day);

            if ($dayOfWeeks === []) {
                continue;
            }

            for ($date = $from->copy(); $date->lte($today); $date->addDay()) {
                if (! in_array($date->dayOfWeek, $dayOfWeeks, true)) {
                    continue;
                }

                // Sesi hari ini yang belum selesai belum bisa disebut "belum absen".
                if ($date->isSameDay($today) && ! $this->sessionFinishedToday($unit, $now)) {
                    continue;
                }

                if (isset($reported[$unit->id][$date->toDateString()])) {
                    continue;
                }

                $items[] = [
                    'unit' => $unit,
                    'date' => $date->copy(),
                    'type' => $date->isSameDay($today) ? 'today' : 'overdue',
                    'start' => $unit->training_hours_start?->format('H:i'),
                    'end' => $unit->training_hours_end?->format('H:i'),
                ];
            }
        }

        return $items;
    }

    /**
     * Nama hari (`Monday,Wednesday`) → nomor hari Carbon (0=Minggu … 6=Sabtu).
     *
     * Menerima nama Inggris maupun Indonesia karena
     * `Carbon::parse('next Minggu')` TIDAK didukung (gagal di PHP).
     *
     * @return array<int, int>
     */
    public function dayOfWeeks(?string $trainingDay): array
    {
        if ($trainingDay === null || trim($trainingDay) === '') {
            return [];
        }

        $result = [];

        foreach (explode(',', $trainingDay) as $day) {
            $dow = self::DAY_NAMES[strtolower(trim($day))] ?? null;

            if ($dow !== null) {
                $result[] = $dow;
            }
        }

        return array_values(array_unique($result));
    }

    private function sessionFinishedToday(Unit $unit, Carbon $now): bool
    {
        if (! $unit->training_hours_end) {
            return true;
        }

        return $now->format('H:i') >= $unit->training_hours_end->format('H:i');
    }
}

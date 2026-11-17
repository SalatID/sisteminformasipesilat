<?php

namespace Tests\Unit\Notification;

use App\Models\Unit;
use App\Services\Notification\MissingAttendanceCollector;
use Tests\TestCase;

/**
 * Menguji pemetaan `units.training_day` → tanggal sesi tanpa menyentuh
 * database.
 *
 * `collect()` sengaja tidak diuji langsung karena memakai Eloquent query
 * builder yang tidak bisa di-mock sebagian (dan RefreshDatabase AKAN
 * menghapus data asli pada database pengembangan `sip`).
 *
 * Logika tanggal sudah diverifikasi terhadap data nyata: 18 unit aktif,
 * 78 sesi terlewat dalam 30 hari (17 Nov 2026).
 */
class MissingAttendanceCollectorTest extends TestCase
{
    public function test_day_of_weeks_maps_english_day_names(): void
    {
        $collector = new MissingAttendanceCollector();

        $this->assertSame([0], $collector->dayOfWeeks('Sunday'));
        $this->assertSame([1], $collector->dayOfWeeks('Monday'));
        $this->assertSame([2], $collector->dayOfWeeks('Tuesday'));
        $this->assertSame([3], $collector->dayOfWeeks('Wednesday'));
        $this->assertSame([4], $collector->dayOfWeeks('Thursday'));
        $this->assertSame([5], $collector->dayOfWeeks('Friday'));
        $this->assertSame([6], $collector->dayOfWeeks('Saturday'));
    }

    public function test_day_of_weeks_maps_indonesian_day_names(): void
    {
        $collector = new MissingAttendanceCollector();

        // Data nyata memakai format Inggris; nama Indonesia tetap didukung.
        $this->assertSame([0, 1], $collector->dayOfWeeks('Minggu,Senin'));
        $this->assertSame([2, 6], $collector->dayOfWeeks('Selasa, Sabtu'));
        $this->assertSame([5], $collector->dayOfWeeks('Jumat'));
    }

    public function test_day_of_weeks_handles_multiple_days_with_spaces(): void
    {
        $collector = new MissingAttendanceCollector();

        $this->assertSame([2, 6], $collector->dayOfWeeks('Tuesday,Saturday'));
        $this->assertSame([1, 3], $collector->dayOfWeeks(' Monday , Wednesday '));
        $this->assertSame([0, 1], $collector->dayOfWeeks(' Minggu , Senin '));
    }

    public function test_day_of_weeks_ignores_empty_and_unknown_values(): void
    {
        $collector = new MissingAttendanceCollector();

        $this->assertSame([], $collector->dayOfWeeks(null));
        $this->assertSame([], $collector->dayOfWeeks(''));
        $this->assertSame([], $collector->dayOfWeeks('   '));
        $this->assertSame([2], $collector->dayOfWeeks('Tuesday, Ngawur'));
        $this->assertSame([2], $collector->dayOfWeeks(',Tuesday,'));
    }

    public function test_day_of_weeks_removes_duplicates(): void
    {
        $collector = new MissingAttendanceCollector();

        $this->assertSame([2], $collector->dayOfWeeks('Tuesday,Tuesday'));
        $this->assertSame([2], $collector->dayOfWeeks('Tuesday,Selasa'));
    }

    /**
     * Simulasi tanggal yang dihasilkan collector untuk 30 hari terakhir:
     * Selasa 19 Okt–17 Nov 2026 → 5 sesi.
     */
    public function test_thirty_day_window_produces_expected_tuesday_sessions(): void
    {
        $collector = new MissingAttendanceCollector();
        $now = \Carbon\Carbon::parse('2026-11-17 16:00:00');
        $from = $now->copy()->startOfDay()->subDays(29);

        $sessions = [];
        for ($d = $from->copy(); $d->lte($now->copy()->startOfDay()); $d->addDay()) {
            if (in_array($d->dayOfWeek, $collector->dayOfWeeks('Tuesday'), true)) {
                $sessions[] = $d->toDateString();
            }
        }

        $this->assertSame([
            '2026-10-20',
            '2026-10-27',
            '2026-11-03',
            '2026-11-10',
            '2026-11-17',
        ], $sessions);
    }
}

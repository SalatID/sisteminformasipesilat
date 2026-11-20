<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Membuat/menyegarkan baris run (kemunculan) untuk satu jadwal.
 *
 * Aman dijalankan berulang: kombinasi (jadwal, occurrence_at) unik dan
 * memakai firstOrCreate. Run yang sudah dikirim/dibatalkan tidak pernah
 * dihapus atau diubah oleh planner.
 */
class SchedulePlanner
{
    public function __construct(
        private readonly OccurrenceCalculator $calculator,
    ) {}

    /**
     * @return int jumlah run baru
     */
    public function plan(ScheduledNotification $schedule, ?CarbonImmutable $now = null): int
    {
        if (! $schedule->is_active || $schedule->trashed()) {
            return 0;
        }

        $created = 0;

        // Kejadian nyata (UAT): run pending pada tanggal yang sama terbentuk
        // dua kali setelah jam kirim diubah. Satu pending saja per tanggal
        // kalender; pending lain pada tanggal itu dibatalkan.
        $this->cancelSupersededPending($schedule, $now);

        foreach ($this->calculator->forSchedule($schedule, $now) as $occurrence) {
            $run = ScheduledNotificationRun::firstOrCreate(
                [
                    'scheduled_notification_id' => $schedule->id,
                    'occurrence_at' => $occurrence->format('Y-m-d H:i:s'),
                ],
                [
                    'public_code' => $this->publicCode($schedule, $occurrence),
                    'status' => ScheduledNotificationRun::STATUS_PENDING,
                ],
            );

            if ($run->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Dipakai setelah jadwal diubah: pending yang tidak lagi cocok dihapus,
     * lalu run yang baru dibuat. Run final (sent/failed/cancelled/missed)
     * tidak disentuh.
     *
     * @return array{deleted: int, created: int}
     */
    public function refresh(ScheduledNotification $schedule, ?CarbonImmutable $now = null): array
    {
        // Run lama harus dibaca SEBELUM pending yang tidak cocok dihapus,
        // supaya perubahan jam kirim (tanggal sama, jam beda) masih terdeteksi.
        $previousPending = $schedule->runs()
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->get(['id', 'occurrence_at']);

        $occurrences = $schedule->is_active && ! $schedule->trashed()
            ? $this->calculator->forSchedule($schedule, $now)
            : [];

        $keep = array_map(
            fn (CarbonImmutable $occurrence) => $occurrence->format('Y-m-d H:i:s'),
            $occurrences,
        );

        $pending = $schedule->runs()->where('status', ScheduledNotificationRun::STATUS_PENDING);

        if ($keep === []) {
            $deleted = $pending->delete();
        } else {
            $deleted = 0;
        }

        // Run yang dibatalkan sistem saat jadwal dinonaktifkan tetap memegang
        // occurrence_at yang sama, sehingga plan() (firstOrCreate) tidak akan
        // pernah membuatnya lagi. Saat jadwal aktif kembali, baris cancel
        // sistem yang belum disiarkan dihapus agar kemunculan mendatang bisa
        // dijadwalkan ulang.
        // `$keep` dibandingkan per tanggal sehingga run pending lain pada
        // tanggal yang sama tidak ikut terhapus.
        if ($schedule->is_active && ! $schedule->trashed() && $keep !== []) {
            $deleted += $schedule->runs()
                ->where('status', ScheduledNotificationRun::STATUS_CANCELLED)
                ->where('cancel_source', 'system')
                ->whereNull('announced_at')
                ->whereIn('occurrence_at', $keep)
                ->delete();
        }

        $created = $schedule->is_active && ! $schedule->trashed()
            ? $this->plan($schedule, $now)
            : 0;

        $created += $this->cancelStalePending($schedule, $occurrences, $previousPending, $now);

        return ['deleted' => $deleted, 'created' => $created];
    }

    /**
     * Setelah jam kirim diubah, run lama pada tanggal yang sama tetap pending
     * dengan `occurrence_at` berbeda sehingga tidak ikut terhapus oleh
     * `whereNotIn` (dan `firstOrCreate` memakai occurrence_at sebagai kunci).
     * Run semacam itu dibatalkan dengan sumber `system` agar tidak terkirim
     * dobel, tetapi tetap menyimpan jejak untuk audit.
     *
     * @param  array<int, CarbonImmutable>  $occurrences
     * @param  Collection<int, ScheduledNotificationRun>  $previousPending
     */
    private function cancelStalePending(
        ScheduledNotification $schedule,
        array $occurrences,
        Collection $previousPending,
        ?CarbonImmutable $now = null,
    ): int {
        $timezone = (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        // Batas bawah: run yang sudah jatuh tempo (sedang dikirim runner) dan
        // run di masa lalu dibiarkan apa adanya.
        $candidates = $previousPending
            ->filter(fn (ScheduledNotificationRun $run) => $run->occurrence_at->greaterThanOrEqualTo($now));

        if ($candidates->isEmpty()) {
            return 0;
        }

        // `$occurrences` dicocokkan per tanggal kalender, bukan per jam kirim.
        $expectedDates = array_unique(array_map(
            fn (CarbonImmutable $occurrence) => $occurrence->format('Y-m-d'),
            $occurrences,
        ));

        $keptOccurrences = array_map(
            fn (CarbonImmutable $occurrence) => $occurrence->format('Y-m-d H:i:s'),
            $occurrences,
        );

        $byDate = fn (ScheduledNotificationRun $run) => $run->occurrence_at
            ->setTimezone($timezone)
            ->format('Y-m-d');

        $byDateTime = fn (ScheduledNotificationRun $run) => $run->occurrence_at
            ->format('Y-m-d H:i:s');

        // 1) Tanggal tidak lagi dijadwalkan (hari/rentang berubah) → hapus,
        //    karena kemunculan itu memang tidak akan pernah terjadi lagi.
        $removedIds = $candidates
            ->filter(fn (ScheduledNotificationRun $run) => ! in_array($byDate($run), $expectedDates, true))
            ->pluck('id')
            ->all();

        if ($removedIds !== []) {
            ScheduledNotificationRun::query()
                ->whereKey($removedIds)
                ->where('status', ScheduledNotificationRun::STATUS_PENDING)
                ->delete();
        }

        // 2) Tanggal sama, jam kirim berbeda → batalkan (bukan kirim dobel).
        $staleIds = $candidates
            ->filter(fn (ScheduledNotificationRun $run) => in_array($byDate($run), $expectedDates, true)
                && ! in_array($byDateTime($run), $keptOccurrences, true))
            ->pluck('id')
            ->all();

        if ($staleIds === []) {
            return count($removedIds);
        }

        $cancelled = ScheduledNotificationRun::query()
            ->whereKey($staleIds)
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->update([
                'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                'cancelled_at' => $now,
                'cancel_reason' => 'jam kirim jadwal diubah',
                'cancel_source' => 'system',
                'updated_at' => $now,
            ]);

        return $cancelled + count($removedIds);
    }

    /**
     * Jaring pengaman idempoten: untuk setiap tanggal kalender hanya boleh ada
     * satu run pending. Bila ada lebih (sisa perubahan jam/rentang sebelum
     * perbaikan), run dengan occurrence_at terbaru dipertahankan dan sisanya
     * dibatalkan dengan sumber `system` (tidak disiarkan, tidak dikirim).
     */
    private function cancelSupersededPending(ScheduledNotification $schedule, ?CarbonImmutable $now = null): int
    {
        $timezone = (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        $pending = $schedule->runs()
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->where('occurrence_at', '>=', $now->format('Y-m-d H:i:s'))
            ->get(['id', 'occurrence_at']);

        $supersededIds = $pending
            ->groupBy(fn (ScheduledNotificationRun $run) => $run->occurrence_at
                ->setTimezone($timezone)
                ->format('Y-m-d'))
            ->filter(fn (Collection $runs) => $runs->count() > 1)
            ->flatMap(fn (Collection $runs) => $runs
                ->sortByDesc(fn (ScheduledNotificationRun $run) => $run->occurrence_at->getTimestamp())
                ->slice(1)
                ->pluck('id'))
            ->values()
            ->all();

        if ($supersededIds === []) {
            return 0;
        }

        return ScheduledNotificationRun::query()
            ->whereKey($supersededIds)
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->update([
                'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                'cancelled_at' => $now,
                'cancel_reason' => 'digantikan kemunculan lain pada tanggal yang sama',
                'cancel_source' => 'system',
                'updated_at' => $now,
            ]);
    }

    /**
     * Batalkan semua run yang masih pending (jadwal dinonaktifkan/dihapus).
     *
     * @return int jumlah run yang dibatalkan
     */
    public function cancelPending(
        ScheduledNotification $schedule,
        string $reason = 'jadwal dinonaktifkan',
        string $source = 'system',
    ): int {
        return $schedule->runs()
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->update([
                'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => mb_substr($reason, 0, 255),
                'cancel_source' => $source,
                'updated_at' => now(),
            ]);
    }

    /**
     * Bersihkan run pending milik jadwal nonaktif/terhapus.
     *
     * @return int jumlah run yang dibatalkan
     */
    public function cancelOrphanRuns(): int
    {
        return ScheduledNotificationRun::query()
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereHas('schedule', fn ($schedule) => $schedule->where('is_active', false))
                    ->orWhereDoesntHave('schedule');
            })
            ->update([
                'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => 'jadwal dinonaktifkan',
                'cancel_source' => 'system',
                'updated_at' => now(),
            ]);
    }

    private function publicCode(ScheduledNotification $schedule, CarbonImmutable $occurrence): string
    {
        $base = $schedule->code.'-'.$occurrence->format('Ymd');
        $code = $base;
        $suffix = 2;

        while (ScheduledNotificationRun::where('public_code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}

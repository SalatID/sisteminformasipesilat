<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Cache;

/**
 * Guard agar siklus notifikasi terjadwal maksimal sekali per interval
 * (default 3 jam), berapa pun frekuensi cron pemanggilnya.
 */
class NotificationRunGuard
{
    private const CACHE_KEY = 'attendance:notify:last_run';

    private const LOCK_KEY = 'attendance:notify:lock';

    public function intervalHours(): int
    {
        return max(0, (int) config('notifications.interval_hours', 3));
    }

    /**
     * Jendela pengiriman yang diizinkan (07:00–21:59). Di luar jendela
     * pengiriman ditunda untuk menjaga pola manusiawi (anti-bot).
     */
    public function withinActiveWindow(?int $hour = null): bool
    {
        $hour ??= (int) now()->format('H');

        return $hour >= 7 && $hour <= 21;
    }

    /**
     * Apakah siklus berikutnya boleh jalan saat ini.
     */
    public function canRun(bool $force = false): bool
    {
        if ($force || $this->intervalHours() === 0) {
            return true;
        }

        return $this->minutesSinceLastRun() >= $this->intervalHours() * 60;
    }

    /**
     * Sisa menit sampai siklus berikutnya (0 bila boleh jalan).
     */
    public function minutesUntilNextRun(): int
    {
        if ($this->intervalHours() === 0) {
            return 0;
        }

        return max(0, (int) ($this->intervalHours() * 60 - $this->minutesSinceLastRun()));
    }

    /**
     * Menit sejak siklus terakhir. Negatif bila jam sistem mundur.
     */
    private function minutesSinceLastRun(): float
    {
        $lastRun = Cache::get(self::CACHE_KEY);

        if ($lastRun === null) {
            return PHP_FLOAT_MAX;
        }

        $elapsed = now()->getTimestamp() - \Carbon\Carbon::parse($lastRun)->getTimestamp();

        return (float) ($elapsed / 60);
    }

    /**
     * Tandai siklus sudah berjalan (dipanggil setelah siklus selesai).
     */
    public function markRun(): void
    {
        Cache::forever(self::CACHE_KEY, now()->toIso8601String());
    }

    /**
     * Jalankan callback hanya bila boleh jalan dan tidak ada proses lain
     * yang sedang berjalan (mencegah overlap).
     *
     * @param  callable(): mixed  $callback
     * @return array{executed: bool, reason: ?string, result: mixed}
     */
    public function runOnce(callable $callback, bool $force = false): array
    {
        if (!$force && !$this->withinActiveWindow()) {
            return ['executed' => false, 'reason' => 'outside_window', 'result' => null];
        }

        if (!$this->canRun($force)) {
            return ['executed' => false, 'reason' => 'interval', 'result' => null];
        }

        $lock = Cache::lock(self::LOCK_KEY, 600);

        if (!$lock->get()) {
            return ['executed' => false, 'reason' => 'locked', 'result' => null];
        }

        try {
            $result = $callback();
            $this->markRun();

            return ['executed' => true, 'reason' => null, 'result' => $result];
        } finally {
            $lock->release();
        }
    }

    /**
     * Reset guard (untuk testing/keperluan operasional).
     */
    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

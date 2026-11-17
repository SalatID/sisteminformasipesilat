<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Cache;

/**
 * Guard agar siklus notifikasi terjadwal maksimal sekali per interval
 * (default 3 jam), berapa pun frekuensi cron pemanggilnya.
 *
 * Sejak 17 Nov 2026 guard dipisah per jenis pekerjaan:
 *   - 'reminder' : berbasis interval (NOTIF_INTERVAL_HOURS, default 3 jam)
 *   - 'report'   : tanpa interval & tanpa jendela jam (jalan tiap menit),
 *                  hanya dilindungi lock anti-overlap.
 */
class NotificationRunGuard
{
    public const SCOPE_ALL = 'all';

    public const SCOPE_REPORT = 'report';

    public const SCOPE_REMINDER = 'reminder';

    private const CACHE_KEY_PREFIX = 'attendance:notify:last_run';

    private const LOCK_KEY_PREFIX = 'attendance:notify:lock';

    public function intervalHours(): int
    {
        return max(0, (int) config('notifications.interval_hours', 3));
    }

    /**
     * Jendela pengiriman yang diizinkan (07:00–21:59). Di luar jendela
     * pengingat ditunda untuk menjaga pola manusiawi (anti-bot).
     *
     * Laporan absensi TIDAK dibatasi jendela ini (keputusan pemilik proses,
     * 17 Nov 2026): begitu ada baris dengan is_notif_send=false, langsung
     * dikirim kapan pun.
     */
    public function withinActiveWindow(?int $hour = null): bool
    {
        $hour ??= (int) now()->format('H');

        return $hour >= 5 && $hour <= 21;
    }

    /**
     * Apakah scope ini dibatasi interval? Hanya pengingat.
     */
    public function usesInterval(string $scope): bool
    {
        return $scope !== self::SCOPE_REPORT;
    }

    /**
     * Apakah scope ini dibatasi jendela jam? Hanya pengingat.
     */
    public function usesWindow(string $scope): bool
    {
        return $scope !== self::SCOPE_REPORT;
    }

    /**
     * Apakah siklus berikutnya boleh jalan saat ini (scope apa pun).
     */
    public function canRun(bool $force = false, string $scope = self::SCOPE_ALL): bool
    {
        if ($force || !$this->usesInterval($scope) || $this->intervalHours() === 0) {
            return true;
        }

        return $this->minutesSinceLastRun($scope) >= $this->intervalHours() * 60;
    }

    /**
     * Sisa menit sampai siklus berikutnya (0 bila boleh jalan).
     */
    public function minutesUntilNextRun(string $scope = self::SCOPE_ALL): int
    {
        if (!$this->usesInterval($scope) || $this->intervalHours() === 0) {
            return 0;
        }

        return max(0, (int) ($this->intervalHours() * 60 - $this->minutesSinceLastRun($scope)));
    }

    /**
     * Menit sejak siklus terakhir. Negatif bila jam sistem mundur.
     */
    private function minutesSinceLastRun(string $scope = self::SCOPE_ALL): float
    {
        $lastRun = Cache::get($this->cacheKey($scope));

        if ($lastRun === null) {
            return PHP_FLOAT_MAX;
        }

        $elapsed = now()->getTimestamp() - \Carbon\Carbon::parse($lastRun)->getTimestamp();

        return (float) ($elapsed / 60);
    }

    /**
     * Tandai siklus sudah berjalan (dipanggil setelah siklus selesai).
     */
    public function markRun(string $scope = self::SCOPE_ALL): void
    {
        Cache::forever($this->cacheKey($scope), now()->toIso8601String());
    }

    /**
     * Jalankan callback hanya bila boleh jalan dan tidak ada proses lain
     * yang sedang berjalan (mencegah overlap).
     *
     * @param  callable(): mixed  $callback
     * @return array{executed: bool, reason: ?string, result: mixed}
     */
    public function runOnce(?callable $callback = null, bool $force = false, string $scope = self::SCOPE_ALL): array
    {
        if (!$force && $this->usesWindow($scope) && !$this->withinActiveWindow()) {
            return ['executed' => false, 'reason' => 'outside_window', 'result' => null];
        }

        if (!$this->canRun($force, $scope)) {
            return ['executed' => false, 'reason' => 'interval', 'result' => null];
        }

        $lock = Cache::lock($this->lockKey($scope), 600);

        if (!$lock->get()) {
            return ['executed' => false, 'reason' => 'locked', 'result' => null];
        }

        try {
            $result = $callback === null ? null : $callback();
            $this->markRun($scope);

            return ['executed' => true, 'reason' => null, 'result' => $result];
        } finally {
            $lock->release();
        }
    }

    /**
     * Reset guard (untuk testing/keperluan operasional).
     */
    public function reset(string $scope = self::SCOPE_ALL): void
    {
        Cache::forget($this->cacheKey($scope));
    }

    private function cacheKey(string $scope): string
    {
        return self::CACHE_KEY_PREFIX . ':' . $scope;
    }

    private function lockKey(string $scope): string
    {
        return self::LOCK_KEY_PREFIX . ':' . $scope;
    }
}

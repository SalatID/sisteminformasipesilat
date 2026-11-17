<?php

namespace App\Console\Support;

use Closure;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;

/**
 * Daftarkan perintah artisan pada scheduler yang DIJALANKAN DI DALAM PROSES
 * PHP yang sama, tanpa subprocess.
 *
 * Kenapa perlu:
 *   $schedule->command('attendance:notify --type=report') membuat
 *   Illuminate\Console\Scheduling\Event yang menjalankan perintah lewat
 *   Symfony Process (proc_open). Di shared hosting dengan
 *   disable_functions=proc_open, `schedule:run` gagal:
 *
 *     The Process class relies on proc_open, which is not available
 *     on your PHP installation.
 *
 * Solusi:
 *   Perintah artisan dipanggil dengan Artisan::call() di proses yang sama.
 *   Karena tidak ada subprocess, output TIDAK bisa dialihkan ulang oleh
 *   Laravel (command yang dibangun Laravel selalu berupa shell string,
 *   `... >> file 2>&1`). Jadi event di bawah menulis log sendiri dengan
 *   file_put_contents(..., FILE_APPEND), meniru format appendOutputTo().
 *
 * Mutex overlap memakai CacheEventMutex yang SAMA dengan schedule bawaan
 * (diambil dari container), sehingga kunci `withoutOverlapping()` tetap
 * cocok dengan cache store & prefix aplikasi.
 */
final class InProcessCommand
{
    public static function artisan(
        Schedule $schedule,
        string $command,
        array $arguments = [],
        ?string $logPath = null,
    ): InProcessCommandEvent {
        $event = new InProcessCommandEvent(
            self::mutex(),
            $command,
            $arguments,
            $logPath,
            self::timezone($schedule),
        );

        $events = $schedule->events();
        $events[] = $event;

        $property = new \ReflectionProperty(Schedule::class, 'events');
        $property->setAccessible(true);
        $property->setValue($schedule, $events);

        return $event;
    }

    private static function mutex(): EventMutex
    {
        $container = Container::getInstance();

        return $container->bound(EventMutex::class)
            ? $container->make(EventMutex::class)
            : $container->make(CacheEventMutex::class);
    }

    /**
     * Timezone schedule (protected) dibaca lewat refleksi agar event baru
     * ikut zona waktu yang sama dengan event lain.
     */
    private static function timezone(Schedule $schedule): mixed
    {
        try {
            $property = new \ReflectionProperty(Schedule::class, 'timezone');
            $property->setAccessible(true);

            return $property->getValue($schedule);
        } catch (\Throwable) {
            return null;
        }
    }
}

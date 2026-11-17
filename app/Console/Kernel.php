<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Notifikasi absensi dijadwalkan setiap 3 jam. Guard berbasis cache di
     * dalam command memastikan maksimal satu siklus per interval, apa pun
     * frekuensi cron yang memanggil schedule:run (disarankan * * * * *).
     *
     * Lihat config/notifications.php dan
     * docs/rencana-implementasi-notifikasi-telegram-whatsapp.md
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('attendance:notify')
            ->everyThreeHours()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cron/schedule.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Dua jenis notifikasi dijalankan terpisah (keputusan pemilik proses,
     * 17 Nov 2026):
     *
     *  1. Laporan absensi — tiap MENIT. Sumber kebenarannya kolom
     *     `attendances.is_notif_send`; begitu bernilai false, pesan langsung
     *     dikirim. Tidak dibatasi jendela jam, hanya lock anti-overlap.
     *
     *  2. Pengingat absensi — tiap NOTIF_INTERVAL_HOURS jam (default 3),
     *     hanya dalam jendela 07:00–21:59.
     *
     * Prasyarat: cron pemanggil `schedule:run` harus berjalan tiap menit.
     *
     * Lihat config/notifications.php dan
     * docs/rencana-implementasi-notifikasi-telegram-whatsapp.md
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('attendance:notify --type=report')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cron/schedule-report.log'));

        $interval = max(1, (int) config('notifications.interval_hours', 3));

        $schedule->command('attendance:notify --type=reminder')
            ->cron('0 */' . $interval . ' * * *')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cron/schedule-reminder.log'));
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

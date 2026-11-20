<?php

namespace App\Console;

use App\Console\Support\InProcessCommand;
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
     * Sejak 17 Nov 2026 BUKAN lagi `$schedule->command()`. Alasan:
     * `->command()` menjalankan perintah sebagai subprocess lewat
     * `proc_open`, yang dinonaktifkan di shared hosting sehingga
     * `schedule:run` gagal dengan:
     *
     *   The Process class relies on proc_open, which is not available
     *   on your PHP installation.
     *
     * InProcessCommand menjalankan artisan di dalam proses ini dan menulis
     * sendiri file log (format sama dengan appendOutputTo()).
     *
     * Prasyarat: cron pemanggil `schedule:run` harus berjalan tiap menit.
     *
     * Lihat config/notifications.php dan
     * docs/rencana-implementasi-notifikasi-telegram-whatsapp.md
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── 1. Laporan absensi: tiap menit, in-process (tanpa proc_open)
        InProcessCommand::artisan(
            $schedule,
            'attendance:notify',
            ['--type' => 'report'],
            storage_path('logs/cron/schedule-report.log'),
        )
            ->everyMinute()
            ->name('attendance:notify --type=report')
            ->withoutOverlapping();

        $interval = max(1, (int) config('notifications.interval_hours', 3));

        // ── 2. Pengingat absensi: tiap NOTIF_INTERVAL_HOURS jam
        InProcessCommand::artisan(
            $schedule,
            'attendance:notify',
            ['--type' => 'reminder'],
            storage_path('logs/cron/schedule-reminder.log'),
        )
            ->cron('0 */' . $interval . ' * * *')
            ->name('attendance:notify --type=reminder')
            ->withoutOverlapping();

        // ── 3. Notifikasi terjadwal: planner harian + runner tiap menit
        $scheduleTimezone = (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));

        InProcessCommand::artisan(
            $schedule,
            'notification:plan',
            [],
            storage_path('logs/cron/schedule-notification-plan.log'),
        )
            ->dailyAt('00:05')
            ->timezone($scheduleTimezone)
            ->name('notification:plan')
            ->withoutOverlapping();

        InProcessCommand::artisan(
            $schedule,
            'notification:run',
            [],
            storage_path('logs/cron/schedule-notification.log'),
        )
            ->everyMinute()
            ->timezone($scheduleTimezone)
            ->name('notification:run')
            ->withoutOverlapping();
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

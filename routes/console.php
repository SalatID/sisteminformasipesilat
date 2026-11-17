<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Notifikasi Absensi (Telegram & WhatsApp)
|--------------------------------------------------------------------------
|
| Dijadwalkan setiap 3 jam. Guard berbasis cache di dalam command memastikan
| maksimal satu siklus per interval, apa pun frekuensi cron yang memanggil
| schedule:run (disarankan * * * * * php artisan schedule:run).
|
| Lihat config/notifications.php dan docs/rencana-implementasi-notifikasi-telegram-whatsapp.md
|
*/

Schedule::command('attendance:notify')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/cron/schedule.log'));

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
| Penjadwalan notifikasi absensi
|--------------------------------------------------------------------------
|
| Definisi jadwal ada di app/Console/Kernel.php (method schedule()).
| Sengaja tidak memakai `Illuminate\Support\Facades\Schedule` agar tetap
| kompatibel dengan Laravel 10 ke bawah pada shared hosting.
|
*/

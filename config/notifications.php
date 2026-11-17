<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider Notifikasi Aktif
    |--------------------------------------------------------------------------
    |
    | Daftar provider dipisah koma. Provider yang tidak tercantum di sini
    | tidak akan pernah dipanggil (0 request HTTP).
    |
    | Nilai yang didukung: "telegram", "whatsapp"
    | Kosong = semua nonaktif.
    |
    | Contoh:
    |   NOTIF_PROVIDERS=telegram
    |   NOTIF_PROVIDERS=whatsapp
    |   NOTIF_PROVIDERS=telegram,whatsapp
    |
    */

    'providers' => array_values(array_filter(array_map(
        fn ($provider) => strtolower(trim($provider)),
        explode(',', (string) env('NOTIF_PROVIDERS', 'telegram'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Interval & Guard Pengiriman
    |--------------------------------------------------------------------------
    |
    | Notifikasi dijadwalkan setiap NOTIF_INTERVAL_HOURS jam. Guard berbasis
    | cache memastikan maksimal satu siklus dalam interval tersebut, berapa
    | pun frekuensi cron-nya.
    |
    | Set NOTIF_INTERVAL_HOURS=0 untuk menonaktifkan guard.
    |
    */

    'interval_hours' => (int) env('NOTIF_INTERVAL_HOURS', 3),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Saat aktif, semua pengiriman WhatsApp dialihkan ke NOTIF_TEST_CHAT_ID
    | (bukan grup) dan pesan diberi prefix "[TEST]".
    |
    */

    'test_mode' => (bool) env('NOTIF_TEST_MODE', false),

    'test_chat_id' => env('NOTIF_TEST_CHAT_ID', '6289654157132@c.us'),

    /*
    |--------------------------------------------------------------------------
    | Pesan Separator (legacy)
    |--------------------------------------------------------------------------
    |
    | Fitur lama "End of Attendance Notification Process" dikirim setiap run.
    | Default mati karena tidak punya nilai bisnis dan menjadi pola khas bot.
    |
    */

    'send_end_marker' => (bool) env('NOTIF_SEND_END_MARKER', false),

    /*
    |--------------------------------------------------------------------------
    | @mention PJ Unit (WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Saat aktif, pesan pengingat absensi men-tag PJ unit berdasarkan
    | coachs.nomor_whatsapp. Maksimum tag per pesan dibatasi agar tidak
    | terlihat seperti spam mention.
    |
    */

    'mention' => [
        'enabled' => (bool) env('NOTIF_WA_MENTION_ENABLED', true),
        'max' => (int) env('NOTIF_WA_MENTION_MAX', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jeda antar pesan (detik)
    |--------------------------------------------------------------------------
    |
    | Jeda minimum antar pesan pada provider yang sama untuk menjaga pola
    | pengiriman tetap manusiawi.
    |
    */

    'send_delay_seconds' => (int) env('NOTIF_SEND_DELAY_SECONDS', 3),

    /*
    |--------------------------------------------------------------------------
    | Channel: Telegram
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel: WhatsApp (WAHA)
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [
        'url' => env('WAHA_URL', env('WAHA_BASE_URL')),
        'api_key' => env('WAHA_API_KEY'),
        'session' => env('WAHA_SESSION', 'default'),
        'chat_id' => env('WAHA_CHAT_ID'),
        'timeout' => (int) env('WAHA_TIMEOUT', 30),
    ],

];

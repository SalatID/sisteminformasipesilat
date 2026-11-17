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
    | Rentang Pemeriksaan Pengingat (hari)
    |--------------------------------------------------------------------------
    |
    | Pesan pengingat mendaftar setiap sesi latihan yang SUDAH TERLEWAT
    | dalam NOTIF_REMINDER_LOOKBACK_DAYS hari terakhir (termasuk hari ini)
    | tetapi belum ada baris absensi pada tanggal tersebut.
    |
    | Sesi dihitung dari units.training_day + units.training_hours_end,
    | sehingga unit yang jadwalnya belum lewat tidak ikut dicela.
    |
    */

    'reminder_lookback_days' => (int) env('NOTIF_REMINDER_LOOKBACK_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Batas Baris Pesan Pengingat
    |--------------------------------------------------------------------------
    |
    | Maksimum baris daftar unit dalam satu pesan pengingat. Baris TERBARU
    | yang ditampilkan; sisanya diringkas pada baris "... dan N sesi lainnya".
    | Tujuannya menjaga pesan tetap utuh (Telegram 4.096 karakter) dan tidak
    | terlihat seperti spam. Set 0 untuk menampilkan semua.
    |
    */

    'reminder_max_lines' => (int) env('NOTIF_REMINDER_MAX_LINES', 25),

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
    | Balasan Bot: Tandai Sesi Libur (WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Admin membalas pesan *Pengingat Absensi Latihan* di grup, lalu menulis:
    |
    |   nomor 1
    |   Diliburkan Sekolah
    |   Alasan : Sedang ujian
    |
    | Bot menandai sesi tersebut libur dan membalas konfirmasi di grup.
    | Detail: docs/rencana-implementasi-libur-via-balasan-wa.md
    |
    */

    'reply' => [
        'enabled' => (bool) env('NOTIF_REPLY_ENABLED', true),

        // Nomor yang BOLEH mengubah status absensi. Kosong = tidak ada yang boleh.
        // Menerima 62812xxx@c.us, 62812xxx, atau 0812xxx (dinormalisasi).
        'allowed_users' => array_values(array_filter(array_map(
            fn ($value) => trim($value),
            explode(',', (string) env('WA_NOTIF_FLAG_HOLIDAY_USERS', ''))
        ))),

        // Panjang maksimum alasan (kolom attendances.reason = 255 karakter).
        'reason_max' => (int) env('NOTIF_REPLY_REASON_MAX', 255),
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

        // Webhook masuk dari WAHA: POST /api/whatsapp/webhook/{secret}.
        // Kosong = webhook nonaktif (selalu 403).
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET'),

        // Kunci HMAC yang sama dengan config webhook di WAHA. Kosong =
        // signature tidak diverifikasi (hanya mengandalkan {secret} di URL).
        'webhook_hmac_key' => env('WAHA_WEBHOOK_HMAC_KEY'),
    ],

];

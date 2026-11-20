# Rencana Implementasi: Notifikasi Terjadwal WhatsApp

> Status: **rencana** (belum diimplementasikan).
> Disusun 20 November 2026 berdasarkan diskusi dengan pemilik proses.
> Fitur terkait yang sudah ada: `docs/rencana-implementasi-notifikasi-telegram-whatsapp.md`
> dan `docs/rencana-implementasi-libur-via-balasan-wa.md`.

## 1. Ringkasan

Saat ini notifikasi latihan TC dikirim manual oleh admin, kapan saja (pagi/siang/sore).
Fitur ini menambahkan **notifikasi terjadwal** yang:

1. Mengirim pesan teks ke **grup atau chat pribadi** pada jadwal yang ditentukan.
2. Isi **pesan latihan** dan **pesan libur** dapat diubah sendiri lewat website.
3. Mendukung pengulangan: sekali, harian, mingguan (banyak hari), dan bulanan
   (tanggal tertentu atau minggu ke-N).
4. Menyimpan riwayat setiap kiriman (berhasil/gagal/dibatalkan) beserta kode
   unik per kemunculan.
5. Memungkinkan pembatalan satu kemunculan lewat balasan WhatsApp
   (`/cancel-schedule`), lalu mengirim pengumuman libur.

Contoh pemakaian:

| Jadwal | Target | Pengulangan | Jam |
|---|---|---|---|
| TC GOR Gondrong | grup GOR | Mingguan, Selasa & Kamis | 12:00 |
| TC Masjid Al Firdaus | grup Al Firdaus | Mingguan, Rabu & Jumat | 12:00 |

## 2. Keputusan yang sudah ditetapkan pemilik proses

| # | Keputusan | Tanggal |
|---|---|---|
| K1 | Satu baris jadwal = satu pola aturan; satu `time` per jadwal. Beberapa jam per hari tidak didukung di v1. | 20 Nov 2026 |
| K2 | Jenis pengulangan v1: `once`, `daily`, `weekly`, `monthly` (mode `date` dan `week`). | 20 Nov 2026 |
| K3 | Satu jadwal hanya punya satu target chat; variasi lain dibuat dengan tombol "Duplikat". | 20 Nov 2026 |
| K4 | `weekly` memakai daftar hari (boleh lebih dari satu), mis. `[2,4]` = Selasa & Kamis. | 20 Nov 2026 |
| K5 | `monthly` mode `week`: `weeks_of_month` `[1,3]` = minggu ke-1 & ke-3; nilai `5` = kemunculan **terakhir** bulan itu. | 20 Nov 2026 |
| K6 | Zona waktu memakai `APP_TIMEZONE` (`Asia/Jakarta`). | 20 Nov 2026 |
| K7 | Perintah pembatalan lewat webhook bernama `/cancel-schedule` (bukan `/libur`). | 20 Nov 2026 |
| K8 | Identitas jadwal: (a) balas pesan bot → `replyTo.id`; (b) ketik **satu kode** `KODE-YYYYMMDD`, mis. `GOR-20261125`. | 20 Nov 2026 |
| K9 | `code` jadwal **tidak bisa diubah** setelah dibuat, agar kode kemunculan tetap stabil. | 20 Nov 2026 |
| K10 | Pesan latihan **dan** pesan libur di-custom via website (textarea + pratinjau + kirim tes). | 20 Nov 2026 |
| K11 | Pembatalan selalu mengirim pengumuman libur **segera** setelah perintah diterima. | 20 Nov 2026 |
| K12 | Whitelist pembatalan memakai `WA_NOTIF_FLAG_HOLIDAY_USERS` yang sudah ada. | 20 Nov 2026 |
| K13 | Fitur ini hanya WhatsApp (WAHA). Telegram tidak termasuk v1. | 20 Nov 2026 |
| K14 | Semua kata kunci webhook libur **tidak case-sensitive**: `/LIBUR`, `/CANCEL-SCHEDULE`, `ALASAN :`, dan kode (`TCGOR-20261125` / `tcgor-20261125`). | 20 Nov 2026 |

## 3. Ruang lingkup

### Termasuk

- CRUD jadwal notifikasi lewat menu admin (Blade + AdminLTE).
- Pesan latihan & pesan libur yang dapat diedit, dengan variabel dan pratinjau.
- Planner yang membuat daftar kemunculan (runs) 60 hari ke depan.
- Runner yang mengirim tiap menit, dengan grace, retry, dan log.
- Pembatalan satu kemunculan via WhatsApp (`/cancel-schedule`) atau via UI.
- Riwayat kiriman + kirim ulang untuk yang gagal.

### Tidak termasuk (v1)

- Mention `@pelatih`/`@PJ` di pesan custom.
- Beberapa jam berbeda dalam satu jadwal (butuh tabel slot).
- Lampiran gambar/dokumen.
- Penandaan absensi unit otomatis dari pembatalan jadwal TC.
- Pengiriman lewat Telegram.
- Penjadwalan pembuatan jadwal lewat chat (CRUD tetap di website).
- Integrasi otomatis dengan `training_centers.training_days` (bisa jadi fase lanjutan).

## 4. Skema database

### 4.1 `scheduled_notifications`

```php
Schema::create('scheduled_notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('code', 12)->unique();              // GOR, FIRDAUS (immutable)
    $table->string('name', 120);
    $table->string('chat_id', 64);                     // target utama (sinkron dgn target urutan 1)
    $table->string('chat_label', 120)->nullable();     // "Grup GOR Gondrong"
    $table->text('message');                           // pesan latihan (custom)
    $table->text('holiday_message')->nullable();       // pesan libur (custom, opsional)
    $table->string('frequency', 10);                   // once|daily|weekly|monthly
    $table->time('time');                              // 12:00
    $table->json('days_of_week')->nullable();          // [2,4] (ISO: 1=Senin ... 7=Minggu)
    $table->string('monthly_mode', 10)->nullable();    // date|week
    $table->json('dates_of_month')->nullable();        // [1,15]
    $table->json('weeks_of_month')->nullable();        // [1,3,5]
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

### 4.1.1 `scheduled_notification_targets` (multi-chat)

Satu pesan/jadwal dikirim ke **semua** chat target.

```php
Schema::create('scheduled_notification_targets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('scheduled_notification_id')
        ->constrained('scheduled_notifications')->cascadeOnDelete();
    $table->string('chat_id', 64);                     // 1203...@g.us / 628...@c.us
    $table->string('label', 120)->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['scheduled_notification_id', 'chat_id'], 'snt_schedule_chat_unique');
    $table->index(['scheduled_notification_id', 'sort_order'], 'snt_schedule_order_index');
});
```

Catatan:

- `scheduled_notifications.chat_id`/`chat_label` **dipertahankan** sebagai target
  utama (selalu sinkron dengan target `sort_order = 0`) agar pemanggil lama yang
  hanya mengirim `chat_id` tunggal tetap berfungsi.
- Migrasi mengisi satu baris target untuk setiap jadwal lama.
- Satu chat tidak boleh muncul dua kali dalam satu jadwal (validasi + unique index).
- Maksimal 50 chat per jadwal.

Catatan:

- `messages` disimpan sebagai teks biasa (bukan HTML). Format WhatsApp (`*bold*`, `_italic_`, baris baru) dikirim apa adanya.
- `holiday_message` kosong → dipakai template default (lihat 6.3).
- `code` dibuat otomatis dari `name` saat create dan **read-only** saat edit.

### 4.2 `scheduled_notification_runs`

Satu baris = satu kemunculan (jadwal + tanggal + jam).

```php
Schema::create('scheduled_notification_runs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('scheduled_notification_id')
        ->constrained('scheduled_notifications')->cascadeOnDelete();
    $table->dateTime('occurrence_at');
    $table->string('public_code', 32)->unique();       // GOR-20261125
    $table->string('status', 16)->default('pending');  // pending|sent|failed|cancelled|missed
    $table->text('message_snapshot')->nullable();      // teks final saat dikirim
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->dateTime('next_attempt_at')->nullable();
    $table->text('last_error')->nullable();
    $table->string('provider_message_id', 128)->nullable()->index();
    $table->json('sent_targets')->nullable();          // target yang sudah menerima pesan
    $table->json('error_targets')->nullable();         // target yang masih gagal (retry)
    $table->dateTime('sent_at')->nullable();

    // Pembatalan / libur
    $table->dateTime('cancelled_at')->nullable();
    $table->string('cancel_reason', 255)->nullable();
    $table->string('cancel_source', 10)->nullable();   // webhook|web|system
    $table->string('cancelled_by', 64)->nullable();    // 628...@c.us atau user id
    $table->dateTime('announced_at')->nullable();
    $table->string('announcement_message_id', 128)->nullable();

    $table->timestamps();

    $table->unique(['scheduled_notification_id', 'occurrence_at']);
    $table->index(['status', 'occurrence_at']);
    $table->index(['status', 'next_attempt_at']);
});
```

Status:

| Status | Arti |
|---|---|
| `pending` | Menunggu dikirim (atau menunggu retry) |
| `sent` | Sudah terkirim |
| `failed` | Gagal setelah batas retry |
| `cancelled` | Dibatalkan (libur) — pesan normal tidak dikirim |
| `missed` | Terlewat karena server mati di luar grace window |

Catatan: untuk pembatalan **setelah** pesan normal terkirim, `status` tetap
`cancelled` tetapi `sent_at` dan `provider_message_id` tetap terisi sebagai jejak.

Multi-chat: `sent_targets`/`error_targets` mencatat hasil per chat. Bila hanya
sebagian target berhasil, run tetap `pending` (retry) dan retry hanya menyentuh
`error_targets`; setelah semua target berhasil, kedua kolom dikosongkan.
`provider_message_id` menyimpan id WAHA milik target pertama.

### 4.3 Tidak ada tabel terpisah untuk "skip"

Pembatalan disimpan pada baris run. Planner selalu membuat run untuk setiap
kemunculan dalam horizon 60 hari, sehingga kode `KODE-YYYYMMDD` selalu bisa
dicari. Jika kode berada di luar horizon, bot membalas bahwa jadwal belum tersedia.

## 5. Struktur file

### 5.1 File baru

```
database/migrations/2026_11_20_000001_create_scheduled_notifications_table.php
database/migrations/2026_11_20_000002_create_scheduled_notification_runs_table.php
database/migrations/2026_11_20_000003_create_scheduled_notification_targets_table.php
database/migrations/2026_11_20_000004_add_targets_to_scheduled_notification_runs_table.php

app/Models/ScheduledNotification.php
app/Models/ScheduledNotificationRun.php
app/Models/ScheduledNotificationTarget.php

app/Services/Notification/Schedule/OccurrenceCalculator.php
app/Services/Notification/Schedule/SchedulePlanner.php
app/Services/Notification/Schedule/ScheduleRunner.php
app/Services/Notification/Schedule/HolidayAnnouncer.php
app/Services/Notification/Schedule/MessageTemplate.php
app/Services/Notification/Schedule/CancelScheduleParser.php
app/Services/Notification/Schedule/CancelScheduleService.php
app/Services/Notification/Schedule/CancelResponder.php

app/Console/Commands/PlanScheduledNotificationsCommand.php
app/Console/Commands/RunScheduledNotificationsCommand.php

app/Http/Controllers/ScheduledNotificationController.php
app/Http/Requests/ScheduledNotificationRequest.php

resources/views/pages/admin/scheduled-notification/index.blade.php
resources/views/pages/admin/scheduled-notification/form.blade.php
resources/views/pages/admin/scheduled-notification/show.blade.php

tests/Unit/Notification/Schedule/OccurrenceCalculatorTest.php
tests/Unit/Notification/Schedule/MessageTemplateTest.php
tests/Unit/Notification/Schedule/CancelScheduleParserTest.php
tests/Feature/ScheduledNotificationTest.php
tests/Feature/ScheduledNotificationCrudTest.php
tests/Feature/CancelScheduleWebhookTest.php
tests/Concerns/UsesScheduledNotificationDatabase.php
```

### 5.2 File yang diubah

| File | Perubahan |
|---|---|
| `routes/web.php` | Resource route + route aksi (`toggle`, `test`, `cancel-run`, `retry`) |
| `app/Console/Kernel.php` | `notification:plan` harian 00:05, `notification:run` tiap menit, in-process |
| `app/Http/Controllers/Controller.php` | Entri menu "Notifikasi Terjadwal" |
| `app/Http/Controllers/Api/WhatsAppWebhookController.php` | Routing prefix `/cancel-schedule` tanpa merusak `/libur` |
| `config/notifications.php` | Blok `schedules`, `cancel_schedule`, `defaults` |
| `app/Services/Notification/Reply/ReplyResponder.php` | Reuse whitelist/mention; tidak perlu diubah isinya |
| `app/Services/Notification/Channels/WhatsAppChannel.php` | Tidak berubah (dipakai `sendTo`) |

## 6. Aturan jadwal

### 6.1 Semantik `frequency`

| `frequency` | Field wajib | Contoh | Kemunculan |
|---|---|---|---|
| `once` | `start_date`, `time` | 2026-11-25 12:00 | 1x |
| `daily` | `time`, `start_date` | 12:00, 20 Nov–31 Des | tiap hari 12:00 |
| `weekly` | `days_of_week`, `time` | `[2,4]` | tiap Selasa & Kamis |
| `monthly` + `monthly_mode=date` | `dates_of_month`, `time` | `[1,15]` | tiap tanggal 1 & 15 |
| `monthly` + `monthly_mode=week` | `weeks_of_month`, `days_of_week`, `time` | `[1,3]`, `[6]` | Sabtu minggu ke-1 & ke-3 |

Aturan umum:

- `start_date` kosong = mulai hari ini (kecuali `once`, wajib).
- `end_date` kosong = tanpa batas.
- Tanggal yang tidak ada (mis. 31 Februari) dilewati.
- `days_of_week` memakai ISO-8601: `1` Senin, `2` Selasa, `3` Rabu, `4` Kamis,
  `5` Jumat, `6` Sabtu, `7` Minggu.
- `weeks_of_month` `5` = kemunculan terakhir hari tersebut di bulan itu
  (bukan selalu tanggal 29–31).
- Duplikat nilai di `days_of_week` / `dates_of_month` / `weeks_of_month`
  ditolak saat validasi.

### 6.2 Contoh perhitungan "minggu ke-N"

November 2026, hari Selasa jatuh pada tanggal 3, 10, 17, 24:

| Nilai | Tanggal |
|---|---|
| `1` | 3 Nov |
| `2` | 10 Nov |
| `3` | 17 Nov |
| `4` | 24 Nov |
| `5` (terakhir) | 24 Nov |

### 6.3 Hari libur & pesan libur

- Jika sebuah run berstatus `cancelled`, pesan normal tidak dikirim.
- Pesan libur memakai `holiday_message` bila diisi; jika kosong, template default:

```
*Latihan Libur*

{nama_jadwal}
{hari}, {tanggal}

Alasan: {alasan}

Mohon maaf atas ketidaknyamanannya.
```

- Pengumuman libur dikirim **segera** saat pembatalan (K11).

### 6.4 Variabel template

| Variabel | Pesan latihan | Pesan libur | Contoh hasil |
|---|---|---|---|
| `{nama_jadwal}` | ya | ya | TC GOR Gondrong |
| `{tanggal}` | ya | ya | 24 November 2026 |
| `{hari}` | ya | ya | Selasa |
| `{alasan}` | - | ya | hujan deras |

- Variabel tidak dikenal ditolak saat simpan (pesan validasi menyebut daftar yang boleh).
- Pratinjau memakai tanggal hari ini dan alasan contoh.
- Panjang maksimum 4.096 karakter per pesan (`NOTIF_SCHEDULE_MESSAGE_MAX`).

## 7. Planner (membuat runs)

Service: `App\Services\Notification\Schedule\SchedulePlanner`.

- Dipanggil saat jadwal dibuat/diubah, dan oleh command `notification:plan` setiap hari 00:05.
- Horizon: **60 hari** (`NOTIF_SCHEDULE_HORIZON_DAYS`).
- Membuat baris run dengan `firstOrCreate(['scheduled_notification_id','occurrence_at'])`
  sehingga aman dijalankan berulang.
- `public_code` = `{schedule.code}-{YYYYMMDD}`. Jika bentrok (kasus langka),
  tambahkan sufiks `-2`, `-3`, dst.
- Saat jadwal diubah:
  - Hapus run `pending` di masa depan yang tidak lagi cocok dengan pola baru.
  - Jangan menyentuh run `sent`, `failed`, `cancelled`, `missed`.
  - Buat ulang run yang cocok.
- Saat jadwal dinonaktifkan/dihapus:
  - Run `pending` di masa depan → `cancelled` dengan `cancel_source=system`
    dan alasan "jadwal dinonaktifkan".
- Command `notification:plan` juga membersihkan run `pending` yatim
  (jadwal sudah tidak aktif).

## 8. Runner (pengiriman)

Command: `php artisan notification:run`.

| Opsi | Fungsi |
|---|---|
| `--dry-run` | Tampilkan yang akan dikirim tanpa memanggil WAHA |
| `--limit=100` | Batasi jumlah run per eksekusi |
| `--id=UUID` | Hanya proses satu jadwal |
| `--force` | Abaikan grace window (untuk uji) |

Alur:

1. Ambil lock `scheduled-notification:runner` (mencegah dua proses).
2. Ambil run `pending` dengan:
   - `occurrence_at <= now`, dan
   - `next_attempt_at` null atau `<= now`, dan
   - `occurrence_at >= now - grace`.
3. Run yang lebih tua dari grace → `missed` (tidak dikirim), tulis log.
4. Untuk setiap run:
   - Jadwal nonaktif/terhapus → `cancelled` (`system`).
   - Ambil lock per run (`Cache::lock("scheduled-notification:run:{id}")`).
   - Render pesan dari `message` + variabel (satu teks untuk semua target).
   - Kirim via `WhatsAppChannel::sendTo()` ke **semua** `chatIds()` jadwal.
     Bila `error_targets` terisi (retry setelah gagal sebagian), hanya chat
     yang masih gagal yang dikirimi.
   - Semua target sukses → `sent`, `sent_at`, `provider_message_id` (id target
     pertama), `message_snapshot`, `sent_targets`/`error_targets` dikosongkan.
   - Sebagian gagal → `sent_targets` diisi target yang berhasil,
     `error_targets` diisi target yang gagal, `attempts++`; jika
     `attempts < 3` → `pending` + `next_attempt_at = now + 5 menit`
     (retry hanya ke `error_targets`); jika tidak → `failed`.
   - Semua gagal → sama seperti perilaku lama (retry, lalu `failed`).
5. Catat ringkasan ke `storage/logs/cron/schedule-notification.log` dan
   `Log::channel('cron')` (menyertakan daftar target yang berhasil/gagal).

Konfigurasi:

```php
'schedules' => [
    'enabled'                 => env('NOTIF_SCHEDULE_ENABLED', true),
    'timezone'                => env('NOTIF_SCHEDULE_TIMEZONE', 'Asia/Jakarta'),
    'horizon_days'            => (int) env('NOTIF_SCHEDULE_HORIZON_DAYS', 60),
    'grace_minutes'           => (int) env('NOTIF_SCHEDULE_GRACE_MINUTES', 30),
    'max_attempts'            => (int) env('NOTIF_SCHEDULE_MAX_ATTEMPTS', 3),
    'retry_delay_minutes'     => (int) env('NOTIF_SCHEDULE_RETRY_DELAY_MINUTES', 5),
    'message_max'             => (int) env('NOTIF_SCHEDULE_MESSAGE_MAX', 4096),
    'runner_limit'            => (int) env('NOTIF_SCHEDULE_RUNNER_LIMIT', 100),
],
```

Catatan:

- **Test mode tetap berlaku**: `WhatsAppChannel::sendTo()` mengalihkan semua
  kiriman ke `NOTIF_TEST_CHAT_ID` dan menambahkan prefix `[TEST]`.
- Runner **tidak** memakai `NotificationRunGuard` (tidak perlu interval 3 jam
  dan tidak dibatasi jendela jam); lock-nya sendiri.
- Jika WAHA timeout padahal pesan sudah terkirim, retry bisa menyebabkan dobel.
  Mitigasi: maksimal 3 percobaan, log lengkap, dan tombol retry manual di UI.
- Untuk tanggal yang jadwalnya `cancelled`, runner hanya mengirim pengumuman
  libur bila `announced_at` masih null (lihat 9.5).

## 9. `/cancel-schedule` (webhook)

### 9.1 Format

**Cara 1 — balas pesan bot (tanpa kode):**

```
/cancel-schedule
Alasan : hujan deras
```

**Cara 2 — kode:**

```
/cancel-schedule GOR-20261125
Alasan : hujan deras
```

Boleh satu baris:

```
/cancel-schedule GOR-20261125 hujan deras
```

Aturan parsing:

- Awalan `/cancel-schedule` wajib dan **tidak case-sensitive**:
  `/cancel-schedule`, `/CANCEL-SCHEDULE`, `/Cancel-Schedule` semuanya diterima.
  Boleh memakai `@bot` (mis. `/cancel-schedule@628123456789`).
- Kode ditulis huruf besar/kecil, dinormalisasi ke huruf besar
  (`TCGOR-20261125`, `tcgor-20261125`, dan `TcGor-20261125` sama).
- `Alasan :` **tidak case-sensitive**: `Alasan :`, `ALASAN :`, `alasan:` diterima,
  spasi sebelum/sesudah `:` opsional.
- Alasan wajib, minimal 3 karakter, maks 255.
- Baris kosong diabaikan.
- Baris asing → balas bantuan format.

Contoh variasi yang harus lolos:

```
/CANCEL-SCHEDULE
ALASAN : HUJAN DERAS
```

```
/Cancel-Schedule tcgor-20261125
alasan: hujan deras
```

Catatan: `/libur` yang sudah ada juga sudah case-insensitive, jadi tidak ada
perubahan perilaku pada jalur tersebut.

### 9.2 Kode

- `scheduled_notifications.code` unik, 4–10 karakter, huruf besar/angka,
  dibuat otomatis dari nama (contoh: `TC GOR Gondrong` → `TCGOR`).
- `scheduled_notification_runs.public_code` = `{code}-{YYYYMMDD}`,
  contoh `TCGOR-20261125`.
- Kode tampil di UI detail jadwal. Tidak dicetak di pesan broadcast.
- Jika user hanya mengetik kode jadwal tanpa tanggal (`TCGOR`), bot membalas
  3 kemunculan terdekat beserta kode lengkapnya.

### 9.3 Resolusi

1. Ada kode eksplisit → cari `runs.public_code` (persis, case-insensitive).
2. Tidak ada kode → `replyTo.id` → `runs.provider_message_id`.
   - Cocokkan persis; jika gagal, cocokkan bagian akhir message-id
     (antisipasi perbedaan `true_`/`false_` antar versi WAHA).
3. Tidak ditemukan → balas bantuan + contoh format.
4. Kode dan quote menunjuk run berbeda → tolak ("kode dan pesan yang dibalas berbeda").

### 9.4 Validasi

| Kondisi | Balasan |
|---|---|
| Pengirim tidak di whitelist | Pesan penolakan yang sudah ada (`ReplyResponder::unauthorized`) |
| Format tidak lengkap | Bantuan format `/cancel-schedule` |
| Kode tidak ditemukan | "Kode jadwal tidak ditemukan." + contoh |
| Kode hanya nama jadwal | Daftar 3 kemunculan terdekat + kode lengkap |
| Run sudah `cancelled` | "Jadwal ini sudah dibatalkan sebelumnya." (idempoten) |
| Tanggal run sudah lewat (sebelum hari ini) | "Jadwal tersebut sudah lewat." |
| Jadwal nonaktif | "Jadwal sedang nonaktif." |
| Alasan < 3 karakter | "Alasan wajib diisi (minimal 3 karakter)." |
| Alasan > 255 karakter | "Alasan maksimal 255 karakter." |

Catatan: run yang `occurrence_at`-nya **hari ini** tetap boleh dibatalkan
walaupun jamnya sudah lewat (kasus utama: pesan 12:00, info libur 15:00).

### 9.5 Efek pembatalan

1. Update run:
   - `status = cancelled`
   - `cancelled_at`, `cancel_reason`
   - `cancel_source = webhook`
   - `cancelled_by` = id pengirim (`628...@c.us`)
2. Render `holiday_message` (atau template default) dengan:
   - `{alasan}` = alasan dari admin
   - `{tanggal}` = tanggal kemunculan (format `24 November 2026`)
   - `{hari}` = nama hari
   - `{nama_jadwal}` = nama jadwal
3. Kirim ke **semua** `chatIds()` jadwal (`HolidayAnnouncer::announce()`).
   Run dianggap terumumkan bila minimal satu target berhasil;
   `announcement_message_id` diisi id WAHA milik target pertama, dan daftar
   target yang berhasil/gagal dicatat di log `cron`.
4. Simpan `announced_at` + `announcement_message_id`.
5. Balas konfirmasi:

```
Sudah saya batalkan:
TC GOR Gondrong, Selasa, 24 November 2026
Alasan : hujan deras

Pengumuman libur sudah dikirim.
```

Jika pengumuman gagal dikirim:

```
Jadwal sudah dibatalkan, tetapi pengumuman libur gagal dikirim.
Silakan coba "Kirim ulang pengumuman" di halaman detail jadwal.
```

### 9.6 Idempoten

- Memakai cache `wa-holiday-reply:{sha1(message_id)}` yang sudah ada (10 menit).
- Run yang sudah `cancelled` tidak dikirimi pengumuman ulang.

### 9.7 Routing webhook

`WhatsAppWebhookController` diubah agar memilih perintah berdasarkan prefix:

```php
if (CancelScheduleParser::hasPrefix($body)) {
    return $this->handleCancelSchedule($message, $body);
}

if (WaReplyCommand::hasPrefix($body)) {
    return $this->handleHolidayReply($message, $body);
}

return $this->ignored('not_a_command');
```

- Pesan tanpa kedua prefix tetap **diam** (tidak dibalas).
- Autentikasi HMAC, secret URL, idempotensi, dan whitelist tidak berubah.
- Handler `/libur` yang lama tidak diubah perilakunya.

## 10. UI & menu

### 10.1 Menu

- Entri baru di `Controller::list_menu()`:

```php
[
    'icon' => 'fas fa-clock',
    'name' => 'Notifikasi Terjadwal',
    'src' => 'scheduled-notifications.index',
    'permision' => ['notif-jadwal_list'],
    'children' => [],
],
```

- Permission dibuat lewat menu **Permission** yang sudah ada dengan nama modul
  `notif-jadwal` (otomatis membuat `notif-jadwal_list`, `_create`, `_edit`, `_delete`
  dan memberi ke role `super-admin`).
- Route memakai middleware `can:` atau pengecekan permission di controller
  mengikuti pola halaman lain.

### 10.2 Halaman daftar

Kolom: nama, kode, **Target Chat** (semua chat + badge jumlah bila > 1),
jadwal ringkas, kiriman berikutnya, status aktif, terakhir terkirim. Aksi:
detail, edit, aktif/nonaktif, hapus, duplikat. Pencarian mencakup nama, kode,
label/chat_id target utama, dan seluruh chat/label di tabel target.

### 10.3 Form jadwal

- **Info dasar**: nama, **Chat Tujuan** (daftar baris chat_id + label, tombol
  **+ Tambah Chat** / hapus baris), status aktif.
- **Pesan latihan**: textarea + daftar variabel + pratinjau + tombol **Kirim tes**.
- **Pesan libur**: textarea (opsional) + pratinjau + tombol **Kirim tes**.
- **Pengulangan**: pilihan jenis, jam, dan field kondisional:
  - `weekly` → checkbox hari.
  - `monthly` → pilihan mode, lalu tanggal atau minggu + hari.
- **Periode**: mulai, sampai (opsional).
- **Kode**: dibuat otomatis, read-only saat edit.

Validasi `ScheduledNotificationRequest`:

- `targets` (array) minimal berisi satu chat; setiap `targets.*.chat_id`
  wajib berformat `...@g.us` / `...@c.us` / `...@s.whatsapp.net`, maksimal 50
  chat, dan tidak boleh duplikat.
- `chat_id` tunggal (pemanggil lama) tetap diterima dan diperlakukan sebagai
  satu-satunya target.
- `message` wajib, maks 4096, minimal 3 karakter.
- `holiday_message` opsional, maks 4096.
- `time` format `HH:MM`.
- `frequency` sesuai aturan 6.1; field yang tidak relevan wajib kosong.
- `start_date <= end_date`.
- Variabel hanya dari daftar 6.4.

### 10.4 Halaman detail

- Ringkasan jadwal + kode + **Chat Tujuan (n)** berisi seluruh chat & label.
- Tabel 60 kemunculan: tanggal, kode, status, waktu kirim, error.
- Aksi per baris: **Batalkan (libur)** dengan modal alasan,
  **Kirim ulang** (untuk `failed`/`missed`; hanya ke target yang belum
  menerima pesan), **Kirim pengumuman** (jika `cancelled` tetapi
  `announced_at` null).
- Riwayat 20 kiriman terakhir.

### 10.5 Kirim tes

- Selalu ke `NOTIF_TEST_CHAT_ID` (bukan ke target produksi), prefix `[TEST]`.
- **Dikirim ke semua chat target jadwal** (masing-masing memperoleh satu pesan).
- Memakai template yang sedang diisi (belum tentu tersimpan).
- Hasil (sukses/gagal) ditampilkan sebagai flash message beserta daftar chat.

## 11. Alur data ringkas

```
[Form/CRUD] -> SchedulePlanner -> runs (60 hari)
    + targets (n chat)                |
                    cron tiap menit -> ScheduleRunner -> WhatsAppChannel::sendTo x n target -> WAHA
                                      |
                    reply /cancel-schedule -> CancelScheduleService -> run=cancelled
                                                              -> holiday_message x n target -> WAHA
```

## 12. Observabilitas

- Log cron: `storage/logs/cron/schedule-notification.log`.
- `Log::channel('cron')` mencatat:
  - run dikirim (id, kode, daftar target, provider message id),
  - run gagal (status, error, attempt, `failed_targets`, `sent_targets`),
  - run `missed`,
  - pembatalan (kode, aktor, alasan),
  - pengumuman libur gagal.
- Di UI detail terlihat status & error per run, jadi admin tidak perlu buka log.

## 13. Keamanan

- CRUD hanya untuk user dengan permission.
- `/cancel-schedule` hanya untuk pengirim di `WA_NOTIF_FLAG_HOLIDAY_USERS`.
- Kode bukan rahasia; otorisasi tetap lewat whitelist.
- Validasi `chat_id` ketat agar tidak salah kirim.
- Tidak ada eksekusi kode/HTML dari isi pesan (teks biasa).
- Rate limit: tidak perlu (jumlah target sedikit), tetapi runner membatasi
  `--limit` per siklus dan jeda `NOTIF_SEND_DELAY_SECONDS` dapat dipakai bila
  nanti target bertambah banyak.

## 14. Rencana pengujian

### Unit

- `OccurrenceCalculatorTest`
  - once, daily, weekly multi-hari, monthly tanggal, monthly minggu ke-N.
  - minggu terakhir (`5`) pada bulan dengan 4 dan 5 kemunculan.
  - `start_date`/`end_date`.
  - tanggal mustahil (31 Feb) dilewati.
  - zona waktu Asia/Jakarta.
- `MessageTemplateTest`
  - render semua variabel, variabel tidak dikenal, alasan kosong.
- `CancelScheduleParserTest`
  - dengan kode, tanpa kode, satu baris, `Alasan :`, tanpa alasan,
    kode hanya nama jadwal, baris asing.
  - **case-insensitive**: `/CANCEL-SCHEDULE`, `ALASAN :`, `tcgor-20261125`,
    dan kombinasi huruf besar/kecil semuanya diterima.

### Feature

- `ScheduledNotificationTest`
  - validasi form (chat id, frekuensi, field wajib).
  - planner membuat run sesuai pola & horizon.
  - edit jadwal menghapus pending lama dan membuat ulang.
  - nonaktifkan jadwal membatalkan pending.
  - runner mengirim sekali (HTTP fake) dan menyimpan `provider_message_id`.
  - grace: run basi → `missed`.
  - retry: gagal 1x → pending; 3x → failed.
  - test mode mengalihkan tujuan.
  - kirim tes ke test chat.
  - **multi-chat**: jadwal dengan banyak target; runner mengirim teks identik
    ke semua target (2 request), status `sent`; gagal sebagian → `pending`,
    `sent_targets`/`error_targets` tercatat, retry hanya ke target gagal dan
    kolom dikosongkan setelah sukses; semua gagal → `failed` setelah 3x;
    pengumuman libur dan kirim tes dikirim ke semua target.
- `ScheduledNotificationCrudTest`
  - tampilan daftar/form/detail (termasuk badge jumlah chat & baris target).
  - simpan jadwal dengan banyak target; `chat_id` tunggal tetap diterima.
  - tolak chat id tidak valid, duplikat, dan daftar kosong di `targets`.
  - update mengganti seluruh daftar target.
  - pencarian menemukan jadwal lewat chat target sekunder.
  - kirim tes dari UI mengirim ke semua target.
  - kirim ulang hanya ke target yang belum menerima dan menandai `sent` bila
    semua target sudah menerima.
- `CancelScheduleWebhookTest`
  - balas pesan → run cancelled + pengumuman terkirim + konfirmasi.
  - kode → run cancelled.
  - kode tanpa tanggal → daftar kemunculan.
  - kode tidak ada → bantuan.
  - non-whitelist → unauthorized.
  - sudah cancelled → idempoten.
  - tanggal lampau → ditolak.
  - `/libur` lama tetap berjalan (regresi).
  - pesan bebas tetap `not_a_command`.

### UAT (staging)

| # | Aksi | Harapan |
|---|---|---|
| S1 | Buat jadwal Selasa & Kamis 12:00, kirim tes | Pesan tes masuk ke nomor tes |
| S2 | Tunggu 12:00 | Pesan masuk ke grup target sekali |
| S3 | Cek detail | Run hari itu `sent`, `provider_message_id` terisi |
| S4 | Balas pesan bot `/cancel-schedule` + alasan | Pengumuman libur masuk, run `cancelled` |
| S5 | Kirim `/cancel-schedule KODE-TANGGAL` untuk besok | Besok tidak ada pesan normal, pengumuman libur terkirim |
| S6 | Matikan cron 1 jam | Run terlewat menjadi `missed`, tidak dikirim telat |
| S7 | Matikan WAHA saat kirim | Retry 3x lalu `failed`, error terlihat di UI |
| S8 | Non-whitelist kirim `/cancel-schedule` | Ditolak |
| S9 | Pesan bebas di grup | Bot diam |
| S10 | Jadwal dengan 3 chat target, **Kirim Tes** | Pesan masuk ke ketiga chat, flash menyebut 3 chat |
| S11 | Runner mengirim run multi-chat | Pesan identik masuk sekali ke setiap chat; run `sent` |
| S12 | Satu chat target gagal saat kirim | Run `pending`, retry hanya ke chat yang gagal (chat lain tidak dobel) |

## 15. Validasi fase dengan SMART

Setiap fase adalah **gate**: fase berikutnya tidak dimulai sebelum seluruh
kriteria S-M-A-R-T dan gate fase tersebut terpenuhi. Jika satu kriteria gagal,
perbaiki dulu di fase yang sama (atau pecah scope secara tertulis), jangan
dilanjutkan.

Definisi:

| Huruf | Arti dalam proyek ini |
|---|---|
| **S** — Specific | Deliverable konkret (file, halaman, command, tabel). |
| **M** — Measurable | Bukti terukur: jumlah test lulus, jumlah run, jumlah HTTP request, isi log, screenshot. |
| **A** — Achievable | Dependensi sudah tersedia (WAHA, cron, DB, AdminLTE, permission). |
| **R** — Relevant | Kaitannya dengan tujuan bisnis: notifikasi tepat waktu & penanganan libur. |
| **T** — Time-bound | Timebox dalam hari kerja + tanggal target (baseline, dapat digeser). |

Ringkasan gate:

| Fase | Timebox | Gate utama | Bukti yang disimpan |
|---|---|---|---|
| 1 | 2 hari (20–23 Nov 2026) | Perhitungan tanggal & template benar | `docs/evidence/notif-terjadwal/fase-1-tests.txt` |
| 2 | 3 hari (24–26 Nov 2026) | Kirim sekali, grace, retry, test mode | `fase-2-dry-run.txt`, `fase-2-send.txt` |
| 3 | 3 hari (27 Nov–1 Des 2026) | CRUD + planner + kirim tes dari UI | `fase-3-ui/` (screenshot) |
| 4 | 3 hari (2–4 Des 2026) | `/cancel-schedule` + pengumuman libur | `fase-4-webhook.txt` |
| 5 | 5 hari (7–11 Des 2026) | UAT 9/9 + go-live + monitoring 3 hari | `fase-5-uat.md` |

### 15.1 Fase 1 — Fondasi data & perhitungan

| Aspek | Kriteria |
|---|---|
| **S** | 2 migrasi (`scheduled_notifications`, `scheduled_notification_runs`), 2 model, blok config `schedules`, `OccurrenceCalculator`, `MessageTemplate`, unit test. |
| **M** | `php artisan migrate` sukses; 5 jenis pola menghasilkan tanggal yang sama dengan tabel manual Nov–Des 2026; `weeks_of_month=[5]` benar untuk bulan dengan 4 dan 5 kemunculan; 4 variabel template dirender; variabel tak dikenal ditolak; **≥ 20 unit test lulus**; seluruh suite hijau; Pint bersih. |
| **A** | Hanya butuh DB dev + PHPUnit; tidak butuh WAHA/kredensial. Pola tanggal sudah disepakati (bagian 6). |
| **R** | Semua fase bergantung pada perhitungan ini; salah tanggal = salah hari kirim. |
| **T** | **2 hari kerja** (20–23 Nov 2026). |

**Gate lulus:** hasil planner 60 hari untuk `TCGOR` (Selasa & Kamis) dan
`FIRDAUS` (Rabu & Jumat) dicocokkan manual dengan kalender dan disetujui.

### 15.2 Fase 2 — Planner & runner

| Aspek | Kriteria |
|---|---|
| **S** | `SchedulePlanner`, `ScheduleRunner`, command `notification:plan` & `notification:run`, entri `Kernel.php`, feature test dengan `Http::fake()`. |
| **M** | Planner 60 hari membuat N run; dijalankan ulang **tidak menambah** run (idempoten). `--dry-run` = **0 HTTP**. Satu run jatuh tempo = **tepat 1 POST** `/api/sendText`, status `sent`, `provider_message_id` terisi. Run terlambat 31 menit = `missed`, **0 POST**. Gagal 2x lalu sukses = `attempts=2`, `sent`. Gagal 3x = `failed` + `last_error`. Test mode = tujuan `NOTIF_TEST_CHAT_ID`. **≥ 12 feature test lulus**; file log cron terbentuk. |
| **A** | Memakai `WhatsAppChannel::sendTo()` dan `InProcessCommand` yang sudah terbukti; tidak ada infrastruktur baru. |
| **R** | Ini mesin pengiriman inti; tanpa fase ini tidak ada pesan terkirim. |
| **T** | **3 hari kerja** (24–26 Nov 2026). |

**Gate lulus:** `notification:run --dry-run` direview, lalu **satu kiriman nyata**
ke `NOTIF_TEST_CHAT_ID` berhasil dan tercatat di DB + log.

### 15.3 Fase 3 — UI CRUD

| Aspek | Kriteria |
|---|---|
| **S** | Route resource + aksi (`toggle`, `test`, `cancel-run`, `retry`), `ScheduledNotificationController`, `ScheduledNotificationRequest`, 3 view, menu, 4 permission. |
| **M** | Create/edit/hapus/aktif-nonaktif berjalan di browser; input salah (chat id, frekuensi, field wajib, alasan) ditolak dengan pesan; simpan jadwal → run langsung muncul di halaman detail; edit pola → pending lama diganti, run `sent` tidak tersentuh; nonaktif → pending dibatalkan; **kirim tes sampai ≤ 30 detik** ke nomor tes; user tanpa permission → 403; menu hanya muncul dengan `notif-jadwal_list`; **≥ 14 feature test lulus**; Pint bersih. |
| **A** | Pola CRUD sudah ada di `TrainingCenterController`/`UnitController`; AdminLTE + permission UI tersedia. |
| **R** | Admin dapat mengelola sendiri tanpa akses database. |
| **T** | **3 hari kerja** (27 Nov–1 Des 2026). |

**Gate lulus:** admin membuat 2 jadwal nyata (GOR & Al Firdaus) di staging
lewat UI, mengirim tes, dan menyetujui tampilan.

### 15.4 Fase 4 — Webhook `/cancel-schedule`

| Aspek | Kriteria |
|---|---|
| **S** | `CancelScheduleParser`, `CancelScheduleService`, `CancelResponder`, routing di `WhatsAppWebhookController`, fixture + test. |
| **M** | Balas pesan → run `cancelled`, pengumuman libur terkirim, konfirmasi memuat nama + tanggal + alasan. Kode → hasil sama. Variasi case-insensitive (`/CANCEL-SCHEDULE`, `ALASAN :`, `tcgor-20261125`) lulus. **9 kasus validasi di 9.4 punya test**. Duplikat message id → tidak kirim pengumuman kedua. `/libur` regresi **100% lulus**; teks bebas tetap `not_a_command`. **≥ 22 test baru** (10 parser + 12 webhook); suite hijau. |
| **A** | `replyTo.id` tersedia di payload WAHA NOWEB; whitelist + idempotensi sudah ada; kode deterministik. |
| **R** | Menyelesaikan kasus libur dadakan dan terencana tanpa buka website. |
| **T** | **3 hari kerja** (2–4 Des 2026). |

**Gate lulus:** uji lokal dengan fixture (balasan & kode) **dan** satu uji di grup
staging; log webhook tersimpan sebagai bukti.

### 15.5 Fase 5 — UAT & go-live

| Aspek | Kriteria |
|---|---|
| **S** | Pelaksanaan UAT S1–S9, aktivasi 2 jadwal produksi, monitoring 3 hari, dokumentasi evidence. |
| **M** | **9/9 skenario UAT lulus** (atau penyimpangan dicatat & disetujui). Kiriman 12:00 pertama: **tepat 1 pesan** per grup, teks & target benar. Monitoring 3 hari: **0 missed, 0 failed, 0 duplikat, 0 unauthorized**. Pembatalan via webhook di produksi: pengumuman terkirim **≤ 1 menit**. Rollback drill: `NOTIF_SCHEDULE_ENABLED=false` → **0 pesan**. |
| **A** | Grup staging, WAHA, whitelist, dan ketersediaan pemilik proses untuk sign-off. |
| **R** | Membuktikan fitur bekerja pada jam & grup nyata, bukan hanya test. |
| **T** | **5 hari kerja** (7–11 Des 2026): UAT 2 hari, go-live 1 hari, monitoring 3 hari. |

**Gate lulus:** sign-off pemilik proses + `docs/evidence/notif-terjadwal/fase-5-uat.md`
berisi hasil 9 skenario, screenshot, dan ringkasan monitoring.

### 15.6 SMART keseluruhan proyek

| Aspek | Kriteria |
|---|---|
| **S** | Notifikasi terjadwal WhatsApp dengan pesan latihan & libur yang dapat di-custom, pengulangan fleksibel, dan pembatalan via `/cancel-schedule`. |
| **M** | 5 gate fase lulus; seluruh suite test hijau; UAT 9/9; monitoring 3 hari bersih. |
| **A** | 16 hari kerja, tanpa infrastruktur baru, memakai WAHA/cron/permission yang sudah ada. |
| **R** | Menghilangkan pengiriman manual yang tidak konsisten dan menangani libur dadakan/terencana. |
| **T** | **20 Nov–11 Des 2026** (baseline; pergeseran harus disetujui tertulis). |

Aturan pergeseran:

- Timebox boleh digeser hanya jika ada blocker eksternal (grup/WAHA/akses).
- Kekurangan fitur **tidak** boleh dikompensasi dengan melewati kriteria M.
- Jika satu fase melebihi timebox > 1 hari, pecah deliverable atau kurangi
  scope fase berikutnya secara tertulis di dokumen ini.


## 16. Risiko & mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Cron tidak jalan | Pesan tidak terkirim | Runner menandai `missed`, log cron, monitoring harian |
| WAHA timeout padahal terkirim | Pesan dobel saat retry | Maks 3 percobaan, log, tombol retry manual |
| Sebagian chat target gagal | Sebagian peserta tidak menerima | Partial retry hanya ke target gagal (`error_targets`), `sent_targets` dicek ulang saat kirim ulang |
| Chat id salah | Pesan masuk ke orang/grup lain | Validasi format + kirim tes + label target |
| Zona waktu server berbeda | Jam kirim meleset | Semua perhitungan memakai `APP_TIMEZONE` |
| Kode bentrok setelah edit | Cancel salah jadwal | `code` immutable; sufiks otomatis untuk bentrok |
| Pesan terlalu panjang | Gagal kirim | Validasi 4.096 karakter + hitungan di form |
| Admin salah batalkan | Pesan libur terkirim | Konfirmasi menyebut nama jadwal + tanggal + alasan |
| Server mati lama | Pengingat basi terkirim | Grace window 30 menit → `missed` |
| Test mode lupa dimatikan | Pesan produksi masuk ke nomor tes | Cek `.env` saat go-live; log target efektif |

## 17. Contoh data

### 17.1 Jadwal TC GOR

```
name          : TC GOR Gondrong
code          : TCGOR
targets       : 1203630xxxxxxxxxx@g.us (Grup GOR Gondrong)
                6281234xxxxxx@c.us (Pelatih)
frequency     : weekly
days_of_week  : [2,4]        // Selasa & Kamis
time          : 12:00
start_date    : 2026-11-20
message       : Latihan TC malam ini pukul 19.30–21.30 di GOR Gondrong.
```

Contoh `holiday_message`:

```
*Latihan Libur*

TC GOR Gondrong, {hari}, {tanggal}
Alasan: {alasan}

Mohon maaf atas ketidaknyamanannya.
```

Kemunculan: `TCGOR-20261124` (Selasa), `TCGOR-20261126` (Kamis), dst.

### 17.2 Pembatalan dadakan

Pesan 12:00 sudah terkirim. Pukul 15:00 admin membalas pesan bot:

```
/cancel-schedule
Alasan : hujan deras
```

Hasil: run `TCGOR-20261124` → `cancelled`, pengumuman libur terkirim,
balasan konfirmasi di grup.

### 17.3 Pembatalan terencana

Admin ingin libur tanggal 26 Nov. Dari UI detail, klik **Batalkan** pada baris
`TCGOR-20261126`, isi alasan. Run `cancelled`; pengumuman libur terkirim
sekarang (atau pada jam jadwal jika opsi itu ditambahkan di fase lanjutan).

## 18. Konfigurasi `.env` (ringkas)

```ini
# Notifikasi terjadwal
NOTIF_SCHEDULE_ENABLED=true
NOTIF_SCHEDULE_TIMEZONE=Asia/Jakarta
NOTIF_SCHEDULE_HORIZON_DAYS=60
NOTIF_SCHEDULE_GRACE_MINUTES=30
NOTIF_SCHEDULE_MAX_ATTEMPTS=3
NOTIF_SCHEDULE_RETRY_DELAY_MINUTES=5
NOTIF_SCHEDULE_MESSAGE_MAX=4096

# Pembatalan lewat WhatsApp memakai whitelist yang sama dengan /libur
WA_NOTIF_FLAG_HOLIDAY_USERS=6281234567890,6289876543210
```

## 19. Pertanyaan terbuka

Tidak ada yang menghambat Fase 1. Dua hal yang bisa diputuskan sambil jalan:

1. Apakah pembatalan jadwal juga harus menandai absensi unit terkait?
   (Saat ini **tidak**; jadwal TC tidak terhubung ke unit.)
2. Apakah perlu opsi "tunda pengumuman libur sampai jam jadwal" di UI?
   (v1: selalu kirim sekarang.)

## 20. Riwayat perubahan

| Tanggal | Perubahan |
|---|---|
| 20 Nov 2026 | Dokumen awal: skema, planner, runner, UI, `/cancel-schedule`, fase implementasi. |
| 20 Nov 2026 | Tambah K14: seluruh kata kunci webhook libur tidak case-sensitive (prefix, `Alasan`, kode). |
| 20 Nov 2026 | Ganti rencana fase dengan validasi **SMART** per fase + gate & timebox (15.1–15.6). |
| 20 Nov 2026 | Fase 1–4 selesai: migrasi, model, planner/runner, UI CRUD, `/cancel-schedule`. Suite 256 test hijau; evidence di `docs/evidence/notif-terjadwal/`. Fase 5 (UAT live) menunggu WAHA & grup staging. |
| 20 Nov 2026 | **Multi-chat**: tabel `scheduled_notification_targets`, kolom run `sent_targets`/`error_targets`, form/daftar/detail menampilkan banyak chat, kirim tes & pengumuman libur ke semua target, runner dengan partial retry. Suite 277 test hijau. |

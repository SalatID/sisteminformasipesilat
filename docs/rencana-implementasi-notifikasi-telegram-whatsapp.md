# Rencana Implementasi Notifikasi Multi-Provider (Telegram + WhatsApp/WAHA)

| Item | Keterangan |
|---|---|
| **Repositori** | `sisteminformasipesilat` (`D:\Project\web-base\sisteminformasipesilat`) |
| **Versi dokumen** | **2.0** (lihat Riwayat Perubahan, Bagian 11) |
| **Tanggal** | 17 November 2026 |
| **Status** | **Fase 0–8 diimplementasikan, diuji ulang 51 gate (51 PASS), dan diceklist; menunggu soak test 7 hari + persetujuan go-live grup** |
| **Pemilik proses** | Tim aplikasi SIP (Asisten Pelatih) |
| **Referensi kode** | `app/Console/Commands/SendAttendanceNotificationCommand.php`, `app/Http/Controllers/AttendanceController.php`, `app/Http/Controllers/AdminCotroller.php`, `config/services.php` |

---

## 1. Ringkasan Eksekutif

Saat ini seluruh notifikasi absensi dikirim **hanya ke Telegram** melalui satu command (`attendance:notify`) yang dipanggil cron eksternal. Tujuan perubahan:

1. Menambah **WhatsApp (WAHA/NOWEB)** sebagai provider kedua tanpa menghilangkan Telegram.
2. Provider dapat diaktifkan salah satu atau keduanya melalui `.env`.
3. Pengiriman dijadwalkan **setiap 3 jam** dengan proteksi anti-duplikasi.
4. Mengubah pengingat absensi dari **1 unit = 1 pesan** menjadi **1 pesan gabungan untuk semua unit**, untuk menekan risiko deteksi bot WhatsApp.
5. Laporan absensi tetap **1 unit = 1 pesan** dan dikirim sekali berdasarkan `attendances.is_notif_send = false`.
6. Pengujian awal hanya ke chat pribadi `6289654157132@c.us`, belum ke grup.
7. Pengingat absensi **men-tag (@mention) PJ unit** berdasarkan nomor WhatsApp yang tersimpan di sistem (kolom `nomor_whatsapp` pada tabel `coachs` — **migrasi & form sudah diimplementasikan**, lihat Fase 3).

Dokumen ini memuat rencana per fase beserta **tujuan SMART** dan **kriteria validasi** yang harus dibuktikan sebelum fase berikutnya dimulai.

---

## 2. Kondisi Saat Ini (Baseline)

### 2.1 Alur yang berjalan

| Komponen | Perilaku | Catatan |
|---|---|---|
| `attendance:notify` | Mengirim laporan absensi yang belum terkirim (`is_notif_send = false`) | 1 attendance = 1 pesan |
| `sendTodaysTrainingReminders()` | Unit latihan hari ini & belum ada absensi | **1 unit = 1 pesan**, diulang setiap run cron (asumsi 30 menit) |
| `sendOverdueTrainingReminders()` | Keterlambatan > 6 hari, hanya jam 19 | 1 unit = 1 pesan; ada bug `Unit::where(...)->all()` |
| `sendEndOffProcessMessage()` | Pesan separator setiap run | Noise, tidak punya nilai bisnis |
| `resendNotif($id)` | Kirim ulang 1 laporan dari UI (`AttendanceController@resendNotif`) | Harus tetap kompatibel |
| Scheduler | `app/Console/Kernel.php` dan `routes/console.php` kosong | Cron eksternal (perlu verifikasi) |

### 2.2 Temuan teknis yang harus ditangani

| ID | Temuan | Dampak |
|---|---|---|
| T-01 | `*bold*` dikirim ke Telegram dengan `parse_mode=html` | Asterisk tampil literal |
| T-02 | `Log::info()` mencetak bot token & chat ID | Kebocoran kredensial |
| T-03 | `Unit::where(...)->all()` | Berpotensi `BadMethodCallException` pada reminder overdue (jam 19:00) |
| T-04 | `$coach->ts->ts_seq` tanpa null check | Error bila data TS tidak ada |
| T-05 | Satu kolom `is_notif_send` untuk semua channel | Tidak bisa retry per provider |
| T-06 | Dependency `kstmostofa/laravel-whatsapp` belum dipakai | Kandidat dihapus; WAHA jadi jalur utama |
| **T-07** | **Tabel `coachs` tidak memiliki kolom nomor telepon** (hanya `name`, `ts_id`, `coach_exam_date`, `coach_exam_at`); tabel `users` juga tidak punya nomor | **Rule 7 (tag PJ) tidak bisa dieksekusi tanpa migrasi data baru** |
| **T-08** | `is_notif_send` diset `true` **segera setelah** `sendTelegramMessage()`, tanpa memeriksa hasil pengiriman | Notifikasi yang gagal terkirim tetap ditandai "terkirim" → pesan hilang permanen |

### 2.3 Catatan penyebutan user

User menyebut field `is_notif_sed`; nama kolom aktual di migrasi `2026_01_10_000000_add_is_notif_send_to_attendances.php` adalah **`is_notif_send`** (boolean, default `false`). Dokumen ini memakai nama kolom yang benar.

---

## 3. Tujuan, Ruang Lingkup, dan Non-Tujuan

### 3.1 Tujuan (Requirement)

| ID | Requirement | Sumber |
|---|---|---|
| R1 | Notifikasi dikirim setiap **3 jam** sekali | Permintaan pengguna (rule 1) |
| R2 | Pengingat absensi menjadi **1 pesan gabungan** untuk semua unit yang belum absen (berlaku untuk Telegram & WhatsApp) | Permintaan pengguna (rule 2) |
| R3 | Laporan absensi tetap **1 unit = 1 pesan**, sekali kirim (`is_notif_send`) | Permintaan pengguna (rule 3) |
| R4 | Provider dapat dikonfigurasi via `.env` (Telegram, WhatsApp, atau keduanya) | Permintaan pengguna (rule 4) |
| R5 | Pesan yang dikirim tidak berpotensi tinggi terdeteksi sebagai bot | Permintaan pengguna (rule 5) |
| R6 | Pengujian awal dikirim ke `6289654157132@c.us`, bukan grup | Permintaan pengguna (rule 6) |
| **R7** | **Pengingat absensi men-tag PJ unit (@mention) di WhatsApp berdasarkan data sistem; kolom `nomor_whatsapp` pada `coachs` + backfill data** | Permintaan pengguna (rule 7) |

### 3.2 Ruang lingkup

- Refactor command `attendance:notify`.
- Penambahan channel Telegram & WhatsApp di atas abstraksi yang sama.
- Penjadwalan Laravel Scheduler + guard interval.
- Migrasi kecil: tracking per provider (`attendances`) dan nomor telepon PJ (`coachs`).
- Penambahan input nomor telepon pada form coach (`AdminCotroller@coachStore/coachUpdate`, `resources/views/pages/admin/coach/form.blade.php`) + backfill data PJ.
- Konfigurasi `.env` baru.
- Pengujian manual & otomatis.

### 3.3 Non-tujuan (fase ini)

- Membuat dashboard/log notifikasi berbasis UI.
- Mengirim media/gambar melalui WhatsApp.
- Mengirim pesan **per-unit ke nomor PJ masing-masing** (chat terpisah per PJ) — pengiriman tetap **satu chat tujuan** (grup/kontak test); PJ hanya di-**@mention di dalam isi pesan** sesuai R7.
- Notifikasi dua arah (membalas pesan masuk).
- Menghapus paket `kstmostofa/laravel-whatsapp` (dilakukan pada perubahan terpisah).

---

## 4. Arsitektur Target

```
attendance:notify (scheduler 3 jam)
        │
        ├── AttendanceRepository / Query
        │
        ├── AttendanceMessageBuilder
        │      ├── buildConsolidatedReminder(units)   → 1 pesan + daftar mention PJ
        │      └── buildAttendanceReport(attendance)  → 1 pesan/unit
        │
        └── NotificationManager
               ├── TelegramChannel  ──► api.telegram.org
               │        └── mention PJ = nama biasa (Telegram bot API tidak
               │            bisa mention user tanpa username)
               └── WhatsAppChannel  ──► WAHA /api/sendText
                        ├── text     : mengandung "@62812xxx" per PJ
                        ├── mentions : ["62812xxx@c.us", ...]  (nama field BENAR, lihat 4.3)
                        │
                        └── ChannelResult { success, httpStatus, error }
```

### 4.1 Struktur file baru

```
app/Services/Notification/
├── Contracts/NotificationChannel.php      # send(string $text, array $mentions = []): ChannelResult
├── Channels/TelegramChannel.php
├── Channels/WhatsAppChannel.php
├── AttendanceMessageBuilder.php
└── NotificationManager.php

config/notifications.php
database/migrations/xxxx_add_notif_sent_channels_to_attendances.php
database/migrations/2026_11_17_000000_add_nomor_whatsapp_to_coachs.php  # sudah diimplementasikan
app/Support/PhoneNormalizer.php            # sudah diimplementasikan
tests/Unit/Notification/ChannelTest.php
tests/Unit/Notification/MessageBuilderTest.php
```

### 4.2 Konfigurasi `.env`

```ini
# Provider aktif: telegram | whatsapp | telegram,whatsapp | kosong = nonaktif
NOTIF_PROVIDERS=telegram,whatsapp
NOTIF_INTERVAL_HOURS=3
NOTIF_SEND_END_MARKER=false
NOTIF_TEST_MODE=true
NOTIF_TEST_CHAT_ID=6289654157132@c.us

# Tag PJ unit pada WhatsApp (R7). false = PJ hanya nama biasa
NOTIF_WA_MENTION_ENABLED=true
# Maksimum PJ di-tag dalam 1 pesan (jaga pola manusiawi)
NOTIF_WA_MENTION_MAX=10

TELEGRAM_BOT_TOKEN=<token>
TELEGRAM_CHAT_ID=<chat_id>

WAHA_URL=https://wahanoweb-666b42.app3.flaz.my.id
WAHA_API_KEY=<api_key>
WAHA_SESSION=sessionsmi
WAHA_CHAT_ID=120363404437691633@g.us
```

### 4.3 Mekanisme @mention WhatsApp (WAHA)

- Request: `POST {WAHA_URL}/api/sendText` dengan body:
  ```json
  {
    "session": "sessionsmi",
    "chatId": "6289654157132@c.us",
    "text": "... 1. Unit A — PJ: @6281234567890 (Budi) ...",
    "mentions": ["6281234567890@c.us"]
  }
  ```
- **Syarat**: teks harus mengandung `@<nomor>` tanpa suffix `@c.us`, dan field `mentions` berisi chatId lengkap `<nomor>@c.us` agar tag ter-render dan men-trigger notifikasi di ponsel PJ.
- Nomor disimpan dinormalisasi ke format **E.164 tanpa tanda `+`** (contoh `6281234567890`).
- **Fallback**: jika `nomor_whatsapp` PJ kosong/tidak valid, atau `NOTIF_WA_MENTION_ENABLED=false`, tulis nama PJ biasa (`PJ: Budi`) tanpa mention — pengiriman tidak gagal.
- **⚠️ Koreksi hasil implementasi (Fase 2):** nama field yang **benar** adalah **`mentions`**, bukan `mentioned`. Uji terhadap engine `NOWEB` membuktikan `mentioned`/`mentionIds` **diabaikan** server (pesan terkirim tanpa `contextInfo.mentionedJid`). Contoh payload final:
  ```json
  {
    "session": "sessionsmi",
    "chatId": "6289654157132@c.us",
    "text": "... PJ: @6289654157132 (Mursalat Asyidiq) ...",
    "mentions": ["6289654157132@c.us"]
  }
  ```
- Telegram: bot API hanya bisa mention via username; jika PJ tidak punya username, tampilkan nama biasa.
- **Kebijakan tag per channel (keputusan pemilik proses, 17 Nov 2026):** notifikasi Telegram **tidak memakai tag `@` sama sekali**. Nomor WA tidak pernah tampil di Telegram — teks Telegram berisi nama PJ polos (`PJ: Mursalat Asyidiq`). Tag `@<nomor>` hanya untuk WhatsApp. Implementasi: `AttendanceMessageBuilder::buildConsolidatedReminder()`/`buildAttendanceReport()` punya parameter `$supportTags` terpisah dari `$supportMentions`; `NotificationManager::send()` menerima `$textWithoutTags` yang dipakai channel tanpa tag.

---

## 5. Matriks Traceability

| Requirement | Fase | Acceptance Criteria |
|---|---|---|
| R1 (3 jam) | Fase 5 | AC-06, AC-07 |
| R2 (gabungan) | Fase 4 | AC-04, AC-05 |
| R3 (laporan tetap) | Fase 4, 7 | AC-08, AC-10 |
| R4 (env provider) | Fase 1, 2 | AC-01, AC-02, AC-03 |
| R5 (anti-bot) | Fase 4, 5, 8 | AC-11 |
| R6 (chat testing) | Fase 1, 8 | AC-09 |
| **R7 (tag PJ)** | **Fase 2, 3, 4, 8** | **AC-13, AC-14, AC-15** |

### Daftar Acceptance Criteria

| ID | Kriteria |
|---|---|
| AC-01 | `NOTIF_PROVIDERS` mengubah channel aktif tanpa perubahan kode |
| AC-02 | Provider tidak terdaftar tidak dieksekusi (0 request) |
| AC-03 | Kegagalan 1 provider tidak menggagalkan provider lain |
| AC-04 | N unit belum absen menghasilkan tepat 1 pesan reminder |
| AC-05 | Reminder kosong tidak mengirim pesan apa pun |
| AC-06 | Maksimal 1 siklus pengiriman per 3 jam |
| AC-07 | Run ganda tidak menghasilkan duplikasi laporan |
| AC-08 | Laporan tetap 1 pesan per attendance, field lengkap |
| AC-09 | Test mode mengarah ke `6289654157132@c.us` dengan prefix `[TEST]` |
| AC-10 | `attendance:notify {id}` tetap berfungsi (backward compatible) |
| AC-11 | Tidak ada pesan separator; teks bervariasi; delay antar pesan ≥ 3 detik |
| AC-12 | Tidak ada kredensial di log |
| **AC-13** | **PJ dengan `phone` valid muncul sebagai @mention yang bisa diklik di WhatsApp (bukti screenshot dari chat test)** |
| **AC-14** | **PJ tanpa `phone`/`phone` tidak valid ditampilkan sebagai nama biasa, tanpa error, tanpa mention** |
| **AC-16** | **Notifikasi Telegram tidak memuat tag `@<nomor>` sama sekali (nama PJ polos); tag `@<nomor>` hanya pada WhatsApp** |
| **AC-15** | **Form coach menerima & menyimpan nomor dalam format ternormalisasi `62812…`; input `0812…`/`+62…` otomatis dinormalisasi; validasi menolak format tidak valid** |

---

## 6. Rencana per Fase dengan Validasi SMART

> **Aturan umum:** setiap fase hanya boleh dinyatakan selesai jika seluruh baris "Exit Criteria" terpenuhi dan bukti validasi dilampirkan pada `docs/evidence/fase-N/`.

### Fase 0 — Inventaris & Keputusan

**Tujuan:** memastikan asumsi teknis benar sebelum mengubah kode.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Mendata jadwal cron server, zona waktu, target chat produksi/test, status paket `kstmostofa/laravel-whatsapp`, **dan inventaris nomor WhatsApp PJ yang valid** |
| **M**easurable | Dokumen inventaris terisi 5/5 item; hasil `crontab -l` dan `php artisan about` terlampir; daftar PJ (nama unit + nomor WA) terisi ≥ 90% |
| **A**chievable | Hanya butuh akses server & repository, tanpa perubahan kode |
| **R**elevant | Menentukan strategi transisi jadwal 3 jam, target pengiriman, dan kelengkapan data untuk R7 |
| **T**ime-bound | Selesai dalam **1 hari kerja** (17–18 November 2026) |

**Deliverable:** `docs/evidence/fase-0/inventaris.md`

**Langkah:**
1. `crontab -l` → catat frekuensi pemanggilan `attendance:notify`.
2. `php artisan about` → konfirmasi timezone `Asia/Jakarta`.
3. Tentukan target produksi (grup `120363404437691633@g.us` atau nomor PJ).
4. Konfirmasi `NOTIF_TEST_CHAT_ID=6289654157132@c.us`.
5. Putuskan: paket sidecar tidak dipakai.
6. **Kumpulkan nomor WhatsApp aktif semua PJ unit dari admin (untuk backfill Fase 3).**

**Validasi:**
```bash
crontab -l | grep attendance
php artisan about | grep -i timezone
php artisan tinker --execute="echo config('app.timezone');"
```

**Exit Criteria:**
- [x] Jadwal cron lama terdokumentasi. *(17 Nov 2026 — `app/Console/Kernel.php` & `routes/console.php` kosong; cron eksternal dicatat)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Timezone terkonfirmasi `Asia/Jakarta`. *(17 Nov 2026 — `php artisan about` + `config('app.timezone')`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Target test & produksi disetujui. *(17 Nov 2026 — test `6289654157132@c.us`, produksi grup `120363404437691633@g.us`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Keputusan paket sidecar tercatat. *(17 Nov 2026 — sidecar tidak dipakai, WAHA jalur utama)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Daftar nomor WA PJ siap di-backfill. *(17 Nov 2026 — 12 PJ unik terinventaris, lalu diisi pada Fase 3)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** tidak ada perubahan sistem.

---

### Fase 1 — Konfigurasi Provider

**Tujuan:** menjadikan provider notifikasi dapat dikontrol dari `.env`.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Membuat `config/notifications.php`, memindahkan kredensial Telegram/WAHA ke env, dan menambahkan flag mention PJ (`NOTIF_WA_MENTION_ENABLED`, `NOTIF_WA_MENTION_MAX`) |
| **M**easurable | 3 kombinasi env (`telegram`, `whatsapp`, `telegram,whatsapp`) menghasilkan daftar provider yang benar; 0 kredensial hardcoded |
| **A**chievable | Murni konfigurasi Laravel, tanpa dependency baru |
| **R**elevant | Memenuhi R4, prasyarat R7, dan menjadi fondasi Fase 2 |
| **T**ime-bound | **0,5 hari kerja** |

**Deliverable:** `config/notifications.php`, `.env.example`, `docs/evidence/fase-1/config-matrix.md`

**Langkah:**
1. Buat `config/notifications.php` sesuai Bagian 4.2.
2. Tambahkan variabel ke `.env` dan `.env.example`.
3. Tambahkan `WAHA_*` (URL, key, session, chat id).
4. Hapus pencatatan token pada log.

**Validasi:**
```bash
php artisan config:clear
php artisan config:show notifications

# Matriks 3 kombinasi
NOTIF_PROVIDERS=telegram php artisan tinker --execute="print_r(config('notifications.providers'));"
NOTIF_PROVIDERS=whatsapp php artisan tinker --execute="print_r(config('notifications.providers'));"
NOTIF_PROVIDERS=telegram,whatsapp php artisan tinker --execute="print_r(config('notifications.providers'));"
```

**Exit Criteria:**
- [x] AC-01: provider berubah hanya via env. *(17 Nov 2026 — 5 kombinasi env diuji)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-12: tidak ada token di `storage/logs/*`. *(17 Nov 2026 — log historis diredaksi, pemindaian `CLEAN`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] `config:show notifications` menampilkan struktur benar (termasuk `mention.enabled` & `mention.max`). *(17 Nov 2026)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** hapus `config/notifications.php`, kembalikan `.env` ke `TELEGRAM_*` saja.

---

### Fase 2 — Abstraksi Channel (Telegram & WhatsApp)

**Tujuan:** menyatukan pengiriman ke satu antarmuka sehingga penambahan provider tidak mengubah command.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Membuat `NotificationChannel`, `TelegramChannel`, `WhatsAppChannel`, `NotificationManager`; kontrak `send(string $text, array $mentions = [])`; `WhatsAppChannel` meneruskan `mentions` ke WAHA |
| **M**easurable | Minimal **7 unit test** lulus (2 sukses, 2 gagal HTTP, 1 timeout, 1 provider tidak aktif, 1 payload `mentions` benar); manager mengembalikan hasil per provider; **1 probe manual ke WAHA membuktikan @mention ter-render** |
| **A**chievable | Menggunakan `Illuminate\Support\Facades\Http` yang sudah tersedia |
| **R**elevant | Memenuhi R4, AC-03 (isolasi kegagalan), dan prasyarat teknis R7 |
| **T**ime-bound | **0,5–1 hari kerja** |

**Deliverable:** 5 file di `app/Services/Notification/`, `tests/Unit/Notification/ChannelTest.php`, `docs/evidence/fase-2/mention-probe.md`

**Langkah:**
1. Definisikan kontrak `send(string $text, array $mentions = []): ChannelResult`.
2. Implementasi Telegram (`sendMessage`, konversi bold → HTML; abaikan `mentions`).
3. Implementasi WhatsApp (`POST {WAHA_URL}/api/sendText` dengan `session`, `chatId`, `text`, `mentions`).
4. `NotificationManager` melakukan loop provider aktif + mengumpulkan hasil.
5. Tambahkan delay 3 detik antar pesan WhatsApp.
6. **Probe sekali ke `6289654157132@c.us` dengan `mentions` untuk memastikan engine WAHA (NOWEB) mendukung mention; jika tidak, catat di evidence dan kunci fallback nama biasa.**

**Validasi:**
```bash
php artisan test --filter=ChannelTest

# Uji manual WhatsApp ke nomor test (termasuk mention)
php artisan tinker --execute="
  app(App\Services\Notification\Channels\WhatsAppChannel::class)
    ->send('[TEST] Halo @6289654157132', ['6289654157132@c.us']);
"
```

**Exit Criteria:**
- [x] AC-02: provider nonaktif menghasilkan 0 request. *(17 Nov 2026 — test `inactive provider is not called`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-03: kegagalan WhatsApp tidak menggagalkan Telegram (dibuktikan dengan test fake HTTP). *(17 Nov 2026 — test `whatsapp failure does not break telegram`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Seluruh unit test lulus. *(17 Nov 2026 — 9/9 ChannelTest)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] **@mention ter-render bisa diklik pada chat test (screenshot).** *(17 Nov 2026 — `contextInfo.mentionedJid: ["6289654157132@s.whatsapp.net"]` pada objek pesan WAHA; bukti mesin, bukan screenshot)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** command lama tetap utuh hingga Fase 7; channel baru belum dipakai produksi.

---

### Fase 3 — Data Nomor PJ: Migrasi, Form, & Backfill (BARU untuk R7)

**Tujuan:** menyediakan nomor WhatsApp PJ sebagai data sistem sehingga pengingat bisa men-tag PJ.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Migrasi kolom `nomor_whatsapp` (nullable, varchar 20) pada `coachs`; input/validasi pada form coach (`AdminCotroller@coachStore/coachUpdate` + `resources/views/pages/admin/coach/form.blade.php`); normalisasi `0812…`/`+62…`/`62…` → `62812…` via `App\Support\PhoneNormalizer`; backfill nomor PJ hasil Fase 0 |
| **M**easurable | Migrasi sukses; **100% PJ unit aktif** memiliki `phone` valid (atau tercatat eksplisit "belum ada nomor"); 3 kasus normalisasi (`0812…`, `+62812…`, `62812…`) lulus unit test |
| **A**chievable | Migrasi aditif (nullable), tanpa perubahan data lama; form hanya menambah 1 field |
| **R**elevant | Prasyarat mutlak R7 (temuan T-07); tag tanpa nomor mustahil dilakukan WhatsApp |
| **T**ime-bound | **0,5 hari kerja** (migrasi+form) + backfill berjalan paralel hingga Fase 8 |

**Deliverable:** migrasi, `app/Support/PhoneNormalizer.php`, update `AdminCotroller.php` + form blade, `tests/Unit/Support/PhoneNormalizerTest.php`, `docs/evidence/fase-3/backfill-status.md`

**Langkah:**
1. ~~Migrasi `Schema::table('coachs', ... $table->string('phone', 20)->nullable())`~~ **✅ Selesai 17 Nov 2026 — `2026_11_17_000000_add_nomor_whatsapp_to_coachs.php`, kolom `nomor_whatsapp` varchar(20) nullable.**
2. Buat `PhoneNormalizer` (buang non-digit, `0` → `62`, buang prefix `+`/`62` ganda, validasi panjang 9–15 digit). **✅ Selesai — `app/Support/PhoneNormalizer.php` (lulus uji manual 8 kasus).**
3. Tambah field `nomor_whatsapp` pada form & validasi controller; simpan hasil normalisasi. **✅ Selesai — form, list, dan detail coach menampilkan nomor; validasi closure + normalisasi di `coachStore`/`coachUpdate`.**
4. Tambah `$fillable['nomor_whatsapp']` pada model `Coach`. **✅ Selesai.**
5. Backfill nomor PJ aktif (dari inventaris Fase 0) via admin UI atau seeder sekali pakai. **✅ Selesai 17 Nov 2026 — 17 dari 18 unit terisi (94,4%); Zaenal Arifin (SDN Pegadungan 01 Pagi) tercatat belum ada nomor dan memakai fallback nama biasa.**
6. Fallback aman: PJ tanpa nomor tetap valid (AC-14).

**Validasi:**
```bash
php artisan migrate
php artisan test --filter=PhoneNormalizerTest

# Cakupan data
php artisan tinker --execute="
  \$total = App\Models\Unit::where('is_active', true)->with('pj')->count();
  \$withPhone = App\Models\Unit::where('is_active', true)->get()
      ->filter(fn(\$u) => \$u->pj && preg_match('/^62\d{9,13}$/', (string)\$u->pj->nomor_whatsapp))->count();
  echo \"PJ dengan nomor_whatsapp valid: {\$withPhone}/{\$total}\";
"
```

**Exit Criteria:**
- [x] Migrasi berjalan tanpa error; rollback migration teruji. *(17 Nov 2026)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Form coach menerima & menormalisasi nomor (AC-15). *(17 Nov 2026 — 11/11 `PhoneNormalizerTest` lulus)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Cakupan `nomor_whatsapp` PJ aktif ≥ 90% saat Fase 8 dimulai (sisanya tercatat eksplisit). *(17 Nov 2026 — 17/18 = 94,4%; Zaenal Arifin tercatat tanpa nomor)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Data lama tidak berubah. *(17 Nov 2026 — migrasi aditif nullable, 0 baris lama dimodifikasi)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** `php artisan migrate:rollback --step=1`; field form dihapus.

---

### Fase 4 — Format Pesan (Konsolidasi Reminder + Tag PJ & Laporan)

**Tujuan:** mengubah reminder menjadi satu pesan gabungan yang men-tag PJ, dan menyesuaikan format tiap channel.

| Elemen SMART | Target |
|---|---|
| **S**pecific | `AttendanceMessageBuilder` menghasilkan (a) 1 pesan reminder gabungan berisi daftar unit + `@<phone>` PJ + array `mentions`, (b) 1 pesan laporan per attendance tanpa perubahan struktur |
| **M**easurable | Uji dengan 3 unit & 30 unit → tepat **1 pesan** (dipecah hanya jika > 4096 karakter untuk Telegram); mention dibatasi `NOTIF_WA_MENTION_MAX` (sisanya nama biasa); laporan memuat **13 field** yang sama seperti baseline |
| **A**chievable | Hanya mengubah builder, tanpa mengubah query data |
| **R**elevant | Memenuhi R2, R3, R5, R7 |
| **T**ime-bound | **0,5 hari kerja** |

**Deliverable:** `AttendanceMessageBuilder.php`, `tests/Unit/Notification/MessageBuilderTest.php`

**Langkah:**
1. Pindahkan `buildAttendanceMessage()` ke builder (perbaiki T-01: HTML untuk Telegram, `*bold*` untuk WA).
2. Buat `buildConsolidatedReminder()` → return `{ text, mentions }` per channel.
3. Null-safe pada relasi `ts`, `ts_seq`, dan `pj` (perbaiki T-04).
4. Hapus parameter separator dari jalur WhatsApp.
5. Sisipkan `@<phone>` pada baris unit yang PJ-nya punya nomor valid; kumpulkan `mentions[]`; batasi jumlah tag sesuai config; fallback nama biasa (AC-14).
6. Batasi link foto pada laporan (maks 4 atau ringkas) untuk bot-safety (R5).

> **Kebijakan header (keputusan pemilik proses, 17 Nov 2026):** header `*[BOT ASSISTEN PELATIH]*` **dihapus** dari semua pesan karena kata "BOT" berisiko memicu filter spam WhatsApp dan menandai nomor sebagai automation unofficial. Pesan kini langsung dimulai dari judul jenis pesan (`*Pengingat Absensi Latihan*` atau `*Absensi Latihan*`).

**Contoh keluaran reminder (WhatsApp):**

```
*Pengingat Absensi Latihan*

Tanggal: 17/11/2026
Unit yang belum mengirim laporan absensi:

1. Unit A (16:00–18:00) — PJ: @6281234567890 (Budi)
2. Unit B (08:00–10:00) — PJ: Sari
3. Unit C (19:00–21:00) — PJ: @6289876543210 (Andi)

Total: 3 unit.
Mohon lengkapi laporan absensi melalui aplikasi.
```

→ dikirim dengan `mentions: ["6281234567890@c.us", "6289876543210@c.us"]`. Unit B tanpa nomor tampil sebagai nama biasa.

**Validasi:**
```bash
php artisan test --filter=MessageBuilderTest
php artisan attendance:notify --type=reminder --dry-run
```

**Exit Criteria:**
- [x] AC-04: N unit → 1 pesan. *(17 Nov 2026 — diuji 3, 30 unit, dan 4 unit riil)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-05: daftar kosong → 0 pesan. *(17 Nov 2026 — builder mengembalikan `null`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-08: laporan tetap lengkap 13 field. *(17 Nov 2026 — test `attendance report contains all 13 fields`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-13: teks + array `mentions` konsisten (setiap `@<nomor>` di teks ada di array). *(17 Nov 2026 — KONSISTEN: YA)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-14: PJ tanpa nomor → nama biasa, tanpa error. *(17 Nov 2026 — Zaenal Arifin)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-16: notifikasi Telegram tidak memuat tag `@<nomor>`. *(17 Nov 2026 — payload Telegram terverifikasi `PJ: Mursalat Asyidiq` tanpa `@`; WhatsApp tetap `@6289654157132` + `mentions`; 2 unit test baru)*
- [x] Tidak ada asterisk literal di Telegram (screenshot bukti). *(17 Nov 2026 — `TelegramChannel` mengonversi `*bold*` → `<b>`; terverifikasi UAT #3 `message_id 8102`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** builder baru tidak dipakai sebelum Fase 7.

---

### Fase 5 — Jadwal 3 Jam & Guard Anti-Duplikasi

**Tujuan:** memastikan pengiriman maksimal sekali per 3 jam, apa pun frekuensi cron.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Menambahkan `Schedule::command(...)->everyThreeHours()` dan guard `Cache` berbasis interval |
| **M**easurable | Simulasi **6 pemanggilan dalam 1 jam** menghasilkan **maksimal 1 pengiriman**; `--force` menghasilkan 2 pengiriman |
| **A**chievable | Laravel Scheduler + cache file/redis yang sudah tersedia |
| **R**elevant | Memenuhi R1 dan AC-06/AC-07 |
| **T**ime-bound | **0,5 hari kerja** |

**Deliverable:** `routes/console.php`, guard di command, `docs/evidence/fase-5/guard-test.md`

**Langkah:**
1. Daftarkan schedule `everyThreeHours()->withoutOverlapping()->onOneServer()`.
2. Tambahkan `Cache::get/put('attendance:notify:last_run')`.
3. Pastikan `attendance:notify {id}` (resend) melewati guard.
4. Ubah cron server menjadi `* * * * * php artisan schedule:run`.

**Validasi:**
```bash
# Simulasi 6 pemanggilan
for i in 1 2 3 4 5 6; do php artisan attendance:notify --type=reminder --dry-run; done
php artisan schedule:list
grep "Skipped" storage/logs/cron/cron.log | tail -5
```

**Exit Criteria:**
- [x] AC-06: hanya 1 siklus per 3 jam. *(17 Nov 2026 — 6 panggilan → 1 kirim)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] AC-07: tidak ada laporan ganda. *(17 Nov 2026 — run kedua "Tidak ada laporan absensi yang perlu dikirim")* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] `attendance:notify {id}` tetap melewati guard. *(17 Nov 2026 — resend exit 0 tanpa cek interval)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] `schedule:list` menampilkan command terdaftar. *(17 Nov 2026 — `0 */3 * * *`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** set `NOTIF_INTERVAL_HOURS=0` untuk menonaktifkan guard dan kembalikan cron lama.

---

### Fase 6 — Tracking per Provider

**Tujuan:** kegagalan salah satu provider tidak menghilangkan kesempatan provider lain.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Menambah kolom JSON `notif_sent_channels` dan logika retry per provider; memperbaiki T-08 (`is_notif_send=true` hanya setelah terbukti sukses) |
| **M**easurable | Skenario Telegram gagal + WhatsApp sukses → `is_notif_send=false`, `notif_sent_channels=["whatsapp"]`; run berikutnya **hanya** mengirim Telegram (dibuktikan 1 request) |
| **A**chievable | Migrasi aditif, tidak mengubah data lama |
| **R**elevant | Mendukung R4 dan AC-03; menutup bug kehilangan notifikasi (T-08) |
| **T**ime-bound | **0,25 hari kerja** |

**Deliverable:** file migrasi, `docs/evidence/fase-6/retry-test.md`

**Langkah:**
1. Migrasi `notif_sent_channels` (nullable JSON).
2. Update model `Attendance` (`$casts`).
3. Manager mencatat provider yang sukses.
4. `is_notif_send=true` hanya jika semua provider aktif sukses (perbaikan T-08).

**Validasi:**
```bash
php artisan migrate
php artisan tinker --execute="
  \$a = App\Models\Attendance::first();
  echo json_encode(\$a->notif_sent_channels);
"
```

**Exit Criteria:**
- [x] Migrasi berjalan tanpa error. *(17 Nov 2026 — kolom `notif_sent_channels` json nullable)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Retry hanya ke provider yang belum sukses. *(17 Nov 2026 — test `failed provider is retried alone on next run`, 1 request)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Data lama tetap valid (`is_notif_send` tidak berubah). *(17 Nov 2026 — 471 attendance utuh, 237 `is_notif_send=false` dipertahankan)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** `php artisan migrate:rollback --step=1`.

---

### Fase 7 — Refactor Command & Integrasi Controller

**Tujuan:** menjadikan `attendance:notify` sebagai orchestrator yang mendukung provider ganda.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Refactor command dengan opsi `--type`, `--dry-run`, `--force`, tetap mendukung argumen `attendance_id` |
| **M**easurable | 5 opsi/argumen berfungsi; `resendNotif` dari UI berhasil (1 klik = 1 pesan) |
| **A**chievable | Memakai service dari fase sebelumnya; tidak mengubah route |
| **R**elevant | Memenuhi R2, R3, R4, R6, AC-10 |
| **T**ime-bound | **0,25 hari kerja** |

**Deliverable:** command ter-refactor, `docs/evidence/fase-7/command-matrix.md`

**Langkah:**
1. Ganti pemanggilan `sendTelegramMessage()` dengan `NotificationManager`.
2. Tambahkan opsi `--type` dan `--dry-run`.
3. Pertahankan `attendance:notify {id}`.
4. Hapus `sendEndOffProcessMessage()` dari jalur default (jaga via `NOTIF_SEND_END_MARKER`).

**Validasi:**
```bash
php artisan attendance:notify --type=report --dry-run
php artisan attendance:notify --type=reminder --dry-run
php artisan attendance:notify {id} --force
```

**Exit Criteria:**
- [x] AC-10: resend via UI berfungsi. *(17 Nov 2026 — `attendance:notify {id}` → exit 0, telegram & whatsapp OK)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Semua opsi menghasilkan perilaku sesuai definisi. *(17 Nov 2026 — 7 baris matriks validasi)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [x] Tidak ada pemanggilan API langsung di command (semua via channel). *(17 Nov 2026 — 0 match `Http::`/URL API di command)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*

**Rollback:** `git revert` commit Fase 7; Fase 1–6 tetap aman.

---

### Fase 8 — UAT, Bot-Safety & Go-Live Bertahap

**Tujuan:** membuktikan sistem stabil dan aman sebelum dikirim ke grup produksi.

| Elemen SMART | Target |
|---|---|
| **S**pecific | Menjalankan 12 skenario uji ke `6289654157132@c.us`, lalu soak test 7 hari |
| **M**easurable | **12/12** skenario lulus; **0** duplikat; **≤ 30 pesan/hari**; **0** sesi WAHA terputus; 100% laporan tersimpan di log |
| **A**chievable | Volume kecil (1 pesan gabungan/3 jam + laporan), nomor khusus |
| **R**elevant | Memenuhi R5, R6, R7; memvalidasi seluruh AC termasuk mention PJ end-to-end |
| **T**ime-bound | **1 hari UAT + 7 hari observasi** (selesai paling lambat 28 November 2026) |

**Deliverable:** `docs/evidence/fase-8/uat-report.md`, `docs/evidence/fase-8/soak-test.md`

**Matriks UAT:**

| # | Skenario | Ekspektasi |
|---|---|---|
| 1 | `--dry-run --type=reminder` | Teks gabungan tercetak, 0 request |
| 2 | WhatsApp test mode | 1 pesan `[TEST]` ke `6289654157132@c.us` |
| 3 | Telegram | Bold tampil benar |
| 4 | Kedua provider | 2 hasil sukses di log |
| 5 | API key WAHA salah | Telegram tetap terkirim, `is_notif_send=false` |
| 6 | Run 2x berurutan | Run kedua skip (guard) |
| 7 | `attendance:notify {id}` | Kirim ulang 1 laporan |
| 8 | Data pelatih/ts kosong | Tidak error |
| 9 | 30 unit belum absen | 1 pesan (dipecah bila > 4096 char); tag dibatasi `NOTIF_WA_MENTION_MAX` |
| 10 | Test mode off | Kirim ke target produksi |
| **11** | **Reminder dengan PJ bernomor valid & PJ tanpa nomor** | **PJ bernomor ter-tag (bisa diklik); PJ tanpa nomor tampil nama biasa; tidak ada error (AC-13, AC-14)** |
| **12** | **`NOTIF_WA_MENTION_ENABLED=false`** | **Semua PJ tampil sebagai nama biasa, tanpa field `mentions` pada payload** |

**Metrik bot-safety (harian):**

| Metrik | Ambang |
|---|---|
| Pesan keluar/hari | ≤ 30 |
| Pesan identik berulang | 0 (selalu ada variasi tanggal/jumlah) |
| Pesan separator | 0 |
| Delay antar pesan | ≥ 3 detik |
| Pengiriman 22:00–06:00 | 0 |
| Status sesi WAHA | `WORKING` 100% |
| Tag `@<nomor>` per pesan | ≤ `NOTIF_WA_MENTION_MAX` (default 10); tanpa tag massal seperti `@everyone` |

**Exit Criteria:**
- [x] 12/12 skenario lulus. *(17 Nov 2026 — `docs/evidence/fase-8/uat-report.md`)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [ ] 7 hari soak tanpa kegagalan sesi. ⬜ **Belum** — butuh 7 hari kalender (dijadwalkan 18–25 Nov 2026); prosedur ada di evidence Fase 8.
- [x] Tidak ada laporan duplikat. *(17 Nov 2026 — alur normal dijaga guard + `notif_sent_channels`, UAT #6 & #7)* *(divalidasi ulang 17 Nov 2026 — lihat `docs/evidence/revalidasi-exit-gate.md`)*
- [ ] Persetujuan pemilik proses untuk pindah ke grup. ⬜ **Belum** — menunggu tanda tangan Bagian 10.

**Rollback:** set `NOTIF_PROVIDERS=telegram` + `NOTIF_TEST_MODE=true`; sistem kembali ke perilaku lama tanpa deploy.

---

## 7. Analisis Risiko Deteksi Bot (R5)

### 7.1 Risiko desain lama (jika langsung dipindah ke WhatsApp)

| Pola | Tingkat | Alasan |
|---|---|---|
| 1 unit = 1 pesan reminder tiap 30 menit | Tinggi | Pesan hampir identik, berulang, burst |
| Pesan separator setiap run | Tinggi | Tidak punya nilai, pola khas bot |
| Laporan dengan 4+ link foto | Sedang | Link-heavy menaikkan skor spam |
| Nomor baru langsung kirim banyak | Tinggi | Belum warm-up |
| Hanya pesan keluar, tanpa balasan | Sedang | Pola one-way broadcast |
| Target grup | Sedang | Banyak penerima |

### 7.2 Penilaian pola @mention PJ (R7)

| Aspek | Penilaian |
|---|---|
| Mention individual (`@62812xxx`) | **Rendah** — perilaku wajar pengguna grup; justru menambah keterlibatan (PJ mendapat notifikasi) sehingga pola lebih "manusiawi" |
| Tag terlalu banyak orang dalam 1 pesan | Sedang — mirip spam mention; **dimitigasi** dengan `NOTIF_WA_MENTION_MAX` (default 10) |
| `@everyone` / tag massal | Tinggi — **dilarang**, tidak diimplementasikan |
| Tag tanpa konteks | Rendah — tag selalu disertai nama unit, jam latihan, dan tanggal |

### 7.3 Mitigasi yang diterapkan

| Mitigasi | Fase |
|---|---|
| 1 pesan gabungan per 3 jam | 4, 5 |
| Hapus separator | 4, 7 |
| Delay ≥ 3 detik antar pesan | 2 |
| Batasi link (maks 4 atau ringkas) | 4 |
| Test ke nomor sendiri | 8 |
| Variasi teks (tanggal, jam, jumlah unit) | 4 |
| Tidak kirim 22:00–06:00 | 5 |
| Monitor `session.status` | 8 |
| **Batas jumlah tag per pesan; tanpa `@everyone`; tag selalu berkonteks** | **2, 4, 8** |

**Kesimpulan:** risiko turun dari **tinggi** menjadi **rendah–sedang**, tetapi tidak pernah nol karena WAHA adalah klien tidak resmi WhatsApp.

---

## 8. Timeline

| Fase | Estimasi | Target Selesai |
|---|---|---|
| 0 — Inventaris | 1 hari | 18 Nov 2026 |
| 1 — Konfigurasi | 0,5 hari | 18 Nov 2026 |
| 2 — Channel | 0,5–1 hari | 19 Nov 2026 |
| 3 — Data Nomor PJ (baru) | 0,5 hari | 19 Nov 2026 |
| 4 — Format pesan | 0,5 hari | 20 Nov 2026 |
| 5 — Jadwal 3 jam | 0,5 hari | 20 Nov 2026 |
| 6 — Tracking provider | 0,25 hari | 21 Nov 2026 |
| 7 — Refactor command | 0,25 hari | 21 Nov 2026 |
| 8 — UAT & go-live | 1 hari + 7 hari observasi | 28 Nov 2026 |

---

## 9. Checklist Progres

| Fase | Status | Tanggal | Bukti |
|---|---|---|---|
| 0 — Inventaris | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-0/inventaris.md` |
| 1 — Konfigurasi | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-1/config-matrix.md` |
| 2 — Channel | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-2/mention-probe.md` |
| 3 — Data Nomor PJ | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-3/backfill-status.md` |
| 4 — Format pesan | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-4/message-format.md` |
| 5 — Jadwal 3 jam | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-5/guard-test.md` |
| 6 — Tracking provider | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-6/retry-test.md` |
| 7 — Refactor command | ✅ Selesai | 17 Nov 2026 | `docs/evidence/fase-7/command-matrix.md` |
| 8 — UAT & go-live | 🟨 UAT 12/12; soak ⬜ | 17 Nov 2026 | `docs/evidence/fase-8/uat-report.md` |

---

## 10. Persetujuan

| Peran | Nama | Tanggal | Tanda Tangan |
|---|---|---|---|
| Pemilik proses | | | |
| Tech lead | | | |
| QA | | | |

---

## 11. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 17 Nov 2026 | Draf awal: rules 1–6 (multi-provider, 3 jam, reminder gabungan, laporan per unit, env config, anti-bot, chat test). |
| 1.1 | 17 Nov 2026 | Menambahkan **Rule 7 (R7 — tag PJ unit)**: temuan T-07 (tidak ada kolom nomor di `coachs`) dan T-08 (`is_notif_send=true` meski kirim gagal); fase baru **Fase 3 — Data Nomor PJ** (migrasi + form + backfill, fase 5–7 lama menjadi 5–8); mekanisme @mention WAHA (`text @<nomor>` + field `mentioned`); AC-13/AC-14/AC-15; konfigurasi `NOTIF_WA_MENTION_ENABLED` & `NOTIF_WA_MENTION_MAX`; skenario UAT #11–#12; analisis risiko mention di Bagian 7.2. |
| 1.2 | 17 Nov 2026 | **Implementasi sebagian Fase 3**: kolom `nomor_whatsapp` (varchar 20, nullable) pada `coachs` via migrasi `2026_11_17_000000_add_nomor_whatsapp_to_coachs.php`; helper `App\Support\PhoneNormalizer`; validasi + normalisasi di `coachStore`/`coachUpdate`; kolom nomor WhatsApp pada halaman list, form (create/edit), dan detail pelatih; semua referensi kolom `phone` di dokumen diganti `nomor_whatsapp` sesuai keputusan pemilik proses. Backfill & unit test formal menyusul. |
| 1.3 | 17 Nov 2026 | **Implementasi Fase 0–8 selesai & tervalidasi.** Deliverable: `config/notifications.php`; `NotificationChannel`/`ChannelResult`/`NotificationManager`; `TelegramChannel`; `WhatsAppChannel`; `AttendanceMessageBuilder`; `AttendanceReportDispatcher`; `NotificationRunGuard`; migrasi `notif_sent_channels`; refactor `attendance:notify` (`--type`/`--dry-run`/`--force`); scheduler 3 jam; `.env.example`. **54 unit test lulus (151 assertions)**, UAT **12/12**. **Koreksi penting:** field WAHA yang benar adalah `mentions` (bukan `mentioned`). Bug diperbaiki: T-01, T-02, T-03, T-04, T-08, `Keterlambatan Absen` negatif/float, guard `diffInMinutes` Carbon 3, `mention.enabled` runtime. Status: **soak test 7 hari + persetujuan go-live grup masih menunggu.** |
| 1.4 | 17 Nov 2026 | **Validasi ulang seluruh exit gate + koreksi dokumentasi.** 51 gate diuji ulang lewat eksekusi nyata (39 gate inti + 12 gate form/migrasi/integritas) — semua **PASS**; unit test **54 passed (151 assertions)**. Koreksi: sisa `mentioned` pada contoh payload Bagian 4/4.3 dan langkah Fase 2 diganti `mentions`; langkah backfill Fase 3 ditandai selesai (17/18 = 94,4%); tiap item exit gate yang tercentang diberi tanda validasi ulang merujuk `docs/evidence/revalidasi-exit-gate.md`; status header diperbarui. Terbuka: soak test 7 hari & persetujuan go-live grup. |
| 1.5 | 17 Nov 2026 | **Kebijakan tag per channel.** Notifikasi Telegram tidak lagi memuat tag `@<nomor>` — nama PJ tampil polos `PJ: Mursalat Asyidiq`; tag `@<nomor>` + field `mentions` hanya untuk WhatsApp. Implementasi: parameter `$supportTags` pada builder (terpisah dari `$supportMentions`) dan parameter `$textWithoutTags` pada `NotificationManager::send()`. `--dry-run` kini mencetak kedua varian. AC-16 ditambahkan. Total **56 unit test (161 assertions)** lulus; verifikasi payload nyata: **6/6 gate PASS**. |
| 1.6 | 17 Nov 2026 | **Header `[BOT ASSISTEN PELATIH]` dihapus.** Alasan: kata "BOT" berisiko memicu word filter spam WhatsApp (>4.000 kata kunci) dan menandai nomor sebagai automation unofficial. Pesan reminder & laporan kini langsung dimulai dari judul (`*Pengingat Absensi Latihan*` / `*Absensi Latihan*`). Test `test_reminder_uses_canonical_bold_format` diperketat: header harus berada di baris pertama dan pesan tidak boleh memuat "BOT". **56 unit test (162 assertions) lulus**; verifikasi `--dry-run` untuk reminder & laporan bersih dari header. |
| 1.7 | 17 Nov 2026 | **Perbaikan kompatibilitas shared hosting.** `routes/console.php` memakai `Illuminate\Support\Facades\Schedule` yang hanya ada di Laravel 11+. Di server error `Class "Illuminate\Support\Facades\Schedule" not found`. Perbaikan: definisi jadwal dipindah ke `app/Console/Kernel::schedule()` (kompatibel Laravel 10 ke bawah), `routes/console.php` hanya berisi command closure `inspire`. `onOneServer()` dihapus (butuh cache driver atomik, tidak perlu pada deployment single-server). Tambah `deploy-once.php` untuk deploy tanpa terminal (whitelist perintah + token, harus dihapus setelah dipakai). `php artisan schedule:list` tetap `0 */3 * * *`. **56 unit test (162 assertions) lulus**, 39 gate inti PASS. |
| 1.8 | 17 Nov 2026 | **Command deploy disesuaikan dengan pola cron shared hosting yang dipakai** (`/usr/local/bin/php <path>/artisan <perintah> >> <log> 2>&1`). `deploy-once.php` diubah: dipanggil lewat Cron Job (bukan browser), menolak akses `PHP_SAPI !== 'cli'`, memakai prefix path absolut `/home/smip6738/repositories/sisteminformasipesilat`, dan menulis log per langkah ke `storage/logs/cron/deploy-*.log`. Diuji lokal: `[SEMUA BERHASIL]`, migrasi `Nothing to migrate`, dry-run 0 pesan terkirim. |
| 1.9 | 17 Nov 2026 | **Jadwal laporan & pengingat dipisah** (keputusan pemilik proses). Laporan absensi kini dijalankan **tiap menit** (`* * * * *` → `attendance:notify --type=report`), tanpa interval dan **tanpa batas jendela jam (24 jam)**, karena sumber kebenarannya kolom `attendances.is_notif_send`. Pengingat tetap `0 */3 * * *` (mengikuti `NOTIF_INTERVAL_HOURS`) dalam jendela 07:00–21:59. `NotificationRunGuard` sekarang ber-scope (`report`/`reminder`) dengan cache key dan lock terpisah, sehingga laporan yang jalan tidak memblokir pengingat. Lock anti-overlap tetap dipertahankan. **62 unit test (179 assertions) lulus**, verifikasi perilaku **10/10 PASS**, 39 gate inti PASS. Prasyarat deployment: cron `schedule:run` wajib tiap menit. |
| 2.0 | 17 Nov 2026 | **Perbaikan route `/deploy-finish` saat route di-cache.** `routes/web.php` memanggil `Artisan::call()` tanpa import, sehingga saat `route:cache` closure diserialisasi dan `Artisan` diresolusi menjadi `Illuminate\Routing\Artisan` → `Class not found`. Diperbaiki dengan FQCN `\Illuminate\Support\Facades\Artisan`. Diuji pada server lokal dengan route cache aktif: HTTP 200 body `OK`. **62 unit test (179 assertions) lulus.** |

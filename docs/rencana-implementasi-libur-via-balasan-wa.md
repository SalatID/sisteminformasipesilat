# Rencana Implementasi: Tandai Sesi Libur Lewat Balasan WhatsApp

> **Status:** RENCANA — belum ada kode yang ditulis.
> **Tanggal:** 18 November 2026
> **Scope tahap 1:** WhatsApp (WAHA, engine **NOWEB**) saja. Telegram menyusul.
> **Dokumen terkait:** `docs/rencana-implementasi-notifikasi-telegram-whatsapp.md`

---

## 1. Ringkasan

Sistem sudah mengirim pengingat absensi ke grup WhatsApp. Sering kali sebuah unit
ternyata **diliburkan** (sekolah ujian, hujan, acara sekolah). Saat ini admin harus
membuka aplikasi untuk mencatat libur tersebut.

Fitur ini membuat admin cukup **membalas pesan pengingat** di grup:

```
nomor 1
Diliburkan Sekolah
Alasan : Sedang ujian
```

Bot akan:
1. Membuat/mengubah baris absensi unit tersebut menjadi status libur + alasan.
2. Membedakan **Diliburkan Sekolah** (`school_holiday`) dan **Diliburkan Pelatih**
   (`cancelled`).
3. Membalas di grup:

```
sudah saya update,
SMP/SMA/SMK Cengkareng 1, Selasa, 17 November 2026, Diliburkan Sekolah Karena Sedang Ujian
```

Efek samping yang menguntungkan: pengingat berikutnya otomatis **berhenti mencela**
unit itu, karena `MissingAttendanceCollector` menganggap unit sudah melapor bila
ada baris `attendances` pada tanggal tersebut, apa pun statusnya.

---

## 2. Keputusan yang sudah ditetapkan pemilik proses

| # | Keputusan | Nilai |
|---|---|---|
| K1 | Channel tahap 1 | **WhatsApp / WAHA**, engine **NOWEB** |
| K2 | Server menerima request masuk | **Ya** (bisa pakai webhook) |
| K3 | Penulisan status | **Case-insensitive** — `Diliburkan Sekolah`, `DILIBURKAN SEKOLAH`, `Diliburkan sekolah` semuanya diterima (spasi berlebih juga ditoleransi) |
| K4 | Status yang didukung | `school_holiday` (Diliburkan Sekolah) dan `cancelled` (Diliburkan Pelatih) |
| K5 | Alasan | Wajib, baris `Alasan :` |
| K6 | Cara memilih sesi | **Nomor baris** pada pesan pengingat (`nomor 1`) |
| K7 | Pengubahan pesan | **Tidak mengedit** pesan lama — kirim pesan baru |
| K8 | Daftar yang boleh flagging | **`.env`** dulu, bisa dipindah ke tabel/menu di tahap berikutnya |
| K9 | Tanpa hak akses | Balas `Anda @xxxx tidak boleh mengubah status absensi` |
| K10 | Penargetan pesan | **Reply/quote** pesan pengingat yang dikirim bot |

---

## 3. Format perintah final

**Struktur balasan (3 baris, urutan tetap):**

```
nomor <N>
Diliburkan Sekolah | Diliburkan Pelatih   ← bebas huruf besar/kecil
Alasan : <teks>
```

**Contoh yang diterima:**

```
nomor 1
Diliburkan Sekolah
Alasan : Sedang ujian
```

**Variasi status yang DITERIMA** (semuanya dianggap sama):

| Contoh ditulis | Status tersimpan |
|---|---|
| `Diliburkan Sekolah` | `school_holiday` |
| `DILIBURKAN SEKOLAH` | `school_holiday` |
| `Diliburkan sekolah` | `school_holiday` |
| `diliburkan   sekolah` (spasi ganda) | `school_holiday` |
| `Diliburkan Pelatih` / `DILIBURKAN PELATIH` / `diliburkan pelatih` | `cancelled` |

**Contoh yang ditolak:** `Libur Sekolah`, `Diliburkan`, `Sekolah Libur`, `Pelatih` (tanpa
kata "Diliburkan").

**Aturan parsing:**

| Elemen | Aturan |
|---|---|
| `nomor N` | Regex `^nomor\s+(\d+)$` per baris, `nomor` huruf kecil, minimal satu spasi. |
| Status | Dicocokkan **case-insensitive** setelah normalisasi: trim, spasi ganda → satu spasi, lalu `mb_strtolower()`. `diliburkan sekolah` → `school_holiday`, `diliburkan pelatih` → `cancelled`. |
| `Alasan :` | Regex `^Alasan\s*:\s*(.+)$` per baris, teks minimal 3 karakter, maksimal `NOTIF_REPLY_REASON_MAX` (default 255, batas kolom `reason`). Spasi di awal/akhir di-trim. |
| Baris kosong | Diabaikan (toleran terhadap enter berlebih). |
| Baris lain | Ditolak dengan pesan bantuan format. |

---

## 4. Alur teknis

```
Admin membalas pesan pengingat di grup
        │  "nomor 1 / Diliburkan Sekolah / Alasan : Sedang ujian"
        ▼
WAHA (NOWEB)  ──webhook event "message"──►  POST /api/whatsapp/webhook/{secret}
        │                                        │
        │                                        ├─ 1. Validasi secret + signature (HMAC)
        │                                        ├─ 2. Abaikan fromMe=true (pesan bot sendiri)
        │                                        ├─ 3. Ambil replyTo (quote) → pesan pengingat asli
        │                                        ├─ 4. Cek nomor pengirim ada di whitelist .env?
        │                                        │     tidak → balas "Anda @xxxx tidak boleh mengubah status absensi"
        │                                        ├─ 5. Parse perintah (format bab 3)
        │                                        ├─ 6. Cocokkan baris ke-N → unit + tanggal
        │                                        ├─ 7. Validasi data (bab 6)
        │                                        ├─ 8. Simpan perubahan ke DB
        │                                        └─ 9. Kirim pesan konfirmasi/error ke grup
        ▼
     200 OK  (diproses)
```

**Mengapa cocokkan lewat `replyTo.id`?**
Bot tidak menyimpan "nomor 1 = unit apa" di database. Sebaliknya, bot mencari **teks
pesan asli** yang dikutip (tersedia di `payload.replyTo.body`) dan mem-parse ulang
daftar bernomor dari teks itu. Keuntungan: balasan tetap valid meski ID pesan tidak
tersimpan, dan urutan nomor selalu konsisten dengan teks yang dibaca admin.

**Fallback bila `replyTo.id` kosong (terjadi di sebagian engine):** gunakan
`payload.replyTo.body`. Bila `body` juga kosong, bot memakai **pesan pengingat
terakhir di chat tersebut** (dicari via tabel `wa_messages` bila `WHATSAPP_PERSIST_INCOMING`
diaktifkan, atau ditolak dengan pesan "Balas pesan pengingat yang ingin diubah").

> **Prasyarat kritis:** nomor baris hanya stabil jika bot menyimpan/menyertakan daftar
> bernomor yang sama dengan yang admin lihat. Karena `buildConsolidatedReminder`
> membatasi ke `NOTIF_REMINDER_MAX_LINES` baris terbaru dan sudah terkirim apa adanya,
> parsing memakai teks pesan asli — bukan hasil generate ulang — sehingga nomor selalu cocok.

---

## 5. Struktur file

### 5.1 File baru

```
app/Services/Notification/Reply/
├── WaReplyCommand.php            # Parse teks balasan → {number, status, reason} | error
├── ReminderLineParser.php        # Parse teks pesan pengingat/laporan → baris {n, unit, date, jam}
├── HolidayTargetResolver.php     # nomor baris + pesan asli → {unit, date, start, end}
├── HolidayFlagService.php        # Validasi + simpan status libur (DB)
└── ReplyResponder.php            # Susun pesan balasan (sukses/gagal/format/akses)

app/Services/Notification/Reply/ReplyResult.php   # Value object hasil pemrosesan

app/Http/Controllers/Api/WhatsAppWebhookController.php

tests/Unit/Notification/WaReplyCommandTest.php
tests/Unit/Notification/ReminderLineParserTest.php
tests/Unit/Notification/HolidayFlagServiceTest.php
tests/Feature/WhatsAppWebhookTest.php
```

### 5.2 File yang diubah

| File | Perubahan |
|---|---|
| `routes/api.php` | Registrasi `POST /api/whatsapp/webhook/{secret}` (tanpa auth Sanctum, middleware `api`). |
| `config/notifications.php` | Blok `reply` (format, whitelist, limit) & `whatsapp.webhook_secret`. |
| `.env.example` | Variabel baru (bab 8). |
| `app/Models/Attendance.php` | Tidak perlu kolom baru (lihat bab 9). |
| `app/Services/Notification/Channels/WhatsAppChannel.php` | Tambah `sendTo(string $chatId, string $text)` untuk membalas di chat yang sama. |

> **Catatan:** `laravel-whatsapp` (paket `kstmostofa`) TIDAK dipakai — itu untuk
> Cloud API / sidecar `whatsapp-web.js`, bukan WAHA. Tidak akan dikonflik.

---

## 6. Aturan validasi

| Kode | Kondisi | Respons bot |
|---|---|---|
| V1 | Secret di URL salah | HTTP 403, tanpa balasan chat |
| V2 | HMAC signature tidak cocok | HTTP 403 |
| V3 | `fromMe = true` (pesan bot) | Diabaikan (HTTP 200, tanpa aksi) |
| V4 | Pengirim bukan di whitelist | `Anda @xxxx tidak boleh mengubah status absensi` |
| V5 | Tidak me-reply pesan bot | `Balas pesan pengingat yang ingin diubah.` |
| V6 | Format tidak sesuai | `Format tidak dikenali.` + contoh perintah |
| V7 | Nomor baris di luar jangkauan pesan | `Nomor X tidak ada pada pesan tersebut.` |
| V8 | Sesi hari ini belum selesai | **Diizinkan** (kasus paling umum: membatalkan latihan yang belum jalan) — dicatat di log |
| V9 | Tanggal lampau (dari pengingat) | **Diizinkan** |
| V10 | Sudah ada baris `training` untuk unit+tanggal itu | `Latihan ini sudah ada laporan absensi; tidak bisa ditandai libur.` |
| V11 | Sudah ditandai libur dengan status sama | Konfirmasi ulang **tanpa** menulis DB: `sudah saya update, ...` |
| V12 | Sudah ditandai libur dengan status **berbeda** | Ubah status + alasan (dianggap koreksi) |
| V13 | Alasan kosong / < 3 karakter | `Alasan wajib diisi.` |
| V14 | Alasan > batas | Dipotong ke `NOTIF_REPLY_REASON_MAX`, bot menambahkan `(dipotong)` |
| V15 | Unit di pesan sudah di-nonaktifkan (`is_active = false`) | `Unit ... sudah tidak aktif.` |
| V16 | Baris ke-N bukan unit (mis. baris `Total:` / `... dan N sesi lainnya`) | `Nomor X bukan baris unit latihan.` |

---

## 7. Format pesan balasan bot

### 7.1 Sukses

```
sudah saya update,
SMP/SMA/SMK Cengkareng 1, Selasa, 17 November 2026, Diliburkan Sekolah Karena Sedang Ujian
```

Rumus: `sudah saya update,\n{unit}, {tanggal}, {status} Karena {alasan}`

> Perbaikan kecil dari contoh pemilik proses: `Karena` tidak diawali kapital
> (`... Diliburkan Sekolah karena Sedang Ujian`) agar terbaca wajar. Bila ingin
> persis seperti contoh, cukup ubah satu string di `ReplyResponder`.

Tanggal memakai format yang sama dengan pengingat: `l, d F Y` (locale `id`).

### 7.2 Tanpa hak akses

```
Anda @62812xxxxxxx tidak boleh mengubah status absensi
```

### 7.3 Format salah

```
Format tidak dikenali.

Contoh balasan:
nomor 1
Diliburkan Sekolah
Alasan : Sedang ujian

Status yang tersedia:
- Diliburkan Sekolah
- Diliburkan Pelatih
```

### 7.4 Nomor tidak ada

```
Nomor 12 tidak ada pada pesan tersebut. Pesan itu hanya memuat 9 baris.
```

### 7.5 Sudah ada laporan latihan

```
Latihan SMP/SMA/SMK Cengkareng 1 tanggal 17 November 2026 sudah ada laporan absensi; tidak bisa ditandai libur.
Hubungi admin untuk mengubah laporan tersebut terlebih dahulu.
```

---

## 8. Konfigurasi `.env`

```ini
# ── Balasan bot: tandai sesi libur ─────────────────────────────
NOTIF_REPLY_ENABLED=true

# Nomor (WAHA chatId) yang BOLEH mengubah status absensi lewat balasan.
# Pisahkan dengan koma. Format: 6281234567890@c.us
# Kosong = tidak ada yang boleh (semua balasan ditolak).
# LID (mis. 123456789@lid) juga bisa dimasukkan bila WAHA melaporkan @lid.
WA_NOTIF_FLAG_HOLIDAY_USERS=

# Batas panjang alasan (kolom attendances.reason = 255)
NOTIF_REPLY_REASON_MAX=255

# ── Webhook WAHA ───────────────────────────────────────────────
# Rahasia pada URL: https://domain-anda/api/whatsapp/webhook/{secret}
WAHA_WEBHOOK_SECRET=

# Kunci HMAC yang SAMA dengan config webhook di WAHA (wa.config.webhooks[].hmac.key).
# Kosong = signature tidak diverifikasi (hanya mengandalkan secret URL).
WAHA_WEBHOOK_HMAC_KEY=
```

**Menghubungkan WAHA ke aplikasi** (sekali saja, via Dashboard WAHA atau API):

```json
POST /api/sessions
{
  "name": "sessionsmi",
  "config": {
    "webhooks": [
      {
        "url": "https://domain-anda/api/whatsapp/webhook/<WAHA_WEBHOOK_SECRET>",
        "events": ["message"],
        "hmac": { "key": "<WAHA_WEBHOOK_HMAC_KEY>" },
        "retries": { "policy": "linear", "delaySeconds": 5, "attempts": 3 }
      }
    ]
  }
}
```

---

## 9. Data & database

**Tidak perlu migrasi baru.** Semua kebutuhan sudah tersedia:

- `attendances.attendance_status` → `school_holiday` / `cancelled`
- `attendances.reason` → alasan
- `attendances.updated_by`, `units` → relasi unit & PJ

**Penulisan perubahan:**

```php
Attendance::updateOrCreate(
    ['unit_id' => $unit->id, 'attendance_date' => $date->toDateString()],
    [
        'attendance_status' => $status,       // school_holiday | cancelled
        'reason'            => $reason,
        'new_member_cnt'    => 0,
        'old_member_cnt'    => 0,
        'report_maker_id'   => null,          // diisi nanti bila perlu
        'created_by'        => ...,           // lihat bab 12 (Q3)
        'is_notif_send'     => false,         // jangan kirim "laporan absensi" untuk sesi libur
        'updated_by'        => ...,
    ]
);
```

> **Perlu diperhatikan:** `sendReports()` mengirim notifikasi untuk
> `attendance_status = 'training'` saja, jadi baris libur tidak akan memicu notifikasi
> laporan. `is_notif_send = false` tetap diset agar aman.

**Audit:** setiap perubahan dicatat di `storage/logs/cron/cron.log`:

```
Libur ditandai via WhatsApp | unit=SMP/SMA/SMK Cengkareng 1 | date=2026-11-17 |
status=school_holiday | by=62812xxxx@c.us | prev=none
```

---

## 10. Rencana per fase

### Fase 1 — Parser (tanpa jaringan & tanpa DB) — ✅ selesai

| Elemen SMART | Target |
|---|---|
| **S**pecific | `WaReplyCommand` (parse perintah) + `ReminderLineParser` (parse teks pengingat) |
| **M**easurable | 25 kasus uji: valid, variasi huruf besar/kecil status, spasi ganda, baris kurang, alasan kosong, nomor bukan angka, dsb → 100% lulus |
| **A**chievable | Pure PHP, tidak bergantung WAHA |
| **T**ime-bound | 0,5 hari |

**Deliverable:** `WaReplyCommand.php`, `ReminderLineParser.php`, 2 file test.

**Contoh yang WAJIB lulus (`ReminderLineParser`):** pesan pengingat 9 baris dari
pemilik proses → menghasilkan tepat 9 entri, baris 1 `SMP/SMA/SMK Cengkareng 1` +
`2026-11-17`, baris 9 `MI Assanusiah` + `2026-10-26`.

**Exit criteria:** `php artisan test --filter=ReminderLineParserTest` hijau.
✅ Terpenuhi: 15 test (parser 9 baris asli, bulan ID/EN, tanggal mustahil).

---

### Fase 2 — Logika bisnis & penyimpanan — ✅ selesai

| Elemen SMART | Target |
|---|---|
| **S**pecific | `HolidayTargetResolver` (nomor → unit+tanggal) dan `HolidayFlagService` (aturan V8–V16) |
| **M**easurable | 15 skenario (bab 6) menghasilkan pesan balasan yang tepat; hanya V10 yang menolak perubahan |
| ****A**chievable** | Eloquent biasa; test memakai model in-memory seperti `ReportRetryTest` (tidak menyentuh DB dev `sip`) |
| **T**ime-bound | 0,75 hari |

**Exit criteria:** `php artisan test --filter=HolidayFlagServiceTest` hijau; tidak ada
`RefreshDatabase` (agar data dev tidak terhapus).

✅ Terpenuhi dengan penyesuaian: aturan V8–V16 dipisah ke `HolidayDecision`
(murni) + `HolidayTargetResolver` (unit finder bisa di-inject), sehingga
total **21 test** (`HolidayDecisionTest` 12, `HolidayTargetResolverTest` 9).
Penulisan DB di `HolidayFlagService` diverifikasi lewat command pratinjau +
UAT, bukan test otomatis — `RefreshDatabase` tetap tidak dipakai.

---

### Fase 3 — Endpoint webhook & pengiriman balasan — ✅ selesai

| Elemen SMART | Target |
|---|---|
| **S**pecific | `WhatsAppWebhookController` + route + `WhatsAppChannel::sendTo()` + whitelist |
| **M**easurable | Webhook palsu (payload WAHA asli) → 200 + 1 request `sendText`; secret salah → 403 (0 request); fromMe → 200 (0 request) |
| **A**chievable | `Http::fake()` untuk WAHA |
| **T**ime-bound | 0,5 hari |

**Exit criteria:** `php artisan test --filter=WhatsAppWebhookTest` hijau.

---

### Fase 4 — UAT di grup staging — ⏳ menunggu grup & WAHA

| Elemen SMART | Target |
|---|---|
| **S**pecific | Uji di grup uji dengan `NOTIF_TEST_MODE=false` + `WAHA_CHAT_ID` grup uji |
| **M**easurable | 8 skenario UAT (bab 11) berjalan sesuai harapan |
| **A**chievable | WAHA sudah running; cukup ubah config session |
| **T**ime-bound | 0,5 hari |

**Exit criteria:** semua skenario UAT lolos + bukti screenshot di `docs/evidence/`.

---

### Fase 5 — Go-live & monitoring — ⏳ setelah Fase 4

- Isi `WA_NOTIF_FLAG_HOLIDAY_USERS` dengan nomor admin yang berwenang.
- Pantau `storage/logs/cron/cron.log` selama 3 hari pertama.
- Pastikan tidak ada balasan loop (bot membalas pesan bot sendiri) — lihat bab 12 Q5.

**Total estimasi:** ± 2,25 hari kerja + 0,5 hari UAT.

---

## 11. Skenario UAT

| # | Aksi di grup | Hasil yang diharapkan |
|---|---|---|
| U1 | Admin reply `nomor 1` + status + alasan | Balasan konfirmasi; baris absensi #1 jadi libur |
| U2 | Admin reply `nomor 9` (paling lama) | Konfirmasi dengan tanggal yang benar |
| U3 | Non-admin reply format valid | `Anda @xxxx tidak boleh mengubah status absensi` |
| U4 | Admin reply tanpa quote | `Balas pesan pengingat yang ingin diubah.` |
| U5 | Admin reply `diliburkan sekolah` (huruf kecil) | **Diterima** — konfirmasi normal |
| U5b | Admin reply `DILIBURKAN PELATIH` (huruf besar) | **Diterima** — status jadi Diliburkan Pelatih |
| U5c | Admin reply `Libur Sekolah` (tanpa kata "Diliburkan") | `Format tidak dikenali.` + contoh |
| U6 | Admin reply `nomor 20` | `Nomor 20 tidak ada pada pesan tersebut ...` |
| U7 | Sesi yang sudah ada laporan `training` | `... sudah ada laporan absensi; tidak bisa ditandai libur.` |
| U8 | Jalankan `attendance:notify --type=reminder --dry-run` setelah U1 | Unit U1 **tidak** muncul lagi di pengingat |

---

## 12. Pertanyaan terbuka (dibutuhkan sebelum Fase 2)

> **Semua sudah terjawab** dan diterapkan pada implementasi. Kolom terakhir
> mencatat keputusan final beserta perbedaannya dengan usulan awal.

| # | Pertanyaan | Usulan default | Keputusan final |
|---|---|---|---|
| Q1 | Format `nomor 1` — apakah cukup `1` saja juga diterima? | Tidak, harus `nomor 1` (sesuai keputusan K6). Menulis `Nomor 1`/`NOMOR 1` juga **ditolak** karena status saja yang case-insensitive | ✅ **Diterima apa adanya.** Hanya `nomor` huruf kecil; `Nomor 1` ditolak. Angka saja (`1`) juga ditolak |
| Q2 | Bila 1 balasan ingin meliburkan beberapa nomor (`nomor 1,2`)? | Tahap 1: satu nomor per balasan. Multi bisa ditambah nanti | ✅ **Satu nomor per balasan** (keputusan pemilik proses 18 Nov 2026) |
| Q3 | `report_maker_id` & `created_by` wajib (NOT NULL) — diisi apa? | `created_by` = UUID user super-admin pertama; `report_maker_id` = `null` **bila kolom diizinkan**; jika tidak, pakai UUID yang sama | ✅ **Keduanya = UUID user ber-role `super-admin`** pertama. `report_maker_id` ternyata NOT NULL, jadi tidak ada opsi `null` |
| Q4 | Bila status berubah dari `training` → libur ditolak, apakah ada jalur "paksa" untuk admin? | Tidak ada di tahap 1 | ✅ Sesuai — tidak ada jalur paksa; ubah lewat menu absensi |
| Q5 | Anti-loop: bot membalas pesan yang berisi kata "nomor"? | Bot mengabaikan `fromMe=true`, dan hanya memproses bila replyTo adalah pesan bot berjudul `*Pengingat Absensi Latihan*` | ⚠️ **Diperluas.** `fromMe=true` tetap diabaikan, tetapi pesan yang *terlihat seperti perintah* (`nomor N` / label status / `Alasan :`) juga diproses walau bukan reply — supaya admin tetap dapat balasan penolakan yang jelas. Obrolan biasa tetap diabaikan |
| Q6 | Apakah bot perlu membalas juga jika `NOTIF_REPLY_ENABLED=false`? | Tidak — semua balasan diabaikan diam-diam | ✅ Sesuai |
| Q7 | Pesan pengingat lama (lebih dari 1 siklus) masih boleh diubah? | Ya, selama teks quoted masih tersedia | ✅ Sesuai — nomor selalu dibaca ulang dari `replyTo.body` |

---

## 13. Risiko & mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| WAHA melaporkan pengirim sebagai `@lid`, bukan `@c.us` | Whitelist nomor tidak cocok → semua ditolak | Sediakan log nomor & LID; whitelist menerima keduanya; uji di Fase 4 |
| `replyTo.body` kosong di suatu versi NOWEB | Bot tidak tahu baris mana | Fallback `replyTo.id` → `GET /api/{session}/chats/{chatId}/messages/{id}`; plus pesan bantuan |
| Balasan dikirim berkali-kali WAHA (retry webhook) | Baris ditulis ulang / balasan dobel | Idempotensi: kunci cache `wa-reply:{payload.id}` 10 menit |
| Nomor baris bergeser karena pesan diedit | Salah unit | Bot memakai teks quoted, bukan generate ulang |
| Admin salah kirim `nomor` untuk pesan lain | Salah unit ditandai libur | Konfirmasi menyebut unit + tanggal secara eksplisit |
| Spam balasan bot di grup | Mengganggu | Hanya membalas bila format valid atau pengirim berhak; pesan error dibatasi 1 per 5 menit per pengirim |

---

## 14. Di luar scope tahap ini

- Telegram
- Multi-nomor dalam satu balasan (`nomor 1,2,5`)
- Menu admin untuk mengelola daftar whitelist (db) & riwayat perubahan
- Edit pesan notifikasi lama
- Notifikasi "libur" ke PJ unit
- Batal libur (kembalikan ke `training`)

---

## 15. Riwayat perubahan

| Tanggal | Perubahan |
|---|---|
| 18 Nov 2026 | Dokumen awal dibuat dari diskusi: scope WhatsApp/NOWEB, format `nomor N` + status case-sensitive + `Alasan :`, whitelist di `.env`, kirim pesan baru (bukan edit). |
| 18 Nov 2026 | Revisi: status kini **case-insensitive** (K3) — `Diliburkan Sekolah` / `DILIBURKAN SEKOLAH` / `Diliburkan sekolah` diterima. Ditambah aturan normalisasi spasi & contoh variasi diterima/ditolak, UAT U5/U5b/U5c, dan penegasan `nomor` tetap huruf kecil. |
| 18 Nov 2026 | Keputusan final: satu nomor per balasan (Q2), `created_by`/`report_maker_id` = UUID super-admin (Q3), bentuk id pengirim `@c.us` (Q4). Fase 1–5 diimplementasikan — lihat bab 16. |

---

## 16. Status Implementasi (18 November 2026)

**Fase 1–3 selesai, Fase 4–5 berupa panduan siap jalan** (UAT butuh grup & WAHA nyata).

### 16.1 File baru

```
app/Services/Notification/Reply/
├── ParsedReply.php               # hasil parsing (ok/error/kode)
├── WaReplyCommand.php            # parse "nomor N" + status + "Alasan :"
├── ReminderLineParser.php        # baca teks pengingat & laporan
├── HolidayTarget.php             # value object unit+tanggal
├── TargetResolution.php          # hasil pemetaan nomor → target
├── HolidayTargetResolver.php     # nomor baris → unit+tanggal
├── HolidayDecision.php           # aturan keputusan murni (tanpa DB)
├── FlagOutcome.php               # hasil penyimpanan
├── HolidayFlagService.php        # simpan status libur
├── SystemActor.php               # UUID super-admin untuk created_by
├── ActorIdentity.php             # normalisasi nomor whitelist
└── ReplyResponder.php            # semua teks balasan bot

app/Http/Controllers/Api/WhatsAppWebhookController.php
app/Console/Commands/HolidayReplyPreviewCommand.php   # pratinjau tanpa WAHA/DB write

tests/Unit/Notification/WaReplyCommandTest.php
tests/Unit/Notification/ReminderLineParserTest.php
tests/Unit/Notification/HolidayTargetResolverTest.php
tests/Unit/Notification/HolidayDecisionTest.php
tests/Feature/WhatsAppWebhookTest.php

docs/evidence/uat-libur-via-balasan-wa.md
docs/evidence/fixtures/pengingat-9-baris.txt
docs/evidence/fixtures/balasan-valid.txt
docs/evidence/fixtures/balasan-pelatih.txt
docs/evidence/fixtures/waha-reply-payload.json
```

### 16.2 File yang diubah

| File | Perubahan |
|---|---|
| `routes/api.php` | `POST /api/whatsapp/webhook/{secret}` (`whatsapp.webhook`) |
| `config/notifications.php` | blok `reply` (enabled, allowed_users, reason_max) + `whatsapp.webhook_secret`, `whatsapp.webhook_hmac_key` |
| `.env.example` | variabel baru (lihat bab 8) |
| `app/Services/Notification/Channels/WhatsAppChannel.php` | `sendTo(chatId, text, mentions)`; `send()` kini mendelegasikan |

### 16.3 Hasil pengujian

```
php artisan test
Tests: 139 passed (460 assertions)
```

Cakupan baru: 48 test parser + 9 test resolver + 12 test keputusan/identitas
+ 17 test webhook.

### 16.4 Penyimpangan/disiplin tambahan terhadap rencana

| Hal | Rencana | Implementasi | Alasan |
|---|---|---|---|
| Urutan baris perintah | "urutan tetap" | urutan **tidak dipaksakan** (dikenali per bentuk), duplikat tetap ditolak | balasan chat sering diselingi baris kosong; tidak mengurangi keamanan |
| `Alasan` | `^Alasan :` | label case-insensitive, spasi sebelum `:` opsional | label, bukan data; mengurangi penolakan yang tidak perlu |
| `nomor` | huruf kecil | **tetap huruf kecil** (`Nomor 1` ditolak) | keputusan Q1 |
| Pencocokan unit | eksak | eksak dulu, lalu fallback `LIKE` terpendek | jaring pengaman bila teks pesan diedit manual |
| Pengirim tanpa whitelist | balas penolakan | hanya dibalas bila pesan **terlihat seperti perintah** atau reply ke pesan bot | mencegah bot berisik di obrolan biasa |
| Idempotensi | tidak ada di rencana | cache 10 menit per `payload.id` | WAHA mengulang webhook non-2xx |
| Verifikasi HMAC | opsional | opsional (`WAHA_WEBHOOK_HMAC_KEY`; kosong = tidak diverifikasi) | memudahkan staging |
| Kolom `reason` kepanjangan | dipotong + `(dipotong)` | sesuai | batas `VARCHAR(255)` |
| Tambahan perintah | tidak ada | `attendance:holiday-preview` (read-only) | UAT tanpa harus menunggu WAHA |

### 16.5 Yang masih manual (bukan kode)

1. Isi `.env` (bab 8) dan `php artisan config:clear`.
2. Daftarkan webhook di WAHA → `docs/evidence/uat-libur-via-balasan-wa.md` bab 2.
3. Isi `WA_NOTIF_FLAG_HOLIDAY_USERS` dengan nomor admin yang berwenang.
4. Jalankan UAT bab 5 dan centang hasilnya.
5. **Tidak ada migrasi database** — kolom `attendance_status`, `reason`,
   `created_by`, `report_maker_id` sudah tersedia.


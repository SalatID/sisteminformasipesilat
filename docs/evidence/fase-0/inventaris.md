# Evidence Fase 0 — Inventaris & Keputusan

| Item | Nilai | Status |
|---|---|---|
| Tanggal inventaris | 17 November 2026 | ✅ |
| Lingkungan | Windows, PHP 8.3.29, Laravel 12.65.0 | ✅ |
| Timezone aplikasi | **Asia/Jakarta** (`.env` `APP_TIMEZONE`, `php artisan about`) | ✅ |
| Cache driver | `file` (`.env` `CACHE_DRIVER`) | ✅ |
| Queue connection | `sync` (`.env` `QUEUE_CONNECTION`) | ✅ |
| Database | MySQL, terhubung | ✅ |
| Scheduler | `app/Console/Kernel.php` kosong, `routes/console.php` tidak punya jadwal attendance | ✅ |
| Cron eksternal | Tidak dapat diperiksa dari environment lokal (perlu akses server produksi) | ⚠️ Dicatat |

---

## 1. Status WAHA (WhatsApp HTTP API)

Diperiksa via `GET {WAHA_URL}/api/sessions/{WAHA_SESSION}`:

```json
{
  "name": "sessionsmi",
  "status": "WORKING",
  "me": { "id": "6289630133235@c.us", "pushName": "Asisten-pelatih" },
  "engine": { "engine": "NOWEB" }
}
```

| Item | Nilai | Keterangan |
|---|---|---|
| WAHA URL | `https://wahanoweb-666b42.app3.flaz.my.id` | ✅ Aktif |
| Session | `sessionsmi` | ✅ `WORKING` |
| Nomor terhubung | `6289630133235` (`Asisten-pelatih`) | ✅ |
| Engine | `NOWEB` | Mendukung field `mentioned` untuk @mention |
| Kredensial | `WAHA_API_KEY` tersedia di `.env` | ✅ |

> **Temuan:** endpoint daftar/detail grup (`GET /api/{session}/chats/{chatId}`, `POST /api/chats/overview`) mengembalikan **404** di instance ini. Artinya nama & daftar peserta grup `120363404437691633@g.us` **tidak bisa diverifikasi lewat API**; verifikasi partisipan akan dilakukan via kembali ke endpoint pesan atau cek manual saat Fase 8.

## 2. Status Telegram

| Item | Nilai |
|---|---|
| `TELEGRAM_BOT_TOKEN` | Tersedia di `.env` (nilai tidak dicetak) |
| `TELEGRAM_CHAT_ID` | `-5182317563` |

## 3. Target Pengiriman

| Target | ID | Kegunaan |
|---|---|---|
| **Test (wajib Fase 1–8)** | `6289654157132@c.us` | `NOTIF_TEST_CHAT_ID` |
| Produksi (nanti) | Grup `120363404437691633@g.us` | Diaktifkan setelah UAT disetujui |
| Telegram produksi | `-5182317563` | Tetap berjalan |

**Keputusan:** sampai Fase 8 selesai + soak test, semua pengiriman WhatsApp diarahkan ke `6289654157132@c.us` (personal), **bukan grup**.

## 4. Keputusan Paket Sidecar

`kstmostofa/laravel-whatsapp` (sidecar whatsapp-web.js) **tidak dipakai** untuk notifikasi ini. Alasan: WAHA sudah aktif `WORKING`, lebih ringan (tanpa Puppeteer/Chromium di server), dan mendukung grup + mention. Paket dibiarkan terpasang (penghapusan = perubahan terpisah, non-goal).

## 5. Inventaris Unit & Nomor PJ (prasyarat R7 / Fase 3)

Data unit aktif dari DB `units` + relasi `pj_id` → `coachs.nomor_whatsapp`:

| No | Unit | Hari | Jam | PJ | nomor_whatsapp |
|---|---|---|---|---|---|
| 1 | KALIDERES | Sunday | 10:00-14:00 | Agam Ikhwan | - |
| 2 | Kuttab At Taqwa | Thursday | 07:00-09:00 | Indah Setya Wati | - |
| 3 | MI NURUL YAKIN | Saturday | 07:00-09:00 | Apriyanto | - |
| 4 | RPTRA KALIDERES | Saturday | 15:30-17:00 | Juminta Ibrahim | - |
| 5 | SD KARAWACI 08  | Tuesday | 15:30-17:30 | Apriyanto | - |
| 6 | SDN Kalideres 05 | Tuesday | 13:30-15:00 | Revika Zulviani | - |
| 7 | SDN Pegadungan 01 Pagi | Tuesday | 13:00-15:00 | Zaenal Arifin | - |
| 8 | SDN Pegadungan 012 Pagi | Thursday | 14:30-15:30 | Hari Darmawan | - |
| 9 | SMA IT AL MAKA | Thursday | 14:30-16:00 | M. Arief P | - |
| 10 | SMK Ad Dakwah | Saturday | 15:30-17:30 | Apriyanto | - |
| 11 | SMK Bangun Nusantara | Sunday | 15:30-17:30 | Eko Purwanto | - |
| 12 | SMK TI YPML | Saturday | 08:00-09:30 | Mursalat Asyidiq | - |
| 13 | SMP Bangun Nusantara | Saturday | 15:30-17:30 | Eko Purwanto | - |
| 14 | SMP IT AL MAKA | Monday | 14:10-15:30 | M. Arief P | - |
| 15 | SMP Kebudayaan | Saturday | 07:00-09:00 | M. Arief P | - |
| 16 | SMP Negeri 225 Jakarta | Wednesday | 14:30-16:30 | M. Arief P | - |
| 17 | SMP/SMA/SMK Cengkareng 1 | Tuesday,Saturday | 15:30-17:30 | Catur Wulandari | - |
| 18 | SMP/SMA/SMK Cengkareng 2 | Monday,Wednesday | 15:00-17:00 | M. Ahyar Ilyas | - |

Total unit aktif: 18
PJ unik: 12
PJ dengan nomor_whatsapp: 0

**Ringkasan:**
- Total unit aktif: **18**
- PJ unik: **12**
- PJ dengan `nomor_whatsapp` valid: **0** ← *blocker R7; backfill dibutuhkan sebelum Fase 8*

## 6. Exit Criteria

- [x] Jadwal cron lama terdokumentasi (`app/Console/Kernel.php` + `routes/console.php` kosong; diasumsikan cron eksternal, perlu konfirmasi server).
- [x] Timezone terkonfirmasi `Asia/Jakarta`.
- [x] Target test & produksi disetujui (test `6289654157132@c.us`, grup `120363404437691633@g.us` di fase akhir).
- [x] Keputusan paket sidecar tercatat (tidak dipakai; WAHA jalur utama).
- [x] Daftar nomor WA PJ siap di-backfill (**terkumpul daftar PJ; nomor menunggu input admin**).

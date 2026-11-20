# Bukti UAT Fase 5 — 20 November 2026 (sisi lokal)

> **Lingkungan:** server lokal `http://127.0.0.1:8000`, WAHA NOWEB
> (`sessionsmi`, WORKING), `NOTIF_TEST_MODE=true`,
> `NOTIF_TEST_CHAT_ID=6289654157132@c.us`, zona `Asia/Jakarta`.
> **Login:** Super Admin (via browser).

## 0. Catatan metode

Webhook sesi WAHA `sessionsmi` **mengarah ke produksi**
(`https://sip.smijakartabarat.org/api/whatsapp/webhook/...`), bukan server lokal.
Agar tidak mengganggu produksi, jalur masuk webhook diuji dengan **payload
identik format WAHA** (event `message`, engine `NOWEB`) yang dikirim ke
`POST /api/whatsapp/webhook/{secret}` dengan HMAC-SHA512 `X-Webhook-Hmac`,
memakai `provider_message_id` **asli** dari pesan yang benar-benar dikirim WAHA.

Yang **terbukti nyata (live WAHA):** pembuatan jadwal, planner, runner,
pengiriman pesan latihan, pengumuman libur, `provider_message_id`.
Yang **disimulasikan (payload webhook):** pesan masuk dari WhatsApp.

---

## 1. Checklist UAT

| # | Aksi | Harapan | Hasil | Catatan / bukti |
|---|---|---|---|---|
| S1 | Buat jadwal + **Kirim Tes** | Pesan tes masuk ≤ 30 dtk; variabel terisi | **OK** | Jadwal `LATIH` dibuat; pratinjau: "Halo, ini pesan latihan untuk Latihan UAT 20 Nov pada Jumat, 20 November 2026." UI: "Pesan percobaan terkirim ke 6289654157132@c.us." Pesan **masuk** ke WA penerima |
| S2 | Run jatuh tempo terkirim | Masuk tepat sekali | **OK (live)** | `LATIH-20261120` → `sent` 09:31:51 WIB; `LATIH-20261120-2` → `sent`, `provider_message_id=3EB046DBC2A16589D9FA30`. Pesan latihan otomatis sungguhan: `UATS2-20261120` → `sent` 10:06:17, `provider_message_id=3EB0854B440C1A7238F270`, isi: "Pesan latihan otomatis UAT S2 S5 S6 pada Jumat, 20 November 2026." Siklus runner berikutnya `due=0` (tidak dobel) |
| S3 | Detail jadwal | Status Terkirim, `provider_message_id` terisi | **OK setelah perbaikan** | Lihat **Bug #1**. Sebelum perbaikan `provider_message_id` kosong |
| S4 | Balas bot `/cancel-schedule` + `Alasan : ...` | Libur terkirim ≤ 1 mnt; run Dibatalkan | **OK** | `LATIH-20261120-2`: `cancelled`, `cancel_source=webhook`, `cancelled_by=6289654157132@c.us`, `cancel_reason="hujan deras"`, `announcement_message_id=3EB05AF05D1B44E28B23D0` |
| S5 | `/cancel-schedule KODE-TANGGAL` (besok) | Besok tidak ada pesan; libur terkirim | **OK (live)** | Kode: `TCRUT-20261121` → `cancelled` (`agenda mendadak`), pengumuman `3EB07C6CDEA81B7D13BB82`. Balasan pesan latihan otomatis: `UATS2-20261120` → `cancelled`, `cancel_source=webhook`, `cancel_reason="lapangan dipakai turnamen"`, pengumuman `3EB0D823E2E53AC9C8CAA4`. UI menampilkan badge **Dibatalkan** + alasan |
| S6 | Cron berhenti > grace 30 mnt | Run menjadi **Terlewat**, tidak dikirim | **OK (live)** | Run `UATS6-20261120` dibuat 08:00; runner dijalankan 10:07 tanpa `--force` → `missed`, `last_error="Terlewat: melewati grace window 30 menit."`, `sent_at=NULL`, `Due=0` |
| S7 | WAHA mati saat kirim | Retry 3x → Gagal; error tampil; Kirim ulang bekerja | **OK (live)** | `WAHA_URL=http://127.0.0.1:9`: retry 1 (`attempts=1`, `next_attempt_at +5 mnt`), retry 2, lalu `failed` (`attempts=3`, `next=NULL`). UI: badge **Gagal** + pesan cURL + "3x percobaan". Tombol **Kirim ulang** → `sent`, `attempts=0`, `provider_message_id=3EB02E45F854BCE4013771`. Bukti: `fase-5-uat/s2-s5-detail-dibatalkan-terkirim.png` |
| S8 | Nomor non-whitelist `/cancel-schedule` | Ditolak; run tidak berubah | **OK** | Respons `unauthorized`; log: `Batal jadwal via WhatsApp ditolak: pengirim tidak berwenang`. `TCRUT-20261122` tetap `pending` |
| S9 | Pesan bebas di grup | Bot diam | **OK** | Respons `{"ok":true,"ignored":"not_a_command"}`, tidak ada balasan |

## 2. Uji tambahan

| # | Aksi | Harapan | Hasil | Catatan |
|---|---|---|---|---|
| T1 | `/CANCEL-SCHEDULE` + `ALASAN : ...` | Diterima (case-insensitive) | **OK** | Dibalas `already` (run sudah dibatalkan di S4) → kata kunci besar dikenali |
| T2 | `tcrut-20261123` (kode kecil) | Diterima, dinormalisasi | **OK** | `cancelled`, kode kanonik `TCRUT-20261123`; pengumuman `3EB04263F52C7CE458AC35` |
| T3 | `/cancel-schedule TCRUT` (tanpa tanggal) | Balas 3 kemunculan terdekat | **OK** | Status `schedule_only` untuk `TCRUT` (diuji dua kali) |
| T4 | `/cancel-schedule` tanpa `Alasan :` | Petunjuk format, run tetap | **OK** | `invalid_format` / `missing_reason` |
| T5 | `Alasan : ok` (2 karakter) | Ditolak | **OK** | `invalid_format` / `reason_too_short` |
| T6 | Run kemarin | Ditolak | **OK (test)** | `CancelScheduleWebhookTest` kasus `past` lulus |
| T7 | Nonaktifkan jadwal | Pending → Dibatalkan (`system`), no send | **OK** | 59 run `pending` → `cancelled`/`system`; log cron tidak bertambah |
| T8 | Aktifkan lagi | Planner membuat ulang; tidak terkirim sendiri | **OK** | 59 run kembali `pending`; 2 `cancelled`/`webhook` tetap |
| T9 | Edit jadwal (ubah jam) | Pending lama diganti | **OK setelah perbaikan** | Lihat **Bug #2**: semula membuat run dobel |
| T10 | Hapus jadwal | Soft delete; pending dibatalkan | **OK (test)** | `ScheduledNotificationCrudTest` lulus |
| T11 | Cron mati | Website normal | **OK** | UI tidak bergantung runner |
| T12 | `NOTIF_SCHEDULE_ENABLED=false` | 0 pesan | **OK (test)** | `runner` mengembalikan `enabled=false`, tanpa pengiriman |

## 3. Bug yang ditemukan & diperbaiki saat UAT

### Bug #1 — `provider_message_id` selalu kosong (kritis)
- **Dampak:** pembatalan lewat *balas pesan bot* tidak akan pernah cocok
  (`missing_quote`), sehingga fitur inti `/cancel-schedule` gagal di produksi.
- **Akar:** `WhatsAppChannel` membaca `id._serialized`/`id`; WAHA NOWEB
  mengembalikan `key.id`.
- **Perbaikan:** `app/Services/Notification/Channels/WhatsAppChannel.php`
  membaca `key.id` lebih dulu (fallback lama dipertahankan).
- **Bukti:** `provider_message_id=3EB046DBC2A16589D9FA30` terisi; webhook balasan
  berhasil mencocokkan run.
- **Test:** `runner stores provider message id from waha noweb key`.

### Bug #2 — run dobel setelah jam kirim diubah
- **Dampak:** mengganti jam (mis. 19:00 → 18:00) menyisakan run lama pending
  pada tanggal yang sama → **pesan terkirim dua kali**.
- **Akar:** `refresh()` memakai `whereNotIn('occurrence_at')` sehingga run jam
  lama tidak terhapus, lalu `firstOrCreate` membuat baris kedua.
- **Perbaikan:** `SchedulePlanner`
  - `refresh()` membaca pending lama sebelum pembersihan, lalu
    `cancelStalePending()` menghapus tanggal yang tak lagi dijadwalkan dan
    membatalkan (`system`) run jam-lama pada tanggal yang sama;
  - `plan()` memanggil `cancelSupersededPending()` sebagai jaring pengaman
    idempoten: satu pending per tanggal kalender.
- **Bukti:** data UAT `TCRUT` = 61 tanggal, 61 pending, **0 tanggal ganda**.
- **Test:** `refresh cancels stale run when time changes`.

### Catatan #3 — `WA_NOTIF_FLAG_HOLIDAY_USERS` kosong di `.env` lokal
Whitelist diisi `6289654157132` untuk UAT S8. **Di produksi wajib diisi**
dengan nomor admin yang berwenang; bila kosong, semua `/cancel-schedule`
maupun `/libur` akan ditolak (`unauthorized`).

### Catatan #4 — UX: membuat jadwal untuk jam yang sudah lewat
Membuat jadwal `once` untuk jam yang sudah lewat hari ini menghasilkan
"0 kemunculan terdekat" tanpa penjelasan penyebab. **Saran (belum dikerjakan):**
tampilkan peringatan di form/notifikasi, mis. "Jam 09:00 sudah lewat; jadwal
sekali ini tidak akan terkirim."

### Catatan #5 — webhook WAHA produksi
Webhook `sessionsmi` mengarah ke produksi. Validasi jalur WhatsApp → aplikasi
secara end-to-end (bukan payload tiruan) tetap perlu dilakukan **di produksi**
setelah fitur di-deploy.

## 3a. Verifikasi multi-chat (20 November 2026)

Satu jadwal kini dapat dikirim ke banyak chat. Verifikasi dilakukan di server
lokal dengan WAHA live (`sessionsmi`), `NOTIF_TEST_MODE=true`.

| # | Aksi | Harapan | Hasil | Bukti |
|---|---|---|---|---|
| M1 | Form jadwal: **+ Tambah Chat** / hapus baris | Baris chat dinamis, `name` ter-reindex | **OK** | `targets[0..2][chat_id]` di-reindex setelah hapus/tambah |
| M2 | Simpan jadwal `UATMU` dengan 3 chat | Tersimpan 3 target; kolom lama = target pertama | **OK** | Flash "berhasil dibuat (UATMU) dengan 9 kemunculan terdekat"; `targets` = 3 baris |
| M3 | Halaman detail | "Chat Tujuan (3)" berisi semua chat + label | **OK** | `fase-5-uat/multi-chat-01-detail.png` |
| M4 | Halaman daftar + pencarian chat sekunder | Badge jumlah chat; pencarian `628111222333` menemukan `UATMU` | **OK** | `fase-5-uat/multi-chat-02-index.png` |
| M5 | Form edit | 3 baris target terisi nilai tersimpan | **OK** | `fase-5-uat/multi-chat-03-form-edit.png` |
| M6 | **Kirim Tes** | Pesan terkirim ke semua chat | **OK (live WAHA)** | Flash: "3 pesan percobaan terkirim ke: 120363404437691633@g.us, 6289654157132@c.us, 628111222333@c.us." Log: `Pesan percobaan jadwal dikirim {targets:[3 chat], failed_targets:[]}` |
| M7 | Edit target (ganti chat ke-3) | Daftar target diganti, kode tetap | **OK** | Detail menampilkan "Asisten 628555666777@c.us" |
| M8 | Runner mengirim run multi-chat (`UATMUL`) | Pesan sama ke setiap chat; run `sent` | **OK (live WAHA)** | `notification:run` → `Due=1, Terkirim=1`; log: `Notifikasi terjadwal terkirim {targets:[2 chat]}`; run `sent`, `provider_message_id=3EB03C04ED6D26671EC17F`; putaran berikutnya `Due=0` (tidak dobel) |
| M9 | Batalkan via webhook `/cancel-schedule` | Pengumuman libur ke semua chat | **OK (live WAHA)** | `cancelled`, `cancel_source=webhook`, `cancel_reason="lapangan banjir"`, log: `Pengumuman libur terkirim {targets:[2 chat], failed_targets:[]}` |
| M10 | Sebagian target gagal | Retry hanya ke target gagal | **OK (test)** | `partial failure retries only failed target` |

Tambahan data: pesan tes M6 dikirim ke grup + nomor tes + nomor pribadi
`628111222333@c.us` (nomor uji untuk membuktikan pengiriman ganda). Log WAHA
mencatat ketiga target sebagai berhasil (`failed_targets: []`).

## 4. Ringkasan suite test

```
Tests:    277 passed (940 assertions)
```

Perintah: `php artisan test` (MySQL `sip_testing`, DB `sip` tidak disentuh).
Pertambahan dari 258: 10 test multi-chat baru di `ScheduledNotificationTest`
dan 9 di `ScheduledNotificationCrudTest`.

## 5. Go-live checklist (sisa, di produksi)

1. Isi `WA_NOTIF_FLAG_HOLIDAY_USERS` dengan nomor admin.
2. Set `NOTIF_TEST_MODE=false` setelah uji akhir.
3. Pastikan **cron server** berjalan (`* * * * * php artisan schedule:run`).
4. `php artisan schedule:list` memuat `notification:plan` (00:05) dan
   `notification:run` (tiap menit).
5. Uji ulang S4/S5 sekali di produksi dengan grup asli.

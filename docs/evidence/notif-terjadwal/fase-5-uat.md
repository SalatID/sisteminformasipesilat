# UAT & Go-Live: Notifikasi Terjadwal WhatsApp

> **Prasyarat:** Fase 1–4 sudah terpasang (lihat
> `docs/rencana-implementasi-notifikasi-terjadwal.md` bab 15).
> **Target UAT:** 7–11 Desember 2026 (Fase 5).
> **Engine:** WAHA **NOWEB**, zona waktu `Asia/Jakarta`.

Isi kolom **Hasil** dengan `OK` / `GAGAL` + catatan. Simpan screenshot di
`docs/evidence/notif-terjadwal/fase-3-ui/` (UI) dan
`docs/evidence/notif-terjadwal/fase-5-uat/` (UAT).

---

## 1. Konfigurasi sebelum UAT

Tambahkan ke `.env` produksi/staging:

```ini
# Notifikasi terjadwal
NOTIF_SCHEDULE_ENABLED=true
NOTIF_SCHEDULE_TIMEZONE=Asia/Jakarta
NOTIF_SCHEDULE_HORIZON_DAYS=60
NOTIF_SCHEDULE_GRACE_MINUTES=30
NOTIF_SCHEDULE_MAX_ATTEMPTS=3
NOTIF_SCHEDULE_RETRY_DELAY_MINUTES=5
NOTIF_SCHEDULE_RUNNER_LIMIT=100

# Pembatalan lewat WhatsApp (whitelist sama dengan /libur)
NOTIF_CANCEL_SCHEDULE_ENABLED=true
NOTIF_CANCEL_SCHEDULE_REASON_MIN=3
NOTIF_CANCEL_SCHEDULE_REASON_MAX=255
NOTIF_CANCEL_SCHEDULE_PREVIEW=3

# Test mode: semua kiriman diarahkan ke nomor tes
NOTIF_TEST_MODE=false
NOTIF_TEST_CHAT_ID=628xxxxxxxxxx@c.us
```

Lalu:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan queue:restart
```

Cek planner & runner:

```bash
php artisan notification:plan --dry-run
php artisan notification:run --dry-run
```

### Cron (wajib, tanpa ini tidak ada pesan terjadwal)

Laravel scheduler telah didaftarkan di `app/Console/Kernel.php`:

- `notification:plan` — harian **00:05**
- `notification:run` — **tiap menit**, `withoutOverlapping()`

Pasang pada crontab server:

```cron
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

Buktikan cron berjalan:

```bash
php artisan schedule:list
tail -f storage/logs/laravel.log
```

---

## 2. Checklist UAT (9 skenario — bab 14 rencana)

| # | Aksi | Harapan | Hasil | Catatan / bukti |
|---|---|---|---|---|
| S1 | Buat jadwal Selasa & Kamis 12:00 (menu **Master Data → Jadwal Notifikasi**), lalu klik **Kirim Tes** | Pesan tes masuk ke nomor tes ≤ 30 detik; isi memuat `{nama_jadwal}`, `{hari}`, `{tanggal}` yang sudah terisi | | |
| S2 | Tunggu 12:00 (atau `php artisan notification:run --force --schedule_id=<id>`) | Pesan masuk ke grup target **tepat sekali** | | |
| S3 | Buka halaman detail jadwal | Run hari itu berstatus **Terkirim**, `provider_message_id` terisi | | |
| S4 | Balas pesan bot dengan `/cancel-schedule` + `Alasan : …` | Pengumuman libur masuk grup ≤ 1 menit; run **Dibatalkan**; konfirmasi memuat nama jadwal, hari, tanggal, alasan | | |
| S5 | Kirim `/cancel-schedule KODE-TANGGAL` untuk kemunculan besok | Besok tidak ada pesan latihan; pengumuman libur terkirim | | |
| S6 | Hentikan cron 1 jam | Run terlambat > 30 menit menjadi **Terlewat**, **tidak** dikirim telat | | |
| S7 | Matikan WAHA saat kirim | Retry 3x lalu **Gagal**; pesan error terlihat di UI; tombol **Kirim ulang** bekerja setelah WAHA hidup | | |
| S8 | Nomor non-whitelist mengirim `/cancel-schedule` | Ditolak; run tidak berubah | | |
| S9 | Pesan bebas di grup | Bot **diam** (tidak ada balasan, `not_a_command` di log) | | |

---

## 3. Uji tambahan (kasus tepi yang wajib dicoba)

| # | Aksi | Harapan | Hasil |
|---|---|---|---|
| T1 | `/CANCEL-SCHEDULE` lalu `ALASAN : hujan deras` (huruf besar) | Diterima (K14: kata kunci tidak case-sensitive) | |
| T2 | `/cancel-schedule tcgor-20261125` (kode huruf kecil) | Diterima; kode dinormalisasi ke huruf besar | |
| T3 | `/cancel-schedule TCGOR` (kode tanpa tanggal) | Bot membalas 3 kemunculan terdekat beserta kode lengkapnya | |
| T4 | `/cancel-schedule` tanpa `Alasan :` | Dibalas petunjuk format, run tidak berubah | |
| T5 | `/cancel-schedule` dengan `Alasan : ok` (2 karakter) | Ditolak ("terlalu pendek") | |
| T6 | `/cancel-schedule` ke run yang sudah lewat (kemarin) | Ditolak ("sudah lewat") | |
| T7 | Nonaktifkan jadwal dari UI | Semua run **Menunggu** menjadi **Dibatalkan** (sumber `system`), tidak ada pesan keluar | |
| T8 | Aktifkan lagi jadwal yang tadi dinonaktifkan | Planner membuat kemunculan baru; tidak ada yang terkirim sendiri | |
| T9 | Edit jadwal (ganti hari) | Pending lama diganti; run **Terkirim** tidak berubah | |
| T10 | Hapus jadwal | Soft delete; pending dibatalkan; tidak ada pesan keluar | |
| T11 | Hapus cron lalu buka website | Website tetap normal; hanya pengiriman terjadwal yang berhenti | |
| T12 | `NOTIF_SCHEDULE_ENABLED=false` lalu `notification:run` | 0 pesan keluar (rollback drill) | |

---

## 4. Monitoring 3 hari (go-live)

Isi tiap hari setelah jam kirim terakhir:

| Hari | Jadwal aktif | Kiriman normal | Missed | Failed | Duplikat | Unauthorized | Catatan |
|---|---|---|---|---|---|---|---|
| H1 | | | | | | | |
| H2 | | | | | | | |
| H3 | | | | | | | |

Query bantu:

```sql
-- ringkasan status per jadwal 3 hari terakhir
SELECT s.code, r.status, COUNT(*) AS jumlah
FROM scheduled_notification_runs r
JOIN scheduled_notifications s ON s.id = r.scheduled_notification_id
WHERE r.occurrence_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
GROUP BY s.code, r.status
ORDER BY s.code, r.status;

-- pembatalan via WhatsApp
SELECT public_code, cancel_source, cancel_reason, cancelled_at, announced_at
FROM scheduled_notification_runs
WHERE status = 'cancelled'
ORDER BY cancelled_at DESC
LIMIT 20;
```

Log:

```bash
grep "Pengumuman libur" storage/logs/laravel.log | tail
grep "Jadwal dibatalkan" storage/logs/laravel.log | tail
```

---

## 5. Rollback

| Situasi | Tindakan |
|---|---|
| Pesan ganda / salah grup | `NOTIF_SCHEDULE_ENABLED=false` + `php artisan config:clear`. Planner tetap jalan tapi runner berhenti total. |
| Cron bermasalah | Hapus entri `schedule:run`; pesan yang lewat 30 menit ditandai `missed`, bukan dikirim telat. |
| Salah jadwal | Nonaktifkan jadwal dari UI (pending dibatalkan tanpa pengumuman). |
| Fitur dihentikan total | `NOTIF_SCHEDULE_ENABLED=false`, `NOTIF_CANCEL_SCHEDULE_ENABLED=false`. Data & riwayat tetap tersimpan; `/libur` tidak terpengaruh. |

---

## 6. Sign-off

| Peran | Nama | Tanggal | Tanda tangan |
|---|---|---|---|
| Pemilik proses (admin TC) | | | |
| Operator WAHA / IT | | | |

**Kriteria go-live:** 9/9 skenario UAT lulus (atau penyimpangan disetujui),
monitoring 3 hari tanpa `missed`/`failed`/duplikat/unauthorized, dan rollback
drill terbukti menghasilkan 0 pesan.

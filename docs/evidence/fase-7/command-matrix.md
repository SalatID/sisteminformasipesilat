# Evidence Fase 7 — Refactor Command & Integrasi Controller

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `app/Console/Commands/SendAttendanceNotificationCommand.php` (refactor total) | ✅ |
| `app/Http/Controllers/AttendanceController.php` (`resendNotif`) | ✅ Kompatibel tanpa perubahan |
| `tests/Unit/Notification/CommandTest.php` | ✅ 6 test |

## 2. Signature Command

```
attendance:notify [options] [--] [<attendance_id>]

Arguments:
  attendance_id   ID attendance yang ingin dikirim ulang (melewati guard)

Options:
  --type[=TYPE]   Jenis notifikasi: all|report|reminder [default: "all"]
  --dry-run       Tampilkan pesan tanpa mengirim
  --force         Abaikan guard interval maupun jendela jam
```

**Backward compatible:** `attendance:notify {id}` tetap menerima `attendance_id` (dipakai `AttendanceController@resendNotif`).

## 3. Matriks Validasi

| # | Perintah | Hasil |
|---|---|---|
| 1 | `--type=reminder --dry-run --force` | Teks gabungan + mentions dicetak, **0 request** |
| 2 | `--type=report --dry-run --force` | Laporan lengkap dicetak, **0 request**, state tidak berubah |
| 3 | `--type=report --force` (kirim nyata) | `is_notif_send=true`, `channels=["telegram","whatsapp"]` |
| 4 | `--type=report --force` (ulang) | "Tidak ada laporan absensi yang perlu dikirim" (0 pesan) |
| 5 | `attendance:notify {id}` | `telegram: OK`, `whatsapp: OK`, exit 0, **melewati guard** |
| 6 | `--type=ngawur` | Exit 1, pesan error |
| 7 | provider kosong | Exit 0, 0 request |

### Guard end-to-end (AC-06 / AC-07)

```
=== 6x panggilan (tanpa force) ===
run 1: Pengingat gabungan diproses untuk 1 unit. | Selesai: 0 laporan, 1 pesan pengingat.
run 2: Siklus notifikasi dilewati (guard interval: sisa 180 menit).
run 3: Siklus notifikasi dilewati (guard interval: sisa 179 menit).
run 4: Siklus notifikasi dilewati (guard interval: sisa 179 menit).
run 5: Siklus notifikasi dilewati (guard interval: sisa 179 menit).
run 6: Siklus notifikasi dilewati (guard interval: sisa 179 menit).

=== --force ===
Pengingat gabungan diproses untuk 1 unit. | Selesai: 0 laporan, 1 pesan pengingat.

=== panggilan berikutnya tanpa force (harus dilewati) ===
Siklus notifikasi dilewati (guard interval: sisa 180 menit).
```

## 4. Bukti Pesan WhatsApp Nyata (test mode)

Dibaca kembali dari WAHA setelah command berjalan:

```
Test chat: 6289654157132@c.us | test_mode=true
--------------------------------------------------
PREFIX [TEST]   : YA
MENTIONED JID   : ["6285781656495@s.whatsapp.net"]     ← tag PJ aktif
JENIS           : PENGINGAT
1. *SMP Negeri 225 Jakarta* (14:30–16:30) — PJ: @6285781656495 (M. Arief P)

--------------------------------------------------
PREFIX [TEST]   : YA
JENIS           : LAPORAN
*Unit*: SDN Pegadungan 01 Pagi
*PJ Unit*: Zaenal Arifin (tanpa nomor → tanpa tag, sesuai AC-14)
```

## 5. Hasil Unit Test

```
PASS Tests\Unit\Notification\CommandTest   (6 passed, 12 assertions)

✓ signature keeps backward compatible argument and new options
✓ invalid type fails
✓ dry run sends zero requests
✓ unknown attendance id returns failure
✓ no active provider sends nothing
✓ command is scheduled every three hours            → expression 0 */3 * * *
```

## 6. Pemeriksaan Arsitektur

| Kriteria | Hasil |
|---|---|
| Tidak ada `Http::` / URL API di dalam command | ✅ 0 match |
| Tidak ada token/kredensial di log command | ✅ 0 match |
| Semua pengiriman via `NotificationManager` → channel | ✅ |
| Separator default mati (`NOTIF_SEND_END_MARKER=false`) | ✅ |

## 7. Exit Criteria

- [x] AC-10: resend via UI berfungsi (`attendance:notify {id}` → exit 0, 2 provider OK; signature dipertahankan).
- [x] Semua opsi (`--type`, `--dry-run`, `--force`, `attendance_id`) berperilaku sesuai definisi.
- [x] Tidak ada pemanggilan API langsung di command — semuanya lewat channel.

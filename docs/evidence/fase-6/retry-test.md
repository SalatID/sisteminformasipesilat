# Evidence Fase 6 — Tracking per Provider

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `database/migrations/2026_11_17_000001_add_notif_sent_channels_to_attendances.php` | ✅ Dijalankan |
| `app/Models/Attendance.php` (`$fillable` + `$casts`: `notif_sent_channels` => array, `is_notif_send` => boolean) | ✅ |
| `app/Services/Notification/AttendanceReportDispatcher.php` | ✅ |
| `app/Services/Notification/NotificationManager.php` (`supportsMentions()`) | ✅ |
| `tests/Unit/Notification/ReportRetryTest.php` | ✅ 5 test |

## 2. Skema

```
attendances.notif_sent_channels  json  NULL
```

Contoh isi: `["telegram","whatsapp"]` — provider yang **sudah sukses** untuk attendance tersebut.

## 3. Perbaikan T-08 (kritis)

| | Kode lama | Kode baru |
|---|---|---|
| Penanda | `is_notif_send = true` **selalu**, tanpa cek hasil | `is_notif_send = true` **hanya jika semua provider aktif sukses** |
| Kegagalan | Notifikasi hilang permanen | Tersimpan di `notif_sent_channels` sebagian → di-retry run berikutnya |

## 4. Hasil Unit Test

```
PASS Tests\Unit\Notification\ReportRetryTest   (5 passed, 17 assertions)

✓ failed provider is not marked as sent              → T-08
✓ failed provider is retried alone on next run       → AC-03 (hanya 1 request, telegram tidak dipanggil)
✓ already sent attendance is skipped                 → 0 request
✓ single provider mode marks sent immediately        → NOTIF_PROVIDERS=whatsapp
✓ all providers failing leaves unsent                → is_notif_send tetap false
```

### Skenario AC-03 (dibuktikan)

| Run | Aksi | Hasil |
|---|---|---|
| N | Telegram sukses, WhatsApp HTTP 500 | `is_notif_send=false`, `notif_sent_channels=["telegram"]` |
| N+1 | Dispatch ulang | hanya WhatsApp dipanggil (**1 request**), telegram **tidak** dipanggil |
| N+1 | Sukses | `is_notif_send=true`, `notif_sent_channels=["telegram","whatsapp"]` |

## 5. Verifikasi DB

```
kolom: notif_sent_channels | tipe: json | null: YES
attendance total: 471
is_notif_send belum true: 237   ← data lama utuh, tidak berubah
```

## 6. Exit Criteria

- [x] Migrasi berjalan tanpa error (kolom JSON nullable).
- [x] Retry hanya ke provider yang belum sukses (1 request, dibuktikan).
- [x] Data lama tetap valid — 471 baris tidak berubah; kolom baru `NULL` untuk data lama; nilai `is_notif_send` lama dipertahankan.

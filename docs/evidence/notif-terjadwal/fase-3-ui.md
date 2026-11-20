# Fase 3 — UI CRUD: status implementasi

**Tanggal:** 20 November 2026
**Gate:** CRUD + planner + kirim tes dari UI.

## Deliverable

| Komponen | File |
|---|---|
| Controller | `app/Http/Controllers/ScheduledNotificationController.php` |
| Validasi | `app/Http/Requests/ScheduledNotificationRequest.php` |
| View daftar | `resources/views/pages/admin/scheduled-notification/index.blade.php` |
| View form | `resources/views/pages/admin/scheduled-notification/form.blade.php` |
| View detail | `resources/views/pages/admin/scheduled-notification/show.blade.php` |
| Partial baris run | `resources/views/pages/admin/scheduled-notification/partials/run-row.blade.php` |
| Route | `routes/web.php` (`scheduled-notification.*`, 14 route) |
| Menu | `Controller::list_menu()` → **Master Data → Jadwal Notifikasi** (`notif-jadwal_list`) |
| Permission | `database/migrations/2026_11_20_000003_create_scheduled_notification_permissions.php` |
| Test | `tests/Feature/ScheduledNotificationCrudTest.php` (24 test) |

## Aksi yang tersedia

- `index` — daftar + pencarian (nama/kode/chat) + filter status aktif.
- `create` / `store` — kode dibuat otomatis (`generateCode`), planner langsung membuat kemunculan.
- `show` — ringkasan, pratinjau pesan latihan/libur, kemunculan mendatang & riwayat, modal pembatalan.
- `edit` / `update` — kode **tidak berubah**; pending lama disegarkan, run final tidak disentuh.
- `destroy` — soft delete + pending dibatalkan (tanpa pesan keluar).
- `toggle` — nonaktif membatalkan pending; aktif kembali memicu `refresh()` agar run `cancelled` sistem dihapus dan dijadwalkan ulang.
- `preview` — JSON pratinjau template + deteksi variabel tidak dikenal.
- `test` — kirim pesan percobaan ke chat jadwal (menghormati test mode).
- `cancel-run` — batalkan satu kemunculan + pengumuman libur (alasan wajib, 3–255 karakter).
- `reannounce` — kirim ulang pengumuman untuk run yang dibatalkan tetapi `announced_at` kosong.
- `retry` — kirim ulang run `failed`/`missed` lewat runner (`force`, `limit 1`).
- `replan` — susun ulang kemunculan (`refresh`).

## Hasil verifikasi

```
tests/Feature/ScheduledNotificationCrudTest.php ......... 24 passed (90 assertions)
tests/Feature/ScheduledNotificationTest.php ............. 10 passed (41 assertions)
tests/Feature/CancelScheduleWebhookTest.php ............. 17 passed (75 assertions)
tests/Feature/WhatsAppWebhookTest.php ................... 29 passed (93 assertions)
tests/Unit/Notification/Schedule ........................ 30 passed (59 assertions)
Full suite .............................................. 256 passed (854 assertions)
```

Pint bersih untuk seluruh file fitur (`app/Services/Notification/Schedule`,
controller/request baru, test fitur). File lama yang memang belum Pint-clean
tidak disentuh.

## Catatan perbaikan selama Fase 3

1. `ScheduledNotificationRequest` — `dates_of_month`/`weeks_of_month` yang
   kosong sebelumnya menjadi `null` sehingga validasi `array` gagal; kini
   selalu dinormalisasi menjadi array (menerima `"1,15"` dari form).
2. `SchedulePlanner::refresh()` — run `cancelled` bersumber `system` yang belum
   diumumkan dihapus saat jadwal aktif kembali, karena `plan()` memakai
   `firstOrCreate` dan tidak akan menghidupkan baris lama.
3. `MessageTemplate::trainingMessage()` + `notifications.schedules.default_training_message`
   agar pratinjau/kirim tes tetap bermakna saat kolom pesan kosong.
4. `HolidayAnnouncer::sendTest()` untuk tombol **Kirim Tes**.
5. Trait test kini membuat tabel Spatie minimal (`permissions`, `roles`,
   pivot) agar layout admin (sidebar `@canany`) bisa dirender.

## Belum (Fase 5)

- Screenshot UI di `fase-3-ui/` (perlu browser + login staging).
- Gate nyata: admin membuat 2 jadwal (GOR & Al Firdaus) di staging, kirim tes,
  dan sign-off tampilan.

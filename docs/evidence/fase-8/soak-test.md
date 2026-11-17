# Soak Test 7 Hari — Fase 8 (Template Pemantauan)

**Jadwal:** 18–25 November 2026
**Target:** `6289654157132@c.us` (test mode aktif, belum ke grup)
**Provider:** `NOTIF_PROVIDERS=telegram,whatsapp`

## Cara Mengisi

Setiap hari, isi satu baris tabel. Data diambil dari:

1. `php artisan attendance:notify --dry-run --force` → cek isi pesan tanpa mengirim.
2. `storage/logs/cron/*.log` → cari `attendance:notify selesai`, `skipped`, `send failed`.
3. `GET {WAHA_URL}/api/sessions/{WAHA_SESSION}` → field `status`.
4. Chat test di WhatsApp → cek jumlah pesan masuk hari itu.

## Tabel Harian

| Hari | Tanggal | Cron jalan | Status sesi | Pengingat terkirim | Laporan terkirim | Gagal/retry | Pesan di luar 07:00–21:59 | Duplikat | Catatan |
|---|---|---|---|---|---|---|---|---|---|
| 1 | 18 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 2 | 19 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 3 | 20 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 4 | 21 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 5 | 22 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 6 | 23 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |
| 7 | 24 Nov 2026 | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ | |

## Kriteria Lulus Soak Test

- [ ] Cron berjalan setiap hari minimal 1 siklus (bukti di `storage/logs/cron/`).
- [ ] Status sesi WAHA `WORKING` pada **7/7** hari (atau setelah re-scan, dengan catatan downtime).
- [ ] Tidak ada pesan di luar jendela 07:00–21:59.
- [ ] Tidak ada notifikasi duplikat untuk attendance yang sama pada alur normal.
- [ ] Tidak ada pesan separator "End of Attendance Notification Process".
- [ ] Tidak ada kredensial pada log (`storage/logs/*`).

## Setelah Lulus

1. Centang item soak test pada `docs/rencana-implementasi-notifikasi-telegram-whatsapp.md` (Fase 8).
2. Minta tanda tangan persetujuan go-live grup (Bagian 10 dokumen rencana).
3. Ubah `.env` produksi: `NOTIF_TEST_MODE=false`, `WAHA_CHAT_ID=120363404437691633@g.us`.
4. Kirim 1 laporan uji ke grup, lalu verifikasi.

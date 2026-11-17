# Evidence Fase 4 — Format Pesan (Konsolidasi Reminder + Tag PJ & Laporan)

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `app/Services/Notification/AttendanceMessageBuilder.php` | ✅ |
| `app/Services/Notification/Channels/WhatsAppChannel.php` (`formatForWhatsApp()`) | ✅ |
| `tests/Unit/Notification/MessageBuilderTest.php` | ✅ 13 test |

Format kanonik channel-agnostic: `*bold*`, `_italic_`, `[label](url)` → Telegram HTML / WhatsApp markdown / WA link teks.

## 2. Hasil Unit Test

```
PASS Tests\Unit\Notification\MessageBuilderTest   (13 passed, 58 assertions)

✓ empty reminder returns null                                    (AC-05)
✓ n units produce exactly one message [3 unit]                   (AC-04)
✓ n units produce exactly one message [30 unit]                  (AC-04)
✓ units with valid phone are mentioned                           (AC-13)
✓ unit without phone falls back to plain name                    (AC-14)
✓ mention disabled shows plain names
✓ channel without mention support gets plain names
✓ mention budget is enforced                                     (max 10)
✓ reminder uses canonical bold format
✓ attendance report contains all 13 fields                       (AC-08)
✓ report without ts does not error                               (T-04)
✓ photo links are limited to four                                (R5)
✓ long message is chunked under limit                            (Telegram 4096)
```

## 3. Uji Data Nyata (4 unit belum absen hari ini)

Query: unit aktif, `training_day` = hari ini, belum ada attendance.

```
=== ITEM: 4 unit belum absen ===
*Pengingat Absensi Latihan*

Tanggal: 17/11/2026
Unit yang belum mengirim laporan absensi:

1. *SDN Pegadungan 01 Pagi* (13:00–15:00) — PJ: Zaenal Arifin
2. *SDN Kalideres 05* (13:30–15:00) — PJ: @6285713929759 (Revika Zulviani)
3. *SD KARAWACI 08* (15:30–17:30) — PJ: @6285718331895 (Apriyanto)
4. *SMP/SMA/SMK Cengkareng 1* (15:30–17:30) — PJ: @6285960104222 (Catur Wulandari)

Total: 4 unit.
Mohon lengkapi laporan absensi melalui aplikasi.
```

Mentions: `["6285713929759@c.us","6285718331895@c.us","6285960104222@c.us"]`

| Kriteria | Hasil |
|---|---|
| 4 unit → **tepat 1 pesan** (AC-04) | ✅ |
| PJ bernomor ter-tag `@nomor (Nama)` (AC-13) | ✅ 3/3 |
| PJ tanpa nomor → nama biasa, tidak error (**Zaenal Arifin**, AC-14) | ✅ |
| Konsistensi `@nomor` di teks ≡ array `mentions` | ✅ YA |
| Versi Telegram: 0 `@nomor`, 0 mentions (bot API tak bisa mention tanpa username) | ✅ |

## 4. Bug Baseline yang Diperbaiki

| Bug | Sebelum | Sesudah |
|---|---|---|
| **Keterlambatan Absen** | `-2.375 hari` (float, terbalik) | `2 hari` (hari kalender bulat, urutan benar) |
| **T-04** relasi `ts` null | `Attempt to read property on null` | Null-safe, tampil nama tanpa alias |
| **T-01** `*bold*` + `parse_mode=html` | asterisk literal | Konversi `*bold*` → `<b>bold</b>` |
| Penomoran pelatih/asisten | Nomor asisten ikut melompat | Dihitung per daftar masing-masing |
| Link foto tak terbatas | Semua link dikirim | Maks 4 + "... dan N foto lainnya" |

## 5. Exit Criteria

- [x] AC-04: N unit → 1 pesan (dibuktikan 3, 30, dan 4 unit riil).
- [x] AC-05: daftar kosong → 0 pesan (`null`, tidak ada request).
- [x] AC-08: laporan tetap lengkap 13 field.
- [x] AC-13: teks `@<nomor>` + array `mentions` konsisten.
- [x] AC-14: PJ tanpa nomor → nama biasa, tanpa error.
- [x] Tidak ada asterisk literal di Telegram — diverifikasi lewat `TelegramChannel` yang mengonversi ke `<b>` (lihat `ChannelTest::telegram_success`).

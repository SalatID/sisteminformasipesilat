# Evidence Fase 3 — Data Nomor PJ (`nomor_whatsapp`)

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `database/migrations/2026_11_17_000000_add_nomor_whatsapp_to_coachs.php` | ✅ Dijalankan |
| `app/Support/PhoneNormalizer.php` | ✅ |
| `app/Models/Coach.php` (`$fillable`) | ✅ |
| `app/Http/Controllers/AdminCotroller.php` (`coachStore` / `coachUpdate`: validasi + normalisasi) | ✅ |
| `resources/views/pages/admin/coach/form.blade.php` (input create & edit) | ✅ |
| `resources/views/pages/admin/coach/list.blade.php` (kolom Nomor WhatsApp) | ✅ |
| `resources/views/pages/admin/coach/show.blade.php` (baris Nomor WhatsApp) | ✅ |
| `tests/Unit/Support/PhoneNormalizerTest.php` | ✅ 11 test |

## 2. Skema

```
coachs.nomor_whatsapp  varchar(20)  NULL  after ts_id
```

Format kanonik: **E.164 tanpa `+`** — `6281234567890` (dipakai langsung sebagai `<nomor>@c.us`).

## 3. Unit Test Normalisasi (AC-15)

```
PASS Tests\Unit\Support\PhoneNormalizerTest  (11 passed, 19 assertions)

✓ 081234567890          → 6281234567890
✓ +6281234567890        → 6281234567890
✓ 6281234567890         → 6281234567890
✓ "62 812-3456-789"     → 628123456789
✓ "  081298765432  "    → 6281298765432
✓ "abc", "12345", "0812", "15551234567"  → DITOLAK
✓ kosong / null         → diizinkan, tersimpan NULL (opsional)
✓ konversi idempoten (62812… tetap 62812…)
```

## 4. Backfill & Cakupan

Nomor WhatsApp PJ diisi oleh pemilik proses melalui halaman **Edit Pelatih**.

```
Total nomor PJ terisi : 16
Nomor tidak valid     : 0
Cakupan unit aktif    : 17/18 = 94,4%   (ambang ≥ 90% ✅)
```

Unit yang PJ-nya belum punya nomor (**akan tampil sebagai nama biasa**, fallback AC-14):

| Unit | PJ |
|---|---|
| SDN Pegadungan 01 Pagi | Zaenal Arifin |

| PJ (contoh) | nomor_whatsapp | Unit |
|---|---|---|
| Mursalat Asyidiq *(subjek uji tag)* | 6289654157132 | SMK TI YPML |
| M. Arief P | 6285781656495 | SMP Negeri 225 Jakarta, SMP IT AL MAKA, SMA IT AL MAKA, SMP Kebudayaan |
| Apriyanto | 6285718331895 | SD KARAWACI 08, MI NURUL YAKIN, SMK Ad Dakwah |
| Eko Purwanto | 6285714060554 | SMP & SMK Bangun Nusantara |
| Catur Wulandari | 6285960104222 | SMP/SMA/SMK Cengkareng 1 |

## 5. Exit Criteria

- [x] Migrasi berjalan tanpa error; rollback migration teruji (via `down()` standar `dropColumn`).
- [x] Form coach menerima & menormalisasi nomor (**AC-15**) — validasi closure + `PhoneNormalizer`, list/form/detail menampilkan nomor.
- [x] Cakupan `nomor_whatsapp` PJ aktif ≥ 90% (**17/18 = 94,4%**); sisanya tercatat eksplisit (Zaenal Arifin).
- [x] Data lama tidak berubah (migrasi aditif nullable, 0 baris lama dimodifikasi).

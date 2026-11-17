# Validasi Ulang Exit Gate Fase 0–8

Tanggal: 17 November 2026
Pemicu: permintaan pemilik proses — "validasi ulang tiap fasenya".

## Metode

Setiap exit gate diuji ulang dengan **eksekusi nyata** (bukan hanya membaca evidence lama):

1. `php artisan test --testsuite=Unit` → 54 test.
2. Skrip validasi 39 gate (`F0`–`F8`): config, channel, builder, guard, dispatcher, command, scheduler, WAHA.
3. Skrip validasi 12 gate tambahan: form coach (validasi + normalisasi), migrasi, integritas data.

## Hasil Ringkas

| Fase | Gate diuji | Hasil |
|---|---|---|
| 0 — Inventaris | 3 | ✅ 3/3 |
| 1 — Konfigurasi | 5 | ✅ 5/5 |
| 2 — Channel | 4 | ✅ 4/4 |
| 3 — Data Nomor PJ | 5 + 9 | ✅ 14/14 |
| 4 — Format pesan | 10 | ✅ 10/10 |
| 5 — Jadwal & guard | 4 | ✅ 4/4 |
| 6 — Tracking provider | 3 + 1 | ✅ 4/4 |
| 7 — Refactor command | 4 | ✅ 4/4 |
| 8 — UAT & bot-safety | 2 | ✅ 2/2 (+ `--dry-run` 0 request) |

**Total: 51 gate PASS, 0 FAIL.** Unit test: **54 passed (151 assertions)**.

## Rincian Bukti

### Fase 0
```
PASS | timezone Asia/Jakarta                                   | Asia/Jakarta
PASS | unit aktif terinventaris                                | 18 unit
PASS | test chat id                                            | 6289654157132@c.us
```

### Fase 1
```
PASS | providers dari env                                     | telegram,whatsapp
PASS | mention config ada                                      | enabled/max OK
PASS | interval 3 jam                                          | 3
PASS | end marker mati                                         | false
PASS | tidak ada kredensial di log                             | temuan=0
```

`php artisan config:show notifications` menampilkan `mention.enabled=true`, `mention.max=10`, `send_delay_seconds=3`.
`php artisan schedule:list` → `0 */3 * * *  php artisan attendance:notify`.

### Fase 2
```
PASS | manager mengenali 2 provider                            | telegram,whatsapp
PASS | whatsapp supports mention                               | true
PASS | telegram tanpa mention                                  | false
PASS | konversi link WA (tanpa markdown)                       | Foto 1: https://x.test/a.jpg
```

### Fase 3 (form coach + migrasi)
```
PASS | form menerima 081298765432                              | valid
PASS | normalisasi 0812... -> 62812...                         | 6281298765432
PASS | normalisasi +62... -> 62...                             | 6281298765432
PASS | form menolak 0812 (terlalu pendek)                      | Format nomor WhatsApp tidak valid.
PASS | form menolak huruf                                      | Format nomor WhatsApp tidak valid.
PASS | form mengizinkan kosong (opsional)                      | valid
PASS | migration punya down() dropColumn                       | ada
PASS | migrasi nomor_whatsapp tercatat berjalan                | ya
PASS | kolom coachs.nomor_whatsapp ada                         | ada
PASS | fillable Coach                                          | ada
PASS | cakupan PJ bernomor >= 90%                              | 17/18 = 94,4%
PASS | semua nomor tersimpan ternormalisasi                    | tidak valid=0
PASS | Mursalat nomor test                                     | 6289654157132
```

### Fase 4
```
PASS | AC-04 30 unit -> 1 pesan                                | panjang=1515
PASS | AC-04 mention dibatasi 10                               | 10
PASS | AC-05 kosong -> null                                    | null
PASS | AC-13 teks @nomor konsisten dgn mentions                | ["628111222333@c.us"]
PASS | AC-14 PJ tanpa nomor = nama biasa                       | fallback OK
PASS | channel non-mention tanpa tag                           | bersih
PASS | AC-08 laporan field lengkap                             | hilang=[]
PASS | keterlambatan bukan negatif/float                       | *Keterlambatan Absen*: 0 hari
PASS | Telegram chunking <= 4000                               | 1 chunk
```

### Fase 5
```
PASS | AC-06 6 panggilan -> 1 eksekusi                         | eksekusi=1
PASS | --force bypass interval                                 | eksekusi=2
PASS | jendela jam aktif                                       | 07-21
```

### Fase 6
```
PASS | kolom notif_sent_channels ada                           | ada
PASS | migrasi notif_sent_channels tercatat berjalan           | ya
PASS | pending provider hanya yang belum sukses                | ["whatsapp"]
PASS | tidak ada pending saat semua sukses                     | kosong
PASS | data attendance utuh                                    | 471
PASS | is_notif_send=false dipertahankan                       | 237
```

### Fase 7
```
PASS | argumen attendance_id ada                               | ada
PASS | opsi --type/--dry-run/--force                           | ada
PASS | command tanpa Http:: langsung                           | bersih
PASS | command tanpa log token                                 | bersih
```

### Fase 8
```
PASS | dry-run 0 request                                       | exit=0
PASS | sesi WAHA WORKING                                       | WORKING
```

## Gate yang Masih Terbuka (bukan kegagalan validasi)

| Gate | Status | Alasan |
|---|---|---|
| 7 hari soak tanpa kegagalan sesi | ⬜ | Butuh 7 hari kalender (18–25 Nov 2026). Template: `soak-test.md`. |
| Persetujuan pemilik proses go-live grup | ⬜ | Menunggu tanda tangan Bagian 10. |

## Catatan Koreksi Dokumen

Validasi ulang ini menemukan sisa-an lama di dokumen rencana yang bisa menyesatkan:

1. **Bagian 4 (arsitektur)** dan **4.1** masih menulis `mentioned` sebagai nama field WAHA → dikoreksi menjadi `mentions`.
2. **Fase 2 langkah 3 & 5** masih menyebut payload `mentioned` → dikoreksi.
3. **Fase 3 langkah 5** masih berstatus "⬜ Menunggu data" padahal nomor sudah terisi 94,4% → diperbarui.
4. **Fase 4 contoh keluaran** masih menulis `mentioned: [...]` → dikoreksi.

Temuan ini sudah diperbaiki pada versi dokumen **1.4** (lalu **1.5** untuk kebijakan tag per channel).

## Catatan Insiden Dokumentasi (17 Nov 2026)Saat proses koreksi, file dokumen sempat rusak (0 byte) akibat perintah PowerShell yang gagal. Pemulihan dilakukan dari snapshot git OpenCode:

- Snapshot: `C:\Users\Lenovo\.local\share\opencode\snapshot\fc8b4fc638199710bdf1b471712de7bcdbfb052e\f60b28010e1ccdf12d380bc39ab88b9826a2480d`
- Blob dokumen v1.3: `e471efbe6aea5025c150de692bfa95b540e0884f` (34 checklist tercentang, 2 kosong)

File dipulihkan utuh lalu ditulis ulang via Python (UTF-8, CRLF). Mojibake ganda (`â€"`, `â”œ`, `â‰¥`, `â¬œ`, dst.) yang muncul akibat penulisan lewat PowerShell juga dibersihkan; verifikasi akhir: **0 karakter rusak (U+FFFD) dan 0 sisa pola mojibake**, 34 checklist `[x]`, 2 checklist `[ ]`.

**Pelajaran:** jangan menulis/mengganti file dokumen ini lewat `Get-Content | Set-Content` PowerShell 5. Gunakan tool `edit` atau Python.


# Evidence Fase 8 — UAT, Bot-Safety & Go-Live Bertahap

Tanggal: 17 November 2026

## 1. Ringkasan

| Item | Hasil |
|---|---|
| Skenario UAT | **12/12 LULUS** |
| Unit test keseluruhan | **54 passed, 151 assertions** |
| Sesi WAHA selama UAT | `WORKING` |
| Target pengiriman | `6289654157132@c.us` (personal, **bukan grup**) |

## 2. Matriks UAT (bukti nyata)

| # | Skenario | Hasil | Bukti |
|---|---|---|---|
| 1 | `--dry-run --type=reminder` | ✅ | Teks gabungan tercetak, **0 request** |
| 2 | WhatsApp test mode | ✅ | HTTP **201**, chatId `6289654157132@c.us`, prefix `[TEST]` |
| 3 | Telegram bold | ✅ | `message_id 8102`, `<b>` ter-render |
| 4 | Kedua provider | ✅ | `telegram:OK, whatsapp:OK` |
| 5 | API key WAHA salah | ✅ | `telegram OK=true`, `whatsapp gagal=true` (isolasi) |
| 6 | Run 2× berurutan | ✅ | run1 executed, run2 `reason=interval` |
| 7 | `attendance:notify {id}` | ✅ | `telegram: OK`, `whatsapp: OK`, exit 0 |
| 8 | Data pelatih/TS kosong | ✅ | Null-safe, tidak error (unit test `report without ts does not error`) |
| 9 | 30 unit belum absen | ✅ | **1 pesan** 1.635 char; 30 baris, mention dibatasi 10 |
| 10 | Test mode off | ✅ | chatId → `120363404437691633@g.us` |
| 11 | PJ bernomor vs tanpa nomor | ✅ | `Eko Purwanto=6285714060554` (tag) \| `Zaenal Arifin` (nama biasa) |
| 12 | `NOTIF_WA_MENTION_ENABLED=false` | ✅ | `mentions=[]`, tanpa `@nomor` |

### Bukti tag PJ aktif (dibaca kembali dari WAHA)

```
PREFIX [TEST]   : YA
MENTIONED JID   : ["6285781656495@s.whatsapp.net"]     ← tag menyala
1. *SMP Negeri 225 Jakarta* (14:30–16:30) — PJ: @6285781656495 (M. Arief P)
```

## 3. Metrik Bot-Safety

Sampel 40 pesan terakhir dari chat test, difilter hari ini (`Asia/Jakarta`):

| Metrik | Ambang | Terukur | Status |
|---|---|---|---|
| Pesan separator | 0 | **0** | ✅ |
| Delay minimum antar pesan | ≥ 3 detik | **3 detik** | ✅ |
| Delay median | — | 10 detik | ✅ |
| Pesan di luar 07:00–21:59 | 0 | **0** | ✅ |
| Status sesi WAHA | `WORKING` | `WORKING` | ✅ |
| Notifikasi identik persis | 0 | **4** | ⚠️ lihat catatan |
| Pesan keluar/hari | ≤ 30 | 9 notifikasi (18 total dgn UAT) | ✅ |

**Catatan temuan #identik:** 4 pesan identik berasal dari **pengujian berulang di menit yang sama** (UAT #4/#7 dijalankan 3×, tiap run memakai `--force` dan mengirim laporan identik untuk attendance yang sama). Pada operasi normal hal ini tidak terjadi karena:
- guard 3 jam membatasi siklus,
- `is_notif_send` + `notif_sent_channels` mencegah laporan terkirim ulang,
- `--force` tidak dipakai otomatis.

**Rekomendasi operasional:** jangan menjalankan `--force` di produksi selain untuk pemulihan manual; pertimbangkan `NOTIF_TEST_MODE=false` + grup hanya setelah soak test.

## 4. Perbaikan Bug Selama Fase 8

| Bug | Dampak | Perbaikan |
|---|---|---|
| `NOTIF_WA_MENTION_ENABLED` diabaikan saat diubah runtime | Tidak bisa mematikan tag tanpa restart | `mentionsAllowed()` membaca config saat pemanggilan |
| `$supportMentions` tidak menutup jalur budget mention | Channel non-mention tetap menghasilkan tag | Budget di-nol-kan bila channel tidak mendukung |
| `$dryRun` tidak di-`use` pada closure query | Fatal error `--type=report --dry-run` | Closure disederhanakan |

## 5. Exit Criteria

- [x] **12/12** skenario UAT lulus.
- [ ] **7 hari soak test** — *belum dapat dilakukan pada sesi ini* (butuh waktu kalender; dijadwalkan 18–25 Nov 2026). Prosedur: jalankan cron `schedule:run` tiap menit, pantau `storage/logs/cron/*` dan `GET /api/sessions/sessionsmi` harian.
- [x] Tidak ada laporan duplikat pada alur normal (guard + `notif_sent_channels`; dibuktikan UAT #6 & #7).
- [ ] **Persetujuan pemilik proses** untuk pindah ke grup — *menunggu approval* (Bagian 10 dokumen rencana).

## 6. Status Go-Live

**SIAP untuk soak test** di `6289654157132@c.us`. **Belum** diarahkan ke grup `120363404437691633@g.us` sampai soak 7 hari + persetujuan pemilik proses.

**Rollback instan (tanpa deploy):**

```ini
NOTIF_PROVIDERS=telegram        # matikan WhatsApp
NOTIF_TEST_MODE=true            # kunci ke chat pribadi
```

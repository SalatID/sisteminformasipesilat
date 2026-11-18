# UAT & Go-Live: Tandai Sesi Libur via Balasan WhatsApp

> **Prasyarat:** Fase 1–3 sudah terpasang (lihat
> `docs/rencana-implementasi-libur-via-balasan-wa.md` bab 16).
> **Tanggal:** 18 November 2026
> **Engine:** WAHA **NOWEB**

Isi kolom **Hasil** dengan `OK` / `GAGAL` + catatan. Simpan screenshot di
`docs/evidence/` bila perlu.

---

## 1. Konfigurasi sebelum UAT

Tambahkan ke `.env` produksi/staging:

```ini
NOTIF_PROVIDERS=whatsapp
NOTIF_REPLY_ENABLED=true
WA_NOTIF_FLAG_HOLIDAY_USERS=6289654157132
NOTIF_REPLY_REASON_MAX=255

WAHA_URL=https://waha-anda
WAHA_API_KEY=...
WAHA_SESSION=sessionsmi
WAHA_CHAT_ID=120363xxxxxxxxxxxxxx@g.us

WAHA_WEBHOOK_SECRET=ganti-dengan-string-acak-panjang
WAHA_WEBHOOK_HMAC_KEY=ganti-dengan-string-acak-panjang
```

Lalu:

```bash
php artisan config:clear
php artisan cache:clear
```

**Catatan penting:** `WA_NOTIF_FLAG_HOLIDAY_USERS` menerima beberapa gaya
penulisan (`6289654157132`, `6289654157132@c.us`, `089654157132`). Pengirim
ber-`@lid` (ID tersembunyi) **bisa** di-whitelist dengan menulis nilai `@lid`
apa adanya (mis. `188643671077027@lid`), atau cukup nomor teleponnya bila
payload WAHA menyertakan padanan nomor (`participantAlt`/`participantPn`).
Nilai yang tidak dikenali dicatat di log sebagai `candidates` — lihat bab 6.

**Format perintah yang berlaku (revisi 17 Nov 2026):**

```
/libur
Nomor 1
Diliburkan Sekolah
Alasan : Sedang ujian
```

Satu pesan boleh memuat beberapa nomor (maks. 10), ditulis sebagai blok berurutan:

```
/libur
Nomor 1
Diliburkan Sekolah
Alasan : ujian

Nomor 2
Diliburkan Pelatih
Alasan : hujan
```

Blok baru dimulai setiap baris `Nomor N`; baris kosong di antaranya opsional.
Bila salah satu blok tidak valid, **seluruh** pesan ditolak dan bot membalas
`Blok ke-N: ...` (tidak ada nomor yang tersimpan sebagian).

Semua kata kunci tidak case-sensitive (`/LIBUR`, `NOMOR 1`, `ALASAN :`).
Awalan `/libur` **wajib**: pesan tanpa awalan dianggap obrolan biasa dan
**diabaikan tanpa balasan** (bot tidak lagi membalas "tidak boleh" untuk
obrolan biasa). Bila `/libur` ditulis tetapi isinya belum lengkap, bot
mengirim contoh format.

---

## 2. Daftarkan webhook di WAHA

Ganti `{WAHA_WEBHOOK_SECRET}` dan `{WAHA_WEBHOOK_HMAC_KEY}` dengan nilai `.env`.

### Cara A — Dashboard WAHA

**Sessions → pilih sesi → Webhooks → tambah**:

| Field | Nilai |
|---|---|
| URL | `https://domain-anda/api/whatsapp/webhook/{WAHA_WEBHOOK_SECRET}` |
| Events | `message` |
| HMAC key | `{WAHA_WEBHOOK_HMAC_KEY}` |
| Retries | `linear`, delay 5 detik, attempts 3 |

### Cara B — API

```bash
curl -X PUT "https://waha-anda/api/sessions/sessionsmi" \
  -H "X-Api-Key: $WAHA_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "sessionsmi",
    "config": {
      "webhooks": [{
        "url": "https://domain-anda/api/whatsapp/webhook/GANTI_SECRET",
        "events": ["message"],
        "hmac": { "key": "GANTI_HMAC" },
        "retries": { "policy": "linear", "delaySeconds": 5, "attempts": 3 }
      }]
    }
  }'
```

### Verifikasi endpoint hidup

```bash
curl -i -X POST "https://domain-anda/api/whatsapp/webhook/SECRET_SALAH" \
  -H "Content-Type: application/json" -d '{"event":"message"}'
# Harus: HTTP 403 {"ok":false,"error":"forbidden"}
```

> HTTP 403 untuk secret salah = endpoint aktif dan aman. Jangan lanjut sebelum
> respons ini benar.

---

## 3. Uji tanpa WAHA (pratinjau lokal)

Tidak mengirim WhatsApp, tidak menulis database.

```bash
php artisan attendance:holiday-preview \
  --quoted-file=docs/evidence/fixtures/pengingat-9-baris.txt \
  --text-file=docs/evidence/fixtures/balasan-valid.txt
```

Harapan (sudah diverifikasi 18 Nov 2026 terhadap data nyata):

```
Tipe pesan     : reminder
Baris          : 1 dari 9
Unit           : SMP/SMA/SMK Cengkareng 1
Tanggal        : 2026-11-17 (Selasa, 17 November 2026)
Status baru    : school_holiday (Diliburkan Sekolah)
Keputusan      : create
── Balasan bot ──
sudah saya update,
SMP/SMA/SMK Cengkareng 1, Selasa, 17 November 2026, Diliburkan Sekolah Karena Sedang ujian
```

| # | Perintah | Harapan | Hasil |
|---|---|---|---|
| P1 | `--text-file=balasan-valid.txt` (`/libur`, `Nomor 1`) | `Baris 1 dari 9`, unit Cengkareng 1, 17 Nov | OK |
| P2 | `--text-file=balasan-pelatih.txt` (`Nomor 3`, `DILIBURKAN PELATIH`) | Status `cancelled`, unit `SD KARAWACI 08`, 14 Nov | OK |
| P3 | `--text-file=balasan-nomor-12.txt` | `Nomor 12 tidak ada pada pesan tersebut. ... hanya memuat 9 baris.` | OK |
| P4 | `--text="Diliburkan Sekolah"` tanpa awalan | **BUKAN PERINTAH — bot akan diam** | OK |
| P5 | `--text-file=balasan-bebas.txt` | **BUKAN PERINTAH — bot akan diam** | OK |
| P6 | `/libur` + `Nomor 9` (`balasan-nomor-9.txt`) | Unit pada pesan tersebut tidak ditemukan (lihat catatan di bawah) | OK |
| P7 | format lengkap **tanpa** `/libur` | **BUKAN PERINTAH — tidak ada awalan /libur, bot akan diam** | OK |
| P8 | `--text-file=balasan-multi.txt` (`/libur`, `Nomor 1` & `Nomor 3`) | Dua blok ditampilkan (Cengkareng 1 & SD KARAWACI 08) + satu balasan gabungan bernomor | OK |

> Catatan data 18 Nov 2026: unit **MI Assanusiah** dan **RA Al Hadi** pada
> contoh lama pemilik proses sudah tidak ada di tabel `units`, sehingga baris
> 4 dan 9 menghasilkan `unit_not_found`. Ini perilaku benar, bukan bug.
> Untuk uji "Diliburkan Pelatih" dipakai baris 3 (`SD KARAWACI 08`) karena
> unit tersebut masih ada.

---

## 4. Uji webhook dengan payload asli

Kirim payload contoh (berisi `replyTo` seperti WAHA NOWEB) ke server:

```bash
curl -i -X POST "https://domain-anda/api/whatsapp/webhook/SECRET" \
  -H "Content-Type: application/json" \
  --data-binary @docs/evidence/fixtures/waha-reply-payload.json
```

Harapan: `{"ok":true,"status":"flagged"}` dan **pesan konfirmasi muncul di grup**
(`WAHA_CHAT_ID`). Karena payload contoh memakai `participant` = nomor di
whitelist, balasan harus berupa konfirmasi, bukan penolakan.

| # | Variasi | Harapan | Hasil |
|---|---|---|---|
| W1 | payload apa adanya | `status: flagged` + konfirmasi di grup | |
| W2 | `participant` diganti nomor lain | `status: unauthorized` + `Anda @... tidak boleh...` | |
| W3 | `fromMe: true` | `ignored: from_me`, tidak ada pesan | |
| W4 | `event: message.ack` | `ignored: event_ignored` | |
| W5 | kirim 2x payload identik | panggilan kedua `ignored: duplicate` | |
| W6 | hapus header HMAC (bila HMAC aktif) | HTTP 403 `invalid_signature` | |
| W7 | `body` diubah jadi teks bebas (`terima kasih pak`) | `ignored: not_a_command` — **tidak ada pesan sama sekali** | |
| W8 | `body` = `/libur` + format salah | `status: invalid_format` + pesan bantuan | |
| W9 | `participant` = `xxx@lid` yang ada di whitelist | `status: flagged` | |
| W10 | `...waha-reply-payload-multi.json` (Nomor 1 & 2) | `status: flagged` + **satu** pesan berisi 2 nomor (atau `all_failed` bila nomor 2 tidak ada di DB uji) | |

> **Uji W1–W6 menulis data sungguhan** ke tabel `attendances` (kecuali W2–W6).
> Lakukan di **grup & database staging**, atau siapkan unit uji khusus.

### 4b. Uji di lokal (tanpa tunnel, tanpa WAHA mengirim ke Anda)

Cocok untuk menguji webhook sebelum WAHA diarahkan ke mesin lokal.

1. `.env` lokal (test mode mengalihkan semua kiriman WA ke nomor Anda + prefix `[TEST]`):

```ini
NOTIF_PROVIDERS=whatsapp
NOTIF_REPLY_ENABLED=true
NOTIF_TEST_MODE=true
NOTIF_TEST_CHAT_ID=628xxxxxxxxxx@c.us
WA_NOTIF_FLAG_HOLIDAY_USERS=6289654157132
WAHA_WEBHOOK_SECRET=local-test
WAHA_WEBHOOK_HMAC_KEY=local-test-hmac
```

2. `php artisan config:clear` lalu `php artisan serve --port=8000`.
3. Kirim payload contoh dengan skrip pembantu:

```powershell
.\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-teks-bebas.json
.\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-nomor-12.json
.\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-unauthorized.json
.\docs\evidence\test-webhook.ps1 -Payload docs/evidence/fixtures/waha-reply-payload-multi.json
# tambahkan -Fresh agar payload.id diacak (bisa diulang tanpa kena idempotensi 10 menit)
```

Hasil yang sudah diverifikasi 17 Nov 2026 (dengan `WA_NOTIF_FLAG_HOLIDAY_USERS` terisi):

| Payload | Respons | Menulis DB? |
|---|---|---|
| `...teks-bebas.json` | `{"ok":true,"ignored":"not_a_command"}` — tanpa pesan balasan | tidak |
| format lengkap tanpa `/libur` | `{"ok":true,"ignored":"not_a_command"}` — tanpa pesan balasan | tidak |
| `...nomor-12.json` | `{"ok":true,"status":"resolve_failed","detail":"number_not_found"}` | tidak |
| `...unauthorized.json` | `{"ok":true,"status":"unauthorized"}` | tidak |
| `waha-reply-payload.json` | `{"ok":true,"status":"flagged","detail":"create"}` | **ya** (1 baris `attendances`) |
| dikirim 2x tanpa `-Fresh` | `{"ok":true,"ignored":"duplicate"}` | tidak |

> Bila `WA_NOTIF_FLAG_HOLIDAY_USERS` kosong, semua perintah yang formatnya valid
> akan dijawab `unauthorized` — isi dulu variabel itu sebelum menguji jalur sukses.

Untuk uji arah sebaliknya (WAHA mengirim ke aplikasi lokal), gunakan tunnel:

```bash
cloudflared tunnel --url http://localhost:8000
# daftarkan https://xxxx.trycloudflare.com/api/whatsapp/webhook/SECRET ke WAHA
```

Bila WAHA berjalan sebagai Docker di mesin yang sama, `http://host.docker.internal:8000`
biasanya cukup tanpa tunnel.


---

## 5. UAT di grup (end-to-end)

Prasyarat: `NOTIF_TEST_MODE=false`, `WAHA_CHAT_ID` = grup uji.

| # | Aksi di grup | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| U1 | Tunggu/trigger `attendance:notify --type=reminder --force`, lalu reply `/libur` + `Nomor 1` + `Diliburkan Sekolah` + `Alasan : Sedang ujian` | Balasan `sudah saya update, ... Diliburkan Sekolah Karena Sedang ujian` | |
| U2 | Cek aplikasi: absensi unit tsb tanggal tsb = **Diliburkan Sekolah** + alasan | Sesuai | |
| U3 | Jalankan `attendance:notify --type=reminder --dry-run` | Unit pada U1 **tidak muncul lagi** | |
| U4 | Reply baris yang statusnya sudah `training` | `... sudah ada laporan absensi; tidak bisa ditandai libur.` | |
| U5 | Reply `NOMOR 1` + `DILIBURKAN SEKOLAH` (huruf besar semua) | **Diterima** — konfirmasi normal | |
| U5b | Reply format lengkap **tanpa** `/libur` | Bot **diam** (awalan wajib) | |
| U6 | Reply `/libur` + `Nomor 3` + `DILIBURKAN PELATIH` | Status jadi `cancelled` | |
| U7 | Reply `/libur` + `Nomor 1` + `Libur Sekolah` + alasan | `Format belum sesuai.` + contoh (karena ada `/libur`) | |
| U7b | Reply teks bebas ke pesan pengingat (`terima kasih pak`) | Bot **diam total** | |
| U7c | Reply tanpa `/libur` dan format tidak lengkap (`Nomor 1` + status saja) | Bot **diam total** | |
| U8 | Non-whitelist reply format valid (dengan `/libur`) | `Anda … tidak boleh mengubah status absensi` | |
| U9 | Reply tanpa quote pesan bot | `Balas pesan *Pengingat Absensi Latihan* ...` | |
| U10 | Obrolan biasa di grup (`pagi semua`) | Bot **diam** | |
| U11 | Reply ulang nomor & alasan yang sama | `sudah saya update sebelumnya, ...` (tanpa menulis ulang) | |
| U12 | Reply nomor yang sama dengan alasan berbeda | Status/alasan diperbarui, konfirmasi normal | |
| U13 | Admin mengirim `/libur` saja (tanpa isi) | Bot mengirim contoh format | |
| U14 | Admin (terdaftar) mengirim dari nomor yang muncul sebagai `@lid` | Perintah **tetap diterima**; jika belum, tambahkan nilai `@lid` ke `WA_NOTIF_FLAG_HOLIDAY_USERS` | |
| U15 | Reply `/libur` + `Nomor 1` (Sekolah/ujian) + `Nomor 2` (Pelatih/hujan) | Satu balasan berisi dua baris hasil; kedua baris absensi berubah | |
| U16 | Reply multi dengan satu nomor tidak ada (mis. `Nomor 1` + `Nomor 99`) | Nomor 1 tetap tersimpan; balasan memuat bagian `Gagal:` dengan `- Nomor 99: ...` | |
| U17 | Reply multi dengan blok kedua tanpa `Alasan :` | **Tidak ada** yang disimpan; bot membalas `Blok ke-2: Alasan wajib diisi...` | |

---

## 6. Pemantauan setelah go-live (Fase 5)

```bash
# Aktivitas penandaan libur
Select-String -Path storage/logs/cron/cron.log -Pattern 'Libur ditandai via WhatsApp' | Select-Object -Last 20

# Penolakan karena tidak berwenang
Select-String -Path storage/logs/cron/cron.log -Pattern 'pengirim tidak berwenang' | Select-Object -Last 20

# Kegagalan kirim balasan
Select-String -Path storage/logs/cron/cron.log -Pattern 'Balasan libur gagal dikirim' | Select-Object -Last 20
```

Pantau 3 hari pertama. Yang perlu dicek:

- [ ] Balasan bot masuk ke grup yang benar (bukan chat pribadi pada test mode).
- [ ] Tidak ada balasan berulang untuk satu perintah (retry WAHA).
- [ ] Tidak ada unit yang salah tertandai libur (konfirmasi selalu menyebut unit + tanggal).
- [ ] Pengingat berikutnya tidak lagi memuat unit yang sudah diliburkan.

---

## 7. Rollback

| Kebutuhan | Tindakan |
|---|---|
| Matikan fitur tanpa deploy | `NOTIF_REPLY_ENABLED=false` + `php artisan config:clear` |
| Matikan webhook saja | hapus webhook pada sesi WAHA |
| Batasi hanya 1 orang | isi `WA_NOTIF_FLAG_HOLIDAY_USERS` dengan satu nomor |
| Koreksi data yang salah | ubah status absensi lewat menu absensi di aplikasi |
| Hapus route | hapus `Route::post('/whatsapp/webhook/...')` di `routes/api.php` |

Tidak ada migrasi database pada fitur ini, sehingga tidak ada rollback skema.

---

## 8. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Bot tidak pernah membalas | webhook belum terdaftar / URL salah | cek Event Monitor WAHA, pastikan 200 |
| HTTP 403 terus | `WAHA_WEBHOOK_SECRET` beda dengan URL | samakan nilainya |
| `invalid_signature` | `WAHA_WEBHOOK_HMAC_KEY` beda dengan WAHA | samakan, atau kosongkan keduanya |
| Semua balasan ditolak "tidak boleh" | whitelist kosong / nomor beda / pengirim `@lid` | isi `WA_NOTIF_FLAG_HOLIDAY_USERS`; lihat nilai `candidates` pada log (bab 6) lalu tambahkan `@lid` atau nomor teleponnya |
| Bot diam padahal admin mengirim perintah | pesan tidak dibuka dengan awalan `/libur` | kirim ulang dengan `/libur` di baris pertama |
| `unit_not_found` | unit sudah dihapus/dinonaktifkan atau nama berubah | tandai libur lewat menu absensi |
| `Balas pesan ...` padahal sudah reply | `replyTo.body` kosong pada versi WAHA Anda | upgrade WAHA ≥ 2026.4.x (NOWEB mengirim `replyTo`) |
| Balasan masuk ke chat pribadi | `NOTIF_TEST_MODE=true` | set `false` |

Log penolakan sekarang memuat `candidates` (semua identitas pengirim yang
terdeteksi) dan `push_name`, sehingga nilai yang harus dimasukkan ke whitelist
bisa langsung disalin:

```
Select-String -Path storage/logs/cron/cron.log -Pattern 'pengirim tidak berwenang' -Context 0,3
```

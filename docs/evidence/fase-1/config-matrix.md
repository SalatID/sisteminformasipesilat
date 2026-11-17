# Evidence Fase 1 — Konfigurasi Provider

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `config/notifications.php` | ✅ Dibuat |
| `.env` (variabel `NOTIF_*`, `WAHA_*`, `TELEGRAM_TIMEOUT`) | ✅ Diperbarui |
| `.env.example` (baru) | ✅ Dibuat |
| Kredensial tidak dicetak ke log (T-02) | ✅ Diperbaiki |

## 2. Matriks Konfigurasi — AC-01

Perintah: `php artisan config:clear` lalu memuat config dengan `NOTIF_PROVIDERS` berbeda.

```
NOTIF_PROVIDERS="telegram"                   => [telegram]
NOTIF_PROVIDERS="whatsapp"                   => [whatsapp]
NOTIF_PROVIDERS="telegram,whatsapp"          => [telegram, whatsapp]
NOTIF_PROVIDERS=""                           => []
NOTIF_PROVIDERS=" telegram , whatsapp "      => [telegram, whatsapp]
```

| Kriteria AC-01 | Hasil |
|---|---|
| Provider berubah hanya lewat env (tanpa ubah kode) | ✅ LULUS |
| Provider kosong = semua nonaktif | ✅ LULUS |
| Toleran spasi & huruf besar/kecil | ✅ LULUS |

## 3. Struktur Config

`php artisan config:show notifications` menampilkan:

```
providers ⇁ 0 ........................... telegram
providers ⇁ 1 ........................... whatsapp
interval_hours ................................. 3
test_mode ................................... true
test_chat_id .................. 6289654157132@c.us
send_end_marker ............................ false
mention ⇁ enabled ........................... true
mention ⇁ max .................................. 10
send_delay_seconds .............................. 3
telegram ⇁ bot_token .................. (tersedia)
telegram ⇁ chat_id ..................... -5182317563
telegram ⇁ timeout ............................. 20
whatsapp ⇁ url ......... https://wahanoweb-666b42.app3.flaz.my.id
whatsapp ⇁ api_key .................... (tersedia)
whatsapp ⇁ session ....................... sessionsmi
whatsapp ⇁ chat_id ......... 120363404437691633@g.us
whatsapp ⇁ timeout ............................. 30
```

## 4. Uji Kebocoran Kredensial — AC-12

Skrip memindai `storage/logs/**/*.log` untuk token Telegram, chat ID, dan API key WAHA.

- **Sebelum perbaikan:** ditemukan 2 baris log berisi bot token & chat ID dari kode lama
  (`Log::info("Attempting to send Telegram token: ...")` pada command lama).
- **Tindakan:** (a) baris historis diredaksi menjadi `[REDACTED_TOKEN]` / `[REDACTED_CHAT_ID]`;
  (b) kode lama diubah — log kini hanya mencatat "Attempting to send Telegram message." tanpa kredensial.
- **Sesudah perbaikan:** `CLEAN: tidak ada kredensial di storage/logs` ✅

## 5. Exit Criteria

- [x] AC-01: provider berubah hanya via env.
- [x] AC-12: tidak ada token di `storage/logs/*`.
- [x] `config:show notifications` menampilkan struktur benar (termasuk `mention.enabled` & `mention.max`).

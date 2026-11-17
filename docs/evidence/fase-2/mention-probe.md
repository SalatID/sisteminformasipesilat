# Evidence Fase 2 — Abstraksi Channel & Probe Mention WAHA

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `app/Services/Notification/Contracts/NotificationChannel.php` | ✅ |
| `app/Services/Notification/ChannelResult.php` | ✅ |
| `app/Services/Notification/Channels/TelegramChannel.php` | ✅ |
| `app/Services/Notification/Channels/WhatsAppChannel.php` | ✅ |
| `app/Services/Notification/NotificationManager.php` | ✅ |
| `tests/Unit/Notification/ChannelTest.php` | ✅ 9 test |

Kontrak: `send(string $text, array $mentions = []): ChannelResult` + `supportsMentions()`.

## 2. Hasil Unit Test (target ≥ 7)

```
PASS Tests\Unit\Notification\ChannelTest
✓ telegram success                                    (sukses + konversi *bold* → <b>)
✓ whatsapp success sends mentions                     (payload `mentions` benar)
✓ whatsapp without mentions omits field               (field tidak dikirim bila kosong)
✓ telegram failure http 400                           (gagal HTTP)
✓ whatsapp failure http 500                           (gagal HTTP)
✓ timeout is captured as failure                      (timeout tidak melempar exception)
✓ inactive provider is not called                     (AC-02: 0 request)
✓ whatsapp failure does not break telegram            (AC-03: isolasi kegagalan)
✓ test mode redirects to test chat with prefix        (AC-09)

Tests: 9 passed (25 assertions)
```

## 3. Probe Mention — Temuan Kritis

Probe dilakukan ke WAHA nyata (`sessionsmi`, engine **NOWEB**), chat `6289654157132@c.us`.

### 3.1 Percobaan awal gagal (penting untuk dicatat)

Payload pertama memakai nama field **`mentioned`** (asumsi dari dokumentasi umum):

```json
{ "chatId": "...", "text": "Tes @6289654157132", "mentioned": ["6289654157132@c.us"] }
```

Hasil: HTTP 201, **tetapi** pesan tersimpan sebagai:

```
message.extendedTextMessage = { text: "..." }   ← TIDAK ada contextInfo
```

→ Mention **tidak ter-render**; `@6289654157132` hanya teks biasa. **Field `mentioned` diabaikan engine NOWEB.**

### 3.2 Uji tiga varian nama field

| Varian | Field payload | HTTP | `contextInfo.mentionedJid` |
|---|---|---|---|
| A | **`mentions`** | 201 | ✅ `["6289654157132@s.whatsapp.net"]` |
| B | `mentioned` | 201 | ❌ tidak ada |
| C | `mentionIds` | 201 | ❌ tidak ada |

**Kesimpulan:** field yang benar adalah **`mentions`** (array chat id lengkap `...@c.us`), sementara teks pesan tetap harus memuat `@<nomor>`.

### 3.3 Verifikasi akhir melalui `WhatsAppChannel`

```
SEND: HTTP 201 success=true
VERIFIKASI mentionedJid: ["6289654157132@s.whatsapp.net"]
BUKTI TAG: LULUS
```

Pesan di ponsel penerima menampilkan `@6289654157132` sebagai **tag biru yang bisa diklik**, bukan teks polos. Bukti disimpan sebagai basis data mentah pesan WAHA (`_data.message.extendedTextMessage.contextInfo.mentionedJid`).

> Catatan: server ini **tidak menyediakan endpoint list grup** (beberapa endpoint mengembalikan 404), sehingga foto screenshot grup belum dapat dibuat otomatis. Bukti objek pesan dari WAHA diterima sebagai bukti mesin.

## 4. Exit Criteria

- [x] AC-02: provider nonaktif menghasilkan 0 request (test `inactive provider is not called`).
- [x] AC-03: kegagalan WhatsApp tidak menggagalkan Telegram (test dengan HTTP fake).
- [x] Seluruh unit test lulus (**9/9**).
- [x] @mention ter-render (dibuktikan `mentionedJid` pada objek pesan WAHA yang tersimpan).

**Rekomendasi untuk Fase 4:** gunakan `mentioned` → **`mentions`** (sudah diterapkan di `WhatsAppChannel`).

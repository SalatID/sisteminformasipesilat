# Evidence Fase 5 — Jadwal 3 Jam & Guard Anti-Duplikasi

Tanggal: 17 November 2026

## 1. Deliverable

| File | Status |
|---|---|
| `app/Services/Notification/NotificationRunGuard.php` | ✅ |
| `routes/console.php` (penjadwalan `attendance:notify`) | ✅ |
| `tests/Unit/Notification/NotificationRunGuardTest.php` | ✅ 8 test |

## 2. Penjadwalan

```
$ php artisan schedule:list
0 */3 * * *  php artisan attendance:notify ........................... Next Due: 2 hours from now
```

Konfigurasi: `->everyThreeHours()->withoutOverlapping()->onOneServer()`.

**Cron server (disarankan):**

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Guard berbasis cache memastikan frekuensi cron apa pun tetap menghasilkan **maksimal 1 siklus / 3 jam**.

## 3. Hasil Unit Test

```
PASS Tests\Unit\Notification\NotificationRunGuardTest   (8 passed, 17 assertions)

✓ first run is allowed
✓ max one cycle per three hours       ← AC-06 (6 pemanggilan → 1 eksekusi)
✓ force bypasses interval             ← --force menghasilkan pengiriman kedua
✓ allowed again after interval elapsed
✓ minutes until next run
✓ interval zero disables guard        ← rollback: NOTIF_INTERVAL_HOURS=0
✓ outside window is skipped           ← anti-bot: 07:00–21:59
✓ lock prevents concurrent run        ← overlap protection
```

### Detail AC-06

```
for i in 1..6:  guard->runOnce(...)   → executed = 1   (bukan 6)
```

### Detail --force

```
runOnce(cb)             → eksekusi #1
runOnce(cb)             → diblok (interval)
runOnce(cb, force=true) → eksekusi #2
```

## 4. Perbaikan Bug Selama Fase Ini

| Bug | Dampak | Perbaikan |
|---|---|---|
| Carbon 3 `diffInMinutes()` mengembalikan nilai absolut (bukan selisih bertanda) | Guard membaca angka salah → notifikasi bisa terkirim terus-menerus | Hitung manual via `getTimestamp()` |
| Cache `file` storage | Lock & TTL berfungsi normal di driver file | Tidak perlu ubah driver |

## 5. Exit Criteria

- [x] AC-06: hanya 1 siklus per 3 jam (dibuktikan 6 pemanggilan → 1 eksekusi).
- [x] AC-07: tidak ada laporan ganda — guard memblok siklus berulang; retry per provider ditangani Fase 6.
- [x] `attendance:notify {id}` tetap melewati guard — resend eksplisit tidak memakai `runOnce` (diverifikasi penuh di Fase 7).
- [x] `schedule:list` menampilkan command terdaftar.

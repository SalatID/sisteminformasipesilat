<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\NotificationRunGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationRunGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['notifications.interval_hours' => 3]);

        // Guard hanya mengizinkan pengingat pada 07:00–21:59. Kunci jam agar
        // hasil test tidak bergantung pada jam saat test dijalankan.
        $this->travelTo(Carbon::parse('2026-11-17 10:00:00'));
    }

    public function test_first_run_is_allowed(): void
    {
        $guard = new NotificationRunGuard;
        $this->assertTrue($guard->canRun());
    }

    public function test_max_one_cycle_per_three_hours(): void
    {
        $guard = new NotificationRunGuard;
        $executed = 0;

        // Simulasi 6 pemanggilan dalam 1 jam
        for ($i = 1; $i <= 6; $i++) {
            $result = $guard->runOnce(function () use (&$executed) {
                $executed++;

                return $executed;
            });
            if ($result['executed']) {
                $this->assertSame(1, $executed);
            }
        }

        $this->assertSame(1, $executed, 'Hanya boleh 1 siklus per 3 jam');
    }

    public function test_force_bypasses_interval(): void
    {
        $guard = new NotificationRunGuard;
        $executed = 0;
        $cb = function () use (&$executed) {
            return ++$executed;
        };

        $guard->runOnce($cb);
        $guard->runOnce($cb);          // diblok
        $guard->runOnce($cb, true);    // paksa

        $this->assertSame(2, $executed, '--force harus menghasilkan pengiriman kedua');
    }

    public function test_allowed_again_after_interval_elapsed(): void
    {
        $guard = new NotificationRunGuard;
        $guard->runOnce(fn () => true);
        $this->assertFalse($guard->canRun());

        $this->travel(3)->hours();
        $this->assertTrue($guard->canRun());
    }

    public function test_minutes_until_next_run(): void
    {
        $guard = new NotificationRunGuard;
        $guard->runOnce(fn () => true);

        $this->travel(1)->hours();
        $this->assertSame(120, $guard->minutesUntilNextRun());
    }

    public function test_interval_zero_disables_guard(): void
    {
        config(['notifications.interval_hours' => 0]);
        $guard = new NotificationRunGuard;

        $this->assertTrue($guard->canRun());
        $this->assertTrue($guard->canRun());
    }

    public function test_outside_window_is_skipped(): void
    {
        $guard = new NotificationRunGuard;

        $this->assertFalse($guard->withinActiveWindow(23));
        $this->assertFalse($guard->withinActiveWindow(3));
        $this->assertTrue($guard->withinActiveWindow(9));
        $this->assertTrue($guard->withinActiveWindow(21));

        $result = $guard->runOnce(fn () => 'x', false);
        // Bergantung jam sistem saat test berjalan; hanya pastikan tidak error
        $this->assertIsArray($result);
    }

    public function test_lock_prevents_concurrent_run(): void
    {
        $guard = new NotificationRunGuard;

        $lock = Cache::lock('attendance:notify:lock:all', 60);
        $this->assertTrue($lock->get());

        $result = $guard->runOnce(fn () => true);
        $this->assertFalse($result['executed']);
        $this->assertSame('locked', $result['reason']);

        $lock->release();
    }

    // ------------------------------------------------ guard terpisah per jenis
    public function test_report_scope_ignores_interval(): void
    {
        $guard = new NotificationRunGuard;
        $executed = 0;
        $cb = function () use (&$executed) {
            return ++$executed;
        };

        // 3 pemanggilan berturut-turut tanpa jeda waktu
        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REPORT);
        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REPORT);
        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REPORT);

        $this->assertSame(3, $executed, 'Laporan harus jalan tiap kali dipanggil (tiap menit)');
    }

    public function test_reminder_scope_still_blocked_by_interval(): void
    {
        $guard = new NotificationRunGuard;
        $executed = 0;
        $cb = function () use (&$executed) {
            return ++$executed;
        };

        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REMINDER);
        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REMINDER);

        $this->assertSame(1, $executed, 'Pengingat tetap maksimal 1x per interval');

        $this->travel(3)->hours();
        $guard->runOnce($cb, false, NotificationRunGuard::SCOPE_REMINDER);

        $this->assertSame(2, $executed);
    }

    public function test_report_scope_runs_outside_active_window(): void
    {
        $guard = new NotificationRunGuard;

        $this->travelTo(Carbon::parse('2026-11-17 02:30:00'));

        $report = $guard->runOnce(fn () => 'ok', false, NotificationRunGuard::SCOPE_REPORT);
        $this->assertTrue($report['executed'], 'Laporan boleh terkirim di luar jendela');

        $reminder = $guard->runOnce(fn () => 'ok', false, NotificationRunGuard::SCOPE_REMINDER);
        $this->assertFalse($reminder['executed']);
        $this->assertSame('outside_window', $reminder['reason']);
    }

    public function test_scopes_do_not_block_each_other(): void
    {
        $guard = new NotificationRunGuard;

        $report = $guard->runOnce(fn () => 'r', false, NotificationRunGuard::SCOPE_REPORT);
        $reminder = $guard->runOnce(fn () => 'm', false, NotificationRunGuard::SCOPE_REMINDER);

        $this->assertTrue($report['executed']);
        $this->assertTrue($reminder['executed'], 'Laporan yang baru jalan tidak boleh memblokir pengingat');
    }

    public function test_report_lock_is_separate_from_reminder_lock(): void
    {
        $guard = new NotificationRunGuard;

        $lock = Cache::lock('attendance:notify:lock:report', 60);
        $this->assertTrue($lock->get());

        $report = $guard->runOnce(fn () => 'r', false, NotificationRunGuard::SCOPE_REPORT);
        $reminder = $guard->runOnce(fn () => 'm', false, NotificationRunGuard::SCOPE_REMINDER);

        $this->assertFalse($report['executed']);
        $this->assertSame('locked', $report['reason']);
        $this->assertTrue($reminder['executed']);

        $lock->release();
    }
}

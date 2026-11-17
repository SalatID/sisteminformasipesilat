<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\NotificationManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'notifications.providers' => ['telegram', 'whatsapp'],
            'notifications.test_mode' => true,
            'notifications.test_chat_id' => '6289654157132@c.us',
            'notifications.mention.enabled' => true,
            'notifications.send_delay_seconds' => 0,
            'notifications.interval_hours' => 3,
            'notifications.telegram.bot_token' => 't',
            'notifications.telegram.chat_id' => '-1',
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
        ]);
    }

    public function test_signature_keeps_backward_compatible_argument_and_new_options(): void
    {
        $definition = Artisan::all()['attendance:notify']->getDefinition();

        $this->assertTrue($definition->hasArgument('attendance_id'));
        $this->assertTrue($definition->hasOption('type'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function test_invalid_type_fails(): void
    {
        $code = Artisan::call('attendance:notify', ['--type' => 'ngawur']);

        $this->assertSame(1, $code);
    }

    public function test_dry_run_sends_zero_requests(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        Artisan::call('attendance:notify', ['--type' => 'all', '--dry-run' => true, '--force' => true]);

        Http::assertNothingSent();
    }

    public function test_unknown_attendance_id_returns_failure(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $code = Artisan::call('attendance:notify', [
            'attendance_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $this->assertSame(1, $code);
        Http::assertNothingSent();
    }

    public function test_no_active_provider_sends_nothing(): void
    {
        config(['notifications.providers' => []]);
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $code = Artisan::call('attendance:notify', ['--force' => true]);

        $this->assertSame(0, $code);
        Http::assertNothingSent();
    }

    public function test_report_runs_every_minute_and_reminder_every_three_hours(): void
    {
        config(['notifications.interval_hours' => 3]);

        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->getSummaryForDisplay() ?? '', 'attendance:notify'));

        $this->assertCount(2, $events, 'Laporan & pengingat harus dijadwalkan terpisah');

        $report = $events->first(fn ($e) => str_contains($e->getSummaryForDisplay(), '--type=report'));
        $reminder = $events->first(fn ($e) => str_contains($e->getSummaryForDisplay(), '--type=reminder'));

        $this->assertNotNull($report, 'Jadwal laporan tidak ditemukan');
        $this->assertNotNull($reminder, 'Jadwal pengingat tidak ditemukan');

        $this->assertSame('* * * * *', $report->expression, 'Laporan harus jalan tiap menit');
        $this->assertSame('0 */3 * * *', $reminder->expression, 'Pengingat harus jalan tiap 3 jam');
    }

    public function test_reminder_schedule_follows_interval_config(): void
    {
        config(['notifications.interval_hours' => 4]);

        // Kernel di-boot ulang agar schedule() membaca config terbaru.
        $kernel = new \App\Console\Kernel(app(), app('events'));
        app()->instance(\Illuminate\Contracts\Console\Kernel::class, $kernel);

        $schedule = new \Illuminate\Console\Scheduling\Schedule();
        $method = new \ReflectionMethod($kernel, 'schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        $reminder = collect($schedule->events())
            ->first(fn ($e) => str_contains($e->getSummaryForDisplay() ?? '', '--type=reminder'));

        $this->assertNotNull($reminder);
        $this->assertSame('0 */4 * * *', $reminder->expression);
    }

    /**
     * Shared hosting menonaktifkan proc_open, sehingga $schedule->command()
     * (subprocess) gagal di schedule:run. Jadwal harus memakai event
     * in-process dan tanpa perintah shell.
     */
    public function test_schedule_never_uses_subprocess(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->getSummaryForDisplay() ?? '', 'attendance:notify'));

        $this->assertCount(2, $events);

        foreach ($events as $event) {
            $this->assertInstanceOf(
                \App\Console\Support\InProcessCommandEvent::class,
                $event,
                'Jadwal notifikasi wajib dijalankan in-process (proc_open dinonaktifkan di hosting)'
            );

            $this->assertEmpty($event->command ?? null, 'Event in-process tidak boleh membangun perintah shell');
            $this->assertTrue($event->withoutOverlapping, 'Lock anti-overlap harus tetap aktif');
        }

        // Menetapkan nama (description) wajib agar mutex dilepas setelah selesai.
        $this->assertSame('attendance:notify --type=report', $events->first()->description);
        $this->assertSame('attendance:notify --type=reminder', $events->last()->description);
    }
}

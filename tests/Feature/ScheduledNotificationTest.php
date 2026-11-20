<?php

namespace Tests\Feature;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use App\Services\Notification\Schedule\HolidayAnnouncer;
use App\Services\Notification\Schedule\SchedulePlanner;
use App\Services\Notification\Schedule\ScheduleRunner;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesScheduledNotificationDatabase;
use Tests\TestCase;

class ScheduledNotificationTest extends TestCase
{
    use UsesScheduledNotificationDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareScheduledNotificationDatabase();

        config([
            'notifications.schedules.enabled' => true,
            'notifications.schedules.timezone' => 'Asia/Jakarta',
            'notifications.schedules.horizon_days' => 60,
            'notifications.schedules.grace_minutes' => 30,
            'notifications.schedules.max_attempts' => 3,
            'notifications.schedules.retry_delay_minutes' => 5,
            'notifications.schedules.runner_limit' => 100,
            'notifications.schedules.default_holiday_message' => "*Latihan Libur*\n\n{nama_jadwal}\n{hari}, {tanggal}\n\nAlasan: {alasan}",
            'notifications.test_mode' => false,
            'notifications.test_chat_id' => '6289654157132@c.us',
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363000000000000@g.us',
            'notifications.whatsapp.timeout' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function schedule(array $overrides = []): ScheduledNotification
    {
        return ScheduledNotification::create(array_merge([
            'code' => 'TCGOR',
            'name' => 'TC GOR Gondrong',
            'chat_id' => '120363404437691633@g.us',
            'chat_label' => 'Grup GOR Gondrong',
            'message' => 'Latihan TC malam ini pukul 19.30–21.30 di GOR Gondrong.',
            'holiday_message' => null,
            'frequency' => 'weekly',
            'time' => '12:00',
            'days_of_week' => [2, 4],
            'monthly_mode' => null,
            'dates_of_month' => null,
            'weeks_of_month' => null,
            'start_date' => '2026-11-20',
            'end_date' => null,
            'is_active' => true,
        ], $overrides));
    }

    private function planner(): SchedulePlanner
    {
        return app(SchedulePlanner::class);
    }

    private function runner(): ScheduleRunner
    {
        return app(ScheduleRunner::class);
    }

    public function test_planner_creates_weekly_runs_for_the_horizon(): void
    {
        $schedule = $this->schedule();

        $created = $this->planner()->plan($schedule);

        // 60 hari sejak 20 Nov 2026: 9 Selasa + 8 Kamis (20 Nov–19 Jan).
        $this->assertSame(17, $created);
        $this->assertSame(17, $schedule->runs()->count());
        $this->assertDatabaseHas('scheduled_notification_runs', [
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_PENDING,
        ]);
    }

    public function test_planner_is_idempotent(): void
    {
        $schedule = $this->schedule();

        $this->planner()->plan($schedule);
        $second = $this->planner()->plan($schedule);

        $this->assertSame(0, $second);
        $this->assertSame(17, $schedule->runs()->count());
    }

    public function test_refresh_cancels_stale_run_when_time_changes(): void
    {
        $schedule = $this->schedule();
        $this->planner()->plan($schedule);

        $schedule->update(['time' => '18:00:00']);
        $this->planner()->refresh($schedule->fresh());

        $stale = $schedule->runs()
            ->whereDate('occurrence_at', '2026-11-24')
            ->orderBy('occurrence_at')
            ->get();

        $this->assertCount(2, $stale);
        $this->assertSame(['cancelled', 'pending'], $stale->pluck('status')->all());
        $this->assertSame('system', $stale->first()->cancel_source);
        $this->assertSame('18:00:00', $stale->last()->occurrence_at->format('H:i:s'));
        $this->assertSame(0, $schedule->runs()->where('status', 'pending')->where('occurrence_at', '<', '2026-11-24 18:00:00')->whereDate('occurrence_at', '2026-11-24')->count());
    }

    public function test_refresh_removes_pending_runs_that_no_longer_match(): void
    {
        $schedule = $this->schedule();
        $this->planner()->plan($schedule);

        $schedule->update(['days_of_week' => [4]]);
        $result = $this->planner()->refresh($schedule->fresh());

        $this->assertGreaterThan(0, $result['deleted'] + $result['created']);
        $this->assertDatabaseMissing('scheduled_notification_runs', [
            'public_code' => 'TCGOR-20261124',
        ]);
        $this->assertDatabaseHas('scheduled_notification_runs', [
            'public_code' => 'TCGOR-20261126',
        ]);
    }

    public function test_deactivating_schedule_cancels_pending_runs(): void
    {
        $schedule = $this->schedule();
        $this->planner()->plan($schedule);

        $cancelled = $this->planner()->cancelPending($schedule->fresh());

        $this->assertSame(17, $cancelled);
        $this->assertSame(0, $schedule->runs()->where('status', 'pending')->count());
        $this->assertSame(17, $schedule->runs()->where('status', 'cancelled')->count());
    }

    public function test_runner_sends_due_run_exactly_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'true_group_ABC123']], 201)]);

        $report = $this->runner()->run();

        $this->assertSame(1, $report['due']);
        $this->assertSame(1, $report['sent']);
        $this->assertSame(0, $report['failed']);
        Http::assertSentCount(1);

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->status);
        $this->assertSame('true_group_ABC123', $run->provider_message_id);
        $this->assertNotNull($run->sent_at);
        $this->assertSame($schedule->message, $run->message_snapshot);

        // Siklus kedua tidak mengirim ulang.
        $second = $this->runner()->run();
        $this->assertSame(0, $second['due']);
        Http::assertSentCount(1);
    }

    public function test_runner_stores_provider_message_id_from_waha_noweb_key(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        // WAHA NOWEB mengembalikan id pesan di `key.id`, bukan `id._serialized`.
        Http::fake(['waha.test/*' => Http::response([
            'key' => ['id' => '3EB046DBC2A16589D9FA30', 'fromMe' => true, 'remoteJid' => '628111@g.us'],
            'status' => 'PENDING',
        ], 201)]);

        $this->runner()->run();

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();
        $this->assertSame('3EB046DBC2A16589D9FA30', $run->provider_message_id);
    }

    public function test_runner_marks_run_outside_grace_as_missed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 10:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_PENDING,
        ]);

        Http::fake();

        $report = $this->runner()->run();

        $this->assertSame(1, $report['missed']);
        $this->assertSame(0, $report['sent']);
        Http::assertNothingSent();

        $this->assertDatabaseHas('scheduled_notification_runs', [
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_MISSED,
        ]);
    }

    public function test_runner_retries_then_marks_failed_after_max_attempts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake(['waha.test/*' => Http::response(['message' => 'server down'], 500)]);

        $first = $this->runner()->run();
        $this->assertSame(1, $first['retried']);

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();
        $this->assertSame(1, $run->attempts);
        $this->assertSame(ScheduledNotificationRun::STATUS_PENDING, $run->status);

        Carbon::setTestNow(Carbon::parse('2026-11-24 12:06:00', 'Asia/Jakarta'));
        $second = $this->runner()->run();
        $this->assertSame(1, $second['retried']);

        Carbon::setTestNow(Carbon::parse('2026-11-24 12:12:00', 'Asia/Jakarta'));
        $third = $this->runner()->run();
        $this->assertSame(1, $third['failed']);

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_FAILED, $run->status);
        $this->assertSame(3, $run->attempts);
        $this->assertNotNull($run->last_error);
    }

    public function test_runner_uses_test_chat_when_test_mode_is_on(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        config([
            'notifications.test_mode' => true,
            'notifications.test_chat_id' => '628111222333@c.us',
        ]);

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'x']], 201)]);

        $this->runner()->run();

        Http::assertSent(function ($request) {
            return $request['chatId'] === '628111222333@c.us'
                && str_starts_with((string) $request['text'], '[TEST]');
        });
    }

    public function test_dry_run_does_not_call_waha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake();

        $report = $this->runner()->run(['dry_run' => true]);

        $this->assertSame(1, $report['due']);
        $this->assertSame(1, count($report['items']));
        Http::assertNothingSent();

        $this->assertSame(
            ScheduledNotificationRun::STATUS_PENDING,
            $schedule->runs()->where('public_code', 'TCGOR-20261124')->first()->status,
        );
    }

    public function test_runner_cancels_run_for_inactive_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 12:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_PENDING,
        ]);

        $schedule->update(['is_active' => false]);
        Http::fake();

        $report = $this->runner()->run();

        $this->assertSame(1, $report['cancelled']);
        Http::assertNothingSent();
        $this->assertDatabaseHas('scheduled_notification_runs', [
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_CANCELLED,
        ]);
    }

    // ------------------------------------------------------------------
    // Multi-chat: satu pesan dikirim ke beberapa chat
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{chat_id: string, label: string}>
     */
    private function twoTargets(): array
    {
        return [
            ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup GOR'],
            ['chat_id' => '628111222333@c.us', 'label' => 'Pelatih'],
        ];
    }

    public function test_schedule_can_have_multiple_chat_targets(): void
    {
        $schedule = $this->schedule();
        $schedule->syncTargets($this->twoTargets());
        $schedule->save();

        $this->assertSame(
            ['120363404437691633@g.us', '628111222333@c.us'],
            $schedule->fresh()->chatIds(),
        );

        // Target utama tetap tersinkron dengan kolom lama.
        $this->assertSame('120363404437691633@g.us', $schedule->fresh()->chat_id);
        $this->assertSame('Grup GOR', $schedule->fresh()->chat_label);
    }

    public function test_runner_sends_same_message_to_every_chat_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $schedule->syncTargets($this->twoTargets());
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake(fn ($request) => Http::response([
            'key' => ['id' => 'msg-'.substr(md5($request['chatId']), 0, 10)],
            'status' => 'PENDING',
        ], 201));

        $report = $this->runner()->run();

        $this->assertSame(1, $report['sent'], 'satu run terkirim');

        Http::assertSentCount(2);

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->status);

        $this->assertNull($run->sent_targets, 'status sent tidak menyimpan daftar parsial');
        $this->assertNull($run->error_targets);

        $sentChats = collect(Http::recorded())->map(fn ($pair) => $pair[0]['chatId'])->sort()->values()->all();
        $this->assertSame(['120363404437691633@g.us', '628111222333@c.us'], $sentChats);

        // Teks identik untuk semua chat.
        $texts = collect(Http::recorded())->map(fn ($pair) => $pair[0]['text'])->unique();
        $this->assertCount(1, $texts);
    }

    public function test_partial_failure_retries_only_failed_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $schedule->syncTargets($this->twoTargets());
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        $calls = [];
        $fail = true;

        Http::fake(function ($request) use (&$calls, &$fail) {
            $calls[] = $request['chatId'];

            // Target pribadi gagal pada percobaan pertama, lalu berhasil.
            if ($fail && $request['chatId'] === '628111222333@c.us') {
                return Http::response(['message' => 'nomor tidak valid'], 500);
            }

            return Http::response(['key' => ['id' => 'ok-'.count($calls)]], 201);
        });

        $this->runner()->run();

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();

        $this->assertSame(ScheduledNotificationRun::STATUS_PENDING, $run->status);
        $this->assertSame(1, $run->attempts);
        $this->assertSame(['120363404437691633@g.us'], $run->sent_targets);
        $this->assertSame(['628111222333@c.us'], $run->error_targets);
        $this->assertStringContainsString('Sebagian gagal', (string) $run->last_error);

        // Retry berikutnya hanya menghubungi target yang gagal.
        $calls = [];
        $fail = false;
        $run->forceFill(['next_attempt_at' => now()->subMinute()])->save();

        $report = $this->runner()->run();

        $this->assertSame(1, $report['sent']);
        $this->assertSame(['628111222333@c.us'], $calls, 'grup tidak dikirimi ulang');

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->status);
        $this->assertNull($run->error_targets);
    }

    public function test_all_targets_failing_marks_run_failed_after_max_attempts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00:00', 'Asia/Jakarta'));

        $schedule = $this->schedule(['days_of_week' => [2]]);
        $schedule->syncTargets($this->twoTargets());
        $this->planner()->plan($schedule, CarbonImmutable::now('Asia/Jakarta'));

        Http::fake(['waha.test/*' => Http::response(['message' => 'server down'], 500)]);

        for ($i = 0; $i < 3; $i++) {
            $this->runner()->run();
            ScheduledNotificationRun::query()->update(['next_attempt_at' => now()->subMinute()]);
        }

        $run = $schedule->runs()->where('public_code', 'TCGOR-20261124')->first();

        $this->assertSame(ScheduledNotificationRun::STATUS_FAILED, $run->status);
        $this->assertSame(3, $run->attempts);
        $this->assertNull($run->sent_targets);
    }

    public function test_holiday_announcement_is_sent_to_every_target(): void
    {
        $schedule = $this->schedule();
        $schedule->syncTargets($this->twoTargets());

        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 12:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_CANCELLED,
            'cancel_source' => 'webhook',
            'cancel_reason' => 'hujan deras',
            'cancelled_at' => now(),
        ]);

        Http::fake(fn ($request) => Http::response(['key' => ['id' => 'ann-'.$request['chatId']]], 201));

        $result = app(HolidayAnnouncer::class)->announce($run, 'hujan deras');

        $this->assertTrue($result->success);
        Http::assertSentCount(2);

        $run->refresh();
        $this->assertNotNull($run->announced_at);
        $this->assertNotNull($run->announcement_message_id);
    }

    public function test_test_send_reaches_every_target(): void
    {
        $schedule = $this->schedule();
        $schedule->syncTargets($this->twoTargets());

        Http::fake(fn ($request) => Http::response(['key' => ['id' => 'test-'.$request['chatId']]], 201));

        $result = app(HolidayAnnouncer::class)->sendTest($schedule, 'Pesan tes');

        $this->assertTrue($result->success);
        Http::assertSentCount(2);
    }
}

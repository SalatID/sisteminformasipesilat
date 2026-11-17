<?php

namespace Tests\Unit\Notification;

use App\Models\Attendance;
use App\Services\Notification\AttendanceReportDispatcher;
use App\Services\Notification\ChannelResult;
use App\Services\Notification\NotificationManager;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'notifications.providers' => ['telegram', 'whatsapp'],
            'notifications.test_mode' => false,
            'notifications.mention.enabled' => false,
            'notifications.telegram.bot_token' => 'test-token',
            'notifications.telegram.chat_id' => '-100123',
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363@g.us',
        ]);
    }

    private function attendance(array $sentChannels = []): Attendance
    {
        $unit = new \App\Models\Unit();
        $unit->setRawAttributes(['name' => 'SMK TI YPML'], true);

        $attendance = new Attendance();
        $attendance->setRawAttributes([
            'old_member_cnt' => 1,
            'new_member_cnt' => 0,
            'notif_sent_channels' => $sentChannels === [] ? null : json_encode($sentChannels),
            'is_notif_send' => 0,
        ], true);
        $attendance->setRelation('unit', $unit);
        $attendance->setRelation('reportMaker', null);
        $attendance->setRelation('attendanceDetails', collect());
        $attendance->exists = true;

        // Cegah save() menyentuh DB pada unit test murni (listener di-reset tiap model)
        Attendance::flushEventListeners();
        Attendance::saving(function () {
            return false;
        });

        return $attendance;
    }

    private function dispatcher(): AttendanceReportDispatcher
    {
        return app(AttendanceReportDispatcher::class);
    }

    public function test_failed_provider_is_not_marked_as_sent(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
            'waha.test/*' => Http::response(['message' => 'down'], 500),
        ]);

        $attendance = $this->attendance();
        $result = $this->dispatcher()->dispatch($attendance);

        $this->assertFalse($result['success']);
        $this->assertFalse($attendance->is_notif_send, 'T-08: gagal kirim tidak boleh menandai terkirim');
        $this->assertSame(['telegram'], $attendance->notif_sent_channels);
    }

    public function test_failed_provider_is_retried_alone_on_next_run(): void
    {
        // Kondisi awal: telegram sudah sukses, whatsapp belum (hasil run sebelumnya)
        $attendance = $this->attendance(['telegram']);

        $pending = $this->dispatcher()->pendingProviders($attendance);
        $this->assertSame(['whatsapp'], $pending);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
            'waha.test/*' => Http::response(['id' => 'x'], 200),
        ]);

        $second = $this->dispatcher()->dispatch($attendance);

        $this->assertTrue($second['success']);
        $this->assertTrue($attendance->is_notif_send, 'Sukses semua provider → terkirim');
        $this->assertSame(['telegram', 'whatsapp'], $attendance->notif_sent_channels);

        // Hanya 1 request (whatsapp), telegram tidak dipanggil ulang
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'api.telegram.org'));
    }

    public function test_already_sent_attendance_is_skipped(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $attendance = $this->attendance(['telegram', 'whatsapp']);
        $attendance->is_notif_send = true;

        $result = $this->dispatcher()->dispatch($attendance);
        $this->assertTrue($result['skipped']);
        Http::assertNothingSent();
    }

    public function test_single_provider_mode_marks_sent_immediately(): void
    {
        config(['notifications.providers' => ['whatsapp']]);
        Http::fake(['waha.test/*' => Http::response(['id' => 'x'], 200)]);

        $attendance = $this->attendance();
        $result = $this->dispatcher()->dispatch($attendance);

        $this->assertTrue($result['success']);
        $this->assertTrue($attendance->is_notif_send);
        $this->assertSame(['whatsapp'], $attendance->notif_sent_channels);
    }

    public function test_all_providers_failing_leaves_unsent(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 400),
            'waha.test/*' => Http::response(['message' => 'down'], 503),
        ]);

        $attendance = $this->attendance();
        $result = $this->dispatcher()->dispatch($attendance);

        $this->assertFalse($result['success']);
        $this->assertFalse($attendance->is_notif_send);
        $this->assertSame([], $attendance->notif_sent_channels);
    }
}

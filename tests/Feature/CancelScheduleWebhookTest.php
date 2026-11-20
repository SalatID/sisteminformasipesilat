<?php

namespace Tests\Feature;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesScheduledNotificationDatabase;
use Tests\TestCase;

class CancelScheduleWebhookTest extends TestCase
{
    use UsesScheduledNotificationDatabase;

    private const SECRET = 'rahasia-webhook';

    /**
     * Cache aplikasi memakai driver file: respons JSON yang identik antar-test
     * bisa tertukar. Setiap test memakai id/cache-key yang benar-benar unik.
     *
     * @var array<string, string>
     */
    private static array $seenIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareScheduledNotificationDatabase();

        config([
            'notifications.providers' => ['whatsapp'],
            'notifications.test_mode' => false,
            'notifications.reply.enabled' => true,
            'notifications.reply.allowed_users' => ['6289654157132'],
            'notifications.reply.reason_max' => 255,
            'notifications.cancel_schedule.enabled' => true,
            'notifications.cancel_schedule.reason_min' => 3,
            'notifications.cancel_schedule.reason_max' => 255,
            'notifications.cancel_schedule.schedule_only_preview' => 3,
            'notifications.schedules.timezone' => 'Asia/Jakarta',
            'notifications.schedules.default_holiday_message' => "*Latihan Libur*\n\n{nama_jadwal}\n{hari}, {tanggal}\n\nAlasan: {alasan}",
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363000000000000@g.us',
            'notifications.whatsapp.webhook_secret' => self::SECRET,
            'notifications.whatsapp.webhook_hmac_key' => '',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-24 15:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function schedule(): ScheduledNotification
    {
        // Kode unik per test: database uji dipakai ulang antar-test.
        return ScheduledNotification::create([
            'code' => 'TC'.strtoupper(substr(uniqid(), -6)),
            'name' => 'TC GOR Gondrong',
            'chat_id' => '120363404437691633@g.us',
            'chat_label' => 'Grup GOR Gondrong',
            'message' => 'Latihan TC malam ini.',
            'frequency' => 'weekly',
            'time' => '12:00',
            'days_of_week' => [2, 4],
            'start_date' => '2026-11-20',
            'is_active' => true,
        ]);
    }

    private function makeRun(
        ScheduledNotification $schedule,
        string $occurrence = '2026-11-24 12:00:00',
        string $status = ScheduledNotificationRun::STATUS_SENT,
        ?string $providerMessageId = 'true_120363404437691633@g.us_3EB0000000000000000001',
    ): ScheduledNotificationRun {
        // ID unik per test: database uji dipakai ulang antar-test.
        $providerMessageId = $providerMessageId === null
            ? null
            : preg_replace('/\d{4}$/', (string) random_int(1000, 9999), $providerMessageId);

        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => $occurrence,
            'public_code' => $schedule->code.'-'.Carbon::parse($occurrence)->format('Ymd'),
            'status' => $status,
            'sent_at' => $status === ScheduledNotificationRun::STATUS_SENT ? $occurrence : null,
            'provider_message_id' => $providerMessageId,
        ]);

        $this->providerMessageId = $providerMessageId;

        return $run;
    }

    private ?string $providerMessageId = null;

    private function payload(array $overrides = []): array
    {
        $message = array_merge([
            'id' => 'false_120363404437691633@g.us_'.uniqid('', true),
            'from' => '120363404437691633@g.us',
            'fromMe' => false,
            'participant' => '6289654157132@c.us',
            'body' => "/cancel-schedule\nAlasan : hujan deras",
            'replyTo' => [
                'id' => $this->providerMessageId
                    ?? 'true_120363404437691633@g.us_3EB0000000000000000001',
                'body' => 'Latihan TC malam ini.',
            ],
        ], $overrides);

        return [
            'event' => 'message',
            'session' => 'sessionsmi',
            'engine' => 'NOWEB',
            'payload' => $message,
        ];
    }

    private function postWebhook(array $payload)
    {
        return $this->postJson('/api/whatsapp/webhook/'.self::SECRET, $payload);
    }

    /**
     * @var array<int, array{0: mixed, 1: mixed}>
     */
    private array $capturedRequests = [];

    /**
     * Ganti Http::recorded(): fake HTTP dicatat global dan bisa terbawa dari
     * test lain. Rekam hanya request milik test ini.
     */
    private function fakeWaha(): void
    {
        $this->capturedRequests = [];

        Http::fake([
            'waha.test/*' => function ($request) {
                $this->capturedRequests[] = [(array) $request->data()];

                return Http::response(['id' => ['_serialized' => 'ann_1']], 201);
            },
        ]);
    }

    private function sentTexts(): array
    {
        return array_map(
            fn (array $pair) => (string) ($pair[0]['text'] ?? ''),
            $this->capturedRequests,
        );
    }

    public function test_reply_cancels_run_and_sends_holiday_announcement(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule);
        $this->fakeWaha();

        $this->postWebhook($this->payload())
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('detail', $schedule->code.'-20261124');

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->status);
        $this->assertSame('hujan deras', $run->cancel_reason);
        $this->assertSame('webhook', $run->cancel_source);
        $this->assertSame('6289654157132@c.us', $run->cancelled_by);
        $this->assertNotNull($run->announced_at);

        $texts = $this->sentTexts();
        $this->assertCount(2, $texts, 'pengumuman libur + konfirmasi');
        $this->assertStringContainsString('Latihan Libur', $texts[0]);
        $this->assertStringContainsString('Selasa, 24 November 2026', $texts[0]);
        $this->assertStringContainsString('Alasan: hujan deras', $texts[0]);
        $this->assertStringContainsString('Sudah saya batalkan', $texts[1]);
        $this->assertStringContainsString('Pengumuman libur sudah dikirim', $texts[1]);
    }

    public function test_code_cancels_run(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule, '2026-11-26 12:00:00');
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$schedule->code}-20261126\nAlasan : hujan deras",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'cancelled');

        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->refresh()->status);
    }

    public function test_uppercase_command_is_accepted(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule, '2026-11-26 12:00:00');
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/CANCEL-SCHEDULE {$schedule->code}-20261126\nALASAN : HUJAN DERAS",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'cancelled');

        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->refresh()->status);
    }

    public function test_schedule_only_code_lists_nearest_occurrences(): void
    {
        $schedule = $this->schedule();
        $this->makeRun($schedule, '2026-11-24 12:00:00');
        $this->makeRun($schedule, '2026-11-26 12:00:00', ScheduledNotificationRun::STATUS_PENDING, null);
        $this->makeRun($schedule, '2026-11-28 12:00:00', ScheduledNotificationRun::STATUS_PENDING, null);
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$schedule->code}\nAlasan : hujan deras",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'schedule_only');

        $texts = $this->sentTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString($schedule->code.'-20261126', $texts[0]);
        $this->assertStringContainsString($schedule->code.'-20261128', $texts[0]);
    }

    public function test_unknown_code_replies_with_help(): void
    {
        $this->schedule();
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule TIDAKADA\nAlasan : hujan deras",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'cancel_rejected');

        $this->assertStringContainsString('tidak ditemukan', $this->sentTexts()[0]);
    }

    public function test_non_whitelisted_sender_is_rejected(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule);
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'participant' => '6281111111111@c.us',
        ]))->assertOk()->assertJsonPath('status', 'unauthorized');

        $this->assertStringContainsString('tidak boleh', $this->sentTexts()[0]);
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->refresh()->status);
    }

    public function test_already_cancelled_run_is_idempotent(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule, '2026-11-24 12:00:00', ScheduledNotificationRun::STATUS_CANCELLED, null);
        // provider_message_id null -> pakai kode agar resolusi deterministik.
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$run->public_code}\nAlasan : hujan deras",
            'replyTo' => null,
        ]))
            ->assertOk()
            ->assertJsonPath('status', 'cancel_rejected');

        $this->assertStringContainsString('sudah dibatalkan', $this->sentTexts()[0]);
        $this->assertCount(1, $this->sentTexts());
        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->refresh()->status);
    }

    public function test_past_run_is_rejected(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule, '2026-11-23 12:00:00');
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$schedule->code}-20261123\nAlasan : hujan deras",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'cancel_rejected');

        $this->assertStringContainsString('sudah lewat', $this->sentTexts()[0]);
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->refresh()->status);
    }

    public function test_inactive_schedule_is_rejected(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule, '2026-11-26 12:00:00', ScheduledNotificationRun::STATUS_PENDING, null);
        $schedule->update(['is_active' => false]);
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$schedule->code}-20261126\nAlasan : hujan deras",
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'cancel_rejected');

        $this->assertStringContainsString('nonaktif', $this->sentTexts()[0]);
        $this->assertSame(ScheduledNotificationRun::STATUS_PENDING, $run->refresh()->status);
    }

    public function test_missing_reason_gets_help(): void
    {
        $schedule = $this->schedule();
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => '/cancel-schedule '.$schedule->code.'-20261126',
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'invalid_format');

        $this->assertStringContainsString('Alasan wajib diisi', $this->sentTexts()[0]);
    }

    public function test_reply_to_unknown_bot_message_asks_for_quote(): void
    {
        $schedule = $this->schedule();

        // Dua run milik jadwal lain: cukup untuk memastikan id yang dikutip
        // memang tidak dikenal, bukan pesan singkat yang kebetulan cocok.
        $otherSchedule = ScheduledNotification::create([
            'code' => 'OT'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Jadwal lain',
            'chat_id' => '120363404437691633@g.us',
            'message' => 'Pesan lain.',
            'frequency' => 'weekly',
            'time' => '12:00',
            'days_of_week' => [2, 4],
            'start_date' => '2026-11-20',
            'is_active' => true,
        ]);
        $this->makeRun($otherSchedule);
        $this->makeRun($schedule);

        $unknownId = 'true_999@g.us_UNKNOWN'.uniqid();
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'replyTo' => ['id' => $unknownId, 'body' => 'pesan lain'],
        ]))->assertOk()->assertJsonPath('status', 'cancel_rejected');

        $texts = $this->sentTexts();
        $this->assertCount(1, $texts);
        $this->assertStringContainsString('Balas (reply) pesan bot', $texts[0]);
        $this->assertStringNotContainsString('sudah dibatalkan', $texts[0]);
    }

    public function test_code_and_reply_pointing_to_different_runs_are_rejected(): void
    {
        $schedule = $this->schedule();
        // Payload() mengutip pesan dari run TERAKHIR yang dibuat, jadi pesan
        // yang dibalas harus dibuat belakangan agar pasti berbeda dengan kode.
        $other = $this->makeRun($schedule, '2026-11-26 12:00:00');
        $first = $this->makeRun($schedule, '2026-11-24 12:00:00');
        $this->fakeWaha();

        $this->postWebhook($this->payload([
            'body' => "/cancel-schedule {$other->public_code}\nAlasan : hujan deras",
        ]))->assertOk()->assertJsonPath('status', 'cancel_rejected');

        $this->assertStringContainsString('berbeda', $this->sentTexts()[0]);
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $other->refresh()->status);
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $first->refresh()->status);
    }

    public function test_duplicate_webhook_delivery_is_processed_once(): void
    {
        $schedule = $this->schedule();
        $this->makeRun($schedule, '2026-11-26 12:00:00');
        $this->fakeWaha();

        $payload = $this->payload([
            'body' => "/cancel-schedule {$schedule->code}-20261126\nAlasan : hujan deras",
            'replyTo' => null,
        ]);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk()->assertJsonPath('ignored', 'duplicate');

        $this->assertCount(2, $this->sentTexts());
    }

    public function test_same_day_run_can_be_cancelled_after_its_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 20:30:00', 'Asia/Jakarta'));

        $schedule = $this->schedule();
        $run = $this->makeRun($schedule);
        $this->fakeWaha();

        $this->postWebhook($this->payload())->assertOk()->assertJsonPath('status', 'cancelled');

        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->refresh()->status);
    }

    public function test_announcement_failure_still_cancels_the_run(): void
    {
        $schedule = $this->schedule();
        $run = $this->makeRun($schedule);

        Http::fake([
            'waha.test/*' => function ($request) {
                $this->capturedRequests[] = [(array) $request->data()];

                return Http::response(['message' => 'down'], 500);
            },
        ]);

        $this->postWebhook($this->payload())->assertOk()->assertJsonPath('status', 'cancelled');

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_CANCELLED, $run->status);
        $this->assertNull($run->announced_at);

        $texts = $this->sentTexts();
        $this->assertStringContainsString('gagal dikirim', end($texts));
    }

    public function test_holiday_reply_command_still_works_regression(): void
    {
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'abc']], 201)]);

        // Tanpa awalan /cancel-schedule dan tanpa data libur: perilaku lama
        // tetap "bantuan format", bukan "not_a_command".
        $this->postWebhook($this->payload([
            'body' => '/libur',
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('status', 'invalid_format');
    }

    public function test_free_text_is_still_ignored(): void
    {
        $this->schedule();
        Http::fake();

        $this->postWebhook($this->payload([
            'body' => 'selamat pagi semua',
            'replyTo' => null,
        ]))->assertOk()->assertJsonPath('ignored', 'not_a_command');

        Http::assertNothingSent();
    }
}

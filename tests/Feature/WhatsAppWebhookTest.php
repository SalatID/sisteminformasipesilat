<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Services\Notification\Reply\FlagOutcome;
use App\Services\Notification\Reply\HolidayFlagService;
use App\Services\Notification\Reply\HolidayTarget;
use App\Services\Notification\Reply\HolidayTargetResolver;
use App\Services\Notification\Reply\TargetResolution;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 3 — endpoint webhook WAHA.
 *
 * Resolver & penyimpanan di-mock agar test ini hanya menguji "pipa" webhook
 * (keamanan, filter event, parsing, pengiriman balasan) dan tidak menyentuh
 * database pengembangan `sip`.
 */
class WhatsAppWebhookTest extends TestCase
{
    private const SECRET = 'rahasia-webhook';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'notifications.providers' => ['whatsapp'],
            'notifications.test_mode' => false,
            'notifications.reply.enabled' => true,
            'notifications.reply.allowed_users' => ['6289654157132'],
            'notifications.reply.reason_max' => 255,
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363000000000000@g.us',
            'notifications.whatsapp.webhook_secret' => self::SECRET,
            'notifications.whatsapp.webhook_hmac_key' => '',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        $message = array_merge([
            'id' => 'false_120363000000000000@g.us_'.uniqid('', true),
            'from' => '120363000000000000@g.us',
            'fromMe' => false,
            'participant' => '6289654157132@c.us',
            'body' => "nomor 1\nDiliburkan Sekolah\nAlasan : Sedang ujian",
            'replyTo' => ['body' => $this->quotedReminder()],
        ], $overrides);

        return [
            'event' => 'message',
            'session' => 'sessionsmi',
            'engine' => 'NOWEB',
            'payload' => $message,
        ];
    }

    private function quotedReminder(): string
    {
        return "*Pengingat Absensi Latihan*\n\n".
            "Unit yang belum mengirim laporan absensi:\n\n".
            "1. *SMP/SMA/SMK Cengkareng 1*, Tanggal Latihan : Selasa, 17 November 2026, Pukul : 15:30–17:30 — PJ: @ka catur (Catur Wulandari)\n\n".
            "Total: 1 unit.\n".
            'Mohon lengkapi laporan absensi melalui aplikasi.';
    }

    private function target(): HolidayTarget
    {
        $unit = new Unit;
        $unit->setRawAttributes([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'SMP/SMA/SMK Cengkareng 1',
            'is_active' => 1,
        ], true);

        return new HolidayTarget(
            1,
            $unit,
            Carbon::parse('2026-11-17'),
            '15:30',
            '17:30',
        );
    }

    private function mockHappyPath(string $action = 'create'): void
    {
        $target = $this->target();

        $this->mock(HolidayTargetResolver::class, function ($mock) use ($target) {
            $mock->shouldReceive('reminderLines')->andReturn([]);
            $mock->shouldReceive('resolve')->andReturn(TargetResolution::found('reminder', $target, 1));
        });

        $this->mock(HolidayFlagService::class, function ($mock) use ($action) {
            $mock->shouldReceive('flag')->andReturn(FlagOutcome::done($action));
        });
    }

    private function postWebhook(array $payload, string $secret = self::SECRET)
    {
        return $this->postJson("/api/whatsapp/webhook/{$secret}", $payload);
    }

    private function sentTexts(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => (string) ($pair[0]['text'] ?? ''))
            ->all();
    }

    public function test_wrong_secret_is_rejected_without_calling_waha(): void
    {
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'x']], 201)]);

        $this->postWebhook($this->payload(), 'secret-salah')->assertStatus(403);

        Http::assertNothingSent();
    }

    public function test_missing_webhook_secret_config_rejects_everything(): void
    {
        config(['notifications.whatsapp.webhook_secret' => '']);
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload())->assertStatus(403);

        Http::assertNothingSent();
    }

    public function test_invalid_hmac_is_rejected(): void
    {
        config(['notifications.whatsapp.webhook_hmac_key' => 'kunci']);
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $raw = json_encode($this->payload());

        $this->call(
            'POST',
            '/api/whatsapp/webhook/'.self::SECRET,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_WEBHOOK_HMAC' => 'tidak-valid'],
            $raw,
        )->assertStatus(403);

        Http::assertNothingSent();
    }

    public function test_valid_hmac_is_accepted(): void
    {
        config(['notifications.whatsapp.webhook_hmac_key' => 'kunci']);
        $this->mockHappyPath();

        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'abc']], 201)]);

        $raw = json_encode($this->payload());

        $this->call(
            'POST',
            '/api/whatsapp/webhook/'.self::SECRET,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_HMAC' => hash_hmac('sha512', $raw, 'kunci'),
            ],
            $raw,
        )->assertStatus(200)->assertJson(['status' => 'flagged']);
    }

    public function test_bot_own_message_is_ignored(): void
    {
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload(['fromMe' => true]))
            ->assertStatus(200)
            ->assertJson(['ignored' => 'from_me']);

        Http::assertNothingSent();
    }

    public function test_other_events_are_ignored(): void
    {
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $payload = $this->payload();
        $payload['event'] = 'message.ack';

        $this->postWebhook($payload)->assertStatus(200)->assertJson(['ignored' => 'event_ignored']);

        Http::assertNothingSent();
    }

    public function test_group_chatter_is_not_answered(): void
    {
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload([
            'body' => 'Selamat pagi semua, sehat selalu',
            'replyTo' => null,
        ]))->assertStatus(200)->assertJson(['ignored' => 'not_a_command']);

        Http::assertNothingSent();
    }

    public function test_unauthorized_sender_is_told_off(): void
    {
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'abc']], 201)]);

        $this->postWebhook($this->payload(['participant' => '6281111111111@c.us']))
            ->assertStatus(200)
            ->assertJson(['status' => 'unauthorized']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/sendText')
                && str_contains((string) $request['text'], 'tidak boleh mengubah status absensi')
                && str_contains((string) $request['text'], '@6281111111111');
        });
    }

    public function test_valid_reply_flags_the_session_and_confirms_in_group(): void
    {
        $this->mockHappyPath('create');
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'abc']], 201)]);

        $this->postWebhook($this->payload())->assertStatus(200)->assertJson(['status' => 'flagged']);

        Http::assertSent(function ($request) {
            $text = (string) $request['text'];

            return str_contains($request->url(), '/api/sendText')
                && $request['chatId'] === '120363000000000000@g.us'
                && str_contains($text, 'sudah saya update,')
                && str_contains($text, 'SMP/SMA/SMK Cengkareng 1')
                && str_contains($text, 'Selasa, 17 November 2026')
                && str_contains($text, 'Diliburkan Sekolah Karena Sedang ujian');
        });
    }

    public function test_lowercase_status_is_accepted(): void
    {
        $this->mockHappyPath('create');
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload([
            'body' => "nomor 1\ndiliburkan sekolah\nAlasan : Sedang ujian",
        ]))->assertStatus(200)->assertJson(['status' => 'flagged']);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Diliburkan Sekolah Karena'));
    }

    public function test_invalid_format_gets_help_message(): void
    {
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload([
            'body' => "nomor 1\nLibur Sekolah\nAlasan : Sedang ujian",
        ]))->assertStatus(200)->assertJson(['status' => 'invalid_format']);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Format tidak dikenali.'));
    }

    public function test_reply_without_quote_is_rejected_with_instruction(): void
    {
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload(['replyTo' => null]))
            ->assertStatus(200)
            ->assertJson(['status' => 'not_reply']);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Balas pesan'));
    }

    public function test_number_out_of_range_is_reported(): void
    {
        $this->mock(HolidayTargetResolver::class, function ($mock) {
            $mock->shouldReceive('reminderLines')->andReturn([]);
            $mock->shouldReceive('resolve')->andReturn(TargetResolution::failed('number_not_found', 9));
        });

        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload([
            'body' => "nomor 12\nDiliburkan Sekolah\nAlasan : Sedang ujian",
        ]))->assertStatus(200)->assertJson(['status' => 'resolve_failed']);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Nomor 12 tidak ada pada pesan tersebut'));
    }

    public function test_already_reported_session_is_refused(): void
    {
        $target = $this->target();

        $this->mock(HolidayTargetResolver::class, function ($mock) use ($target) {
            $mock->shouldReceive('reminderLines')->andReturn([]);
            $mock->shouldReceive('resolve')->andReturn(TargetResolution::found('reminder', $target, 1));
        });

        $this->mock(HolidayFlagService::class, function ($mock) {
            $mock->shouldReceive('flag')->andReturn(FlagOutcome::rejected('reject_training'));
        });

        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload())
            ->assertStatus(200)
            ->assertJson(['status' => 'flag_rejected', 'detail' => 'reject_training']);

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'sudah ada laporan absensi'));
    }

    public function test_duplicate_webhook_delivery_is_processed_once(): void
    {
        $this->mockHappyPath('create');
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $payload = $this->payload();

        $this->postWebhook($payload)->assertStatus(200)->assertJson(['status' => 'flagged']);
        $this->postWebhook($payload)->assertStatus(200)->assertJson(['ignored' => 'duplicate']);

        $this->assertCount(1, $this->sentTexts());
    }

    public function test_reply_disabled_ignores_everything(): void
    {
        config(['notifications.reply.enabled' => false]);
        Http::fake(['waha.test/*' => Http::response([], 201)]);

        $this->postWebhook($this->payload())->assertStatus(200)->assertJson(['ignored' => 'reply_disabled']);

        Http::assertNothingSent();
    }

    public function test_waha_failure_does_not_break_the_webhook_response(): void
    {
        $this->mockHappyPath('create');
        Http::fake(['waha.test/*' => Http::response(['message' => 'down'], 500)]);

        $this->postWebhook($this->payload())->assertStatus(200)->assertJson(['status' => 'flagged']);
    }
}

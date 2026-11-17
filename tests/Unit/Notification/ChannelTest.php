<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\Channels\TelegramChannel;
use App\Services\Notification\Channels\WhatsAppChannel;
use App\Services\Notification\NotificationManager;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'notifications.providers' => ['telegram', 'whatsapp'],
            'notifications.test_mode' => false,
            'notifications.test_chat_id' => '6289654157132@c.us',
            'notifications.telegram.bot_token' => 'test-token',
            'notifications.telegram.chat_id' => '-100123',
            'notifications.telegram.timeout' => 5,
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'test-api-key',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363@g.us',
            'notifications.whatsapp.timeout' => 5,
        ]);
    }

    /** 1. Sukses Telegram */
    public function test_telegram_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
        ]);

        $result = (new TelegramChannel())->send('*Halo* dunia');

        $this->assertTrue($result->success);
        $this->assertSame('telegram', $result->provider);
        $this->assertSame('42', $result->externalId);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && $request['parse_mode'] === 'HTML'
                && $request['text'] === '<b>Halo</b> dunia';
        });
    }

    /** 2. Sukses WhatsApp + payload `mentions` benar */
    public function test_whatsapp_success_sends_mentions(): void
    {
        Http::fake([
            'waha.test/*' => Http::response(['id' => ['_serialized' => 'true_62812@c.us_ABC']], 200),
        ]);

        $result = (new WhatsAppChannel())->send('Halo @628111', ['628111@c.us', '628111@c.us']);

        $this->assertTrue($result->success);
        $this->assertSame('whatsapp', $result->provider);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://waha.test/api/sendText'
                && $request->hasHeader('X-Api-Key', 'test-api-key')
                && $request['session'] === 'sessionsmi'
                && $request['chatId'] === '120363@g.us'
                && $request['mentions'] === ['628111@c.us']
                && !isset($request['mentioned']);
        });
    }

    /** 2b. Tanpa mention: field `mentions` tidak dikirim sama sekali */
    public function test_whatsapp_without_mentions_omits_field(): void
    {
        Http::fake(['waha.test/*' => Http::response(['id' => 'x'], 200)]);

        (new WhatsAppChannel())->send('Halo');

        Http::assertSent(fn ($request) => !isset($request['mentions']) && !isset($request['mentioned']));
    }

    /** 3. Gagal HTTP Telegram (400) */
    public function test_telegram_failure_http_400(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400),
        ]);

        $result = (new TelegramChannel())->send('Halo');

        $this->assertFalse($result->success);
        $this->assertSame(400, $result->httpStatus);
        $this->assertStringContainsString('chat not found', $result->error);
    }

    /** 4. Gagal HTTP WhatsApp (500) */
    public function test_whatsapp_failure_http_500(): void
    {
        Http::fake([
            'waha.test/*' => Http::response(['message' => 'Internal error'], 500),
        ]);

        $result = (new WhatsAppChannel())->send('Halo');

        $this->assertFalse($result->success);
        $this->assertSame(500, $result->httpStatus);
    }

    /** 5. Timeout tidak melempar exception */
    public function test_timeout_is_captured_as_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
        });

        $whatsapp = (new WhatsAppChannel())->send('Halo');
        $telegram = (new TelegramChannel())->send('Halo');

        $this->assertFalse($whatsapp->success);
        $this->assertStringContainsString('timed out', $whatsapp->error);
        $this->assertFalse($telegram->success);
    }

    /** 6. Provider nonaktif = 0 request */
    public function test_inactive_provider_is_not_called(): void
    {
        config(['notifications.providers' => ['telegram']]);
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $manager = app(NotificationManager::class);
        $results = $manager->send('Halo');

        $this->assertSame(['telegram'], $manager->activeProviders());
        $this->assertCount(1, $results);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'waha.test'));
        Http::assertSentCount(1);
    }

    /** 7. Kegagalan WhatsApp tidak menggagalkan Telegram (isolasi) */
    public function test_whatsapp_failure_does_not_break_telegram(): void
    {
        Http::fake([
            'waha.test/*' => Http::response(['message' => 'session not working'], 422),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 7]], 200),
        ]);

        $manager = app(NotificationManager::class);
        $results = $manager->send('Halo');

        $this->assertCount(2, $results);
        $this->assertSame(['telegram'], $manager->succeededProviders($results));
        $this->assertFalse($manager->allSucceeded($results));
    }

    /** 8. Test mode mengarah ke chat pribadi + prefix [TEST] */
    public function test_test_mode_redirects_to_test_chat_with_prefix(): void
    {
        config(['notifications.test_mode' => true]);
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'x']], 200)]);

        $channel = new WhatsAppChannel();
        $this->assertSame('6289654157132@c.us', $channel->resolveChatId());

        $channel->send('Pengingat');

        Http::assertSent(function ($request) {
            return $request['chatId'] === '6289654157132@c.us'
                && str_starts_with($request['text'], '[TEST] Pengingat');
        });
    }
}

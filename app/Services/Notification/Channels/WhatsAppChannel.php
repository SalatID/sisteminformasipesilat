<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\ChannelResult;
use App\Services\Notification\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public function supportsMentions(): bool
    {
        return true;
    }

    /**
     * WhatsApp tidak me-render markdown link, sehingga [label](url) diubah
     * menjadi label: url (atau url saja bila label generik).
     */
    public function formatForWhatsApp(string $text): string
    {
        return preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', '$1: $2', $text);
    }

    /**
     * ChAT ID tujuan efektif. Saat test mode aktif, selalu diarahkan ke
     * NOTIF_TEST_CHAT_ID (chat pribadi) alih-alih grup produksi.
     */
    public function resolveChatId(): ?string
    {
        if ((bool) config('notifications.test_mode', false)) {
            return (string) config('notifications.test_chat_id');
        }

        $chatId = (string) config('notifications.whatsapp.chat_id');

        return $chatId !== '' ? $chatId : null;
    }

    public function send(string $text, array $mentions = []): ChannelResult
    {
        $chatId = $this->resolveChatId();

        if ($chatId === null || $chatId === '') {
            return ChannelResult::failure($this->name(), null, 'WAHA url/session/chat id belum dikonfigurasi.');
        }

        return $this->sendTo($chatId, $text, $mentions);
    }

    /**
     * Kirim ke chat tertentu (mis. membalas di grup tempat balasan admin
     * masuk). Saat test mode aktif, tujuan tetap dialihkan ke
     * NOTIF_TEST_CHAT_ID agar grup produksi tidak terganggu.
     *
     * @param  array<int, string>  $mentions
     */
    public function sendTo(string $chatId, string $text, array $mentions = []): ChannelResult
    {
        $baseUrl = rtrim((string) config('notifications.whatsapp.url'), '/');
        $apiKey = (string) config('notifications.whatsapp.api_key');
        $session = (string) config('notifications.whatsapp.session');

        if ((bool) config('notifications.test_mode', false)) {
            $chatId = (string) config('notifications.test_chat_id');
        }

        if ($baseUrl === '' || $session === '' || $chatId === '') {
            return ChannelResult::failure($this->name(), null, 'WAHA url/session/chat id belum dikonfigurasi.');
        }

        $text = $this->applyTestPrefix($text);
        $text = $this->formatForWhatsApp($text);

        $payload = [
            'session' => $session,
            'chatId' => $chatId,
            'text' => $text,
        ];

        // Field nama yang benar pada WAHA (verified engine NOWEB) adalah `mentions`.
        // Nilai `mentioned`/`mentionIds` diabaikan server dan menghasilkan teks biasa.
        // Hanya dikirim bila ada mention; menjaga payload minimal.
        if ($mentions !== []) {
            $payload['mentions'] = array_values(array_unique($mentions));
        }

        try {
            $request = Http::timeout((int) config('notifications.whatsapp.timeout', 30))
                ->acceptJson()
                ->asJson();

            if ($apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->post("{$baseUrl}/api/sendText", $payload);

            if ($response->successful()) {
                return ChannelResult::success(
                    $this->name(),
                    $response->status(),
                    (string) ($response->json('id._serialized') ?? $response->json('id') ?? '')
                );
            }

            $error = mb_substr((string) ($response->json('message') ?? $response->body()), 0, 500);
            Log::channel('cron')->error('WhatsApp send failed', [
                'status' => $response->status(),
                'error' => $error,
                'chat_id' => $chatId,
            ]);

            return ChannelResult::failure($this->name(), $response->status(), $error);
        } catch (Throwable $e) {
            Log::channel('cron')->error('WhatsApp send exception', ['error' => $e->getMessage()]);

            return ChannelResult::failure($this->name(), null, $e->getMessage());
        }
    }

    private function applyTestPrefix(string $text): string
    {
        if (!(bool) config('notifications.test_mode', false)) {
            return $text;
        }

        if (str_starts_with($text, '[TEST]')) {
            return $text;
        }

        return "[TEST] " . $text;
    }
}

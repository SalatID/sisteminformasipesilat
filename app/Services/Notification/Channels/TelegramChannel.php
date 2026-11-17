<?php

namespace App\Services\Notification\Channels;

use App\Services\Notification\ChannelResult;
use App\Services\Notification\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'telegram';
    }

    public function supportsMentions(): bool
    {
        return false;
    }

    public function send(string $text, array $mentions = []): ChannelResult
    {
        $token = (string) config('notifications.telegram.bot_token');
        $chatId = (string) config('notifications.telegram.chat_id');

        if ($token === '' || $chatId === '') {
            return ChannelResult::failure($this->name(), null, 'Telegram bot token atau chat id belum dikonfigurasi.');
        }

        try {
            $response = Http::timeout((int) config('notifications.telegram.timeout', 20))
                ->acceptJson()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $this->toTelegramHtml($text),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful() && ($response->json('ok') ?? false) === true) {
                return ChannelResult::success(
                    $this->name(),
                    $response->status(),
                    (string) ($response->json('result.message_id') ?? '')
                );
            }

            $error = (string) ($response->json('description') ?? 'Respons Telegram tidak sukses.');
            Log::channel('cron')->error('Telegram send failed', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            return ChannelResult::failure($this->name(), $response->status(), $error);
        } catch (Throwable $e) {
            Log::channel('cron')->error('Telegram send exception', ['error' => $e->getMessage()]);

            return ChannelResult::failure($this->name(), null, $e->getMessage());
        }
    }

    /**
     * Konversi format teks kanonik (*bold*, _italic_, [teks](url)) menjadi HTML Telegram.
     */
    private function toTelegramHtml(string $text): string
    {
        $escaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);

        // [label](url) -> <a href="url">label</a>
        $escaped = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', '<a href="$2">$1</a>', $escaped);

        // *bold* -> <b>bold</b>
        $escaped = preg_replace('/\*([^*\n]+)\*/', '<b>$1</b>', $escaped);

        // _italic_ -> <i>italic</i>
        $escaped = preg_replace('/(?<![\w])_([^_\n]+)_(?![\w])/', '<i>$1</i>', $escaped);

        return $escaped;
    }
}

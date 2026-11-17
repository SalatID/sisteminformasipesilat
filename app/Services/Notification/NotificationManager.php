<?php

namespace App\Services\Notification;

use App\Services\Notification\Channels\TelegramChannel;
use App\Services\Notification\Channels\WhatsAppChannel;
use App\Services\Notification\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class NotificationManager
{
    /** @var array<string, NotificationChannel> */
    private array $channels = [];

    public function __construct(TelegramChannel $telegram, WhatsAppChannel $whatsapp)
    {
        foreach ([$telegram, $whatsapp] as $channel) {
            $this->channels[$channel->name()] = $channel;
        }
    }

    /**
     * Daftar provider aktif sesuai `notifications.providers`.
     *
     * @return array<int, string>
     */
    public function activeProviders(): array
    {
        $active = [];

        foreach ((array) config('notifications.providers', []) as $provider) {
            if (isset($this->channels[$provider])) {
                $active[] = $provider;
            }
        }

        return array_values(array_unique($active));
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function activeChannels(): array
    {
        return array_map(fn (string $provider) => $this->channels[$provider], $this->activeProviders());
    }

    public function channel(string $provider): NotificationChannel
    {
        if (!isset($this->channels[$provider])) {
            throw new InvalidArgumentException("Provider notifikasi tidak dikenal: {$provider}");
        }

        return $this->channels[$provider];
    }

    /**
     * Apakah ada provider aktif yang mendukung @mention.
     */
    public function supportsMentions(): bool
    {
        foreach ($this->activeChannels() as $channel) {
            if ($channel->supportsMentions()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kirim teks ke seluruh provider aktif. Kegagalan satu provider TIDAK
     * menghentikan provider lain.
     *
     * @param  string  $text  teks ber-tag `@<nomor>` (varian kaya) — dipakai channel pendukung mention
     * @param  array<int, string>  $mentions  chat id lengkap (hanya dipakai channel pendukung mention)
     * @param  array<int, string>  $onlyProviders  batasi ke provider tertentu (retry per provider)
     * @param  string|null  $textWithoutTags  teks tanpa `@<nomor>` untuk channel tanpa tag (Telegram)
     * @return array<int, ChannelResult>
     */
    public function send(string $text, array $mentions = [], array $onlyProviders = [], ?string $textWithoutTags = null): array
    {
        $targets = $onlyProviders === []
            ? $this->activeProviders()
            : array_values(array_intersect($this->activeProviders(), $onlyProviders));

        $results = [];

        foreach ($targets as $provider) {
            $channel = $this->channels[$provider];

            try {
                // Channel tanpa mention (Telegram) memakai teks tanpa `@<nomor>` bila disediakan.
                $channelText = $channel->supportsMentions() ? $text : ($textWithoutTags ?? $text);

                $results[] = $channel->send($channelText, $channel->supportsMentions() ? $mentions : []);
            } catch (\Throwable $e) {
                Log::channel('cron')->error("Notification channel {$provider} exception", [
                    'error' => $e->getMessage(),
                ]);
                $results[] = ChannelResult::failure($provider, null, $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Apakah semua provider yang dituju sukses.
     *
     * @param  array<int, ChannelResult>  $results
     */
    public function allSucceeded(array $results): bool
    {
        if ($results === []) {
            return false;
        }

        foreach ($results as $result) {
            if (!$result->success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Daftar provider yang sukses dari sekumpulan hasil.
     *
     * @param  array<int, ChannelResult>  $results
     * @return array<int, string>
     */
    public function succeededProviders(array $results): array
    {
        return array_values(array_map(
            fn (ChannelResult $r) => $r->provider,
            array_filter($results, fn (ChannelResult $r) => $r->success)
        ));
    }
}

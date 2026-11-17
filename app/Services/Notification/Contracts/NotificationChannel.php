<?php

namespace App\Services\Notification\Contracts;

use App\Services\Notification\ChannelResult;

interface NotificationChannel
{
    /**
     * Nama provider (dipakai pada config `notifications.providers`).
     */
    public function name(): string;

    /**
     * Kirim satu pesan.
     *
     * @param  string  $text      Teks pesan (sudah diformat sesuai channel bila perlu).
     * @param  array<int, string>  $mentions  Daftar chat id lengkap untuk channel yang mendukung mention
     *                                        (contoh: ["6281234567890@c.us"]). Channel yang tidak
     *                                        mendukung mention mengabaikan parameter ini.
     */
    public function send(string $text, array $mentions = []): ChannelResult;

    /**
     * Apakah channel mendukung @mention.
     */
    public function supportsMentions(): bool;
}

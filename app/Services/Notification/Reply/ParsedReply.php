<?php

namespace App\Services\Notification\Reply;

/**
 * Hasil parsing teks balasan admin menjadi perintah penandaan libur.
 *
 * Dibuat sebagai value object agar pemanggil tidak perlu menebak-nebak isi
 * array dan agar pesan kesalahan bisa dipetakan ke balasan bot.
 */
class ParsedReply
{
    /**
     * @param  array<string, string>  $context  data pendukung pesan kesalahan (mis. baris yang salah)
     */
    private function __construct(
        public readonly bool $ok,
        public readonly ?int $number = null,
        public readonly ?string $status = null,
        public readonly ?string $statusLabel = null,
        public readonly ?string $reason = null,
        public readonly ?string $error = null,
        public readonly array $context = [],
        public readonly bool $truncated = false,
    ) {}

    public static function valid(
        int $number,
        string $status,
        string $statusLabel,
        string $reason,
        bool $truncated = false,
    ): self {
        return new self(
            ok: true,
            number: $number,
            status: $status,
            statusLabel: $statusLabel,
            reason: $reason,
            truncated: $truncated,
        );
    }

    /**
     * @param  array<string, string>  $context
     */
    public static function invalid(string $error, array $context = []): self
    {
        return new self(ok: false, error: $error, context: $context);
    }
}

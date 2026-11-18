<?php

namespace App\Services\Notification\Reply;

/**
 * Hasil parsing satu pesan balasan yang boleh memuat BEBERAPA perintah
 * (satu perintah = satu nomor baris).
 *
 * Setiap perintah disimpan sebagai {@see ParsedReply} agar pemetaan pesan
 * kesalahan tetap bisa memakai ulang `ReplyResponder::forParseError()`.
 */
class ParsedReplyBatch
{
    /**
     * @param  array<int, ParsedReply>  $items
     * @param  array<string, int|string>  $context  mis. `block` (blok yang gagal) & `blocks` (total blok)
     */
    private function __construct(
        public readonly bool $ok,
        public readonly array $items = [],
        public readonly ?string $error = null,
        public readonly array $context = [],
    ) {}

    /**
     * @param  array<int, ParsedReply>  $items
     */
    public static function valid(array $items): self
    {
        return new self(ok: true, items: array_values($items));
    }

    /**
     * @param  array<string, int|string>  $context
     */
    public static function invalid(string $error, array $context = []): self
    {
        return new self(ok: false, error: $error, context: $context);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Blok yang gagal, dalam bentuk yang bisa dipetakan ke pesan bantuan.
     */
    public function errorReply(): ParsedReply
    {
        return ParsedReply::invalid((string) $this->error, $this->context);
    }

    public function blockNumber(): int
    {
        return (int) ($this->context['block'] ?? 0);
    }

    public function blockCount(): int
    {
        return (int) ($this->context['blocks'] ?? $this->count());
    }
}

<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalisasi nomor WhatsApp ke format E.164 tanpa tanda '+' (contoh: 6281234567890).
     *
     * Contoh konversi:
     * - 081234567890    -> 6281234567890
     * - +6281234567890  -> 6281234567890
     * - 62 812-3456-789 -> 628123456789
     *
     * Mengembalikan null bila input kosong.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $input));

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Cek apakah input pengguna dapat dinormalisasi menjadi nomor WhatsApp yang valid.
     * Input kosong dianggap valid karena kolom bersifat opsional.
     */
    public static function isValidInput(?string $input): bool
    {
        if (trim((string) $input) === '') {
            return true;
        }

        $normalized = self::normalize($input);

        if ($normalized === null) {
            return false;
        }

        return str_starts_with($normalized, '62')
            && strlen($normalized) >= 9
            && strlen($normalized) <= 15;
    }
}

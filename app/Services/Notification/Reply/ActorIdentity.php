<?php

namespace App\Services\Notification\Reply;

/**
 * Normalisasi identitas pengirim WhatsApp agar daftar whitelist di `.env`
 * bisa ditulis dengan beberapa gaya:
 *
 *   WA_NOTIF_FLAG_HOLIDAY_USERS=6289654157132@c.us, 089654157132 ,6281234567890
 *
 * Semua bentuk di atas dibandingkan sebagai digit nomor yang sama.
 */
class ActorIdentity
{
    public static function key(?string $id): string
    {
        $id = mb_strtolower(trim((string) $id), 'UTF-8');

        if ($id === '') {
            return '';
        }

        // ID tersembunyi (LID) tidak bisa diturunkan menjadi nomor telepon.
        if (str_ends_with($id, '@lid')) {
            return $id;
        }

        $bare = explode('@', $id)[0];
        $digits = preg_replace('/\D+/', '', $bare) ?? '';

        if ($digits === '') {
            return $id;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Teks mention (@62812...) untuk sebuah chat id.
     */
    public static function mentionText(?string $id): ?string
    {
        $key = self::key($id);

        return ctype_digit($key) && $key !== '' ? '@'.$key : null;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public static function isAllowed(?string $id, array $allowed): bool
    {
        $key = self::key($id);

        if ($key === '') {
            return false;
        }

        foreach ($allowed as $candidate) {
            if (self::key($candidate) === $key) {
                return true;
            }
        }

        return false;
    }
}

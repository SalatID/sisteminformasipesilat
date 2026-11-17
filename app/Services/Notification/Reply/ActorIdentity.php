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

    /**
     * Semua identitas pengirim yang mungkin ada pada payload WAHA.
     *
     * WAHA NOWEB kadang melaporkan pengirim sebagai `@lid` (ID tersembunyi)
     * alih-alih nomor telepon. Bila tersedia, WAHA juga menyertakan padanan
     * nomor teleponnya (`@c.us`) di salah satu field berikut, sehingga
     * whitelist nomor tetap bisa dipakai.
     *
     * SENGAJA hanya membaca field milik PENGIRIM, bukan pesan yang dikutip,
     * agar orang lain tidak bisa "menumpang" identitas admin dengan cara
     * mengutip pesan admin.
     *
     * @param  array<string, mixed>  $message
     * @return array<int, string>
     */
    public static function candidates(array $message): array
    {
        $candidates = [];

        foreach (['participant', 'participantAlt', 'participantPn', 'senderPn', 'author'] as $key) {
            $value = $message[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $candidates[] = trim($value);
            }
        }

        foreach (['participant', 'participantAlt', 'participantPn', 'senderPn'] as $key) {
            $value = data_get($message, '_data.key.'.$key);

            if (is_string($value) && trim($value) !== '') {
                $candidates[] = trim($value);
            }
        }

        // Pada chat pribadi, `from` memang pengirim. Pada grup, `from` adalah
        // id grup sehingga tidak dipakai.
        $from = $message['from'] ?? null;

        if (is_string($from) && $from !== '' && ! str_ends_with($from, '@g.us')) {
            $candidates[] = $from;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<int, string>  $candidates
     * @param  array<int, string>  $allowed
     */
    public static function anyAllowed(array $candidates, array $allowed): bool
    {
        foreach ($candidates as $candidate) {
            if (self::isAllowed($candidate, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identitas yang paling enak ditampilkan: nomor telepon lebih dulu,
     * baru `@lid`.
     *
     * @param  array<int, string>  $candidates
     */
    public static function preferred(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (self::mentionText($candidate) !== null) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
    }
}

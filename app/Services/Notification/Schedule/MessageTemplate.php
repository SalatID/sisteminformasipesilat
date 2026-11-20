<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use Carbon\CarbonInterface;

/**
 * Merender template pesan latihan/libur.
 *
 * Variabel yang didukung: {nama_jadwal}, {tanggal}, {hari}, {alasan}.
 * Variabel lain ditolak saat validasi form agar tidak muncul sebagai teks
 * mentah di WhatsApp.
 */
class MessageTemplate
{
    public const VARIABLES = ['nama_jadwal', 'tanggal', 'hari', 'alasan'];

    /**
     * @return array<int, string>
     */
    public function unknownVariables(?string $template): array
    {
        if ($template === null || trim($template) === '') {
            return [];
        }

        preg_match_all('/\{\s*([a-zA-Z0-9_]+)\s*\}/u', $template, $matches);

        $found = array_values(array_unique(array_map(
            fn (string $value) => mb_strtolower($value, 'UTF-8'),
            $matches[1] ?? [],
        )));

        return array_values(array_diff($found, self::VARIABLES));
    }

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        $replacements = [];

        foreach (self::VARIABLES as $variable) {
            $replacements['{'.$variable.'}'] = (string) ($values[$variable] ?? '');
        }

        return strtr($template, $replacements);
    }

    /**
     * Nilai variabel untuk satu kemunculan.
     *
     * @return array<string, string>
     */
    public function values(ScheduledNotification $schedule, CarbonInterface $occurrence, ?string $reason = null): array
    {
        return [
            'nama_jadwal' => (string) $schedule->name,
            'tanggal' => OccurrenceCalculator::dateLabel($occurrence),
            'hari' => OccurrenceCalculator::dayName($occurrence),
            'alasan' => (string) ($reason ?? ''),
        ];
    }

    /**
     * Nilai contoh untuk pratinjau di form.
     *
     * @return array<string, string>
     */
    public function sampleValues(ScheduledNotification $schedule, ?CarbonInterface $date = null): array
    {
        return $this->values($schedule, $date ?? now(), 'hujan deras');
    }

    public function holidayMessage(ScheduledNotification $schedule): string
    {
        $template = trim((string) $schedule->holiday_message);

        if ($template === '') {
            $template = (string) config('notifications.schedules.default_holiday_message');
        }

        return $template;
    }

    /**
     * Template pesan latihan: kolom `message`; bila kosong jatuh ke template
     * bawaan sehingga pratinjau & kirim tes tetap berisi teks bermakna.
     */
    public function trainingMessage(ScheduledNotification $schedule): string
    {
        $template = trim((string) $schedule->message);

        if ($template === '') {
            $template = (string) config('notifications.schedules.default_training_message');
        }

        return $template;
    }
}

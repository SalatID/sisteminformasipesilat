<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Menghitung daftar kemunculan jadwal. Semua perhitungan memakai zona waktu
 * `notifications.schedules.timezone` (default Asia/Jakarta) dan tanggal
 * kalender nyata, sehingga tanggal mustahil (31 Februari) otomatis dilewati.
 */
class OccurrenceCalculator
{
    private string $timezone;

    public function __construct(?string $timezone = null)
    {
        $this->timezone = $timezone
            ?: (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));
    }

    /**
     * Kemunculan untuk sebuah jadwal dalam horizon planner.
     *
     * @return array<int, CarbonImmutable>
     */
    public function forSchedule(ScheduledNotification $schedule, ?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now($this->timezone))->setTimezone($this->timezone);
        $horizon = max(1, (int) config('notifications.schedules.horizon_days', 60));

        $occurrences = $this->datesBetween(
            $this->attributes($schedule),
            $now->startOfDay(),
            $now->addDays($horizon)->endOfDay(),
        );

        // Jangan membuat run untuk jam yang sudah lewat hari ini. Run yang
        // sudah terkirim tetap ada di DB dan tidak tersentuh planner.
        return array_values(array_filter(
            $occurrences,
            fn (CarbonImmutable $occurrence) => $occurrence->greaterThanOrEqualTo($now),
        ));
    }

    /**
     * Semua kemunculan di antara dua tanggal (inklusif), tanpa memfilter jam.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, CarbonImmutable>
     */
    public function datesBetween(array $attributes, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->setTimezone($this->timezone)->startOfDay();
        $to = $to->setTimezone($this->timezone)->endOfDay();

        $startDate = ! empty($attributes['start_date'])
            ? CarbonImmutable::parse((string) $attributes['start_date'], $this->timezone)->startOfDay()
            : $from;

        $endDate = ! empty($attributes['end_date'])
            ? CarbonImmutable::parse((string) $attributes['end_date'], $this->timezone)->endOfDay()
            : $to;

        $cursor = $from->greaterThan($startDate) ? $from : $startDate;
        $last = $to->lessThan($endDate) ? $to : $endDate;

        if ($cursor->greaterThan($last)) {
            return [];
        }

        [$hour, $minute] = $this->timeParts((string) ($attributes['time'] ?? '00:00'));
        $frequency = (string) ($attributes['frequency'] ?? 'once');

        if ($frequency === 'once') {
            $date = $startDate;

            if ($date->lessThan($from) || $date->greaterThan($last)) {
                return [];
            }

            return [$date->setTime($hour, $minute)];
        }

        $daysOfWeek = $this->intList($attributes['days_of_week'] ?? []);
        $datesOfMonth = $this->intList($attributes['dates_of_month'] ?? []);
        $weeksOfMonth = $this->intList($attributes['weeks_of_month'] ?? []);
        $monthlyMode = (string) ($attributes['monthly_mode'] ?? 'date');

        $occurrences = [];

        for ($date = $cursor; $date->lessThanOrEqualTo($last); $date = $date->addDay()) {
            $matches = match ($frequency) {
                'daily' => true,
                'weekly' => in_array($date->dayOfWeekIso, $daysOfWeek, true),
                'monthly' => $monthlyMode === 'week'
                    ? $this->matchesWeekOfMonth($date, $daysOfWeek, $weeksOfMonth)
                    : in_array($date->day, $datesOfMonth, true),
                default => false,
            };

            if ($matches) {
                $occurrences[] = $date->setTime($hour, $minute);
            }
        }

        return $occurrences;
    }

    /**
     * @param  array<int, int>  $daysOfWeek
     * @param  array<int, int>  $weeksOfMonth
     */
    private function matchesWeekOfMonth(CarbonImmutable $date, array $daysOfWeek, array $weeksOfMonth): bool
    {
        if (! in_array($date->dayOfWeekIso, $daysOfWeek, true)) {
            return false;
        }

        $weekIndex = intdiv($date->day - 1, 7) + 1;
        $isLastOccurrence = $date->day + 7 > $date->daysInMonth;

        if (in_array($weekIndex, $weeksOfMonth, true)) {
            return true;
        }

        // Nilai 5 = kemunculan terakhir hari tersebut pada bulan itu.
        return in_array(5, $weeksOfMonth, true) && $isLastOccurrence;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function timeParts(string $time): array
    {
        $parts = explode(':', $time);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);

        return [max(0, min(23, $hour)), max(0, min(59, $minute))];
    }

    /**
     * @return array<int, int>
     */
    private function intList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ScheduledNotification $schedule): array
    {
        return [
            'frequency' => $schedule->frequency,
            'time' => $schedule->timeLabel(),
            'days_of_week' => $schedule->days_of_week,
            'monthly_mode' => $schedule->monthly_mode,
            'dates_of_month' => $schedule->dates_of_month,
            'weeks_of_month' => $schedule->weeks_of_month,
            'start_date' => $schedule->start_date?->toDateString(),
            'end_date' => $schedule->end_date?->toDateString(),
        ];
    }

    public static function dayName(CarbonInterface $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$date->dayOfWeekIso] ?? '';
    }

    public static function monthName(CarbonInterface $date): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$date->month] ?? '';
    }

    public static function dateLabel(CarbonInterface $date): string
    {
        return $date->day.' '.self::monthName($date).' '.$date->year;
    }
}

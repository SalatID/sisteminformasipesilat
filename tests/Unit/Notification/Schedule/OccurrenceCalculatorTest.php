<?php

namespace Tests\Unit\Notification\Schedule;

use App\Services\Notification\Schedule\OccurrenceCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class OccurrenceCalculatorTest extends TestCase
{
    private function calculator(): OccurrenceCalculator
    {
        return new OccurrenceCalculator('Asia/Jakarta');
    }

    private function dates(array $attributes, string $from, string $to): array
    {
        return array_map(
            fn (CarbonImmutable $date) => $date->format('Y-m-d H:i'),
            $this->calculator()->datesBetween(
                $attributes,
                CarbonImmutable::parse($from, 'Asia/Jakarta'),
                CarbonImmutable::parse($to, 'Asia/Jakarta'),
            ),
        );
    }

    public function test_once_only_returns_the_start_date(): void
    {
        $dates = $this->dates([
            'frequency' => 'once',
            'time' => '12:00',
            'start_date' => '2026-11-25',
        ], '2026-11-20', '2026-12-20');

        $this->assertSame(['2026-11-25 12:00'], $dates);
    }

    public function test_daily_returns_every_day_in_range(): void
    {
        $dates = $this->dates([
            'frequency' => 'daily',
            'time' => '08:30',
        ], '2026-11-20', '2026-11-22');

        $this->assertSame([
            '2026-11-20 08:30',
            '2026-11-21 08:30',
            '2026-11-22 08:30',
        ], $dates);
    }

    public function test_weekly_supports_multiple_days(): void
    {
        // Selasa (2) & Kamis (4) antara 20–30 Nov 2026.
        $dates = $this->dates([
            'frequency' => 'weekly',
            'time' => '12:00',
            'days_of_week' => [2, 4],
        ], '2026-11-20', '2026-11-30');

        $this->assertSame([
            '2026-11-24 12:00',
            '2026-11-26 12:00',
        ], $dates);
    }

    public function test_monthly_by_date(): void
    {
        $dates = $this->dates([
            'frequency' => 'monthly',
            'monthly_mode' => 'date',
            'time' => '12:00',
            'dates_of_month' => [1, 15],
        ], '2026-11-01', '2026-11-30');

        $this->assertSame([
            '2026-11-01 12:00',
            '2026-11-15 12:00',
        ], $dates);
    }

    public function test_monthly_by_week(): void
    {
        // Selasa minggu ke-1 & ke-3 November 2026: 3 dan 17.
        $dates = $this->dates([
            'frequency' => 'monthly',
            'monthly_mode' => 'week',
            'time' => '12:00',
            'days_of_week' => [2],
            'weeks_of_month' => [1, 3],
        ], '2026-11-01', '2026-11-30');

        $this->assertSame([
            '2026-11-03 12:00',
            '2026-11-17 12:00',
        ], $dates);
    }

    public function test_week_five_means_last_occurrence_event_when_month_has_four(): void
    {
        // November 2026 hanya punya 4 hari Selasa (3, 10, 17, 24).
        $dates = $this->dates([
            'frequency' => 'monthly',
            'monthly_mode' => 'week',
            'time' => '12:00',
            'days_of_week' => [2],
            'weeks_of_month' => [5],
        ], '2026-11-01', '2026-11-30');

        $this->assertSame(['2026-11-24 12:00'], $dates);
    }

    public function test_week_five_means_last_occurrence_when_month_has_five(): void
    {
        // Desember 2026 punya 5 hari Selasa (1, 8, 15, 22, 29).
        $dates = $this->dates([
            'frequency' => 'monthly',
            'monthly_mode' => 'week',
            'time' => '12:00',
            'days_of_week' => [2],
            'weeks_of_month' => [5],
        ], '2026-12-01', '2026-12-31');

        $this->assertSame(['2026-12-29 12:00'], $dates);
    }

    public function test_start_and_end_date_limit_occurrences(): void
    {
        $dates = $this->dates([
            'frequency' => 'daily',
            'time' => '07:00',
            'start_date' => '2026-11-22',
            'end_date' => '2026-11-24',
        ], '2026-11-20', '2026-11-30');

        $this->assertSame([
            '2026-11-22 07:00',
            '2026-11-23 07:00',
            '2026-11-24 07:00',
        ], $dates);
    }

    public function test_impossible_date_is_skipped(): void
    {
        $dates = $this->dates([
            'frequency' => 'monthly',
            'monthly_mode' => 'date',
            'time' => '12:00',
            'dates_of_month' => [31],
        ], '2027-02-01', '2027-02-28');

        $this->assertSame([], $dates);
    }

    public function test_occurrences_use_jakarta_timezone(): void
    {
        $dates = $this->calculator()->datesBetween([
            'frequency' => 'once',
            'time' => '12:00',
            'start_date' => '2026-11-25',
        ], CarbonImmutable::parse('2026-11-20', 'UTC'), CarbonImmutable::parse('2026-11-30', 'UTC'));

        $this->assertCount(1, $dates);
        $this->assertSame('Asia/Jakarta', $dates[0]->timezoneName);
        $this->assertSame('2026-11-25 12:00', $dates[0]->format('Y-m-d H:i'));
    }
}

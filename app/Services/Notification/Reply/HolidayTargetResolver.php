<?php

namespace App\Services\Notification\Reply;

use App\Models\Unit;
use Carbon\Carbon;
use Closure;

/**
 * Memetakan "nomor N" + teks pesan notifikasi yang dikutip menjadi unit & tanggal.
 *
 * Bot tidak menyimpan pemetaan nomor di database: nomor selalu dibaca ulang
 * dari teks pesan asli yang dikutip admin (`replyTo.body`). Dengan begitu
 * nomor yang ditulis admin dijamin sama dengan yang ia lihat di grup.
 *
 * Pemetaan nama unit bisa diganti lewat `$unitFinder` agar logika penomoran
 * dapat diuji tanpa database.
 */
class HolidayTargetResolver
{
    /**
     * @var Closure(string): ?Unit
     */
    private readonly Closure $unitFinder;

    public function __construct(
        private readonly ReminderLineParser $parser,
        ?Closure $unitFinder = null,
    ) {
        $this->unitFinder = $unitFinder ?? fn (string $name): ?Unit => $this->findUnitInDatabase($name);
    }

    public function resolve(string $quotedText, int $number): TargetResolution
    {
        $quotedText = trim($quotedText);

        if ($quotedText === '') {
            return TargetResolution::failed('not_notification');
        }

        $report = $this->parser->parseReport($quotedText);

        if ($report !== null && $this->parser->isReport($quotedText)) {
            if ($number !== 1) {
                return TargetResolution::failed('number_not_found', 1);
            }

            return $this->build($number, $report['unit'], $report['date'], null, null, 'report', 1);
        }

        $entries = $this->parser->parseReminder($quotedText);

        if ($entries === []) {
            return TargetResolution::failed('not_notification');
        }

        $total = count($entries);

        foreach ($entries as $entry) {
            if ($entry['number'] === $number) {
                return $this->build(
                    $number,
                    $entry['unit'],
                    $entry['date'],
                    $entry['start'],
                    $entry['end'],
                    'reminder',
                    $total,
                );
            }
        }

        return TargetResolution::failed('number_not_found', $total);
    }

    /**
     * @return array<int, array{number: int, unit: string, date: ?Carbon, dateLabel: string, start: ?string, end: ?string, pj: ?string}>
     */
    public function reminderLines(string $quotedText): array
    {
        return $this->parser->parseReminder($quotedText);
    }

    private function build(
        int $number,
        string $unitName,
        ?Carbon $date,
        ?string $start,
        ?string $end,
        string $type,
        int $totalLines,
    ): TargetResolution {
        if ($date === null) {
            return TargetResolution::failed('date_unreadable', $totalLines);
        }

        $unit = ($this->unitFinder)(trim($unitName));

        if ($unit === null) {
            return TargetResolution::failed('unit_not_found', $totalLines);
        }

        return TargetResolution::found(
            $type,
            new HolidayTarget($number, $unit, $date->copy()->startOfDay(), $start, $end),
            $totalLines,
        );
    }

    private function findUnitInDatabase(string $name): ?Unit
    {
        if ($name === '') {
            return null;
        }

        // Eksak dulu (case-insensitive) agar "SDN 01" tidak tertukar dengan
        // "SDN 01 Pagi"; pencocokan longgar hanya sebagai jaring pengaman bila
        // teks pesan sempat diedit manual.
        return Unit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name, 'UTF-8')])
            ->first()
            ?? Unit::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name, 'UTF-8').'%'])
                ->orderByRaw('LENGTH(name) ASC')
                ->first();
    }
}

<?php

namespace App\Services\Notification\Reply;

use App\Models\Unit;
use Carbon\Carbon;

/**
 * Satu sesi latihan yang akan ditandai libur: unit + tanggal (+ jam latihan).
 */
class HolidayTarget
{
    public function __construct(
        public readonly int $number,
        public readonly Unit $unit,
        public readonly Carbon $date,
        public readonly ?string $start = null,
        public readonly ?string $end = null,
    ) {}

    public function unitName(): string
    {
        // Beberapa baris `units.name` punya spasi di ujung (mis. "SD KARAWACI 08 ");
        // rapikan saat ditampilkan agar balasan bot tidak terlihat dobel spasi.
        return trim((string) ($this->unit->name ?? 'Unknown'));
    }

    public function dateLabel(): string
    {
        return $this->date->copy()
            ->locale('id')
            ->translatedFormat('l, d F Y');
    }

    public function pukulLabel(): string
    {
        if ($this->start === null || $this->end === null) {
            return '-';
        }

        return $this->start.'–'.$this->end;
    }
}

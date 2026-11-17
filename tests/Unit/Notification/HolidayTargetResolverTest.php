<?php

namespace Tests\Unit\Notification;

use App\Models\Unit;
use App\Services\Notification\Reply\HolidayTargetResolver;
use App\Services\Notification\Reply\ReminderLineParser;
use Tests\TestCase;

/**
 * Fase 2 — pemetaan "nomor N" ke unit + tanggal.
 *
 * Memakai pesan pengingat ASLI 9 baris dari pemilik proses dan unit tiruan,
 * sehingga logika penomoran teruji tanpa menyentuh database.
 */
class HolidayTargetResolverTest extends TestCase
{
    private const REMINDER = <<<'TXT'
*Pengingat Absensi Latihan*

Unit yang belum mengirim laporan absensi:

1. *SMP/SMA/SMK Cengkareng 1*, Tanggal Latihan : Selasa, 17 November 2026, Pukul : 15:30–17:30 — PJ: @ka catur (Catur Wulandari)
2. *SDN Kalideres 05*, Tanggal Latihan : Selasa, 17 November 2026, Pukul : 13:30–15:00 — PJ: @Revika SMI (Revika Zulviani)
3. *SD KARAWACI 08*, Tanggal Latihan : Sabtu, 14 November 2026, Pukul : 10:00–12:00 — PJ: @Bang Apriyanto - SMI Jakbar (Apriyanto)
4. *RA Al Hadi*, Tanggal Latihan : Sabtu, 14 November 2026, Pukul : 08:00–09:00 — PJ: @Bang Ikhwan SMI (Agam Ikhwan)
5. *SMA IT AL MAKA*, Tanggal Latihan : Kamis, 12 November 2026, Pukul : 14:30–16:00 — PJ: @Muhamad Arief Prihantoro (M. Arief P)
6. *SMP/SMA/SMK Cengkareng 1*, Tanggal Latihan : Selasa, 10 November 2026, Pukul : 15:30–17:30 — PJ: @ka catur (Catur Wulandari)
7. *SMP/SMA/SMK Cengkareng 1*, Tanggal Latihan : Selasa, 03 November 2026, Pukul : 15:30–17:30 — PJ: @ka catur (Catur Wulandari)
8. *SDN Pegadungan 01 Pagi*, Tanggal Latihan : Sabtu, 31 Oktober 2026, Pukul : 08:00–10:00 — PJ: @Bang Ubay SMI (Ummul Ubaedillah)
9. *MI Assanusiah*, Tanggal Latihan : Senin, 26 Oktober 2026, Pukul : 12:30–14:00 — PJ: @Bang Apriyanto - SMI Jakbar (Apriyanto)

Total: 9 unit.
Mohon lengkapi laporan absensi melalui aplikasi.
TXT;

    private const REPORT = <<<'TXT'
*Absensi Latihan*

*Unit*: SDN Pegadungan 01
*PJ Unit*: Zaenal Arifin
*Tanggal Latihan*: 17/11/2026
*Jam Latihan*: 13:00 - 15:00
TXT;

    /**
     * "Database" unit tiruan: nama → model Unit tanpa koneksi.
     */
    private function resolver(array $unitNames): HolidayTargetResolver
    {
        $finder = function (string $name) use ($unitNames): ?Unit {
            foreach ($unitNames as $index => $unitName) {
                if (mb_strtolower($unitName) === mb_strtolower($name)) {
                    $unit = new Unit;
                    $unit->setRawAttributes([
                        'id' => '00000000-0000-0000-0000-00000000000'.$index,
                        'name' => $unitName,
                        'is_active' => 1,
                    ], true);

                    return $unit;
                }
            }

            return null;
        };

        return new HolidayTargetResolver(new ReminderLineParser, $finder);
    }

    private function allUnits(): array
    {
        return [
            'SMP/SMA/SMK Cengkareng 1',
            'SDN Kalideres 05',
            'SD KARAWACI 08',
            'RA Al Hadi',
            'SMA IT AL MAKA',
            'SDN Pegadungan 01 Pagi',
            'MI Assanusiah',
            'SDN Pegadungan 01',
        ];
    }

    public function test_number_one_maps_to_cengkareng_1_on_17_november(): void
    {
        $result = $this->resolver($this->allUnits())->resolve(self::REMINDER, 1);

        $this->assertTrue($result->ok);
        $this->assertSame('reminder', $result->type);
        $this->assertSame(9, $result->totalLines);
        $this->assertNotNull($result->target);
        $this->assertSame('SMP/SMA/SMK Cengkareng 1', $result->target->unitName());
        $this->assertSame('2026-11-17', $result->target->date->toDateString());
        $this->assertSame('15:30', $result->target->start);
        $this->assertSame('17:30', $result->target->end);
    }

    public function test_number_nine_maps_to_the_last_line(): void
    {
        $result = $this->resolver($this->allUnits())->resolve(self::REMINDER, 9);

        $this->assertTrue($result->ok);
        $this->assertSame('MI Assanusiah', $result->target->unitName());
        $this->assertSame('2026-10-26', $result->target->date->toDateString());
        $this->assertSame('12:30', $result->target->start);
    }

    public function test_number_three_keeps_its_own_date(): void
    {
        $result = $this->resolver($this->allUnits())->resolve(self::REMINDER, 3);

        $this->assertTrue($result->ok);
        $this->assertSame('SD KARAWACI 08', $result->target->unitName());
        $this->assertSame('2026-11-14', $result->target->date->toDateString());
    }

    public function test_number_out_of_range_is_reported_with_line_count(): void
    {
        $result = $this->resolver($this->allUnits())->resolve(self::REMINDER, 12);

        $this->assertFalse($result->ok);
        $this->assertSame('number_not_found', $result->error);
        $this->assertSame(9, $result->totalLines);
    }

    public function test_zero_and_negative_numbers_are_not_found(): void
    {
        $resolver = $this->resolver($this->allUnits());

        $this->assertSame('number_not_found', $resolver->resolve(self::REMINDER, 0)->error);
        $this->assertSame('number_not_found', $resolver->resolve(self::REMINDER, -1)->error);
    }

    public function test_unknown_unit_is_reported(): void
    {
        $result = $this->resolver(['SMP/SMA/SMK Cengkareng 1'])->resolve(self::REMINDER, 9);

        $this->assertFalse($result->ok);
        $this->assertSame('unit_not_found', $result->error);
        $this->assertSame(9, $result->totalLines);
    }

    public function test_report_message_only_accepts_number_one(): void
    {
        $resolver = $this->resolver($this->allUnits());

        $first = $resolver->resolve(self::REPORT, 1);
        $this->assertTrue($first->ok);
        $this->assertSame('report', $first->type);
        $this->assertSame('SDN Pegadungan 01', $first->target->unitName());
        $this->assertSame('2026-11-17', $first->target->date->toDateString());

        $second = $resolver->resolve(self::REPORT, 2);
        $this->assertFalse($second->ok);
        $this->assertSame('number_not_found', $second->error);
    }

    public function test_plain_chat_text_is_not_a_notification(): void
    {
        $result = $this->resolver($this->allUnits())->resolve('Selamat pagi semua, latihan tetap jalan.', 1);

        $this->assertFalse($result->ok);
        $this->assertSame('not_notification', $result->error);
    }

    public function test_empty_quote_is_not_a_notification(): void
    {
        $result = $this->resolver($this->allUnits())->resolve('   ', 1);

        $this->assertFalse($result->ok);
        $this->assertSame('not_notification', $result->error);
    }
}

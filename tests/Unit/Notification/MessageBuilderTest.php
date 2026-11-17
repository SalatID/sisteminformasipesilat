<?php

namespace Tests\Unit\Notification;

use App\Models\Attendance;
use App\Models\Coach;
use App\Models\Ts;
use App\Models\Unit;
use App\Services\Notification\AttendanceMessageBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MessageBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['notifications.mention.enabled' => true]);
        Carbon::setTestNow(Carbon::parse('2026-11-17 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function unit(string $name, ?string $start, ?string $end, ?Coach $pj = null): Unit
    {
        $unit = new Unit();
        $unit->setRawAttributes([
            'name' => $name,
            'training_hours_start' => $start,
            'training_hours_end' => $end,
        ], true);

        if ($pj) {
            $unit->setRelation('pj', $pj);
        }

        return $unit;
    }

    private function pj(string $name, ?string $phone): Coach
    {
        $coach = new Coach();
        $coach->setRawAttributes(['nomor_whatsapp' => $phone], true);
        $coach->setAttribute('name', $name);

        return $coach;
    }

    private function item(Unit $unit, string $type = 'today', ?string $date = null): array
    {
        return ['unit' => $unit, 'date' => Carbon::parse($date ?? '2026-11-17'), 'type' => $type];
    }

    // ---------------------------------------------------------------- AC-05
    public function test_empty_reminder_returns_null(): void
    {
        $this->assertNull((new AttendanceMessageBuilder())->buildConsolidatedReminder([]));
    }

    // ---------------------------------------------------------------- AC-04
    #[DataProvider('unitCounts')]
    public function test_n_units_produce_exactly_one_message(int $count): void
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = $this->item($this->unit("Unit {$i}", '08:00', '10:00', $this->pj("PJ {$i}", '6281234567890')));
        }

        $result = (new AttendanceMessageBuilder())->buildConsolidatedReminder($items, true);

        $this->assertNotNull($result);
        $this->assertIsString($result['text']);
        $this->assertStringContainsString("Total: {$count} unit.", $result['text']);

        // Jumlah baris unit = jumlah input (tidak ada pesan terpisah)
        preg_match_all('/^\d+\. /m', $result['text'], $m);
        $this->assertCount($count, $m[0]);
    }

    public static function unitCounts(): array
    {
        return ['3 unit' => [3], '30 unit' => [30]];
    }

    // ---------------------------------------------------------------- AC-13
    public function test_units_with_valid_phone_are_mentioned(): void
    {
        $items = [
            $this->item($this->unit('SMK TI YPML', '08:00', '09:30', $this->pj('Mursalat Asyidiq', '6289654157132'))),
            $this->item($this->unit('SDN Kalideres 05', '13:30', '15:00', $this->pj('Revika Zulviani', '085713929759'))),
        ];

        $result = (new AttendanceMessageBuilder())->buildConsolidatedReminder($items, true);

        $this->assertStringContainsString('@6289654157132 (Mursalat Asyidiq)', $result['text']);
        $this->assertStringContainsString('@6285713929759 (Revika Zulviani)', $result['text']);
        $this->assertSame(['6289654157132@c.us', '6285713929759@c.us'], $result['mentions']);

        // Setiap @nomor di teks HARUS punya entri di array mentions (konsistensi AC-13)
        preg_match_all('/@(62\d{8,14})/', $result['text'], $m);
        foreach (array_unique($m[1]) as $phone) {
            $this->assertContains("{$phone}@c.us", $result['mentions']);
        }
    }

    // ---------------------------------------------------------------- AC-14
    public function test_unit_without_phone_falls_back_to_plain_name(): void
    {
        $items = [
            $this->item($this->unit('SDN Pegadungan 01 Pagi', '13:00', '15:00', $this->pj('Zaenal Arifin', null))),
            $this->item($this->unit('Unit X', '08:00', '09:00', $this->pj('Tanpa Nomor', 'abc'))),
            $this->item($this->unit('Unit Y', '09:00', '10:00', null)),
        ];

        $result = (new AttendanceMessageBuilder())->buildConsolidatedReminder($items, true);

        $this->assertStringContainsString('— PJ: Zaenal Arifin', $result['text']);
        $this->assertStringContainsString('— PJ: Tanpa Nomor', $result['text']);
        $this->assertStringContainsString('— PJ: Unknown', $result['text']);
        $this->assertStringNotContainsString('@', str_replace('@628', '', $result['text']));
        $this->assertSame([], $result['mentions']);
    }

    public function test_mention_disabled_shows_plain_names(): void
    {
        $items = [$this->item($this->unit('Unit A', '08:00', '09:00', $this->pj('Budi', '6281234567890')))];

        $result = (new AttendanceMessageBuilder(false, 10))->buildConsolidatedReminder($items, true);

        $this->assertStringContainsString('— PJ: Budi', $result['text']);
        $this->assertSame([], $result['mentions']);
    }

    public function test_mention_flag_from_config_is_respected_at_runtime(): void
    {
        config(['notifications.mention.enabled' => false]);

        $items = [$this->item($this->unit('Unit A', '08:00', '09:00', $this->pj('Budi', '6281234567890')))];
        $result = (new AttendanceMessageBuilder(true, 10))->buildConsolidatedReminder($items, true);

        $this->assertStringContainsString('— PJ: Budi', $result['text']);
        $this->assertSame([], $result['mentions']);
    }

    public function test_channel_without_mention_support_gets_plain_names(): void
    {
        $items = [$this->item($this->unit('Unit A', '08:00', '09:00', $this->pj('Budi', '6281234567890')))];

        $result = (new AttendanceMessageBuilder(true, 10))->buildConsolidatedReminder($items, false);

        $this->assertStringContainsString('— PJ: Budi', $result['text']);
        $this->assertSame([], $result['mentions']);
    }

    public function test_mention_budget_is_enforced(): void
    {
        $items = [];
        for ($i = 1; $i <= 15; $i++) {
            $items[] = $this->item($this->unit("Unit {$i}", '08:00', '10:00', $this->pj("PJ {$i}", '62812345678' . str_pad((string) $i, 2, '0', STR_PAD_LEFT))));
        }

        $result = (new AttendanceMessageBuilder(true, 10))->buildConsolidatedReminder($items, true);

        $this->assertCount(10, $result['mentions']);
        $this->assertSame(5, substr_count($result['text'], '— PJ: PJ'));
    }

    // ---------------------------------------------------------------- Formatting
    public function test_reminder_uses_canonical_bold_format(): void
    {
        $items = [$this->item($this->unit('Unit A', '08:00', '09:00'))];

        $text = (new AttendanceMessageBuilder())->buildConsolidatedReminder($items)['text'];

        $this->assertStringContainsString('*[BOT ASSISTEN PELATIH]*', $text);
        $this->assertStringContainsString('*Pengingat Absensi Latihan*', $text);
        $this->assertStringContainsString('Tanggal: 17/11/2026', $text);
    }

    // ---------------------------------------------------------------- AC-08
    public function test_attendance_report_contains_all_13_fields(): void
    {
        $ts = new Ts();
        $ts->setRawAttributes(['alias' => 'SH', 'ts_seq' => 5], true);

        $coach = new Coach();
        $coach->setRawAttributes(['name' => 'Budi Santoso'], true);
        $coach->setRelation('ts', $ts);

        $assTs = new Ts();
        $assTs->setRawAttributes(['alias' => 'SK', 'ts_seq' => 2], true);
        $assCoach = new Coach();
        $assCoach->setRawAttributes(['name' => 'Andi'], true);
        $assCoach->setRelation('ts', $assTs);

        $detail1 = new \App\Models\AttendanceDetail();
        $detail1->setRelation('coach', $coach);
        $detail2 = new \App\Models\AttendanceDetail();
        $detail2->setRelation('coach', $assCoach);

        $unit = $this->unit('SMK TI YPML', '08:00', '09:30', $this->pj('Mursalat Asyidiq', '6289654157132'));

        $attendance = new Attendance();
        $attendance->setRawAttributes([
            'old_member_cnt' => 20,
            'new_member_cnt' => 3,
            'attendance_date' => '2026-11-15 00:00:00',
            'attendance_image' => 'https://contoh.test/1.jpg, https://contoh.test/2.jpg',
        ], true);
        $attendance->setRelation('unit', $unit);
        $attendance->setRelation('reportMaker', (object) ['name' => 'Sari']);
        $attendance->setRelation('attendanceDetails', new Collection([$detail1, $detail2]));
        $attendance->created_at = Carbon::parse('2026-11-17 09:00:00');

        $result = (new AttendanceMessageBuilder())->buildAttendanceReport($attendance, true);
        $text = $result['text'];

        $expected = [
            '*Unit*: SMK TI YPML',
            '*PJ Unit*: Mursalat Asyidiq (@6289654157132)',
            '*Tanggal Latihan*: 15/11/2026',
            '*Tanggal Absen*: 17/11/2026',
            '*Keterlambatan Absen*: 2 hari',
            '*Jam Latihan*: 08:00 - 09:30',
            '*Pembuat Laporan*: Sari',
            '*Anggota Lama*: 20',
            '*Anggota Baru*: 3',
            '*Pelatih*:',
            '1. Budi Santoso (SH)',
            '*Asisten Pelatih*:',
            '1. Andi (SK)',
            '*Foto Latihan*:',
        ];

        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $text, "Field hilang: {$needle}");
        }

        $this->assertSame(['6289654157132@c.us'], $result['mentions']);
    }

    public function test_report_without_ts_does_not_error(): void
    {
        $coach = new Coach();
        $coach->setRawAttributes(['name' => 'Tanpa TS'], true);
        $coach->setRelation('ts', null);

        $detail = new \App\Models\AttendanceDetail();
        $detail->setRelation('coach', $coach);

        $attendance = new Attendance();
        $attendance->setRawAttributes(['old_member_cnt' => 0, 'new_member_cnt' => 0], true);
        $unit = new Unit();
        $unit->setRawAttributes(['name' => 'Unit Z'], true);
        $attendance->setRelation('unit', $unit);
        $attendance->setRelation('reportMaker', null);
        $attendance->setRelation('attendanceDetails', new Collection([$detail]));

        $text = (new AttendanceMessageBuilder())->buildAttendanceReport($attendance)['text'];

        $this->assertStringContainsString('Tanpa TS', $text);
        $this->assertStringContainsString('Unknown', $text);
    }

    public function test_photo_links_are_limited_to_four(): void
    {
        $attendance = new Attendance();
        $attendance->setRawAttributes([
            'old_member_cnt' => 0,
            'new_member_cnt' => 0,
            'attendance_image' => implode(',', array_map(fn ($i) => "https://contoh.test/{$i}.jpg", range(1, 7))),
        ], true);
        $attendance->setRelation('unit', $this->unit('Unit A', '08:00', '09:00'));
        $attendance->setRelation('reportMaker', null);
        $attendance->setRelation('attendanceDetails', new Collection());

        $text = (new AttendanceMessageBuilder())->buildAttendanceReport($attendance)['text'];

        $this->assertStringContainsString('[Foto 4](https://contoh.test/4.jpg)', $text);
        $this->assertStringNotContainsString('[Foto 5]', $text);
        $this->assertStringContainsString('... dan 3 foto lainnya', $text);
    }

    // ---------------------------------------------------------------- Telegram chunking
    public function test_long_message_is_chunked_under_limit(): void
    {
        $items = [];
        for ($i = 1; $i <= 200; $i++) {
            $items[] = $this->item($this->unit("Unit dengan nama panjang sekali nomor {$i}", '08:00', '10:00', $this->pj("PJ {$i}", '6281234567890')));
        }

        $builder = new AttendanceMessageBuilder(true, 10);
        $text = $builder->buildConsolidatedReminder($items, true)['text'];
        $chunks = $builder->chunkForTelegram($text, 4000);

        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(4000, mb_strlen($chunk));
        }
        $this->assertSame(trim($text), trim(implode("\n", $chunks)));
    }

    // ---------------------------------------------------------------- Telegram tanpa tag
    public function test_telegram_text_never_contains_at_phone_number(): void
    {
        $items = [$this->item($this->unit('Unit SMK TI YPML', '08:00', '09:00', $this->pj('Mursalat Asyidiq', '6289654157132')))];

        $builder = new AttendanceMessageBuilder(true, 10);

        // Varian WhatsApp: ber-tag dan punya mentions.
        $tagged = $builder->buildConsolidatedReminder($items, true);
        $this->assertStringContainsString('@6289654157132', $tagged['text']);
        $this->assertSame(['6289654157132@c.us'], $tagged['mentions']);

        // Varian Telegram: tidak ada `@<nomor>` dan tanpa mentions.
        $plain = $builder->buildConsolidatedReminder($items, false, false);
        $this->assertStringNotContainsString('@6289654157132', $plain['text']);
        $this->assertSame([], $plain['mentions']);
        $this->assertStringContainsString('PJ: Mursalat Asyidiq', $plain['text']);
    }

    public function test_report_telegram_variant_has_no_at_phone_number(): void
    {
        $unit = $this->unit('Unit SMK TI YPML', '08:00', '10:00', $this->pj('Mursalat Asyidiq', '6289654157132'));

        $attendance = new Attendance();
        $attendance->setRawAttributes([
            'attendance_date' => '2026-11-17',
            'created_at' => '2026-11-17 09:30:00',
            'old_member_cnt' => 5,
            'new_member_cnt' => 1,
        ], true);
        $attendance->setRelation('unit', $unit);
        $attendance->setRelation('reportMaker', null);
        $attendance->setRelation('attendanceDetails', new Collection());

        $builder = new AttendanceMessageBuilder(true, 10);

        $tagged = $builder->buildAttendanceReport($attendance, true);
        $this->assertStringContainsString('(@6289654157132)', $tagged['text']);
        $this->assertSame(['6289654157132@c.us'], $tagged['mentions']);

        $plain = $builder->buildAttendanceReport($attendance, false, false);
        $this->assertStringNotContainsString('@6289654157132', $plain['text']);
        $this->assertSame([], $plain['mentions']);
        $this->assertStringContainsString('*PJ Unit*: Mursalat Asyidiq', $plain['text']);
    }
}

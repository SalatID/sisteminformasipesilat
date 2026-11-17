<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\Reply\ReminderLineParser;
use Tests\TestCase;

/**
 * Fase 1 — membaca teks pesan notifikasi yang dikutip admin.
 *
 * Fixture utama adalah pesan pengingat ASLI 9 baris dari pemilik proses
 * (17 Nov 2026), agar format nyata ikut terjaga oleh test.
 */
class ReminderLineParserTest extends TestCase
{
    private ReminderLineParser $parser;

    private const REAL_REMINDER = <<<'TXT'
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

    private const REAL_REPORT = <<<'TXT'
*Absensi Latihan*

*Unit*: SDN Pegadungan 01
*PJ Unit*: Zaenal Arifin
*Tanggal Latihan*: 17/11/2026
*Tanggal Absen*: 17/11/2026
*Keterlambatan Absen*: 0 hari
*Jam Latihan*: 13:00 - 15:00
*Pembuat Laporan*: Mursalat Asyidiq
*Anggota Lama*: 12
*Anggota Baru*: 3

*Pelatih*:
1. Mursalat Asyidiq

*Asisten Pelatih*:
Tidak ada asisten pelatih
*Foto Latihan*:
1. [Foto 1](https://example.com/foto.jpg)
TXT;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);

        $this->parser = new ReminderLineParser;
    }

    public function test_detects_reminder_and_report_titles(): void
    {
        $this->assertTrue($this->parser->isReminder(self::REAL_REMINDER));
        $this->assertFalse($this->parser->isReport(self::REAL_REMINDER));

        $this->assertTrue($this->parser->isReport(self::REAL_REPORT));
        $this->assertFalse($this->parser->isReminder(self::REAL_REPORT));

        $this->assertTrue($this->parser->isNotification(self::REAL_REMINDER));
        $this->assertTrue($this->parser->isNotification(self::REAL_REPORT));
        $this->assertFalse($this->parser->isNotification('Halo semua, latihan tetap jalan.'));
    }

    public function test_parses_all_nine_real_reminder_lines(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);

        $this->assertCount(9, $entries);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], array_column($entries, 'number'));
    }

    public function test_first_line_maps_to_cengkareng_1_on_17_november_2026(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);
        $first = $entries[0];

        $this->assertSame('SMP/SMA/SMK Cengkareng 1', $first['unit']);
        $this->assertNotNull($first['date']);
        $this->assertSame('2026-11-17', $first['date']->toDateString());
        $this->assertSame('15:30', $first['start']);
        $this->assertSame('17:30', $first['end']);
        $this->assertSame('@ka catur (Catur Wulandari)', $first['pj']);
    }

    public function test_last_line_maps_to_mi_asanusiah_on_26_october_2026(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);
        $last = $entries[8];

        $this->assertSame('MI Assanusiah', $last['unit']);
        $this->assertSame('2026-10-26', $last['date']->toDateString());
        $this->assertSame('12:30', $last['start']);
        $this->assertSame('14:00', $last['end']);
    }

    public function test_unit_names_with_slash_are_kept_intact(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);

        $this->assertSame('SMP/SMA/SMK Cengkareng 1', $entries[5]['unit']);
        $this->assertSame('2026-11-10', $entries[5]['date']->toDateString());
    }

    public function test_pj_name_containing_hyphen_does_not_break_the_line(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);

        // "— PJ: @Bang Apriyanto - SMI Jakbar (Apriyanto)"
        $this->assertSame('@Bang Apriyanto - SMI Jakbar (Apriyanto)', $entries[2]['pj']);
        $this->assertSame('10:00', $entries[2]['start']);
    }

    public function test_summary_line_is_not_parsed_as_a_unit(): void
    {
        $entries = $this->parser->parseReminder(self::REAL_REMINDER);

        foreach ($entries as $entry) {
            $this->assertStringNotContainsString('Total:', $entry['unit']);
            $this->assertStringNotContainsString('Mohon', $entry['unit']);
        }
    }

    public function test_parses_report_unit_and_date(): void
    {
        $report = $this->parser->parseReport(self::REAL_REPORT);

        $this->assertNotNull($report);
        $this->assertSame('SDN Pegadungan 01', $report['unit']);
        $this->assertSame('2026-11-17', $report['date']->toDateString());
    }

    public function test_report_without_unit_returns_null(): void
    {
        $this->assertNull($this->parser->parseReport("*Absensi Latihan*\n\n*Tanggal Latihan*: 17/11/2026"));
    }

    public function test_reminder_with_hyphen_separator_is_supported(): void
    {
        $text = "*Pengingat Absensi Latihan*\n\n1. *SDN A*, Tanggal Latihan : Senin, 17 November 2026, Pukul : 08:00 - 09:00 - PJ: Budi";

        $entries = $this->parser->parseReminder($text);

        $this->assertCount(1, $entries);
        $this->assertSame('08:00', $entries[0]['start']);
        $this->assertSame('09:00', $entries[0]['end']);
    }

    public function test_reminder_without_pj_is_supported(): void
    {
        $text = "*Pengingat Absensi Latihan*\n\n1. *SDN A*, Tanggal Latihan : Senin, 17 November 2026, Pukul : 08:00–09:00 — PJ: -";

        $entries = $this->parser->parseReminder($text);

        $this->assertCount(1, $entries);
        $this->assertSame('-', $entries[0]['pj']);
    }

    public function test_parse_date_supports_indonesian_and_english_months(): void
    {
        foreach (['Selasa, 17 November 2026', '17 November 2026', '17 Nov 2026', '17/11/2026'] as $value) {
            $date = $this->parser->parseDate($value);

            $this->assertNotNull($date, "Tanggal '{$value}' gagal dibaca.");
            $this->assertSame('2026-11-17', $date->toDateString());
        }

        $this->assertSame('2026-03-05', $this->parser->parseDate('Kamis, 05 Maret 2026')->toDateString());
        $this->assertSame('2026-08-01', $this->parser->parseDate('1 August 2026')->toDateString());
    }

    public function test_parse_date_returns_null_for_garbage(): void
    {
        $this->assertNull($this->parser->parseDate(''));
        $this->assertNull($this->parser->parseDate('kemarin'));
        $this->assertNull($this->parser->parseDate('31 Februari 2026'));
    }

    public function test_random_chat_text_is_not_a_notification(): void
    {
        $this->assertSame([], $this->parser->parseReminder('Selamat pagi, latihan hari ini tetap jalan ya.'));
        $this->assertNull($this->parser->parseReport('Selamat pagi semua.'));
    }
}

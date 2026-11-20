<?php

namespace Tests\Unit\Notification\Schedule;

use App\Models\ScheduledNotification;
use App\Services\Notification\Schedule\MessageTemplate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class MessageTemplateTest extends TestCase
{
    private function schedule(): ScheduledNotification
    {
        $schedule = new ScheduledNotification;
        $schedule->setRawAttributes(['name' => 'TC GOR Gondrong'], true);

        return $schedule;
    }

    public function test_render_replaces_all_supported_variables(): void
    {
        $template = new MessageTemplate;

        $text = $template->render(
            'Halo {nama_jadwal}, {hari} {tanggal}. Alasan: {alasan}',
            [
                'nama_jadwal' => 'TC GOR Gondrong',
                'hari' => 'Selasa',
                'tanggal' => '24 November 2026',
                'alasan' => 'hujan deras',
            ],
        );

        $this->assertSame(
            'Halo TC GOR Gondrong, Selasa 24 November 2026. Alasan: hujan deras',
            $text,
        );
    }

    public function test_values_use_indonesian_day_and_month_names(): void
    {
        $template = new MessageTemplate;

        $values = $template->values(
            $this->schedule(),
            CarbonImmutable::parse('2026-11-24 12:00', 'Asia/Jakarta'),
            'hujan deras',
        );

        $this->assertSame('Selasa', $values['hari']);
        $this->assertSame('24 November 2026', $values['tanggal']);
        $this->assertSame('TC GOR Gondrong', $values['nama_jadwal']);
        $this->assertSame('hujan deras', $values['alasan']);
    }

    public function test_unknown_variables_are_detected(): void
    {
        $template = new MessageTemplate;

        $unknown = $template->unknownVariables('Halo {nama_jadwal} {jam} {tanggal}');

        $this->assertSame(['jam'], $unknown);
    }

    public function test_known_variables_are_accepted(): void
    {
        $template = new MessageTemplate;

        $this->assertSame(
            [],
            $template->unknownVariables('{nama_jadwal} {tanggal} {hari} {alasan}'),
        );
    }

    public function test_empty_reason_renders_empty_string(): void
    {
        $template = new MessageTemplate;

        $text = $template->render('Alasan: {alasan}', ['alasan' => null]);

        $this->assertSame('Alasan: ', $text);
    }
}

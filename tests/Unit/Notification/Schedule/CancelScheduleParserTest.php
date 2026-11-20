<?php

namespace Tests\Unit\Notification\Schedule;

use App\Services\Notification\Schedule\CancelScheduleParser;
use PHPUnit\Framework\TestCase;

class CancelScheduleParserTest extends TestCase
{
    private function parse(string $text)
    {
        return (new CancelScheduleParser)->parse($text);
    }

    public function test_has_prefix_is_case_insensitive(): void
    {
        $parser = new CancelScheduleParser;

        $this->assertTrue($parser->hasPrefix('/cancel-schedule'));
        $this->assertTrue($parser->hasPrefix('/CANCEL-SCHEDULE'));
        $this->assertTrue($parser->hasPrefix('/Cancel-Schedule'));
        $this->assertTrue($parser->hasPrefix('/cancel-schedule@628123456789'));
        $this->assertFalse($parser->hasPrefix('/libur'));
        $this->assertFalse($parser->hasPrefix('/cancel-scheduled'));
        $this->assertFalse($parser->hasPrefix('halo'));
    }

    public function test_parse_without_code_uses_reply(): void
    {
        $command = $this->parse("/cancel-schedule\nAlasan : hujan deras");

        $this->assertTrue($command->ok);
        $this->assertNull($command->target);
        $this->assertSame('hujan deras', $command->reason);
    }

    public function test_parse_with_code_and_reason(): void
    {
        $command = $this->parse("/cancel-schedule TCGOR-20261125\nAlasan : hujan deras");

        $this->assertTrue($command->ok);
        $this->assertSame('TCGOR-20261125', $command->target);
        $this->assertSame('hujan deras', $command->reason);
    }

    public function test_single_line_with_code_and_reason(): void
    {
        $command = $this->parse('/cancel-schedule TCGOR-20261125 hujan deras');

        $this->assertTrue($command->ok);
        $this->assertSame('TCGOR-20261125', $command->target);
        $this->assertSame('hujan deras', $command->reason);
    }

    public function test_all_uppercase_variation_is_accepted(): void
    {
        $command = $this->parse("/CANCEL-SCHEDULE\nALASAN : HUJAN DERAS");

        $this->assertTrue($command->ok);
        $this->assertSame('HUJAN DERAS', $command->reason);
    }

    public function test_lowercase_code_is_normalized(): void
    {
        $command = $this->parse("/Cancel-Schedule tcgor-20261125\nalasan: hujan deras");

        $this->assertTrue($command->ok);
        $this->assertSame('TCGOR-20261125', $command->target);
        $this->assertSame('hujan deras', $command->reason);
    }

    public function test_mixed_case_code_is_normalized(): void
    {
        $command = $this->parse("/cancel-schedule TcGor-20261125\nAlasan: hujan");

        $this->assertTrue($command->ok);
        $this->assertSame('TCGOR-20261125', $command->target);
    }

    public function test_missing_reason_is_rejected(): void
    {
        $command = $this->parse('/cancel-schedule TCGOR-20261125');

        $this->assertFalse($command->ok);
        $this->assertSame('missing_reason', $command->error);
    }

    public function test_reason_too_short_is_rejected(): void
    {
        $command = $this->parse("/cancel-schedule\nAlasan : ok");

        $this->assertFalse($command->ok);
        $this->assertSame('reason_too_short', $command->error);
    }

    public function test_reason_too_long_is_rejected(): void
    {
        $command = $this->parse("/cancel-schedule\nAlasan : ".str_repeat('a', 256));

        $this->assertFalse($command->ok);
        $this->assertSame('reason_too_long', $command->error);
    }

    public function test_duplicate_reason_is_rejected(): void
    {
        $command = $this->parse("/cancel-schedule\nAlasan : hujan\nAlasan : banjir");

        $this->assertFalse($command->ok);
        $this->assertSame('duplicate_reason', $command->error);
    }

    public function test_unknown_line_after_reason_is_rejected(): void
    {
        $command = $this->parse("/cancel-schedule\nAlasan : hujan deras\nbaris asing");

        $this->assertFalse($command->ok);
        $this->assertSame('unknown_line', $command->error);
    }

    public function test_reason_line_after_code_without_label_is_accepted(): void
    {
        $command = $this->parse("/cancel-schedule TCGOR-20261125\nhujan deras");

        $this->assertTrue($command->ok);
        $this->assertSame('hujan deras', $command->reason);
    }

    public function test_empty_lines_are_ignored(): void
    {
        $command = $this->parse("\n/cancel-schedule\n\nAlasan :\n\n\nhujan deras di lapangan\n\n");

        $this->assertTrue($command->ok);
        $this->assertSame('hujan deras di lapangan', $command->reason);
    }

    public function test_missing_prefix_is_rejected(): void
    {
        $command = $this->parse('Alasan : hujan deras');

        $this->assertFalse($command->ok);
        $this->assertSame('missing_prefix', $command->error);
    }
}

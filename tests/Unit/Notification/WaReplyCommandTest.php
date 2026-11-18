<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\Reply\WaReplyCommand;
use Tests\TestCase;

/**
 * Fase 1 — parsing balasan admin.
 *
 * Keputusan pemilik proses (18 Nov 2026):
 *   - `nomor` huruf kecil, `Nomor 1` ditolak.
 *   - Status TIDAK case-sensitive.
 *   - Satu balasan = satu nomor.
 */
class WaReplyCommandTest extends TestCase
{
    private WaReplyCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        config(['notifications.reply.reason_max' => 255]);

        $this->command = new WaReplyCommand;
    }

    public function test_parses_the_documented_format_with_prefix(): void
    {
        $result = $this->command->parse("/libur\nNomor 1\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertTrue($result->ok);
        $this->assertSame(1, $result->number);
        $this->assertSame('school_holiday', $result->status);
        $this->assertSame('Diliburkan Sekolah', $result->statusLabel);
        $this->assertSame('Sedang ujian', $result->reason);
        $this->assertFalse($result->truncated);
    }

    public function test_prefix_is_case_insensitive(): void
    {
        foreach (['/libur', '/LIBUR', '/Libur'] as $prefix) {
            $result = $this->command->parse("{$prefix}\nNomor 1\nDiliburkan Sekolah\nAlasan : Sedang ujian");

            $this->assertTrue($result->ok, "Awalan '{$prefix}' seharusnya diterima.");
            $this->assertSame(1, $result->number);
        }
    }

    public function test_prefix_may_share_the_line_with_nomor(): void
    {
        $result = $this->command->parse("/libur Nomor 1\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertTrue($result->ok);
        $this->assertSame(1, $result->number);
    }

    public function test_prefix_with_bot_suffix_is_accepted(): void
    {
        $result = $this->command->parse("/libur@6289654157132\nNomor 2\nDiliburkan Pelatih\nAlasan : Hujan");

        $this->assertTrue($result->ok);
        $this->assertSame(2, $result->number);
    }

    /**
     * Keputusan pemilik proses (17 Nov 2026): awalan /libur WAJIB. Tanpa
     * awalan, format selengkap apa pun dianggap obrolan biasa.
     */
    public function test_format_without_prefix_is_not_a_command(): void
    {
        $result = $this->command->parse("Nomor 3\nDiliburkan Sekolah\nAlasan : Rapat guru");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_prefix', $result->error);
    }

    public function test_has_prefix_only_detects_standalone_libur(): void
    {
        $this->assertTrue($this->command->hasPrefix("/libur\nNomor 1"));
        $this->assertTrue($this->command->hasPrefix('/LIBUR Nomor 1'));
        $this->assertTrue($this->command->hasPrefix("\n\n  /libur  \nNomor 1"));
        $this->assertFalse($this->command->hasPrefix('/liburan Nomor 1'));
        $this->assertFalse($this->command->hasPrefix('tolong /libur nomor 1'));
        $this->assertFalse($this->command->hasPrefix('Nomor 1'));
        $this->assertFalse($this->command->hasPrefix(''));
    }

    public function test_prefix_alone_is_incomplete_not_silent(): void
    {
        $result = $this->command->parse('/libur');

        $this->assertFalse($result->ok);
        $this->assertSame('empty', $result->error);
    }

    public function test_parses_diliburkan_pelatih(): void
    {
        $result = $this->command->parse("/libur\nnomor 12\nDiliburkan Pelatih\nAlasan : Hujan deras");

        $this->assertTrue($result->ok);
        $this->assertSame(12, $result->number);
        $this->assertSame('cancelled', $result->status);
        $this->assertSame('Diliburkan Pelatih', $result->statusLabel);
    }

    /**
     * Inti revisi 18 Nov 2026: status tidak case-sensitive.
     */
    public function test_status_is_case_insensitive(): void
    {
        $variants = [
            'Diliburkan Sekolah',
            'DILIBURKAN SEKOLAH',
            'Diliburkan sekolah',
            'diliburkan sekolah',
            'DiLiBuRkAn SeKoLaH',
            'diliburkan   sekolah',
            '  Diliburkan Sekolah  ',
        ];

        foreach ($variants as $variant) {
            $result = $this->command->parse("/libur\nnomor 1\n{$variant}\nAlasan : Sedang ujian");

            $this->assertTrue($result->ok, "Status '{$variant}' seharusnya diterima.");
            $this->assertSame('school_holiday', $result->status, "Status '{$variant}' salah dipetakan.");
            $this->assertSame('Diliburkan Sekolah', $result->statusLabel);
        }
    }

    public function test_diliburkan_pelatih_is_case_insensitive(): void
    {
        foreach (['DILIBURKAN PELATIH', 'diliburkan pelatih', 'Diliburkan   Pelatih'] as $variant) {
            $result = $this->command->parse("/libur\nnomor 3\n{$variant}\nAlasan : Lapangan banjir");

            $this->assertTrue($result->ok, "Status '{$variant}' seharusnya diterima.");
            $this->assertSame('cancelled', $result->status);
        }
    }

    public function test_status_without_diliburkan_prefix_is_rejected(): void
    {
        foreach (['Libur Sekolah', 'Diliburkan', 'Sekolah Libur', 'Pelatih', 'school holiday'] as $variant) {
            $result = $this->command->parse("/libur\nnomor 1\n{$variant}\nAlasan : Sedang ujian");

            $this->assertFalse($result->ok, "Status '{$variant}' seharusnya ditolak.");
            $this->assertSame('unknown_line', $result->error);
        }
    }

    public function test_nomor_keyword_is_case_insensitive(): void
    {
        foreach (['nomor 1', 'Nomor 1', 'NOMOR 1', 'NoMoR 1'] as $variant) {
            $result = $this->command->parse("/libur\n{$variant}\nDiliburkan Sekolah\nAlasan : Sedang ujian");

            $this->assertTrue($result->ok, "Kata kunci '{$variant}' seharusnya diterima.");
            $this->assertSame(1, $result->number);
        }
    }

    public function test_nomor_without_space_is_rejected(): void
    {
        $result = $this->command->parse("/libur\nNomor1\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('unknown_line', $result->error);
    }

    public function test_reason_label_is_case_insensitive_and_spaces_optional(): void
    {
        foreach (['Alasan : Ujian', 'alasan : Ujian', 'ALASAN:Ujian', 'Alasan:   Ujian'] as $variant) {
            $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\n{$variant}");

            $this->assertTrue($result->ok, "Label '{$variant}' seharusnya diterima.");
            $this->assertSame('Ujian', $result->reason);
        }
    }

    public function test_line_order_is_tolerant_and_blank_lines_ignored(): void
    {
        $result = $this->command->parse("/libur\nAlasan : Sedang ujian\n\nDiliburkan Sekolah\n\nnomor 2\n");

        $this->assertTrue($result->ok);
        $this->assertSame(2, $result->number);
        $this->assertSame('school_holiday', $result->status);
        $this->assertSame('Sedang ujian', $result->reason);
    }

    public function test_crlf_line_endings_are_supported(): void
    {
        $result = $this->command->parse("/libur\r\nnomor 4\r\nDiliburkan Sekolah\r\nAlasan : Ujian semester");

        $this->assertTrue($result->ok);
        $this->assertSame(4, $result->number);
        $this->assertSame('Ujian semester', $result->reason);
    }

    public function test_missing_number(): void
    {
        $result = $this->command->parse("/libur\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_number', $result->error);
    }

    public function test_missing_status(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_status', $result->error);
    }

    public function test_missing_reason(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_reason', $result->error);
    }

    public function test_reason_too_short(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\nAlasan : ab");

        $this->assertFalse($result->ok);
        $this->assertSame('reason_too_short', $result->error);
    }

    public function test_reason_is_truncated_to_column_limit(): void
    {
        $reason = str_repeat('a', 300);
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\nAlasan : {$reason}");

        $this->assertTrue($result->ok);
        $this->assertTrue($result->truncated);
        $this->assertSame(255, mb_strlen((string) $result->reason));
    }

    public function test_number_must_be_positive(): void
    {
        $result = $this->command->parse("/libur\nnomor 0\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('number_not_positive', $result->error);
    }

    public function test_number_not_an_integer_is_rejected(): void
    {
        $result = $this->command->parse("/libur\nnomor satu\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('unknown_line', $result->error);
    }

    public function test_only_one_number_per_reply(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nnomor 2\nDiliburkan Sekolah\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('duplicate_number', $result->error);
    }

    public function test_duplicate_status_is_rejected(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\nDiliburkan Pelatih\nAlasan : Sedang ujian");

        $this->assertFalse($result->ok);
        $this->assertSame('duplicate_status', $result->error);
    }

    public function test_duplicate_reason_is_rejected(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\nAlasan : satu\nAlasan : dua");

        $this->assertFalse($result->ok);
        $this->assertSame('duplicate_reason', $result->error);
    }

    public function test_empty_reply(): void
    {
        $result = $this->command->parse("   \n\n  ");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_prefix', $result->error);
    }

    public function test_prefix_alone_is_reported_as_empty(): void
    {
        $result = $this->command->parse('/libur');

        $this->assertFalse($result->ok);
        $this->assertSame('empty', $result->error);
    }

    public function test_unknown_line_is_reported_with_context(): void
    {
        $result = $this->command->parse("/libur\nnomor 1\nDiliburkan Sekolah\nAlasan : Ujian\nterima kasih");

        $this->assertFalse($result->ok);
        $this->assertSame('unknown_line', $result->error);
        $this->assertSame('terima kasih', $result->context['line']);
    }

    public function test_free_text_is_not_a_command_without_prefix(): void
    {
        $result = $this->command->parse('terima kasih pak');

        $this->assertFalse($result->ok);
        $this->assertSame('missing_prefix', $result->error);
        $this->assertFalse($this->command->hasPrefix('terima kasih pak'));
    }

    public function test_partial_attempt_without_prefix_is_not_a_command(): void
    {
        $result = $this->command->parse("Nomor 1\nDiliburkan Sekolah");

        $this->assertFalse($result->ok);
        $this->assertSame('missing_prefix', $result->error);
    }

    public function test_status_labels_are_exposed_for_help_text(): void
    {
        $this->assertSame(['Diliburkan Sekolah', 'Diliburkan Pelatih'], $this->command->statusLabels());
    }

    // ------------------------------------------------ banyak nomor (multi-unit)

    public function test_parses_multiple_blocks_in_one_message(): void
    {
        $batch = $this->command->parseBatch(
            "/libur\nNomor 1\nDiliburkan Sekolah\nAlasan : ujian\n\nNomor 2\nDiliburkan Pelatih\nAlasan : hujan"
        );

        $this->assertTrue($batch->ok);
        $this->assertSame(2, $batch->count());
        $this->assertSame(1, $batch->items[0]->number);
        $this->assertSame('school_holiday', $batch->items[0]->status);
        $this->assertSame('ujian', $batch->items[0]->reason);
        $this->assertSame(2, $batch->items[1]->number);
        $this->assertSame('cancelled', $batch->items[1]->status);
        $this->assertSame('hujan', $batch->items[1]->reason);
    }

    public function test_blocks_do_not_require_blank_lines(): void
    {
        $batch = $this->command->parseBatch(
            "/libur\nNomor 1\nDiliburkan Sekolah\nAlasan : ujian\nNomor 2\nDiliburkan Pelatih\nAlasan : hujan\nNomor 3\nDiliburkan Sekolah\nAlasan : rapat"
        );

        $this->assertTrue($batch->ok);
        $this->assertSame(3, $batch->count());
        $this->assertSame([1, 2, 3], array_map(fn ($item) => $item->number, $batch->items));
    }

    public function test_duplicate_number_across_blocks_is_rejected(): void
    {
        $batch = $this->command->parseBatch(
            "/libur\nNomor 1\nDiliburkan Sekolah\nAlasan : ujian\n\nNomor 1\nDiliburkan Pelatih\nAlasan : hujan"
        );

        $this->assertFalse($batch->ok);
        $this->assertSame('duplicate_number', $batch->error);
        $this->assertSame(2, $batch->blockNumber());
    }

    public function test_error_in_second_block_reports_block_number(): void
    {
        $batch = $this->command->parseBatch(
            "/libur\nNomor 1\nDiliburkan Sekolah\nAlasan : ujian\n\nNomor 2\nDiliburkan Pelatih"
        );

        $this->assertFalse($batch->ok);
        $this->assertSame('missing_reason', $batch->error);
        $this->assertSame(2, $batch->blockNumber());
        $this->assertSame(2, $batch->blockCount());
    }

    public function test_too_many_blocks_is_rejected(): void
    {
        $text = "/libur\n";

        for ($i = 1; $i <= WaReplyCommand::MAX_BLOCKS + 1; $i++) {
            $text .= "Nomor {$i}\nDiliburkan Sekolah\nAlasan : ujian\n";
        }

        $batch = $this->command->parseBatch($text);

        $this->assertFalse($batch->ok);
        $this->assertSame('too_many_blocks', $batch->error);
    }

    public function test_single_block_batch_has_block_count_one(): void
    {
        $batch = $this->command->parseBatch("/libur\nNomor 1\nDiliburkan Sekolah");

        $this->assertFalse($batch->ok);
        $this->assertSame('missing_reason', $batch->error);
        $this->assertSame(1, $batch->blockCount());
    }
}

<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\Reply\ActorIdentity;
use App\Services\Notification\Reply\HolidayDecision;
use Tests\TestCase;

/**
 * Fase 2 — aturan keputusan (murni, tanpa database) + normalisasi identitas.
 * Referensi: docs/rencana-implementasi-libur-via-balasan-wa.md bab 6.
 */
class HolidayDecisionTest extends TestCase
{
    public function test_creates_when_no_attendance_exists(): void
    {
        $this->assertSame(
            HolidayDecision::CREATE,
            HolidayDecision::decide(null, null, 'school_holiday', 'Ujian', true),
        );
    }

    public function test_rejects_when_unit_is_inactive(): void
    {
        $this->assertSame(
            HolidayDecision::REJECT_INACTIVE_UNIT,
            HolidayDecision::decide(null, null, 'school_holiday', 'Ujian', false),
        );
    }

    public function test_rejects_when_training_report_already_exists(): void
    {
        $this->assertSame(
            HolidayDecision::REJECT_TRAINING,
            HolidayDecision::decide('training', null, 'school_holiday', 'Ujian', true),
        );
    }

    public function test_unchanged_when_status_and_reason_are_identical(): void
    {
        $this->assertSame(
            HolidayDecision::UNCHANGED,
            HolidayDecision::decide('school_holiday', 'Ujian', 'school_holiday', 'Ujian', true),
        );
    }

    public function test_updates_when_status_differs(): void
    {
        $this->assertSame(
            HolidayDecision::UPDATE,
            HolidayDecision::decide('school_holiday', 'Ujian', 'cancelled', 'Ujian', true),
        );
    }

    public function test_updates_when_reason_differs(): void
    {
        $this->assertSame(
            HolidayDecision::UPDATE,
            HolidayDecision::decide('school_holiday', 'Ujian', 'school_holiday', 'Hujan', true),
        );
    }

    public function test_updates_other_holiday_statuses(): void
    {
        $this->assertSame(
            HolidayDecision::UPDATE,
            HolidayDecision::decide('ramadhan_break', 'Ramadhan', 'cancelled', 'Hujan', true),
        );
    }

    /**
     * Whitelist `.env` harus menerima beberapa gaya penulisan nomor.
     */
    public function test_actor_identity_normalizes_phone_formats(): void
    {
        $this->assertSame('6289654157132', ActorIdentity::key('6289654157132@c.us'));
        $this->assertSame('6289654157132', ActorIdentity::key('089654157132'));
        $this->assertSame('6289654157132', ActorIdentity::key('+62 896-5415-7132'));
    }

    public function test_actor_identity_keeps_lid_as_is(): void
    {
        $this->assertSame('123456789@lid', ActorIdentity::key('123456789@lid'));
    }

    public function test_allow_list_accepts_equivalent_formats(): void
    {
        $allowed = ['6289654157132@c.us', '081234567890'];

        $this->assertTrue(ActorIdentity::isAllowed('6289654157132@c.us', $allowed));
        $this->assertTrue(ActorIdentity::isAllowed('6289654157132', $allowed));
        $this->assertTrue(ActorIdentity::isAllowed('6281234567890@c.us', $allowed));
        $this->assertFalse(ActorIdentity::isAllowed('6289999999999@c.us', $allowed));
        $this->assertFalse(ActorIdentity::isAllowed('', $allowed));
        $this->assertFalse(ActorIdentity::isAllowed('6289654157132@c.us', []));
    }

    public function test_mention_text_only_for_phone_numbers(): void
    {
        $this->assertSame('@6289654157132', ActorIdentity::mentionText('6289654157132@c.us'));
        $this->assertSame('@6289654157132', ActorIdentity::mentionText('089654157132'));
        $this->assertNull(ActorIdentity::mentionText('123456789@lid'));
        $this->assertNull(ActorIdentity::mentionText(null));
    }
}

<?php

namespace App\Services\Notification\Reply;

use App\Models\Attendance;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menyimpan status libur ke `attendances` beserta alasannya.
 *
 * Baris yang dibuat/disentuh di sini memakai `is_notif_send = false` supaya
 * tidak pernah memicu notifikasi "Laporan Absensi" (laporan hanya dikirim
 * untuk `attendance_status = 'training'`).
 *
 * Efek samping yang diharapkan: `MissingAttendanceCollector` menganggap unit
 * sudah melapor bila ada baris absensi pada tanggal tersebut, apa pun
 * statusnya — jadi unit yang diliburkan otomatis berhenti muncul di pengingat.
 */
class HolidayFlagService
{
    public function __construct(
        private readonly SystemActor $systemActor,
    ) {}

    public function flag(
        HolidayTarget $target,
        string $status,
        string $reason,
        string $actorId,
    ): FlagOutcome {
        $unit = $target->unit;

        $existing = Attendance::query()
            ->where('unit_id', $unit->id)
            ->whereDate('attendance_date', $target->date->toDateString())
            ->first();

        $decision = HolidayDecision::decide(
            $existing?->attendance_status,
            $existing?->reason,
            $status,
            $reason,
            (bool) $unit->is_active,
        );

        if ($decision === HolidayDecision::REJECT_TRAINING) {
            return FlagOutcome::rejected('reject_training');
        }

        if ($decision === HolidayDecision::REJECT_INACTIVE_UNIT) {
            return FlagOutcome::rejected('reject_inactive_unit');
        }

        if ($decision === HolidayDecision::UNCHANGED) {
            $this->log($target, $status, $actorId, 'unchanged');

            return FlagOutcome::done(HolidayDecision::UNCHANGED);
        }

        $actorUuid = $this->systemActor->id();

        if ($actorUuid === null) {
            Log::channel('cron')->error('Libur via WhatsApp gagal: tidak ada user untuk created_by.');

            return FlagOutcome::rejected('no_system_actor');
        }

        try {
            if ($decision === HolidayDecision::CREATE) {
                Attendance::create([
                    'unit_id' => $unit->id,
                    'attendance_date' => $target->date->toDateString(),
                    'attendance_status' => $status,
                    'reason' => $reason,
                    'new_member_cnt' => 0,
                    'old_member_cnt' => 0,
                    'report_maker_id' => $actorUuid,
                    'created_by' => $actorUuid,
                    'is_notif_send' => false,
                ]);
            } else {
                $existing->attendance_status = $status;
                $existing->reason = $reason;
                $existing->updated_by = $actorUuid;
                $existing->is_notif_send = false;
                $existing->save();
            }
        } catch (Throwable $e) {
            Log::channel('cron')->error('Libur via WhatsApp gagal menyimpan', [
                'unit_id' => $unit->id,
                'date' => $target->date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return FlagOutcome::rejected('write_failed');
        }

        $this->log($target, $status, $actorId, $decision);

        return FlagOutcome::done($decision);
    }

    private function log(HolidayTarget $target, string $status, string $actorId, string $action): void
    {
        Log::channel('cron')->info('Libur ditandai via WhatsApp', [
            'action' => $action,
            'unit_id' => $target->unit->id,
            'unit' => $target->unitName(),
            'date' => $target->date->toDateString(),
            'status' => $status,
            'by' => $actorId,
            'line' => $target->number,
        ]);
    }
}

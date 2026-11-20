<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mencari run yang dimaksud lalu membatalkannya (dan mengirim pengumuman
 * libur). Dipakai oleh webhook `/cancel-schedule` dan aksi UI.
 *
 * Resolusi (bagian 9.3 rencana):
 *   1. kode eksplisit  -> runs.public_code (case-insensitive),
 *      kode tanpa tanggal -> 3 kemunculan terdekat jadwal tersebut,
 *   2. tanpa kode      -> replyTo.id == runs.provider_message_id
 *      (fallback: cocokkan message-id saja untuk antisipasi `true_`/`false_`),
 *   3. kode + reply menunjuk run berbeda -> tolak.
 */
class CancelScheduleService
{
    public function __construct(
        private readonly HolidayAnnouncer $announcer,
    ) {}

    public function resolve(?string $code, ?string $replyToId): CancelResolution
    {
        $byCode = null;

        if ($code !== null && $code !== '') {
            $byCode = $this->findByCode($code);

            if ($byCode instanceof CancelResolution) {
                return $byCode;
            }
        }

        $byReply = null;

        if ($replyToId !== null && $replyToId !== '') {
            $byReply = $this->findByReply($replyToId);
        }

        if ($byCode instanceof ScheduledNotificationRun && $byReply instanceof ScheduledNotificationRun) {
            return $byCode->id === $byReply->id
                ? CancelResolution::ok($byCode)
                : CancelResolution::mismatch();
        }

        if ($byCode instanceof ScheduledNotificationRun) {
            return CancelResolution::ok($byCode);
        }

        if ($byReply instanceof ScheduledNotificationRun) {
            return CancelResolution::ok($byReply);
        }

        // Ada kode, tetapi tidak ada run/kode yang cocok.
        if ($code !== null && $code !== '') {
            return CancelResolution::notFound();
        }

        return CancelResolution::missingQuote();
    }

    private function findByCode(string $code): ScheduledNotificationRun|CancelResolution
    {
        $code = mb_strtoupper(trim($code), 'UTF-8');

        $run = ScheduledNotificationRun::query()
            ->with('schedule')
            ->whereRaw('UPPER(public_code) = ?', [$code])
            ->first();

        if ($run) {
            return $run;
        }

        // Kode hanya nama jadwal: tampilkan kemunculan terdekat.
        $schedule = ScheduledNotification::query()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if (! $schedule) {
            return CancelResolution::notFound();
        }

        $limit = max(1, (int) config('notifications.cancel_schedule.schedule_only_preview', 3));

        $runs = ScheduledNotificationRun::query()
            ->with('schedule')
            ->where('scheduled_notification_id', $schedule->id)
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->where('occurrence_at', '>=', $this->todayStart())
            ->orderBy('occurrence_at')
            ->limit($limit)
            ->get()
            ->all();

        return CancelResolution::scheduleOnly($runs);
    }

    private function findByReply(string $replyToId): ?ScheduledNotificationRun
    {
        $replyToId = trim($replyToId);

        if ($replyToId === '') {
            return null;
        }

        $run = ScheduledNotificationRun::query()
            ->with('schedule')
            ->where('provider_message_id', $replyToId)
            ->first();

        if ($run) {
            return $run;
        }

        // WAHA kadang melaporkan id dengan prefix true_/false_ yang berbeda.
        // Cocokkan sufiks secara PERSIS (bukan LIKE: id memuat karakter `_`
        // yang menjadi wildcard pada LIKE, sehingga bisa mencocokkan run lain).
        $suffix = preg_replace('/^(true|false)_/i', '', $replyToId);

        if ($suffix === '' || $suffix === $replyToId || strlen($suffix) < 16) {
            return null;
        }

        return ScheduledNotificationRun::query()
            ->with('schedule')
            ->where(function ($query) use ($suffix) {
                $query->where('provider_message_id', $suffix)
                    ->orWhere('provider_message_id', 'true_'.$suffix)
                    ->orWhere('provider_message_id', 'false_'.$suffix);
            })
            ->first();
    }

    /**
     * Validasi tambahan setelah run ditemukan.
     */
    public function validate(CancelResolution $resolution): CancelResolution
    {
        if (! $resolution->isOk()) {
            return $resolution;
        }

        $run = $resolution->run;
        $schedule = $run->schedule;

        if ($run->status === ScheduledNotificationRun::STATUS_CANCELLED) {
            return CancelResolution::already($run);
        }

        if (! $schedule || $schedule->trashed() || ! $schedule->is_active) {
            return CancelResolution::inactive($run);
        }

        if ($run->occurrence_at->lt($this->todayStart())) {
            return CancelResolution::past($run);
        }

        return $resolution;
    }

    public function cancel(
        ScheduledNotificationRun $run,
        string $reason,
        string $source = 'webhook',
        ?string $actor = null,
    ): CancelOutcome {
        $run = DB::transaction(function () use ($run, $reason, $source, $actor) {
            $run = ScheduledNotificationRun::query()->lockForUpdate()->find($run->id);

            if ($run->status === ScheduledNotificationRun::STATUS_CANCELLED) {
                return $run;
            }

            $run->forceFill([
                'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => mb_substr($reason, 0, 255),
                'cancel_source' => $source,
                'cancelled_by' => $actor,
            ])->save();

            return $run;
        });

        $announcementSent = false;

        // Kirim pengumuman libur sekali saja (idempoten).
        if ($run->announced_at === null) {
            $result = $this->announcer->announce($run, $reason);
            $announcementSent = $result->success;
        }

        Log::channel('cron')->info('Jadwal dibatalkan', [
            'run' => $run->public_code,
            'source' => $source,
            'actor' => $actor,
            'reason' => $reason,
            'announcement_sent' => $announcementSent,
        ]);

        return CancelOutcome::cancelled($run->refresh(), $announcementSent);
    }

    /**
     * Batalkan lewat UI (tanpa perlu parser).
     */
    public function cancelById(string $runId, string $reason, ?string $actor = null): CancelOutcome
    {
        $run = ScheduledNotificationRun::with('schedule')->find($runId);

        if (! $run) {
            return CancelOutcome::fromResolution(CancelResolution::notFound());
        }

        $resolution = $this->validate(CancelResolution::ok($run));

        if (! $resolution->isOk()) {
            return CancelOutcome::fromResolution($resolution);
        }

        return $this->cancel($run, $reason, 'web', $actor);
    }

    private function todayStart(): CarbonImmutable
    {
        $timezone = (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));

        return CarbonImmutable::now($timezone)->startOfDay();
    }
}

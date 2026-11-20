<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotificationRun;
use App\Services\Notification\Channels\WhatsAppChannel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengirim run yang jatuh tempo.
 *
 * Aturan utama:
 *   - hanya run pending yang belum melewati grace window,
 *   - run yang lebih tua dari grace menjadi `missed` (tidak dikirim telat),
 *   - retry maksimal N kali dengan jeda,
 *   - jadwal nonaktif/terhapus langsung membatalkan run,
 *   - lock global + lock per run mencegah kiriman ganda.
 */
class ScheduleRunner
{
    public function __construct(
        private readonly WhatsAppChannel $channel,
        private readonly MessageTemplate $template,
        private readonly HolidayAnnouncer $announcer,
    ) {}

    /**
     * @param  array{dry_run?: bool, limit?: int|null, schedule_id?: string|null, force?: bool}  $options
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $timezone = (string) config('notifications.schedules.timezone', config('app.timezone', 'Asia/Jakarta'));
        $now = CarbonImmutable::now($timezone);

        $report = [
            'enabled' => (bool) config('notifications.schedules.enabled', true),
            'locked' => false,
            'dry_run' => (bool) ($options['dry_run'] ?? false),
            'due' => 0,
            'sent' => 0,
            'retried' => 0,
            'failed' => 0,
            'missed' => 0,
            'cancelled' => 0,
            'announced' => 0,
            'items' => [],
        ];

        if (! $report['enabled']) {
            return $report;
        }

        $lock = Cache::lock('scheduled-notification:runner', 300);

        if (! $lock->get()) {
            $report['locked'] = true;

            return $report;
        }

        try {
            $graceMinutes = max(1, (int) config('notifications.schedules.grace_minutes', 30));
            $graceStart = $now->subMinutes($graceMinutes);
            $force = (bool) ($options['force'] ?? false);
            $scheduleId = $options['schedule_id'] ?? null;

            if (! $force) {
                $report['missed'] = $this->markMissed($graceStart, $scheduleId, $now);
            }

            $limit = max(1, (int) ($options['limit'] ?? config('notifications.schedules.runner_limit', 100)));

            $query = ScheduledNotificationRun::query()
                ->with(['schedule' => fn ($query) => $query->withTrashed()])
                ->where('status', ScheduledNotificationRun::STATUS_PENDING)
                ->where('occurrence_at', '<=', $now->format('Y-m-d H:i:s'))
                ->where(function ($query) use ($now) {
                    $query->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', $now->format('Y-m-d H:i:s'));
                })
                ->orderBy('occurrence_at');

            if (! $force) {
                $query->where('occurrence_at', '>=', $graceStart->format('Y-m-d H:i:s'));
            }

            if (is_string($scheduleId) && $scheduleId !== '') {
                $query->where('scheduled_notification_id', $scheduleId);
            }

            $runs = $query->limit($limit)->get();
            $report['due'] = $runs->count();

            foreach ($runs as $run) {
                $this->processRun($run, $now, $report, (bool) $report['dry_run'], $graceMinutes);
            }

            if (! $report['dry_run']) {
                $report['announced'] = $this->announcePending($now);
            }

            Log::channel('cron')->info('Runner notifikasi terjadwal selesai', $report);
        } finally {
            $lock->release();
        }

        return $report;
    }

    private function markMissed(CarbonImmutable $graceStart, ?string $scheduleId, CarbonImmutable $now): int
    {
        $query = ScheduledNotificationRun::query()
            ->where('status', ScheduledNotificationRun::STATUS_PENDING)
            ->where('occurrence_at', '<', $graceStart->format('Y-m-d H:i:s'));

        if (is_string($scheduleId) && $scheduleId !== '') {
            $query->where('scheduled_notification_id', $scheduleId);
        }

        $count = (clone $query)->count();

        if ($count > 0) {
            $query->update([
                'status' => ScheduledNotificationRun::STATUS_MISSED,
                'last_error' => 'Terlewat: melewati grace window '.config('notifications.schedules.grace_minutes', 30).' menit.',
                'updated_at' => $now,
            ]);

            Log::channel('cron')->warning('Run notifikasi terjadwal terlewat', [
                'count' => $count,
                'grace_minutes' => config('notifications.schedules.grace_minutes', 30),
            ]);
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function processRun(
        ScheduledNotificationRun $run,
        CarbonImmutable $now,
        array &$report,
        bool $dryRun,
        int $graceMinutes,
    ): void {
        $lock = Cache::lock('scheduled-notification:run:'.$run->id, 120);

        if (! $lock->get()) {
            return;
        }

        try {
            $run->refresh();

            if (! $run->isPending()) {
                return;
            }

            $schedule = $run->schedule;

            if (! $schedule || $schedule->trashed() || ! $schedule->is_active) {
                $run->update([
                    'status' => ScheduledNotificationRun::STATUS_CANCELLED,
                    'cancelled_at' => $now,
                    'cancel_reason' => 'jadwal dinonaktifkan',
                    'cancel_source' => 'system',
                ]);

                $report['cancelled']++;

                return;
            }

            $text = $this->template->render(
                (string) $schedule->message,
                $this->template->values($schedule, $run->occurrence_at),
            );

            $targets = $schedule->chatIds();

            // Percobaan sebelumnya sebagian berhasil: hanya kirim ke target
            // yang masih gagal agar chat lain tidak menerima pesan dobel.
            $pendingTargets = array_values(array_filter((array) $run->error_targets));

            if ($pendingTargets !== []) {
                $targets = array_values(array_intersect($targets, $pendingTargets));
            }

            if ($targets === []) {
                $run->update([
                    'status' => ScheduledNotificationRun::STATUS_FAILED,
                    'last_error' => 'Jadwal tidak memiliki chat target.',
                    'next_attempt_at' => null,
                ]);

                $report['failed']++;

                Log::channel('cron')->error('Notifikasi terjadwal tidak punya chat target', [
                    'code' => $run->public_code,
                ]);

                return;
            }

            if ($dryRun) {
                foreach ($targets as $target) {
                    $report['items'][] = [
                        'code' => $run->public_code,
                        'target' => $target,
                        'occurrence_at' => $run->occurrence_at->format('Y-m-d H:i'),
                    ];
                }

                return;
            }

            [$successes, $failures] = $this->deliver($targets, $text, $run->public_code);

            if ($successes !== [] && $failures === []) {
                $run->update([
                    'status' => ScheduledNotificationRun::STATUS_SENT,
                    'message_snapshot' => $text,
                    'sent_at' => $now,
                    'provider_message_id' => $successes[0]['external_id'],
                    'last_error' => null,
                    'next_attempt_at' => null,
                    // Semua target sudah menerima pesan: catatan partial
                    // failure dari percobaan sebelumnya tidak relevan lagi.
                    'sent_targets' => null,
                    'error_targets' => null,
                ]);

                $report['sent']++;

                Log::channel('cron')->info('Notifikasi terjadwal terkirim', [
                    'code' => $run->public_code,
                    'targets' => array_column($successes, 'target'),
                ]);

                return;
            }

            // Sebagian target gagal: run ditandai GAGAL agar terlihat di UI,
            // tetapi target yang sudah berhasil TIDAK dikirimi lagi saat
            // "Kirim ulang" (lihat retryRun) untuk menghindari pesan dobel.
            $errorParts = array_map(
                fn (array $failure) => $failure['target'].': '.$failure['error'],
                $failures,
            );

            $error = implode(' | ', $errorParts);

            if ($successes !== []) {
                $error = 'Sebagian gagal ('.count($failures).'/'.count($targets).' target). '.$error;
            }

            $attempts = (int) $run->attempts + 1;
            $maxAttempts = max(1, (int) config('notifications.schedules.max_attempts', 3));
            $retryDelay = max(1, (int) config('notifications.schedules.retry_delay_minutes', 5));

            // Target yang sudah menerima pesan dicatat agar tidak dikirim dobel.
            // `sent_targets` menggabungkan hasil percobaan sebelumnya.
            $previousSent = array_values(array_filter((array) $run->sent_targets));
            $sentTargets = array_values(array_unique(array_merge($previousSent, array_column($successes, 'target'))));
            $failedTargets = count($failures) > 0
                ? array_column($failures, 'target')
                : $targets;

            $run->update([
                'message_snapshot' => $text,
                'error_targets' => $failedTargets,
                'sent_targets' => $sentTargets === [] ? null : $sentTargets,
                'provider_message_id' => $successes[0]['external_id'] ?? $run->provider_message_id,
            ]);

            if ($attempts >= $maxAttempts) {
                $run->update([
                    'status' => ScheduledNotificationRun::STATUS_FAILED,
                    'attempts' => $attempts,
                    'last_error' => mb_substr($error, 0, 1000),
                    'next_attempt_at' => null,
                ]);

                $report['failed']++;
            } else {
                $run->update([
                    'status' => ScheduledNotificationRun::STATUS_PENDING,
                    'attempts' => $attempts,
                    'last_error' => mb_substr($error, 0, 1000),
                    'next_attempt_at' => $now->addMinutes($retryDelay),
                ]);

                $report['retried']++;
            }

            Log::channel('cron')->error('Notifikasi terjadwal gagal dikirim', [
                'code' => $run->public_code,
                'attempts' => $attempts,
                'error' => $error,
                'failed_targets' => $failedTargets,
                'sent_targets' => $sentTargets,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Kirim satu teks ke semua target. Kegagalan satu target tidak
     * menghentikan target lain.
     *
     * @param  array<int, string>  $targets
     * @return array{0: array<int, array{target: string, external_id: string}>, 1: array<int, array{target: string, error: string}>}
     */
    private function deliver(array $targets, string $text, ?string $code = null): array
    {
        $successes = [];
        $failures = [];

        foreach (array_values(array_unique($targets)) as $chatId) {
            try {
                $result = $this->channel->sendTo($chatId, $text);
            } catch (Throwable $e) {
                Log::channel('cron')->error('Notifikasi terjadwal gagal dikirim (exception)', [
                    'code' => $code,
                    'target' => $chatId,
                    'error' => $e->getMessage(),
                ]);

                $failures[] = ['target' => $chatId, 'error' => $e->getMessage()];

                continue;
            }

            if (! $result->success) {
                $failures[] = ['target' => $chatId, 'error' => (string) ($result->error ?? 'tanpa keterangan')];

                continue;
            }

            $successes[] = ['target' => $chatId, 'external_id' => (string) $result->externalId];
        }

        return [$successes, $failures];
    }

    /**
     * Jaring pengaman: kirim pengumuman libur yang sebelumnya gagal.
     * Dibatasi 24 jam terakhir agar run lama tidak tiba-tiba diumumkan.
     */
    private function announcePending(CarbonImmutable $now): int
    {
        $maxAttempts = max(1, (int) config('notifications.schedules.max_attempts', 3));

        $runs = ScheduledNotificationRun::query()
            ->with(['schedule' => fn ($query) => $query->withTrashed()])
            ->where('status', ScheduledNotificationRun::STATUS_CANCELLED)
            ->whereNull('announced_at')
            ->whereIn('cancel_source', ['webhook', 'web'])
            ->whereNotNull('cancelled_at')
            ->where('cancelled_at', '>=', $now->subDay()->format('Y-m-d H:i:s'))
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('cancelled_at')
            ->limit(20)
            ->get();

        $sent = 0;

        foreach ($runs as $run) {
            $result = $this->announcer->announce($run);

            if ($result->success) {
                $sent++;

                continue;
            }

            $run->increment('attempts');
            $run->update(['last_error' => mb_substr((string) $result->error, 0, 1000)]);
        }

        return $sent;
    }
}

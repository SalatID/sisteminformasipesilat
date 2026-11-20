<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use App\Services\Notification\ChannelResult;
use App\Services\Notification\Channels\WhatsAppChannel;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengirim pengumuman libur untuk satu run.
 *
 * Dipakai oleh pembatalan via webhook, aksi UI, dan runner (sebagai jaring
 * pengaman bila pengumuman sebelumnya gagal). Satu jadwal bisa punya banyak
 * chat target: pesan dikirim ke semuanya, dan run baru dianggap terumumkan
 * bila MINIMAL satu target berhasil (sisanya dicatat di log).
 */
class HolidayAnnouncer
{
    public function __construct(
        private readonly WhatsAppChannel $channel,
        private readonly MessageTemplate $template,
    ) {}

    public function announce(ScheduledNotificationRun $run, ?string $reason = null): ChannelResult
    {
        $schedule = $run->schedule()->withTrashed()->first();

        if (! $schedule) {
            return ChannelResult::failure('whatsapp', null, 'Jadwal tidak ditemukan.');
        }

        $reason = (string) ($reason ?? $run->cancel_reason ?? '');
        $message = $this->template->holidayMessage($schedule);
        $text = $this->template->render(
            $message,
            $this->template->values($schedule, $run->occurrence_at, $reason),
        );

        $targets = $schedule->chatIds();

        if ($targets === []) {
            return ChannelResult::failure('whatsapp', null, 'Jadwal tidak memiliki chat target.');
        }

        [$successes, $failures] = $this->deliver($targets, $text, $run->public_code);

        if ($successes === []) {
            $error = implode(' | ', array_map(
                fn (array $failure) => $failure['target'].': '.$failure['error'],
                $failures,
            ));

            Log::channel('cron')->error('Pengumuman libur gagal dikirim ke semua target', [
                'run' => $run->public_code,
                'error' => $error,
            ]);

            return ChannelResult::failure('whatsapp', null, $error);
        }

        // External id target pertama dipakai untuk kompatibilitas kolom lama;
        // seluruh id tersimpan di log dan tabel target run.
        $primary = $successes[0];

        $run->forceFill([
            'announced_at' => now(),
            'announcement_message_id' => $primary['external_id'],
        ])->save();

        Log::channel('cron')->info('Pengumuman libur terkirim', [
            'run' => $run->public_code,
            'targets' => array_column($successes, 'target'),
            'failed_targets' => array_column($failures, 'target'),
        ]);

        return ChannelResult::success('whatsapp', null, $primary['external_id'] ?? '');
    }

    /**
     * Kirim pesan percobaan ke semua chat jadwal (tanpa mengubah run apa pun).
     */
    public function sendTest(ScheduledNotification $schedule, string $text): ChannelResult
    {
        $targets = $schedule->chatIds();

        if ($targets === []) {
            return ChannelResult::failure('whatsapp', null, 'Jadwal tidak memiliki chat target.');
        }

        [$successes, $failures] = $this->deliver($targets, $text, $schedule->code);

        Log::channel('cron')->info('Pesan percobaan jadwal dikirim', [
            'schedule' => $schedule->code,
            'targets' => array_column($successes, 'target'),
            'failed_targets' => array_column($failures, 'target'),
        ]);

        if ($successes === []) {
            $error = implode(' | ', array_map(
                fn (array $failure) => $failure['target'].': '.$failure['error'],
                $failures,
            ));

            return ChannelResult::failure('whatsapp', null, $error);
        }

        return ChannelResult::success('whatsapp', null, $successes[0]['external_id'] ?? '');
    }

    /**
     * Kirim satu teks ke semua target. Kegagalan satu target tidak
     * menghentikan pengiriman ke target lain.
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
                Log::channel('cron')->error('Pengumuman jadwal gagal dikirim (exception)', [
                    'code' => $code,
                    'target' => $chatId,
                    'error' => $e->getMessage(),
                ]);

                $failures[] = ['target' => $chatId, 'error' => $e->getMessage()];

                continue;
            }

            if (! $result->success) {
                Log::channel('cron')->error('Pengumuman jadwal gagal dikirim', [
                    'code' => $code,
                    'target' => $chatId,
                    'error' => $result->error,
                ]);

                $failures[] = ['target' => $chatId, 'error' => (string) ($result->error ?? 'tanpa keterangan')];

                continue;
            }

            $successes[] = ['target' => $chatId, 'external_id' => (string) $result->externalId];
        }

        return [$successes, $failures];
    }
}

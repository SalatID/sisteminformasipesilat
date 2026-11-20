<?php

namespace App\Console\Commands;

use App\Services\Notification\Schedule\ScheduleRunner;
use Illuminate\Console\Command;

class RunScheduledNotificationsCommand extends Command
{
    protected $signature = 'notification:run
                            {--dry-run : Tampilkan yang akan dikirim tanpa memanggil WAHA}
                            {--limit= : Batasi jumlah run per eksekusi}
                            {--id= : Hanya proses satu jadwal (UUID)}
                            {--force : Abaikan grace window (untuk uji)}';

    protected $description = 'Kirim run notifikasi terjadwal yang sudah jatuh tempo';

    public function handle(ScheduleRunner $runner): int
    {
        $limit = $this->option('limit');
        $id = $this->option('id');

        $report = $runner->run([
            'dry_run' => (bool) $this->option('dry-run'),
            'limit' => $limit !== null && $limit !== '' ? (int) $limit : null,
            'schedule_id' => is_string($id) && $id !== '' ? $id : null,
            'force' => (bool) $this->option('force'),
        ]);

        if (! $report['enabled']) {
            $this->warn('Fitur notifikasi terjadwal sedang nonaktif (NOTIF_SCHEDULE_ENABLED=false).');

            return Command::SUCCESS;
        }

        if ($report['locked']) {
            $this->warn('Runner lain masih berjalan; siklus ini dilewati.');

            return Command::SUCCESS;
        }

        foreach ($report['items'] as $item) {
            $this->line(sprintf(
                '[dry-run] %s -> %s (%s)',
                $item['code'],
                $item['target'],
                $item['occurrence_at'],
            ));
        }

        $this->table(
            ['Due', 'Terkirim', 'Retry', 'Gagal', 'Terlewat', 'Dibatalkan', 'Pengumuman'],
            [[
                $report['due'],
                $report['sent'],
                $report['retried'],
                $report['failed'],
                $report['missed'],
                $report['cancelled'],
                $report['announced'],
            ]],
        );

        return Command::SUCCESS;
    }
}

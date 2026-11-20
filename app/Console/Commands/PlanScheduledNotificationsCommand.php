<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Services\Notification\Schedule\SchedulePlanner;
use Illuminate\Console\Command;

class PlanScheduledNotificationsCommand extends Command
{
    protected $signature = 'notification:plan
                            {--id= : Hanya rencanakan satu jadwal (UUID)}
                            {--days= : Horizon hari (default dari config)}';

    protected $description = 'Buat/segarkan run notifikasi terjadwal untuk horizon tertentu';

    public function handle(SchedulePlanner $planner): int
    {
        $days = $this->option('days');

        if ($days !== null && $days !== '') {
            config(['notifications.schedules.horizon_days' => max(1, (int) $days)]);
        }

        $cancelledOrphans = $planner->cancelOrphanRuns();
        $this->info("Run pending milik jadwal nonaktif dibatalkan: {$cancelledOrphans}");

        $query = ScheduledNotification::query()->where('is_active', true);

        $id = $this->option('id');

        if (is_string($id) && $id !== '') {
            $query->where('id', $id);
        }

        $schedules = $query->orderBy('name')->get();

        $created = 0;

        foreach ($schedules as $schedule) {
            $count = $planner->plan($schedule);
            $created += $count;
            $this->line(sprintf(
                '- %s (%s): %d run baru',
                $schedule->name,
                $schedule->code,
                $count,
            ));
        }

        $this->info(sprintf(
            'Selesai: %d jadwal, %d run baru, horizon %d hari.',
            $schedules->count(),
            $created,
            (int) config('notifications.schedules.horizon_days', 60),
        ));

        return Command::SUCCESS;
    }
}

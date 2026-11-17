<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Unit;
use App\Services\Notification\AttendanceMessageBuilder;
use App\Services\Notification\AttendanceReportDispatcher;
use App\Services\Notification\NotificationManager;
use App\Services\Notification\NotificationRunGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAttendanceNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:notify
                            {attendance_id? : ID attendance yang ingin dikirim ulang (melewati guard)}
                            {--type=all : Jenis notifikasi: all|report|reminder}
                            {--dry-run : Tampilkan pesan tanpa mengirim}
                            {--force : Abaikan guard interval maupun jendela jam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi absensi (Telegram & WhatsApp) berdasarkan config/notifications.php';

    private bool $sentAnything = false;

    public function handle(
        NotificationManager $manager,
        AttendanceReportDispatcher $reportDispatcher,
        NotificationRunGuard $guard,
    ): int {
        $attendanceId = $this->argument('attendance_id');
        $type = strtolower((string) $this->option('type'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (!in_array($type, ['all', 'report', 'reminder'], true)) {
            $this->error("Opsi --type tidak valid: {$type}. Gunakan all|report|reminder.");

            return Command::FAILURE;
        }

        if ($manager->activeProviders() === []) {
            $this->warn('Tidak ada provider notifikasi aktif (NOTIF_PROVIDERS kosong). Tidak ada yang dikirim.');

            return Command::SUCCESS;
        }

        $this->info('Provider aktif: ' . implode(', ', $manager->activeProviders()) . ($dryRun ? ' [DRY-RUN]' : ''));

        // Resend manual: selalu jalan, tidak memakai guard interval.
        if ($attendanceId) {
            return $this->sendSingle($attendanceId, $reportDispatcher, $dryRun);
        }

        $outcome = $guard->runOnce(function () use ($type, $dryRun, $manager, $reportDispatcher) {
            $result = ['reports' => 0, 'reminders' => 0];

            if ($type === 'all' || $type === 'report') {
                $result['reports'] = $this->sendReports($reportDispatcher, $dryRun);
            }

            if ($type === 'all' || $type === 'reminder') {
                $result['reminders'] = $this->sendConsolidatedReminder($manager, $dryRun);
            }

            return $result;
        }, $force);

        if (!$outcome['executed']) {
            $reason = match ($outcome['reason']) {
                'outside_window' => 'di luar jendela pengiriman (07:00–21:59)',
                'interval' => 'guard interval: sisa ' . $guard->minutesUntilNextRun() . ' menit',
                'locked' => 'proses lain sedang berjalan',
                default => (string) $outcome['reason'],
            };

            $message = "Siklus notifikasi dilewati ({$reason}).";
            $this->warn($message);
            Log::channel('cron')->info("attendance:notify skipped: {$reason}");

            return Command::SUCCESS;
        }

        $this->info("Selesai: {$outcome['result']['reports']} laporan, {$outcome['result']['reminders']} pesan pengingat.");
        Log::channel('cron')->info('attendance:notify selesai', $outcome['result']);

        if ($this->shouldSendEndMarker($dryRun)) {
            $this->sendEndMarker($manager);
        }

        return Command::SUCCESS;
    }

    private function sendSingle(string $attendanceId, AttendanceReportDispatcher $dispatcher, bool $dryRun): int
    {
        $attendance = Attendance::with(['unit.pj', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->where('id', $attendanceId)
            ->whereHas('unit', fn ($q) => $q->where('is_active', true))
            ->first();

        if (!$attendance) {
            $this->error("Attendance ID {$attendanceId} tidak ditemukan atau unit tidak aktif.");
            Log::channel('cron')->error("attendance:notify resend gagal: ID {$attendanceId} tidak ditemukan.");

            return Command::FAILURE;
        }

        if ($dryRun) {
            $report = AttendanceMessageBuilder::fromConfig()->buildAttendanceReport($attendance, true);
            $plainReport = AttendanceMessageBuilder::fromConfig()->buildAttendanceReport($attendance, false, false);
            $this->line('[WhatsApp — ber-tag]');
            $this->line($report['text']);
            $this->line('Mentions: ' . json_encode($report['mentions']));
            $this->line('[Telegram — tanpa tag]');
            $this->line($plainReport['text']);

            return Command::SUCCESS;
        }

        $result = $dispatcher->dispatch($attendance, true);

        foreach ($result['results'] as $channelResult) {
            $line = sprintf(
                '  %-9s : %s%s',
                $channelResult->provider,
                $channelResult->success ? 'OK' : 'GAGAL',
                $channelResult->error ? ' — ' . $channelResult->error : ''
            );
            $channelResult->success ? $this->line($line) : $this->error($line);
        }

        $this->sentAnything = $result['success'];

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }

    private function sendReports(AttendanceReportDispatcher $dispatcher, bool $dryRun): int
    {
        $attendances = Attendance::with(['unit.pj', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->where('attendance_status', 'training')
            ->whereHas('unit', fn ($q) => $q->where('is_active', true))
            ->where('is_notif_send', false)
            ->get();

        if ($attendances->isEmpty()) {
            $this->line('Tidak ada laporan absensi yang perlu dikirim.');

            return 0;
        }

        $count = 0;
        $builder = AttendanceMessageBuilder::fromConfig();

        foreach ($attendances as $attendance) {
            if ($dryRun) {
                $report = $builder->buildAttendanceReport($attendance, true);
                $plainReport = $builder->buildAttendanceReport($attendance, false, false);
                $this->line(str_repeat('-', 40));
                $this->line('[WhatsApp — ber-tag]');
                $this->line($report['text']);
                $this->line('[Telegram — tanpa tag]');
                $this->line($plainReport['text']);
                $count++;
                continue;
            }

            if ($dispatcher->pendingProviders($attendance) === []) {
                continue;
            }

            $result = $dispatcher->dispatch($attendance);

            if ($result['success']) {
                $count++;
                $this->line("Laporan terkirim: {$attendance->id}");
            } else {
                $this->warn("Laporan gagal sebagian: {$attendance->id} — akan di-retry provider yang belum sukses.");
            }

            $this->pace();
        }

        $this->sentAnything = $this->sentAnything || $count > 0;

        return $count;
    }

    private function sendConsolidatedReminder(NotificationManager $manager, bool $dryRun): int
    {
        $items = $this->collectReminderItems();

        if ($items === []) {
            $this->line('Tidak ada unit yang perlu diingatkan.');

            return 0;
        }

        $builder = AttendanceMessageBuilder::fromConfig();
        $payload = $builder->buildConsolidatedReminder($items, $manager->supportsMentions());

        if ($payload === null) {
            return 0;
        }

        // Tag `@<nomor>` hanya untuk channel pendukung mention (WhatsApp);
        // Telegram menerima versi teks tanpa tag sama sekali.
        $taggedText = $payload['text'];
        $plainPayload = $builder->buildConsolidatedReminder($items, false, false);
        $plainText = $plainPayload['text'] ?? $taggedText;
        $telegramChunks = $builder->chunkForTelegram($plainText);

        if ($dryRun) {
            $this->line(str_repeat('=', 40));
            $this->line('[WhatsApp — ber-tag]');
            $this->line($taggedText);
            $this->line('Mentions: ' . json_encode($payload['mentions']));
            $this->line('[Telegram — tanpa tag]');
            $this->line($plainText);
            $this->line('Telegram chunks: ' . count($telegramChunks));

            return 0;
        }

        $sent = 0;

        foreach ($manager->activeChannels() as $channel) {
            if (!$channel->supportsMentions()) {
                // Channel tanpa tag (Telegram): teks tanpa `@<nomor>` sama sekali.
                $allOk = true;
                foreach ($telegramChunks as $chunk) {
                    $results = $manager->send($chunk, [], [$channel->name()]);
                    $allOk = $allOk && $manager->allSucceeded($results);
                    $this->pace();
                }
                $sent += $allOk ? 1 : 0;
                continue;
            }

            $results = $manager->send($taggedText, $payload['mentions'], [$channel->name()]);
            $sent += $manager->allSucceeded($results) ? 1 : 0;
        }

        $this->sentAnything = $this->sentAnything || $sent > 0;
        $this->line('Pengingat gabungan diproses untuk ' . count($items) . ' unit.');

        return $sent > 0 ? 1 : 0;
    }

    /**
     * Unit yang latihannya sudah selesai hari ini tetapi belum melapor absensi,
     * serta unit yang terlambat (lewat hari latihan) belum melapor.
     *
     * @return array<int, array{unit: Unit, date: Carbon, type: string}>
     */
    private function collectReminderItems(): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $todayName = $today->format('l');
        $items = [];

        $unitsToday = Unit::with('pj')
            ->where('training_day', 'like', "%{$todayName}%")
            ->where('is_active', true)
            ->get();

        foreach ($unitsToday as $unit) {
            if ($unit->training_hours_end && $now->format('H:i') < $unit->training_hours_end->format('H:i')) {
                continue;
            }

            $hasAttendance = Attendance::where('unit_id', $unit->id)
                ->where('attendance_date', $today->toDateString())
                ->exists();

            if (!$hasAttendance) {
                $items[] = ['unit' => $unit, 'date' => $today, 'type' => 'today'];
            }
        }

        // Overdue: jadwal terakhir > 6 hari lalu dan belum ada laporan.
        foreach (Unit::with('pj')->where('is_active', true)->get() as $unit) {
            $lastTrainingDate = $this->getLastTrainingDate($unit->training_day);

            if (!$lastTrainingDate || !$lastTrainingDate->lt($now->copy()->subDays(6))) {
                continue;
            }

            $hasAttendance = Attendance::where('unit_id', $unit->id)
                ->where('attendance_date', $lastTrainingDate->toDateString())
                ->exists();

            if ($hasAttendance) {
                continue;
            }

            // Hindari duplikasi bila unit juga sudah tercatat pada daftar hari ini.
            $alreadyListed = collect($items)->contains(fn ($i) => $i['unit']->id === $unit->id);

            if (!$alreadyListed) {
                $items[] = ['unit' => $unit, 'date' => $lastTrainingDate, 'type' => 'overdue'];
            }
        }

        return $items;
    }

    /**
     * Tanggal latihan terakhir untuk satu/jumlah hari latihan.
     */
    private function getLastTrainingDate(?string $trainingDay): ?Carbon
    {
        if (!$trainingDay) {
            return null;
        }

        $today = Carbon::today();
        $days = array_filter(array_map('trim', explode(',', $trainingDay)));
        $best = null;

        foreach ($days as $day) {
            try {
                $dayOfWeek = Carbon::parse("next {$day}")->dayOfWeek;
            } catch (\Throwable) {
                continue;
            }

            $currentDayOfWeek = $today->dayOfWeek;

            $date = $currentDayOfWeek === $dayOfWeek
                ? $today->copy()
                : $today->copy()->previous($day);

            if ($best === null || $date->gt($best)) {
                $best = $date;
            }
        }

        return $best;
    }

    private function shouldSendEndMarker(bool $dryRun): bool
    {
        return !$dryRun
            && $this->sentAnything
            && (bool) config('notifications.send_end_marker', false);
    }

    private function sendEndMarker(NotificationManager $manager): void
    {
        $message = "---\nEnd of Attendance Notification Process " . Carbon::now()->format('d/m/Y H:i') . "\n---";
        $manager->send($message);
        Log::channel('cron')->info('End marker terkirim.');
    }

    /**
     * Jeda antar pesan untuk menjaga pola pengiriman tetap manusiawi.
     */
    private function pace(): void
    {
        $seconds = (int) config('notifications.send_delay_seconds', 3);

        if ($seconds > 0) {
            sleep($seconds);
        }
    }
}

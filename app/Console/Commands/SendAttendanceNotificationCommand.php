<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use Carbon\Carbon;

class SendAttendanceNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send attendance notifications to Telegram for unsent records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        // 1. Send existing attendance notifications
        $this->sendExistingNotifications();

        // 2. Send reminders for today's training without attendance
        $this->sendTodaysTrainingReminders();

        // 3. Send reminders for overdue trainings
        if ($now->hour === 19) {
            $this->sendOverdueTrainingReminders();
        }

        // 4. Add Separator
        $this->sendEndOffProcessMessage();

        Log::channel('cron')->info('All notification processes completed.');
        $this->info('All notification processes completed.');
    }

    /**
     * Send notifications for existing attendance records.
     */
    private function sendExistingNotifications()
    {
         Log::channel('cron')->info('Send attendance notifications...');
         $this->info('Send attendance notifications...');
        $attendances = Attendance::with(['unit', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->where('is_notif_send', false)->where('attendance_status','training')
            ->get();

        if ($attendances->isEmpty()) {
            Log::channel('cron')->info('No unsent attendance notifications found.');
            $this->info('No unsent attendance notifications found.');
        }else{
            foreach ($attendances as $attendance) {
                $message = $this->buildAttendanceMessage($attendance);
                $this->sendTelegramMessage($message);
                $attendance->update(['is_notif_send' => true]);
                Log::channel('cron')->info("Attendance notification sent for ID: {$attendance->id}");
                $this->info("Attendance notification sent for ID: {$attendance->id}");
            }
        }
    }

    /**
     * Send reminders for units with training today but no attendance.
     */
    private function sendTodaysTrainingReminders()
    {
        Log::channel('cron')->info('Send attendance reminder...');
        $this->info('Send attendance reminder...');
        $today = Carbon::today();
        $todayName = $today->format('l'); // e.g., 'Monday'
        $now = Carbon::now();

        $units = \App\Models\Unit::where('training_day', 'like', "%{$todayName}%")->get();

        foreach ($units as $unit) {
            // Check if training has ended
            if ($unit->training_hours_end && $now->format('H:i') < $unit->training_hours_end->format('H:i')) {
                continue; // Training not yet ended
            }

            $hasAttendance = Attendance::where('unit_id', $unit->id)
                ->where('attendance_date', $today->toDateString())
                ->exists();

            if (!$hasAttendance) {
                $message = $this->buildReminderMessage($unit, $today, 'today');
                $this->sendTelegramMessage($message);
                Log::channel('cron')->info("Today's training reminder sent for unit: {$unit->name}");
                $this->info("Today's training reminder sent for unit: {$unit->name}");
            }
        }
    }

    /**
     * Send reminders for overdue trainings (more than 7 days past training day without attendance).
     */
    private function sendOverdueTrainingReminders()
    {
        Log::channel('cron')->info('Send attendance overdue reminder...');
        $this->info('Send attendance overdue reminder...');

        $units = \App\Models\Unit::all();

        foreach ($units as $unit) {
            $lastTrainingDate = $this->getLastTrainingDate($unit->training_day);

            if ($lastTrainingDate && $lastTrainingDate->lt(Carbon::now()->subDays(6))) {
                $hasAttendance = Attendance::where('unit_id', $unit->id)
                    ->where('attendance_date', $lastTrainingDate->toDateString())
                    ->exists();

                if (!$hasAttendance) {
                    $message = $this->buildReminderMessage($unit, $lastTrainingDate, 'overdue');
                    $this->sendTelegramMessage($message);
                    Log::channel('cron')->info("Overdue training reminder sent for unit: {$unit->name} on {$lastTrainingDate->toDateString()}");
                    $this->info("Overdue training reminder sent for unit: {$unit->name} on {$lastTrainingDate->toDateString()}");
                }
            }
        }
    }

    private function sendEndOffProcessMessage()
    {
        $now = Carbon::now();
        $separatorMessage = "----------------------------------------\n" .
                            "End of Attendance Notification Process {$now}\n" .
                            "----------------------------------------";
        $this->sendTelegramMessage($separatorMessage);
        Log::channel('cron')->info('End of Attendance Notification Process message sent.');
        $this->info('End of Attendance Notification Process message sent.');
    }

    /**
     * Get the date of the last training day.
     */
    private function getLastTrainingDate($trainingDay)
    {
        $today = Carbon::today();
        $dayOfWeek = Carbon::parse("next {$trainingDay}")->dayOfWeek;
        $currentDayOfWeek = $today->dayOfWeek;
        if ($currentDayOfWeek === $dayOfWeek) {
            return $today;
        } elseif ($currentDayOfWeek > $dayOfWeek) {
            return $today->previous($trainingDay);
        } else {
            return $today->previous($trainingDay);
        }
    }

    /**
     * Send message to Telegram.
     */
    private function sendTelegramMessage($message)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            $this->error('Telegram bot token or chat ID not configured.');
            return;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $this->escapeMarkdown($message),
                'parse_mode' => 'MarkdownV2',
            ]);

            if (!$response->successful()) {
                $this->error('Failed to send Telegram message.');
                $this->error('Telegram send failed: ' . $response->body());
                Log::channel('cron')->error('Telegram send failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('Error sending Telegram message: ' . $e->getMessage());
            Log::channel('cron')->error('SendAttendanceNotificationCommand error: ' . $e->getMessage());
        }
    }

    /**
     * Build the attendance notification message.
     */
    private function buildAttendanceMessage($attendance)
    {
        $unitName = $attendance->unit ? $attendance->unit->name : 'Unknown';
        $pjUnit = $attendance->unit && $attendance->unit->pj ? $attendance->unit->pj->name : 'Unknown';
        $trainingDate = $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('d/m/Y') : 'Unknown';
        $createdAt = $attendance->created_at ? Carbon::parse($attendance->created_at)->format('d/m/Y') : 'Unknown';
        $delayDays = $attendance->created_at && $attendance->attendance_date
            ? Carbon::parse($attendance->created_at)->diffInDays(Carbon::parse($attendance->attendance_date))
            : 0;
        $reportMaker = $attendance->reportMaker ? $attendance->reportMaker->name : 'Unknown';
        $coaches = $attendance->attendanceDetails;
        $coachesList = '';
        if ($coaches->isNotEmpty()) {
            foreach ($coaches as $index => $detail) {
                $coach = $detail->coach;
                $alias = $coach && $coach->ts ? $coach->ts->alias : '';
                $coachesList .= ($index + 1) . ". {$coach->name} ({$alias})\n";
            }
        } else {
            $coachesList = 'Tidak ada';
        }

        $photoLinks = '';
        if ($attendance->attendance_image) {
            $images = array_filter(array_map('trim', explode(',', $attendance->attendance_image)));
            foreach ($images as $index => $image) {
                $photoLinks .= ($index + 1) . ". <a href=\"{$image}\">$image</a>\n";
            }
        } else {
            $photoLinks = 'Tidak ada';
        }

        return "*[BOT ASSISTEN PELATIH]*\n" .
               "*Absensi Latihan*\n\n" .
               "*Unit*: {$unitName}\n" .
               "*PJ Unit*: {$pjUnit}\n" .
               "*Tanggal Latihan*: {$trainingDate}\n" .
               "*Tanggal Absen*: {$createdAt}\n" .
               "*Keterlambatan Absen*: {$delayDays} hari\n" .
               "*Pembuat Laporan*: {$reportMaker}\n" .
               "*Anggota Lama*: {$attendance->old_member_cnt}\n" .
               "*Anggota Baru*: {$attendance->new_member_cnt}\n\n" .
               "*Pelatih*:\n{$coachesList}\n" .
               "*Foto Latihan*:\n{$photoLinks}";
    }

    /**
     * Build the reminder message for missing attendance.
     */
    private function buildReminderMessage($unit, $date, $type)
    {
        $unitName = $unit->name;
        $pjUnit = $unit->pj ? $unit->pj->name : 'Unknown';
        $dateStr = $date->format('d/m/Y');

        if ($type === 'today') {
            $title = 'Pengingat: Absensi Latihan Hari Ini';
            $message = "Unit {$unitName} memiliki jadwal latihan hari ini ({$dateStr}), namun laporan absensi belum diterima.";
        } elseif ($type === 'overdue') {
            $title = 'Pengingat: Absensi Latihan Terlambat';
            $daysOverdue = Carbon::now()->diffInDays($date);
            $message = "Unit {$unitName} memiliki jadwal latihan pada {$dateStr} ({$daysOverdue} hari yang lalu), namun laporan absensi belum diterima.";
        } else {
            return '';
        }

        return "*[BOT ASSISTEN PELATIH]*\n" .
               "*{$title}*\n\n" .
               "*Unit*: {$unitName}\n" .
               "*PJ Unit*: {$pjUnit}\n" .
               "{$message}";
    }

    private function escapeMarkdown($text)
    {
        $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace($specialChars, array_map(fn($c) => '\\'.$c, $specialChars), $text);
    }
}
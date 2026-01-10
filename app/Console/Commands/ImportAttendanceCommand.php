<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Unit;
use App\Models\Coach;
use Illuminate\Support\Str;

class ImportAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import attendance data from Google Sheets CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vT74uyEUsXMGy-vyp5sIXxXv-zexBPoxpHCUdpEp7TZcNTx9G3g_F5baahimXU63P66uL-9n5XfAh7S/pub?gid=1049366104&single=true&output=csv';

        $this->info('Fetching CSV from Google Sheets...');

        try {
            $response = Http::get($url);

            if ($response->failed()) {
                $this->error('Failed to fetch CSV');
                return;
            }

            $csvData = $response->body();
            $lines = explode("\n", $csvData);
            $headers = str_getcsv(array_shift($lines));

            $this->info('Processing rows...');

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $data = str_getcsv($line);

                $row = array_combine($headers, $data);
                // Map columns
                $unit = Unit::where('name', $row['Nama Unit'])->first();
                if (!$unit) {
                    $this->warn("Unit not found: {$row['Nama Unit']}");
                    continue;
                }

                // Assume pembuat laporan is user name, but since no user table linked, use name or skip
                // For now, set report_maker_id to null or find user
                $reportMaker = Coach::where('name', $this->cleanCoachName($row['Pembuat Laporan']))->first(); // TODO: find user by name

                $attendance = Attendance::updateOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'attendance_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $row['Tanggal Latihan'])->format('Y-m-d'),
                    ],
                    [
                        'attendance_status' => 'training', // Default
                        'new_member_cnt' => (int) $row['Anggota Baru'],
                        'old_member_cnt' => (int) $row['Anggota Lama'],
                        'report_maker_id' => $reportMaker->id,
                        'attendance_image' => $row['Foto Latihan'] ?? null,
                        'created_by' => $reportMaker->id,
                        'created_at' => \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $row['Timestamp'])->format('Y-m-d'),
                    ]
                );

                // For coaches present
                // Assume there is a column 'Pelatih yang hadir' with comma-separated names
                if (isset($row['Pelatih yang hadir'])) {
                    $coachNames = explode(',', $row['Pelatih yang hadir']);
                    foreach ($coachNames as $name) {
                        $name = trim($name);
                        $cleanName = $this->cleanCoachName($name);
                        $coach = Coach::where('name', $cleanName)->first();
                        if ($coach) {
                            AttendanceDetail::updateOrCreate(
                                [
                                    'attendance_id' => $attendance->id,
                                    'coach_id' => $coach->id,
                                ],
                                [
                                    'created_by' => $reportMaker->id,
                                ]
                            );
                        } else {
                            $this->warn("Coach not found: {$name}");
                        }
                    }
                }

                $this->info("Imported attendance for unit: {$row['Nama Unit']}");
            }

            $this->info('Import completed successfully.');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('ImportAttendanceCommand error: ' . $e->getMessage());
        }
    }

    /**
     * Clean coach name by removing suffixes like (SU), (ST), etc.
     */
    private function cleanCoachName($name)
    {
        return preg_replace('/\s*\((SU|ST|SM|PMM|PMT)\)\s*/', '', $name);
    }
}
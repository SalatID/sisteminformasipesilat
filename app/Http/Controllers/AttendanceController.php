<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;


class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRoles = $user->getRoleNames();
        
        $attendances = Attendance::with(['unit', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->when(!$userRoles->contains('super-admin') && $userRoles->contains('pj-unit'), function ($query) use ($user) {
                $unitIds = \App\Models\Unit::where('pj_id', $user->coach_id)->pluck('id');
                return $query->whereIn('unit_id', $unitIds);
            })
            ->when($request->filled('start_date'), function ($query) use ($request) {
                return $query->where('attendance_date', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                return $query->where('attendance_date', '<=', $request->end_date);
            })
            ->when($request->filled('attendance_status'), function ($query) use ($request) {
                return $query->where('attendance_status', $request->attendance_status);
            })
            ->when($request->filled('unit_id'), function ($query) use ($request) {
                return $query->where('unit_id', $request->unit_id);
            })
            ->when(!$request->hasAny(['start_date', 'end_date']), function ($query) {
                return $query->where('attendance_date', '>=', now()->subMonths(2));
            })
            ->orderBy('attendance_date', 'desc')
            ->get();
            
        return view('pages.admin.attendance.coach.list', compact('attendances'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'unit_id' => 'required|uuid|exists:units,id',
            'attendance_date' => 'required|date',
            'attendance_status' => 'required|in:training,cancelled,school_holiday,ramadhan_break',
            'reason' => 'required_unless:attendance_status,training|nullable|string|max:255',
        ]);

        // Additional fields can be set here
        $validatedData['report_maker_id'] = auth()->user()->id; // Assuming the logged-in user is the report maker
        $validatedData['new_member_cnt'] = 0; // Default value, can be modified as needed
        $validatedData['old_member_cnt'] = 0; // Default value, can be modified as needed
        $validatedData['created_by'] = auth()->user()->id;

        $ins = Attendance::create($validatedData);
        if (!$ins){
            return redirect()->route('attendance.coach.index')->with(["error"=>true,"message"=>"Tambah Absensi Gagal"]);
        }

        return redirect()->route('attendance.coach.index')->with(["error"=>false,"message"=>"Tambah Absensi Berhasil"]);
    }
    public function destroy($id)
    {
        $id = Crypt::decryptString($id);
        $attendance = Attendance::findOrFail($id);
        $attendance->deleted_by = auth()->user()->id;
        $attendance->save();
        $delete = $attendance->delete();

        if (!$delete){
            return response()->json(["error"=>true,"message"=>"Hapus Absensi Gagal"]);
        }

        return response()->json(["error"=>false,"message"=>"Hapus Absensi Berhasil"]);
    }
    public function syncAttendance()
    {
        Artisan::call('attendance:import');

        return redirect()->back()->with(["error"=>false,"message"=>"Sync Berjalan Di Latar Belakang, Mohon Cek Berkala"]);
    }

    public function resendNotif($id)
    {
        Artisan::call('attendance:notify', [
            'attendance_id' => $id,
        ]);

        return redirect()->back()->with(["error"=>false,"message"=>"Pengiriman Ulang Notifikasi Berjalan Di Latar Belakang, Mohon Cek Berkala"]);
    }

    public function unitAttendanceReport(Request $request)
    {
       // Bulan & tahun (default bulan ini)
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth();

        // Initialize report with all units
        $allUnits = \App\Models\Unit::all();
        $report = [];
        
        foreach ($allUnits as $unit) {
            $report[$unit->id] = [
                'unit_name' => $unit->name,
                'weeks' => [
                    1 => [],
                    2 => [],
                    3 => [],
                    4 => [],
                    5 => [],
                ]
            ];
        }

        // Ambil attendance dalam bulan tsb
        $attendances = Attendance::with('unit')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->get();

        foreach ($attendances as $attendance) {
            $unitId = $attendance->unit_id;
            $date = Carbon::parse($attendance->attendance_date);
            
            // Calculate week number: Week 1 = 1-3 Jan, Week 2 = 4-10 Jan, etc.
            // Get the day of month (1-31)
            $dayOfMonth = $date->day;
            
            // Week 1: days 1-3, Week 2: days 4-10, Week 3: days 11-17, Week 4: days 18-24, Week 5: days 25-31
            if ($dayOfMonth <= 3) {
                $week = 1;
            } elseif ($dayOfMonth <= 10) {
                $week = 2;
            } elseif ($dayOfMonth <= 17) {
                $week = 3;
            } elseif ($dayOfMonth <= 24) {
                $week = 4;
            } else {
                $week = 5;
            }

            // Store attendance data for view formatting (support multiple per week)
            if (!isset($report[$unitId])) {
                continue; // Skip if unit not in report
            }
            $report[$unitId]['weeks'][$week][] = $attendance;
        }

        // Determine which weeks have passed
        $currentDate = Carbon::now();
        $currentWeek = 0;
        
        if ($year == $currentDate->year && $month == $currentDate->month) {
            $dayOfMonth = $currentDate->day;
            if ($dayOfMonth <= 3) {
                $currentWeek = 1;
            } elseif ($dayOfMonth <= 10) {
                $currentWeek = 2;
            } elseif ($dayOfMonth <= 17) {
                $currentWeek = 3;
            } elseif ($dayOfMonth <= 24) {
                $currentWeek = 4;
            } else {
                $currentWeek = 5;
            }
        } elseif ($year < $currentDate->year || ($year == $currentDate->year && $month < $currentDate->month)) {
            // Past month, all weeks have passed
            $currentWeek = 5;
        }
        // If future month, currentWeek stays 0 (no weeks have passed)

        return view('pages.admin.attendance.report.monthly-unit', [
            'report' => $report,
            'month' => $month,
            'year' => $year,
            'currentWeek' => $currentWeek
        ]);
    }
}

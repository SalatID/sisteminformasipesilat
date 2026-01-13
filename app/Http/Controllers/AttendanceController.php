<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Artisan;

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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function index()
    {
         $attendances = Attendance::with(['unit', 'reportMaker', 'attendanceDetails.coach.ts'])
         ->get();
            return view('pages.admin.attendance.coach.list', compact('attendances'));
    }

    public function store(Request $request)
    {
        $attendanceIds = $request->input('attendance_ids', []);
        if (!empty($attendanceIds)) {
            Attendance::whereIn('id', $attendanceIds)->update(['is_notif_send' => true]);
        }
        return redirect()->route('attendance.coach.index')->with('success', 'Selected attendances marked as notified.');
    }
}

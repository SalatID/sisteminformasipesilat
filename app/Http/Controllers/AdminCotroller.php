<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class AdminCotroller extends Controller
{
    public function dashboard(Request $request){
        $start = $request->get('start_period',Carbon::now()->startOfMonth()->toDateString());  // atau input filter dashboard
        $end   = $request->get('end_period',Carbon::now()->toDateString());
        $almakaUnitId = 'a0cc1baa-2345-4c29-b2e9-5cb47b770f2b';

        $topCoachesUnit = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('coachs as c', 'c.id', '=', 'ad.coach_id')
        ->select([
            'c.id',
            'c.name',
            DB::raw('COUNT(*) as hadir_unit'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->groupBy('c.id', 'c.name')
        ->orderByDesc('hadir_unit')
        ->limit(10)
        ->get();

        $topCoachesAlmaka = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('coachs as c', 'c.id', '=', 'ad.coach_id')
        ->select([
            'c.id',
            'c.name',
            DB::raw('COUNT(*) as hadir_almaka'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '=', $almakaUnitId)
        ->groupBy('c.id', 'c.name')
        ->orderByDesc('hadir_almaka')
        ->limit(10)
        ->get();

        $topUnitsMostCoaches = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('COUNT(DISTINCT ad.coach_id) as total_coach'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderByDesc('total_coach')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        $topUnitsLeastCoaches = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('COUNT(DISTINCT ad.coach_id) as total_coach'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderBy('total_coach', 'asc')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        $topUnitsMostMembersAvg = DB::table('attendances as a')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('AVG(COALESCE(a.new_member_cnt,0) + COALESCE(a.old_member_cnt,0)) as avg_peserta'),
            DB::raw('COUNT(*) as total_sesi'),
        ])
        ->whereNull('a.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderByDesc('avg_peserta')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        $topUnitsLeastMembers = DB::table('attendances as a')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('SUM(COALESCE(a.new_member_cnt,0) + COALESCE(a.old_member_cnt,0)) as total_peserta'),
        ])
        ->whereNull('a.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderBy('total_peserta', 'asc')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        $topUnitsMostTraining = DB::table('attendances as a')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('COUNT(*) as total_latihan'),
        ])
        ->whereNull('a.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderByDesc('total_latihan')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        $topUnitsMostNoTraining = DB::table('attendances as a')
        ->join('units as u', 'u.id', '=', 'a.unit_id')
        ->select([
            'u.id',
            'u.name',
            DB::raw('COUNT(*) as total_tidak_latihan'),
        ])
        ->whereNull('a.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', '!=', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderByDesc('total_tidak_latihan')
        ->limit(10)
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->get();

        // KPI metrics
        $kpi = [];
        $kpi['total_training'] = DB::table('attendances')
            ->whereNull('deleted_at')
            ->where('attendance_status', 'training')
            ->whereBetween('attendance_date', [$start, $end])
            ->count();

        $kpi['total_holiday_or_cancelled'] = DB::table('attendances')
            ->whereNull('deleted_at')
            ->whereBetween('attendance_date', [$start, $end])
            ->where('attendance_status', '!=', 'training')
            ->count();

        $kpi['active_units'] = DB::table('attendances')
            ->whereNull('deleted_at')
            ->where('attendance_status', 'training')
            ->whereBetween('attendance_date', [$start, $end])
            ->distinct('unit_id')
            ->count('unit_id');

        $kpi['active_coaches'] = DB::table('attendance_details as ad')
            ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
            ->whereNull('a.deleted_at')
            ->whereNull('ad.deleted_at')
            ->whereBetween('a.attendance_date', [$start, $end])
            ->where('a.attendance_status', 'training')
            ->distinct('ad.coach_id')
            ->count('ad.coach_id');


        return view('pages.admin.dashboard', [
            'topCoachesUnit' => $topCoachesUnit,
            'topCoachesAlmaka' => $topCoachesAlmaka,
            'topUnitsMostCoaches' => $topUnitsMostCoaches,
            'topUnitsLeastCoaches' => $topUnitsLeastCoaches,
            'topUnitsMostMembersAvg' => $topUnitsMostMembersAvg,
            'topUnitsLeastMembers' => $topUnitsLeastMembers,
            'topUnitsMostTraining' => $topUnitsMostTraining,
            'topUnitsMostNoTraining' => $topUnitsMostNoTraining,
            'kpi' => $kpi,
        ]);
    }
    public function index(){

    }
    public function create(){
        
    }
    public function store(){
        
    }
    public function show(){
        
    }
    public function edit(){
        
    }
    public function update(){
        
    }
    public function destroy(){
        
    }
}

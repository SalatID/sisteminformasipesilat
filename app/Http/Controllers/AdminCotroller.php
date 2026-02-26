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
        ->join('ts as ts', 'ts.id', '=', 'c.ts_id')
        ->select([
            'c.id',
            'c.name',
            'ts.name as ts_name',
            'ts.ts_seq as ts_seq',
            DB::raw('COUNT(*) as hadir_unit'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->where('ts.ts_seq', '>', 4)
        ->groupBy('c.id', 'c.name', 'ts.name','ts.ts_seq')
        ->orderByDesc('hadir_unit')
        ->orderByDesc('ts.ts_seq')
        ->limit(10)
        ->get();

        $topCoachesAlmaka = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('coachs as c', 'c.id', '=', 'ad.coach_id')
        ->join('ts as ts', 'ts.id', '=', 'c.ts_id')
        ->select([
            'c.id',
            'c.name',
            'ts.name as ts_name',
            'ts.ts_seq as ts_seq',
            DB::raw('COUNT(*) as hadir_almaka'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '=', $almakaUnitId)
        ->where('ts.ts_seq', '>', 4)
        ->groupBy('c.id', 'c.name', 'ts.name','ts.ts_seq')
        ->orderByDesc('hadir_almaka')
        ->orderByDesc('ts.ts_seq')
        ->limit(10)
        ->get();

        $topAssCoachesUnit = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('coachs as c', 'c.id', '=', 'ad.coach_id')
        ->join('ts as ts', 'ts.id', '=', 'c.ts_id')
        ->select([
            'c.id',
            'c.name',
            'ts.name as ts_name',
            'ts.ts_seq as ts_seq',
            DB::raw('COUNT(*) as hadir_unit'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '<>', $almakaUnitId)
        ->where('ts.ts_seq', '<=', 4)
        ->groupBy('c.id', 'c.name', 'ts.name','ts.ts_seq')
        ->orderByDesc('hadir_unit')
        ->orderByDesc('ts.ts_seq')
        ->limit(10)
        ->get();

        $topAssCoachesAlmaka = DB::table('attendance_details as ad')
        ->join('attendances as a', 'a.id', '=', 'ad.attendance_id')
        ->join('coachs as c', 'c.id', '=', 'ad.coach_id')
        ->join('ts as ts', 'ts.id', '=', 'c.ts_id')
        ->select([
            'c.id',
            'c.name',
            'ts.name as ts_name',
            'ts.ts_seq as ts_seq',
            DB::raw('COUNT(*) as hadir_almaka'),
        ])
        ->whereNull('a.deleted_at')
        ->whereNull('ad.deleted_at')
        ->where('a.attendance_status', 'training')
        ->where('ts.ts_seq', '<=', 4)
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.unit_id', '=', $almakaUnitId)
        ->groupBy('c.id', 'c.name', 'ts.name','ts.ts_seq')
        ->orderByDesc('hadir_almaka')
        ->orderByDesc('ts.ts_seq')
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
           DB::raw('AVG(COALESCE(a.new_member_cnt,0) + COALESCE(a.old_member_cnt,0)) as avg_peserta'),
            DB::raw('COUNT(*) as total_sesi'),
        ])
        ->whereNull('a.deleted_at')
        ->whereBetween('a.attendance_date', [$start, $end])
        ->where('a.attendance_status', 'training')
        ->groupBy('u.id', 'u.name')
        ->orderBy('avg_peserta', 'asc')
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


        // Top 10 Greatest Contributions
        $startPeriod = Carbon::parse($start)->format('Y-m');
        $endPeriod = Carbon::parse($end)->format('Y-m');
        
        $topContributionGreatestResults = DB::select(
            "SELECT c.id AS coach_id, c.name AS nama_pelatih, ts.alias AS tingkatan_sabuk, ts.ts_seq,
                    COALESCE(SUM(cd.total), 0) AS total_contribution
            FROM coachs c
            JOIN ts ON c.ts_id = ts.id
            LEFT JOIN contribution_details cd ON cd.coach_id = c.id AND cd.deleted_at IS NULL
            LEFT JOIN contributions cn ON cn.id = cd.contribution_id AND cn.periode BETWEEN ? AND ? AND cn.deleted_at IS NULL
             WHERE cn.periode BETWEEN ? AND ? AND ts.ts_seq > 4
            GROUP BY c.id, c.name, ts.alias, ts.ts_seq
            HAVING COALESCE(SUM(cd.total), 0) > 0
            ORDER BY total_contribution DESC
            LIMIT 10",
            [$startPeriod, $endPeriod, $startPeriod, $endPeriod]
        );

        // Top 10 Lowest Contributions (excluding zero)
        $topContributionLowestResults = DB::select(
            "SELECT c.id AS coach_id, c.name AS nama_pelatih, ts.alias AS tingkatan_sabuk, ts.ts_seq,
                    COALESCE(SUM(cd.total), 0) AS total_contribution
            FROM coachs c
            JOIN ts ON c.ts_id = ts.id
            LEFT JOIN contribution_details cd ON cd.coach_id = c.id AND cd.deleted_at IS NULL
            LEFT JOIN contributions cn ON cn.id = cd.contribution_id AND cn.periode BETWEEN ? AND ? AND cn.deleted_at IS NULL
            WHERE cn.periode BETWEEN ? AND ? AND ts.ts_seq > 4
            GROUP BY c.id, c.name, ts.alias, ts.ts_seq
            HAVING COALESCE(SUM(cd.total), 0) > 0
            ORDER BY total_contribution ASC
            LIMIT 10",
            [$startPeriod, $endPeriod, $startPeriod, $endPeriod]
        );

        $topAssistantContributionGreatestResults = DB::select(
            "SELECT c.id AS coach_id, c.name AS nama_pelatih, ts.alias AS tingkatan_sabuk, ts.ts_seq,
                    COALESCE(SUM(cd.total), 0) AS total_contribution
            FROM coachs c
            JOIN ts ON c.ts_id = ts.id
            LEFT JOIN contribution_details cd ON cd.coach_id = c.id AND cd.deleted_at IS NULL
            LEFT JOIN contributions cn ON cn.id = cd.contribution_id AND cn.periode BETWEEN ? AND ? AND cn.deleted_at IS NULL
             WHERE cn.periode BETWEEN ? AND ? AND ts.ts_seq <= 4
            GROUP BY c.id, c.name, ts.alias, ts.ts_seq
            HAVING COALESCE(SUM(cd.total), 0) > 0
            ORDER BY total_contribution DESC
            LIMIT 10",
            [$startPeriod, $endPeriod, $startPeriod, $endPeriod]
        );

        // Top 10 Lowest Contributions (excluding zero)
        $topAssistantContributionLowestResults = DB::select(
            "SELECT c.id AS coach_id, c.name AS nama_pelatih, ts.alias AS tingkatan_sabuk, ts.ts_seq,
                    COALESCE(SUM(cd.total), 0) AS total_contribution
            FROM coachs c
            JOIN ts ON c.ts_id = ts.id
            LEFT JOIN contribution_details cd ON cd.coach_id = c.id AND cd.deleted_at IS NULL
            LEFT JOIN contributions cn ON cn.id = cd.contribution_id AND cn.periode BETWEEN ? AND ? AND cn.deleted_at IS NULL
            WHERE cn.periode BETWEEN ? AND ? AND ts.ts_seq <= 4
            GROUP BY c.id, c.name, ts.alias, ts.ts_seq
            HAVING COALESCE(SUM(cd.total), 0) > 0
            ORDER BY total_contribution ASC
            LIMIT 10",
            [$startPeriod, $endPeriod, $startPeriod, $endPeriod]
        );

        // Average unit member monthly - last 6 months with zero fill
        $unitMembersMonthly = DB::select(
            "WITH RECURSIVE months AS (
                SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m') as periode
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH), '%Y-%m')
                FROM months
                WHERE DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH) <= NOW()
            )
            SELECT 
                m.periode as month,
                COALESCE(AVG(COALESCE(a.new_member_cnt, 0) + COALESCE(a.old_member_cnt, 0)), 0) as avg_members
            FROM months m
            LEFT JOIN attendances a ON DATE_FORMAT(a.attendance_date, '%Y-%m') = m.periode 
                AND a.deleted_at IS NULL 
                AND a.attendance_status = 'training'
            GROUP BY m.periode
            ORDER BY m.periode ASC"
        );

        // Monthly contributions - Coach and Komwil (last 6 months with zero fill)
        $monthlyContributions = DB::select(
            "WITH RECURSIVE months AS (
                SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m') as periode
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH), '%Y-%m')
                FROM months
                WHERE DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH) <= NOW()
            )
            SELECT 
                m.periode as month,
                COALESCE(SUM(cn.pj_share), 0) as coach_contribution,
                COALESCE(SUM(cn.kas_share + cn.saving_share), 0) as komwil_contribution
            FROM months m
            LEFT JOIN contributions cn ON cn.periode = m.periode AND cn.deleted_at IS NULL
            LEFT JOIN contribution_details cd ON cd.contribution_id = cn.id AND cd.deleted_at IS NULL
            LEFT JOIN coachs c ON c.id = cd.coach_id
            LEFT JOIN ts ON ts.id = c.ts_id
            GROUP BY m.periode
            ORDER BY m.periode ASC"
        );

        // Total monthly contributions (last 6 months with zero fill)
        $totalMonthlyContributions = DB::select(
            "WITH RECURSIVE months AS (
                SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m') as periode
                UNION ALL
                SELECT DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH), '%Y-%m')
                FROM months
                WHERE DATE_ADD(STR_TO_DATE(CONCAT(periode, '-01'), '%Y-%m-%d'), INTERVAL 1 MONTH) <= NOW()
            )
            SELECT 
                m.periode as month,
                COALESCE(SUM(cd.total), 0) as total_contribution
            FROM months m
            LEFT JOIN contributions cn ON cn.periode = m.periode AND cn.deleted_at IS NULL
            LEFT JOIN contribution_details cd ON cd.contribution_id = cn.id AND cd.deleted_at IS NULL
            GROUP BY m.periode
            ORDER BY m.periode ASC"
        );

        // Get average contribution dates for all units
        $unitAverageContributionDates = $this->getUnitAverageContributionDates();

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
            'topAssCoachesUnit' => $topAssCoachesUnit,
            'topAssCoachesAlmaka' => $topAssCoachesAlmaka,
            'topContributionGreatestResults' => $topContributionGreatestResults,
            'topContributionLowestResults' => $topContributionLowestResults,
            'topAssistantContributionGreatestResults' => $topAssistantContributionGreatestResults,
            'topAssistantContributionLowestResults' => $topAssistantContributionLowestResults,
            'unitMembersMonthly' => $unitMembersMonthly,
            'monthlyContributions' => $monthlyContributions,
            'totalMonthlyContributions' => $totalMonthlyContributions,
            'unitAverageContributionDates' => $unitAverageContributionDates
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

    private function getUnitAverageContributionDates()
    {
        // Get all units
        $units = \App\Models\Unit::orderBy('name')->get();
        $result = [];

        // Get previous month periode
        $previousMonth = Carbon::now()->subMonth();
        $previousPeriode = $previousMonth->format('Y-m');

        foreach ($units as $unit) {
            // Get current year and month
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            $currentPeriode = sprintf('%04d-%02d', $currentYear, $currentMonth);

            // Check if previous month contribution exists
            $previousContributionExists = \App\Models\Contribution::where('unit_id', $unit->id)
                ->where('periode', $previousPeriode)
                ->exists();

            // Get 6 months back
            $sixMonthsAgo = Carbon::now()->subMonths(6);
            $sixMonthsAgoPeriode = $sixMonthsAgo->format('Y-m');

            // Get past contributions (excluding current period)
            $pastContributions = \App\Models\Contribution::where('unit_id', $unit->id)
                ->where('periode', '!=', $currentPeriode)
                ->whereBetween('periode', [
                    $sixMonthsAgoPeriode,
                    $previousPeriode
                ])
                ->get();

            if ($pastContributions->isEmpty()) {
                continue; // Skip if no historical data
            }

            // Collect day of month from created_at dates
            $createdDays = [];
            foreach ($pastContributions as $contribution) {
                $createdDate = Carbon::parse($contribution->created_at);
                $day = $createdDate->day;
                $createdDays[] = $day;
            }

            // Calculate average day
            $averageDay = round(array_sum($createdDays) / count($createdDays));

            // Get month name for average day (using current month as reference)
            $monthName = Carbon::now()->locale('id')->translatedFormat('F');

            // Get previous month contribution for comparison
            $previousContribution = \App\Models\Contribution::where('unit_id', $unit->id)
                ->where('periode', $previousPeriode)
                ->first();

            $comparisonLabel = '';
            $comparisonClass = '';

            if ($previousContributionExists && $previousContribution) {
                // Compare from previous month's created_at
                $previousDay = Carbon::parse($previousContribution->created_at)->day;
                $daysDifference = abs($previousDay - $averageDay);

                if ($previousDay < $averageDay) {
                    $comparisonLabel = "Lebih Cepat {$daysDifference} Hari";
                    $comparisonClass = 'badge bg-success';
                } else {
                    $comparisonLabel = "Terlambat {$daysDifference} Hari";
                    $comparisonClass = 'badge bg-warning text-dark';
                }
            } else {
                // Previous period doesn't exist - compare from current date instead
                $currentDay = Carbon::now()->day;
                $daysDifference = abs($currentDay - $averageDay);

                $comparisonLabel = "Periode Terakhir Belum Dihitung";
                
                if ($currentDay < $averageDay) {
                    $comparisonLabel .= ", Lebih Cepat {$daysDifference} Hari";
                    $comparisonClass = 'badge bg-secondary';
                } else {
                    $comparisonLabel .= ", Terlambat {$daysDifference} Hari";
                    $comparisonClass = 'badge bg-secondary';
                }
            }

            // Get created_at date for display (use previous contribution's created_at or current date)
            $displayCreatedDate = $previousContribution 
                ? $previousContribution->created_at->locale('id')->translatedFormat('d F Y')
                : "<span class='badge badge-danger'>Belum Ada Kontribusi</span>";

            $result[] = [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'average_day' => $averageDay,
                'month_name' => $monthName,
                'previous_period_exists' => $previousContributionExists,
                'comparison_label' => $comparisonLabel,
                'comparison_class' => $comparisonClass,
                'display_created_date' => $displayCreatedDate
            ];
        }
        // dd($result);

        // Convert periode to Indonesian format (e.g., "2026-01" to "Januari 2026")
        $periodeCarbon = Carbon::createFromFormat('Y-m', $previousPeriode);
        $periodeFormatted = $periodeCarbon->locale('id')->translatedFormat('F Y');

        return [
            "periode" => $periodeFormatted,
            "data" => $result
        ];
    }
}

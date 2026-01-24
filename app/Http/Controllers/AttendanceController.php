<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use \App\Models\Contribution;
use \App\Models\ContributionDetail;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRoles = $user->getRoleNames();
        
        $attendances = Attendance::with(['unit', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->join('units', 'attendances.unit_id', '=', 'units.id')
            ->when(!$userRoles->contains('super-admin') && $userRoles->contains('pj-unit'), function ($query) use ($user) {
                $unitIds = \App\Models\Unit::where('pj_id', $user->coach_id)->pluck('id');
                return $query->whereIn('attendances.unit_id', $unitIds);
            })
            ->when($request->filled('start_date'), function ($query) use ($request) {
                return $query->where('attendances.attendance_date', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                return $query->where('attendances.attendance_date', '<=', $request->end_date);
            })
            ->when($request->filled('attendance_status'), function ($query) use ($request) {
                return $query->where('attendances.attendance_status', $request->attendance_status);
            })
            ->when($request->filled('unit_id'), function ($query) use ($request) {
                return $query->where('attendances.unit_id', $request->unit_id);
            })
            ->when(!$request->hasAny(['start_date', 'end_date']), function ($query) {
                return $query->where('attendances.attendance_date', '>=', now()->subMonths(2));
            })
            ->orderBy('units.name', 'asc')
            ->orderBy('attendances.attendance_date', 'desc')
            ->select('attendances.*')
            ->get();
            
        return view('pages.admin.attendance.coach.list', compact('attendances'));
    }

    public function show($id)
    {
        $id = Crypt::decryptString($id);
        $attendance = Attendance::with(['unit', 'reportMaker', 'attendanceDetails.coach.ts'])
            ->findOrFail($id);

        return view('pages.admin.attendance.coach.detail', compact('attendance'));
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

        // Get user and roles
        $user = auth()->user();
        $userRoles = $user->getRoleNames();

        // Initialize report with all units (filtered by role)
        $allUnits = self::GetUnitList();
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

    public function contributionReceiptUnit(Request $request){
        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);
        $unitId = $request->get('unit_id');
        $contributionAmount = $request->get('contribution_amount');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth();

        // Get user and roles
        $user = auth()->user();
        $userRoles = $user->getRoleNames();

        // Get all units for filter
        $units = self::GetUnitList();
        
        // Handle form submission with image upload
        if ($request->isMethod('post') && $unitId && $contributionAmount) {
            // Now save to database
            $validatedData = $request->validate([
                'unit_id' => 'required|uuid|exists:units,id',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer',
                'contribution_amount' => 'required|numeric',
                'contribution_receipt_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // First, calculate all the data
            $unit = \App\Models\Unit::findOrFail($unitId);
            
            // Get attendances for the unit in selected month
            $attendances = Attendance::with(['unit', 'attendanceDetails.coach.ts'])
                ->where('unit_id', $unitId)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->orderBy('attendance_date')
                ->get();

            // Get all coaches who attended in this month
            $coachIds = $attendances->pluck('attendanceDetails')->flatten()->pluck('coach_id')->unique();
            $coaches = \App\Models\Coach::with('ts')
                ->join('ts', 'coachs.ts_id', '=', 'ts.id')
                ->whereIn('coachs.id', $coachIds)
                ->orderBy('ts.ts_seq', 'desc')
                ->orderBy('coachs.name', 'asc')
                ->select('coachs.*')
                ->get();

            // Build attendance report per coach
            $coachAttendance = [];
            foreach ($coaches as $coach) {
                $coachAttendance[$coach->id] = [
                    'coach' => $coach,
                    'weeks' => [1 => [], 2 => [], 3 => [], 4 => [], 5 => []],
                    'total_attendance' => 0
                ];
            }
            if(!isset($coachAttendance[$unit->pj_id])){
                // Ensure PJ is included even if no attendance
                $coachAttendance[$unit->pj_id] = [
                    'coach' => \App\Models\Coach::with('ts')->where('id', $unit->pj_id)->first(),
                    'weeks' => [1 => [], 2 => [], 3 => [], 4 => [], 5 => []],
                    'total_attendance' => 0
                ];
            }

            // Fill attendance dates
            foreach ($attendances as $attendance) {
                $date = Carbon::parse($attendance->attendance_date);
                $dayOfMonth = $date->day;
                
                // Calculate week number
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

                $isTraining = $attendance->attendance_status == 'training';
                
                if ($isTraining) {
                    foreach ($attendance->attendanceDetails as $detail) {
                        if (isset($coachAttendance[$detail->coach_id])) {
                            $coachAttendance[$detail->coach_id]['weeks'][$week][] = [
                                'date' => $date->toDateString(),
                                'status' => $attendance->attendance_status,
                                'reason' => $attendance->reason
                            ];
                            $coachAttendance[$detail->coach_id]['total_attendance']++;
                        }
                    }
                } else {
                    foreach ($coachAttendance as $coachId => &$coachData) {
                        $coachData['weeks'][$week][] = [
                            'date' => $date->toDateString(),
                            'status' => $attendance->attendance_status,
                            'reason' => $attendance->reason
                        ];
                    }
                }
            }
           
            // Calculate contributions
            $totalFinalValue = 0;
            $totalAttendance = 0;
            foreach ($coachAttendance as &$data) {
                $coach = $data['coach'];
                $attendance = $data['total_attendance'];
                $multiplier = (int) ($coach->ts->multiplier ?? 1);
                $isPJ = ($unit->pj_id == $coach->id) ? 1 : 0;

                $finalValue = $attendance * $multiplier + $isPJ;
                
                $data['multiplier'] = $multiplier;
                $data['is_pj'] = $isPJ;
                $data['final_value'] = $finalValue;
                
                $totalFinalValue += $finalValue;
                $totalAttendance += $data['total_attendance'];
            }
            // Calculate nominal per meeting from contribution amount
            $pjShare = $contributionAmount * 0.65;
            $kasShare = $contributionAmount * 0.20;
            $savingsShare = $contributionAmount * 0.15;
            $nominalPerMeeting = $totalFinalValue > 0 ? ($pjShare / $totalFinalValue) : 0;
            $nominalPerMeeting = floor($nominalPerMeeting / 100) * 100;
            $totalContribution = 0;
            
            // Second pass: calculate amounts
            foreach ($coachAttendance as &$data) {
                $totalAmount = $data['final_value'] * $nominalPerMeeting;
                $data['total_amount'] = $totalAmount;
                $totalContribution += $totalAmount;
            }
            $difference = $pjShare - $totalContribution;

            $periode = sprintf('%04d-%02d', $validatedData['year'], $validatedData['month']);

            try {
                \DB::beginTransaction();

                // Handle image upload
                $imagePath = null;
                if ($request->hasFile('contribution_receipt_img')) {
                    $image = $request->file('contribution_receipt_img');
                    
                    // Additional security validation
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    $fileMimeType = $image->getMimeType();
                    
                    if (!in_array($fileMimeType, $allowedMimes)) {
                        \DB::rollBack();
                        return redirect()->back()->with(['error' => true, 'message' => 'File harus berupa gambar (JPEG, PNG, GIF)']);
                    }
                    
                    // Verify it's actually an image by checking image properties
                    $imageInfo = @getimagesize($image->getRealPath());
                    if ($imageInfo === false) {
                        \DB::rollBack();
                        return redirect()->back()->with(['error' => true, 'message' => 'File yang diupload bukan gambar yang valid']);
                    }
                    
                    // Move to appropriate directory based on environment
                    if (app()->environment('production')) {
                        // Production: public_html structure
                        $dir = base_path('../../public_html/sip/contribution_receipts/');
                    } else {
                        // Local development: standard public folder
                        $dir = public_path('contribution_receipts/');
                    }
                    
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    $imageName = 'receipt_' . $unitId . '_' . $periode . '_' . time() . '.' . $image->getClientOriginalExtension();
                    $image->move($dir, $imageName);
                    $imagePath = 'contribution_receipts/' . $imageName;
                }

                // Check if contribution exists
                $existingContribution = Contribution::where('unit_id', $validatedData['unit_id'])
                    ->where('periode', $periode)
                    ->first();

                // Prepare data for update/create
                $contributionData = [
                    'contribution_amount' => $validatedData['contribution_amount'],
                    'pj_share' => $pjShare,
                    'pj_percentage' => 65.00,
                    'kas_share' => $kasShare,
                    'kas_percentage' => 20.00,
                    'saving_share' => $savingsShare,
                    'saving_percentage' => 15.00,
                    'difference' => $difference,
                    'revision_count' => $existingContribution ? ($existingContribution->revision_count + 1) : 1,
                    'updated_by' => auth()->user()->id,
                ];

                // Set created_by only for new records
                if (!$existingContribution) {
                    $contributionData['created_by'] = auth()->user()->id;
                }

                // Only update image path if a new image was uploaded
                if ($imagePath) {
                    $contributionData['contribution_receipt_img'] = $imagePath;
                }

                // Create or update contribution
                $contribution = Contribution::updateOrCreate(
                    [
                        'unit_id' => $validatedData['unit_id'],
                        'periode' => $periode,
                    ],
                    $contributionData
                );
                
                // Create or update contribution details
                foreach ($coachAttendance as &$data) {
                    // Check if detail exists
                    $existingDetail = ContributionDetail::where('contribution_id', $contribution->id)
                        ->where('coach_id', $data['coach']->id)
                        ->first();

                    $detailData = [
                        'multiplier' => $data['multiplier'],
                        'attendance' => $data['total_attendance'],
                        'is_pj' => $data['is_pj'],
                        'final_value' => $data['final_value'],
                        'amount_per_attendance' => $nominalPerMeeting,
                        'total' => $data['total_amount'],
                        'updated_by' => auth()->user()->id,
                    ];

                    // Set created_by only for new records
                    if (!$existingDetail) {
                        $detailData['created_by'] = auth()->user()->id;
                    }
                    // print_r($detailData);

                    ContributionDetail::updateOrCreate(
                        [
                            'contribution_id' => $contribution->id,
                            'coach_id' => $data['coach']->id,
                        ],
                        $detailData
                    );
                }

                \DB::commit();
                // After saving, redirect to GET request to show data from database
                return redirect()->route('receipt.contribution.unit.index', [
                    'unit_id' => $validatedData['unit_id'],
                    'month' => $validatedData['month'],
                    'year' => $validatedData['year'],
                    'contribution_amount' => $validatedData['contribution_amount']
                ])->with(['error' => false, 'message' => 'Data kontribusi berhasil disimpan']);

            } catch (\Exception $e) {
                \DB::rollBack();
                return redirect()->back()->with(['error' => true, 'message' => 'Gagal menyimpan data kontribusi: ' . $e->getMessage()]);
            }
        }
        
        // If no unit selected, return view with units list
        if (!$unitId) {
            return view('pages.admin.attendance.receipt.contribution-unit', [
                'units' => $units,
                'month' => $month,
                'year' => $year,
                'unitData' => null,
                'contributionAmount' => $contributionAmount
            ]);
        }

        // GET request: Check if data exists in database first
        $periode = sprintf('%04d-%02d', $year, $month);
        $existingContribution = \App\Models\Contribution::with(['contributionDetails.coach.ts', 'unit'])
            ->where('unit_id', $unitId)
            ->where('periode', $periode)
            ->first();

        $unit = \App\Models\Unit::findOrFail($unitId);

        // Get attendances for the unit in selected month (for attendance table display)
        $attendances = Attendance::with(['unit', 'attendanceDetails.coach.ts'])
            ->where('unit_id', $unitId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->orderBy('attendance_date')
            ->get();

        // If data exists in DB, use it
        if ($existingContribution && $contributionAmount == $existingContribution->contribution_amount) {
            // Build coachAttendance array from database
            $coachAttendance = [];
            $totalAttendance = 0;
            $totalFinalValue = 0;
            $totalContribution = 0;

            foreach ($existingContribution->contributionDetails as $detail) {
                $coach = $detail->coach;
                
                // Build weeks data from attendances
                $weeks = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
                foreach ($attendances as $attendance) {
                    $date = Carbon::parse($attendance->attendance_date);
                    $dayOfMonth = $date->day;
                    
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

                    $isTraining = $attendance->attendance_status == 'training';
                    
                    if ($isTraining) {
                        foreach ($attendance->attendanceDetails as $attDetail) {
                            if ($attDetail->coach_id == $coach->id) {
                                $weeks[$week][] = [
                                    'date' => $date->toDateString(),
                                    'status' => $attendance->attendance_status,
                                    'reason' => $attendance->reason
                                ];
                            }
                        }
                    } else {
                        $weeks[$week][] = [
                            'date' => $date->toDateString(),
                            'status' => $attendance->attendance_status,
                            'reason' => $attendance->reason
                        ];
                    }
                }

                $coachAttendance[$coach->id] = [
                    'coach' => $coach,
                    'weeks' => $weeks,
                    'total_attendance' => $detail->attendance,
                    'multiplier' => $detail->multiplier,
                    'is_pj' => $detail->is_pj,
                    'final_value' => $detail->final_value,
                    'total_amount' => $detail->total,
                ];

                $totalAttendance += $detail->attendance;
                $totalFinalValue += $detail->final_value;
                $totalContribution += $detail->total;
            }

            return view('pages.admin.attendance.receipt.contribution-unit', [
                'units' => $units,
                'unit' => $unit,
                'month' => $month,
                'year' => $year,
                'coachAttendance' => $coachAttendance,
                'nominalPerMeeting' => $existingContribution->contributionDetails->first()->amount_per_attendance ?? 0,
                'totalContribution' => $totalContribution,
                'pjShare' => $existingContribution->pj_share,
                'kasShare' => $existingContribution->kas_share,
                'savingsShare' => $existingContribution->saving_share,
                'difference' => $existingContribution->difference,
                'unitData' => true,
                'contributionAmount' => $existingContribution->contribution_amount,
                'totalAttendance' => $totalAttendance,
                'totalFinalValue' => $totalFinalValue,
                'existingContribution' => $existingContribution
            ]);
        }

        // If no data in DB or different amount, calculate fresh data for preview
        // Get all coaches who attended in this month
        $coachIds = $attendances->pluck('attendanceDetails')->flatten()->pluck('coach_id')->unique();
        $coaches = \App\Models\Coach::with('ts')
            ->join('ts', 'coachs.ts_id', '=', 'ts.id')
            ->whereIn('coachs.id', $coachIds)
            ->orderBy('ts.ts_seq', 'desc')
            ->orderBy('coachs.name', 'asc')
            ->select('coachs.*')
            ->get();

        // Build attendance report per coach
        $coachAttendance = [];
        foreach ($coaches as $coach) {
            $coachAttendance[$coach->id] = [
                'coach' => $coach,
                'weeks' => [1 => [], 2 => [], 3 => [], 4 => [], 5 => []],
                'total_attendance' => 0
            ];
        }

        // Fill attendance dates
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->attendance_date);
            $dayOfMonth = $date->day;
            
            // Calculate week number
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

            $isTraining = $attendance->attendance_status == 'training';
            
            if ($isTraining) {
                foreach ($attendance->attendanceDetails as $detail) {
                    if (isset($coachAttendance[$detail->coach_id])) {
                        $coachAttendance[$detail->coach_id]['weeks'][$week][] = [
                            'date' => $date->toDateString(),
                            'status' => $attendance->attendance_status,
                            'reason' => $attendance->reason
                        ];
                        $coachAttendance[$detail->coach_id]['total_attendance']++;
                    }
                }
            } else {
                foreach ($coachAttendance as $coachId => &$coachData) {
                    $coachData['weeks'][$week][] = [
                        'date' => $date->toDateString(),
                        'status' => $attendance->attendance_status,
                        'reason' => $attendance->reason
                    ];
                }
            }
        }

        // Calculate contributions
        $totalFinalValue = 0;
        $totalAttendance = 0;
        
        foreach ($coachAttendance as &$data) {
            $coach = $data['coach'];
            $attendance = $data['total_attendance'];
            $multiplier = (int) ($coach->ts->multiplier ?? 1);
            $isPJ = ($unit->pj_id == $coach->id) ? 1 : 0;
            
            $finalValue = $attendance * $multiplier + $isPJ;
            
            $data['multiplier'] = $multiplier;
            $data['is_pj'] = $isPJ;
            $data['final_value'] = $finalValue;
            
            $totalFinalValue += $finalValue;
            $totalAttendance += $data['total_attendance'];
        }
            
        // Calculate nominal per meeting from contribution amount
        $pjShare = $contributionAmount * 0.65;
        $kasShare = $contributionAmount * 0.20;
        $savingsShare = $contributionAmount * 0.15;
        $nominalPerMeeting = $totalFinalValue > 0 ? ($pjShare / $totalFinalValue) : 0;
        $nominalPerMeeting = floor($nominalPerMeeting / 100) * 100;
        $totalContribution = 0;
        
        // Second pass: calculate amounts
        foreach ($coachAttendance as &$data) {
            $totalAmount = $data['final_value'] * $nominalPerMeeting;
            $data['total_amount'] = $totalAmount;
            $totalContribution += $totalAmount;
        }
        
        $difference = $pjShare - $totalContribution;

        return view('pages.admin.attendance.receipt.contribution-unit', [
            'units' => $units,
            'unit' => $unit,
            'month' => $month,
            'year' => $year,
            'coachAttendance' => $coachAttendance,
            'nominalPerMeeting' => $nominalPerMeeting,
            'totalContribution' => $totalContribution,
            'pjShare' => $pjShare,
            'kasShare' => $kasShare,
            'savingsShare' => $savingsShare,
            'difference' => $difference,
            'unitData' => true,
            'contributionAmount' => $contributionAmount,
            'totalAttendance' => $totalAttendance,
            'totalFinalValue' => $totalFinalValue,
            'existingContribution' => $existingContribution
        ]);
    }

    public function contributionHistory(Request $request)
    {
        $user = auth()->user();
        $userRoles = $user->getRoleNames();
        
        // Get all units for filter
        $units = self::GetUnitList();
        
        // Build query
        $contributions = Contribution::with(['unit'])
            ->when(!$userRoles->contains('super-admin') && $userRoles->contains('pj-unit'), function ($query) use ($user) {
                $unitIds = \App\Models\Unit::where('pj_id', $user->coach_id)->pluck('id');
                return $query->whereIn('unit_id', $unitIds);
            })
            ->when($request->filled('unit_id'), function ($query) use ($request) {
                return $query->where('unit_id', $request->unit_id);
            })
            ->when($request->filled('periode'), function ($query) use ($request) {
                return $query->where('periode', 'like', $request->periode . '%');
            })
            ->orderBy('periode', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.admin.attendance.receipt.contribution-history', [
            'contributions' => $contributions,
            'units' => $units
        ]);
    }

    public function deleteContribution($id)
    {
        try {
            $contribution = Contribution::findOrFail($id);
            
            // Delete contribution details first
            ContributionDetail::where('contribution_id', $id)->delete();
            
            // Delete contribution
            $contribution->delete();
            
            return response()->json([
                'error' => false,
                'message' => 'Data kontribusi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Gagal menghapus data kontribusi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function attendancePercentageReport(Request $request)
    {
        // Handle month picker format (YYYY-MM)
        $startPeriod = $request->get('start_period', date('Y') . '-01');
        $endPeriod = $request->get('end_period', date('Y') . '-12');
        $tsFilter = $request->get('ts_id');
        
        // Extract year and month from period
        $startDate = Carbon::parse($startPeriod . '-01');
        $endDate = Carbon::parse($endPeriod . '-01')->endOfMonth();
        
        // Get all belt levels for filter
        $tsList = \App\Models\Ts::orderBy('name', 'asc')->get();
        
        // Get Kalideres unit ID
        $kalideresUnitId = 'a0cc1baa-2345-4c29-b2e9-5cb47b770f2b';
        
        // Execute raw SQL query
        $results = \DB::select("
            SELECT 
                DATE_FORMAT(a.attendance_date, '%Y-%m') AS periode, 
                u.name AS nama_pelatih, 
                ts.name AS tingkatan_sabuk, 
                SUM(
                    CASE WHEN CAST(
                        a.unit_id AS BINARY(16)
                    ) <> CAST(
                        ? AS BINARY(16)
                    ) THEN 1 ELSE 0 END
                ) AS kehadiran_di_unit, 
                SUM(
                    CASE WHEN CAST(
                        a.unit_id AS BINARY(16)
                    ) = CAST(
                        ? AS BINARY(16)
                    ) THEN 1 ELSE 0 END
                ) AS kehadiran_di_kalideres 
            FROM 
                attendances a 
                JOIN attendance_details ad ON ad.attendance_id = a.id 
                JOIN coachs u ON u.id = ad.coach_id 
                JOIN ts ts ON ts.id = u.ts_id
            WHERE 
                a.deleted_at IS NULL 
                AND ad.deleted_at IS NULL 
                AND a.attendance_status = 'training' 
                AND a.attendance_date BETWEEN ? AND ?
                " . ($tsFilter ? " AND u.ts_id = ?" : "") . "
            GROUP BY 
                periode, 
                u.id, 
                u.name, 
                u.ts_id 
            ORDER BY 
                periode ASC, 
                u.name ASC
        ", array_merge(
            [
                $kalideresUnitId, $kalideresUnitId,
                $startDate->toDateString(), $endDate->toDateString()
            ],
            $tsFilter ? [$tsFilter] : []
        ));
        
        // Generate all months in the selected range
        $months = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $months[] = $current->format('Y-m');
            $current->addMonth();
        }
        
        // Transform data: Group by coach and pivot by month
        $coaches = [];
        
        foreach ($results as $row) {
            $coachKey = $row->nama_pelatih;
            
            if (!isset($coaches[$coachKey])) {
                $coaches[$coachKey] = [
                    'nama_pelatih' => $row->nama_pelatih,
                    'tingkatan_sabuk' => $row->tingkatan_sabuk,
                    'months' => []
                ];
            }
            
            // Add monthly data
            $coaches[$coachKey]['months'][$row->periode] = [
                'kehadiran_di_unit' => $row->kehadiran_di_unit,
                'kehadiran_di_kalideres' => $row->kehadiran_di_kalideres
            ];
        }
        
        return view('pages.admin.attendance.report.attendance-percentage', [
            'coaches' => $coaches,
            'months' => $months,
            'startPeriod' => $startPeriod,
            'endPeriod' => $endPeriod,
            'tsFilter' => $tsFilter,
            'tsList' => $tsList
        ]);
    }
}

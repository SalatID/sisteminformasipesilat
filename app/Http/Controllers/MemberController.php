<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class MemberController extends Controller
{
    /**
     * Upload and save document image
     */
    private function handleDocumentImage($request, $fieldName, $documentType)
    {
        if (!$request->hasFile($fieldName)) {
            return null;
        }

        $image = $request->file($fieldName);
        
        // Additional security validation
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $fileMimeType = $image->getMimeType();
        
        if (!in_array($fileMimeType, $allowedMimes)) {
            return null;
        }
        
        // Verify it's actually an image
        $imageInfo = @getimagesize($image->getRealPath());
        if ($imageInfo === false) {
            return null;
        }
        
        // Move to appropriate directory
        if (app()->environment('production')) {
            $dir = base_path('../../public_html/sip/members/documents/');
        } else {
            $dir = public_path('storage/members/documents/');
        }
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $imageName = 'document_' . $documentType . '_' . time() . '.' . $image->getClientOriginalExtension();
        $image->move($dir, $imageName);
        
        return 'storage/members/documents/' . $imageName;
    }

    /**
     * Generate unique member ID based on joined_date
     * Format: YYMMSEQ (e.g., 2401001)
     */
    private function generateMemberId($joinedDate)
    {
        $date = Carbon::parse($joinedDate);
        $yearMonth = $date->format('ym');
        
        // Find the last sequence number for this month
        $lastMember = \App\Models\Member::where('member_id', 'like', $yearMonth . '%')
            ->orderBy('member_id', 'desc')
            ->first();
        
        if ($lastMember) {
            // Extract sequence from last member_id
            $lastSeq = (int) substr($lastMember->member_id, -3);
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }
        
        return $yearMonth . str_pad($newSeq, 3, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        $members = \App\Models\Member::with([
            'ts:id,name,ts_seq',
            'unit:id,name',
            'exams' => function($query) {
                $query->where('examinee_type', 'member')
                      ->whereNull('deleted_at')
                      ->latest('exam_date')
                      ->limit(1);
            },
            'exams.tsBefore:id,name',
            'exams.tsAfter:id,name'
        ])
            ->where(function($query) {
                // Exclude pending self-registered members
                $query->where('registration_status', '!=', 'pending')
                      ->orWhere('is_self_registered', false);
            })
            ->join('ts', 'ts.id', '=', 'members.ts_id')
            ->select('members.*')
            ->orderBy('ts.ts_seq', 'desc')
            ->orderBy('members.name', 'asc')
            ->paginate(15);
        
        return view('pages.admin.member.list', compact('members'));
    }

    public function create()
    {
        $ts_list = \App\Models\Ts::orderBy('ts_seq', 'asc')->get();
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();
        return view('pages.admin.member.form', compact('ts_list', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ts_id' => ['required', 'uuid', 'exists:ts,id'],
            'joined_date' => ['required', 'date'],
            'unit_id' => ['nullable', 'uuid', 'exists:units,id'],
            'gender' => ['nullable', 'in:male,female'],
            'school_level' => ['nullable', 'in:SD,SMP,SMA/K,Kuliah,Bekerja'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'citizen_number' => ['required', 'string', 'max:50', 'unique:members,citizen_number'],
            'family_card_number' => ['required', 'string', 'max:50', 'unique:members,family_card_number'],
            'bpjs_number' => ['required', 'string', 'max:50', 'unique:members,bpjs_number'],
            'citizen_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'family_card_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'bpjs_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Auto-generate member_id
        $validated['member_id'] = $this->generateMemberId($validated['joined_date']);

        if ($request->hasFile('picture')) {
            $image = $request->file('picture');
            
            // Additional security validation
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileMimeType = $image->getMimeType();
            
            if (!in_array($fileMimeType, $allowedMimes)) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'File harus berupa gambar (JPEG, PNG, GIF)'
                ])->withInput();
            }
            
            // Verify it's actually an image by checking image properties
            $imageInfo = @getimagesize($image->getRealPath());
            if ($imageInfo === false) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'File yang diupload bukan gambar yang valid'
                ])->withInput();
            }
            
            // Move to appropriate directory based on environment
            if (app()->environment('production')) {
                // Production: public_html structure
                $dir = base_path('../../public_html/sip/members/');
            } else {
                // Local development: standard public folder
                $dir = public_path('storage/members/');
            }
            
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $imageName = 'member_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($dir, $imageName);
            $validated['picture'] = 'storage/members/' . $imageName;
        }

        // Handle document images
        if ($citizenImg = $this->handleDocumentImage($request, 'citizen_img', 'citizen')) {
            $validated['citizen_img'] = $citizenImg;
        }
        if ($familyCardImg = $this->handleDocumentImage($request, 'family_card_img', 'family_card')) {
            $validated['family_card_img'] = $familyCardImg;
        }
        if ($bpjsImg = $this->handleDocumentImage($request, 'bpjs_img', 'bpjs')) {
            $validated['bpjs_img'] = $bpjsImg;
        }

        $validated['created_by'] = auth()->user()->id;
        // Admin-added members are automatically approved
        $validated['registration_status'] = 'approved';
        $validated['is_self_registered'] = false;
        $validated['approved_by'] = auth()->user()->id;
        $validated['approved_at'] = now();

        if (\App\Models\Member::create($validated)) {
            return redirect()->route('member.index')->with([
                'error' => false,
                'message' => 'Pesilat berhasil ditambahkan'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal menambahkan pesilat'
        ])->withInput();
    }

    public function show(Request $request, $id)
    {
        $member = \App\Models\Member::with([
            'ts:id,name,ts_seq',
            'unit:id,name',
            'attendanceDetails.attendance:id,attendance_date,attendance_status,unit_id',
            'attendanceDetails.attendance.unit:id,name'
        ])->find($id);

        if (!$member) {
            return redirect()->route('member.index')->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        // Set default date ranges for attendance
        $now = Carbon::now();
        $currentMonthStart = $now->clone()->startOfMonth();
        $currentMonthEnd = $now->clone()->endOfMonth();

        // Get date filter parameters for attendance from query string
        $attendanceMonth = $request->get('attendance_month', $now->format('Y-m'));
        $dateStart = Carbon::createFromFormat('Y-m', $attendanceMonth)->startOfMonth()->toDateString();
        $dateEnd = Carbon::createFromFormat('Y-m', $attendanceMonth)->endOfMonth()->toDateString();

        // Get attendance history with date filter
        $attendances = \App\Models\MemberAttendanceDetail::where('member_id', $id)
            ->with('attendance:id,attendance_date,attendance_status,unit_id')
            ->with('attendance.unit:id,name')
            ->whereHas('attendance', function($query) use ($dateStart, $dateEnd) {
                $query->whereBetween('attendance_date', [$dateStart, $dateEnd]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get exam history
        $exams = \App\Models\Exam::where('examinee_type', 'member')
            ->where('examinee_id', $id)
            ->with(['tsBefore:id,name', 'tsAfter:id,name'])
            ->whereNull('deleted_at')
            ->orderBy('exam_date', 'desc')
            ->get();

        // Get performance records
        $performance_records = \App\Models\MemberPerformanceRecord::where('member_id', $id)
            ->with('unit:id,name')
            ->orderBy('training_date', 'desc')
            ->get();

        // Calculate attendance summary for selected date range
        $attendanceSummary = \App\Models\MemberAttendanceDetail::where('member_id', $id)
            ->with('attendance:id,attendance_date,unit_id')
            ->with('attendance.unit:id,name')
            ->whereHas('attendance', function($query) use ($dateStart, $dateEnd) {
                $query->whereBetween('attendance_date', [$dateStart, $dateEnd]);
            })
            ->get();

        $totalAttendance = $attendanceSummary->count();
        
        $attendanceByUnit = $attendanceSummary->groupBy('attendance.unit.name')
            ->map(function($items) {
                return $items->count();
            })
            ->toArray();

        // Get average performance for member
        $performance_average = \App\Models\MemberPerformanceRecord::where('member_id', $id)
            ->select(
                DB::raw('ROUND(AVG(endurance), 2) as avg_endurance'),
                DB::raw('ROUND(AVG(strength), 2) as avg_strength'),
                DB::raw('ROUND(AVG(technique), 2) as avg_technique'),
                DB::raw('COUNT(*) as total_records')
            )
            ->first();

        $attendance_summary = [
            'total' => $totalAttendance,
            'by_unit' => $attendanceByUnit
        ];

        return view('pages.admin.member.show', compact(
            'member',
            'attendances',
            'exams',
            'performance_records',
            'attendance_summary',
            'performance_average'
        ));
    }

    public function edit($id)
    {
        $member = \App\Models\Member::find($id);
        
        if (!$member) {
            return redirect()->route('member.index')->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $ts_list = \App\Models\Ts::orderBy('ts_seq', 'asc')->get();
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();

        // Get exam history for the edit page
        $exams = \App\Models\Exam::where('examinee_type', 'member')
            ->where('examinee_id', $id)
            ->with(['tsBefore:id,name', 'tsAfter:id,name'])
            ->orderBy('exam_date', 'desc')
            ->get();

        // Get available training centers (not already attached to this member)
        $attachedCenterIds = $member->trainingCenters()->pluck('training_centers.id')->toArray();
        $available_training_centers = \App\Models\TrainingCenter::whereNotIn('id', $attachedCenterIds)
            ->orderBy('name', 'asc')
            ->get();

        // Day labels for training centers
        $dayLabels = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        return view('pages.admin.member.form', compact('member', 'ts_list', 'units', 'exams', 'available_training_centers', 'dayLabels'));
    }

    public function update(Request $request, $id)
    {
        $member = \App\Models\Member::find($id);

        if (!$member) {
            return redirect()->route('member.index')->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'member_id' => ['required', 'string', 'max:50', 'unique:members,member_id,' . $id . ',id'],
            'ts_id' => ['required', 'uuid', 'exists:ts,id'],
            'joined_date' => ['required', 'date'],
            'unit_id' => ['nullable', 'uuid', 'exists:units,id'],
            'gender' => ['nullable', 'in:male,female'],
            'school_level' => ['nullable', 'string', 'max:100'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'citizen_number' => ['required', 'string', 'max:50', 'unique:members,citizen_number,' . $id . ',id'],
            'family_card_number' => ['required', 'string', 'max:50', 'unique:members,family_card_number,' . $id . ',id'],
            'bpjs_number' => ['required', 'string', 'max:50', 'unique:members,bpjs_number,' . $id . ',id'],
            'citizen_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'family_card_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'bpjs_img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($request->hasFile('picture')) {
            // Delete old picture if exists
            if ($member->picture && file_exists(public_path($member->picture))) {
                unlink(public_path($member->picture));
            }

            $image = $request->file('picture');
            
            // Additional security validation
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileMimeType = $image->getMimeType();
            
            if (!in_array($fileMimeType, $allowedMimes)) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'File harus berupa gambar (JPEG, PNG, GIF)'
                ])->withInput();
            }
            
            // Verify it's actually an image by checking image properties
            $imageInfo = @getimagesize($image->getRealPath());
            if ($imageInfo === false) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'File yang diupload bukan gambar yang valid'
                ])->withInput();
            }
            
            // Move to appropriate directory based on environment
            if (app()->environment('production')) {
                // Production: public_html structure
                $dir = base_path('../../public_html/sip/members/');
            } else {
                // Local development: standard public folder
                $dir = public_path('storage/members/');
            }
            
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $imageName = 'member_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($dir, $imageName);
            $validated['picture'] = 'storage/members/' . $imageName;
        }

        // Handle document images
        if ($citizenImg = $this->handleDocumentImage($request, 'citizen_img', 'citizen')) {
            // Delete old image if exists
            if ($member->citizen_img && file_exists(public_path($member->citizen_img))) {
                unlink(public_path($member->citizen_img));
            }
            $validated['citizen_img'] = $citizenImg;
        }
        if ($familyCardImg = $this->handleDocumentImage($request, 'family_card_img', 'family_card')) {
            // Delete old image if exists
            if ($member->family_card_img && file_exists(public_path($member->family_card_img))) {
                unlink(public_path($member->family_card_img));
            }
            $validated['family_card_img'] = $familyCardImg;
        }
        if ($bpjsImg = $this->handleDocumentImage($request, 'bpjs_img', 'bpjs')) {
            // Delete old image if exists
            if ($member->bpjs_img && file_exists(public_path($member->bpjs_img))) {
                unlink(public_path($member->bpjs_img));
            }
            $validated['bpjs_img'] = $bpjsImg;
        }

        $validated['updated_by'] = auth()->user()->id;

        if ($member->update($validated)) {
            return redirect()->route('member.show', $id)->with([
                'error' => false,
                'message' => 'Pesilat berhasil diperbarui'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal memperbarui pesilat'
        ])->withInput();
    }

    public function destroy($id)
    {
        $member = \App\Models\Member::find($id);

        if (!$member) {
            return redirect()->route('member.index')->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $member->deleted_by = auth()->user()->id;
        $member->save();
        $member->delete();

        return redirect()->route('member.index')->with([
            'error' => false,
            'message' => 'Pesilat berhasil dihapus'
        ]);
    }

    public function memberExamStore(Request $request, $memberId)
    {
        $member = \App\Models\Member::find($memberId);
        
        if (!$member) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $validated = $request->validate([
            'exam_date' => ['required', 'date'],
            'exam_end_date' => ['required', 'date', 'after_or_equal:exam_date'],
            'exam_location' => ['required', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'ts_before' => ['nullable', 'uuid', 'exists:ts,id'],
            'ts_after' => ['nullable', 'uuid', 'exists:ts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['examinee_id'] = $memberId;
        $validated['examinee_type'] = 'member';
        $validated['created_by'] = auth()->user()->id;

        if (\App\Models\Exam::create($validated)) {
            return redirect()->route('member.edit', $memberId)->with([
                'error' => false,
                'message' => 'Riwayat ujian berhasil ditambahkan'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal menambahkan riwayat ujian'
        ])->withInput();
    }

    public function memberExamDestroy($memberId, $examId)
    {
        $exam = \App\Models\Exam::where('id', $examId)
            ->where('examinee_type', 'member')
            ->where('examinee_id', $memberId)
            ->first();
        
        if (!$exam) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Riwayat ujian tidak ditemukan'
            ]);
        }

        $exam->deleted_by = auth()->user()->id;
        $exam->save();
        $exam->delete();

        return redirect()->back()->with([
            'error' => false,
            'message' => 'Riwayat ujian berhasil dihapus'
        ]);
    }

    /**
     * Show pending member registrations
     */
    public function pending()
    {
        $pending_members = \App\Models\Member::pending()
            ->where('is_self_registered', true)
            ->with(['ts:id,name,ts_seq', 'unit:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('pages.admin.member.pending-registrations', compact('pending_members'));
    }

    /**
     * Approve member registration
     */
    public function approveMember($id)
    {
        $member = \App\Models\Member::find($id);

        if (!$member) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        if (!$member->isPending()) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pendaftaran ini sudah diproses sebelumnya'
            ]);
        }

        $member->approve(auth()->user()->id);

        return redirect()->back()->with([
            'error' => false,
            'message' => 'Pendaftaran ' . $member->name . ' berhasil disetujui!'
        ]);
    }

    /**
     * Reject member registration
     */
    public function rejectMember(Request $request, $id)
    {
        $member = \App\Models\Member::find($id);

        if (!$member) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        if (!$member->isPending()) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pendaftaran ini sudah diproses sebelumnya'
            ]);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $member->reject(auth()->user()->id, $validated['rejection_reason']);

        return redirect()->back()->with([
            'error' => false,
            'message' => 'Pendaftaran ' . $member->name . ' berhasil ditolak!'
        ]);
    }
}

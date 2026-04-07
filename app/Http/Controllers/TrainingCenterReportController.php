<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class TrainingCenterReportController extends Controller
{
    /**
     * Show list of training center reports
     */
    public function index()
    {
        $reports = \App\Models\MemberPerformanceRecord::with(['trainingCenter', 'member'])
            ->orderBy('training_date', 'desc')
            ->paginate(20);

        return view('pages.admin.training-center-report.index', compact('reports'));
    }

    /**
     * Show form to select training center and date
     */
    public function create()
    {
        $training_centers = \App\Models\TrainingCenter::orderBy('name', 'asc')->get();
        $training_center = null;
        $training_date = null;
        $training_type = null;
        $members = collect();
        $existingRecords = [];
        
        return view('pages.admin.training-center-report.create', compact(
            'training_centers',
            'training_center',
            'training_date',
            'training_type',
            'members',
            'existingRecords'
        ));
    }

    /**
     * Show input form for creating new report with members list
     * @deprecated Use create() method instead - kept for backward compatibility
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'training_center_id' => ['required', 'uuid', 'exists:training_centers,id'],
            'training_date' => ['required', 'date'],
            'training_type' => ['required', 'in:online,offline'],
        ]);

        $training_center = \App\Models\TrainingCenter::findOrFail($validated['training_center_id']);
        $training_date = $validated['training_date'];
        $training_type = $validated['training_type'];

        // Get members associated with this training center
        $members = $training_center->members()
            ->with(['ts:id,name'])
            ->orderBy('members.name', 'asc')
            ->get();

        // Check if records already exist for this date
        $existingRecords = \App\Models\MemberPerformanceRecord::where('training_center_id', $validated['training_center_id'])
            ->whereDate('training_date', $training_date)
            ->get()
            ->keyBy('member_id');

        return view('pages.admin.training-center-report.add', compact(
            'training_center',
            'training_date',
            'training_type',
            'members',
            'existingRecords'
        ));
    }

    /**
     * Get members for a training center (AJAX endpoint)
     */
    public function getMembers(Request $request)
    {
        $validated = $request->validate([
            'training_center_id' => ['required', 'uuid', 'exists:training_centers,id'],
            'training_date' => ['required', 'date'],
        ]);

        $training_center = \App\Models\TrainingCenter::findOrFail($validated['training_center_id']);
        
        // Get members associated with this training center
        $members = $training_center->members()
            ->with(['ts:id,name'])
            ->orderBy('members.name', 'asc')
            ->get();

        // Check if records already exist for this date
        $existingRecords = \App\Models\MemberPerformanceRecord::where('training_center_id', $validated['training_center_id'])
            ->whereDate('training_date', $validated['training_date'])
            ->get()
            ->keyBy('member_id');

        return response()->json([
            'success' => true,
            'training_center' => $training_center,
            'members' => $members,
            'existing_records' => $existingRecords,
        ]);
    }

    /**
     * Show report with saved data (read-only display)
     */
    public function show(Request $request, $training_center_id)
    {
        $query = \App\Models\MemberPerformanceRecord::with(['trainingCenter', 'member'])
            ->where('training_center_id', $training_center_id);

        // Filter by date if provided
        if ($request->has('date')) {
            $query->whereDate('training_date', $request->date);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('training_type', $request->type);
        }

        $records = $query->orderBy('training_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('training-center-report.index')->with('error', 'Laporan tidak ditemukan');
        }

        $training_center = $records->first()->trainingCenter;
        $grouped = $records->groupBy(['training_date', 'training_type']);

        return view('pages.admin.training-center-report.show', compact(
            'training_center',
            'records',
            'grouped'
        ));
    }

    /**
     * Show edit form for existing report
     */
    public function edit($training_center_id)
    {
        $training_center = \App\Models\TrainingCenter::findOrFail($training_center_id);

        // Get all unique dates for this TC (grouped by date and type)
        $records = \App\Models\MemberPerformanceRecord::with(['member'])
            ->where('training_center_id', $training_center_id)
            ->orderBy('training_date', 'desc')
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('training-center-report.index')->with('error', 'Laporan tidak ditemukan');
        }

        // Get the latest record's date and type as default
        $firstRecord = $records->first();
        $training_date = $firstRecord->training_date->toDateString();
        $training_type = $firstRecord->training_type;

        // Get members associated with this training center
        $members = $training_center->members()
            ->with(['ts:id,name'])
            ->orderBy('members.name', 'asc')
            ->get();

        // Get existing records for the date/type
        $existingRecords = $records
            ->filter(fn($r) => $r->training_date->toDateString() === $training_date && $r->training_type === $training_type)
            ->keyBy('member_id');

        return view('pages.admin.training-center-report.edit', compact(
            'training_center',
            'training_date',
            'training_type',
            'members',
            'existingRecords'
        ));
    }

    /**
     * Store new bulk performance records
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_center_id' => ['required', 'uuid', 'exists:training_centers,id'],
            'training_date' => ['required', 'date'],
            'training_type' => ['required', 'in:online,offline'],
            'members' => ['required', 'array'],
            'members.*.member_id' => ['required', 'uuid', 'exists:members,id'],
            'members.*.attended' => ['nullable', 'in:1'],
            'members.*.kas' => ['nullable', 'in:1'],
            'members.*.endurance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'members.*.strength' => ['nullable', 'integer', 'min:0', 'max:100'],
            'members.*.technique' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $training_center_id = $validated['training_center_id'];
        $training_date = $validated['training_date'];
        $training_type = $validated['training_type'];

        // Delete existing records for this date/center (refresh)
        \App\Models\MemberPerformanceRecord::where('training_center_id', $training_center_id)
            ->whereDate('training_date', $training_date)
            ->where('training_type', $training_type)
            ->delete();

        // Create new records
        foreach ($validated['members'] as $memberData) {
            \App\Models\MemberPerformanceRecord::create([
                'member_id' => $memberData['member_id'],
                'training_center_id' => $training_center_id,
                'training_date' => $training_date,
                'training_type' => $training_type,
                'attended' => isset($memberData['attended']) && $memberData['attended'] == '1',
                'kas' => isset($memberData['kas']) && $memberData['kas'] == '1',
                'endurance' => $memberData['endurance'] ?? null,
                'strength' => $memberData['strength'] ?? null,
                'technique' => $memberData['technique'] ?? null,
                'created_by' => auth()->user()->id,
            ]);
        }

        return redirect()->route('training-center-report.show', $training_center_id)->with([
            'error' => false,
            'message' => 'Laporan TC berhasil disimpan!'
        ]);
    }

    /**
     * Update existing bulk performance records
     */
    public function update(Request $request, $training_center_id)
    {
        $validated = $request->validate([
            'training_date' => ['required', 'date'],
            'training_type' => ['required', 'in:online,offline'],
            'members' => ['required', 'array'],
            'members.*.member_id' => ['required', 'uuid', 'exists:members,id'],
            'members.*.attended' => ['nullable', 'in:1'],
            'members.*.kas' => ['nullable', 'in:1'],
            'members.*.endurance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'members.*.strength' => ['nullable', 'integer', 'min:0', 'max:100'],
            'members.*.technique' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $training_date = $validated['training_date'];
        $training_type = $validated['training_type'];

        // Delete existing records for this date/center/type
        \App\Models\MemberPerformanceRecord::where('training_center_id', $training_center_id)
            ->whereDate('training_date', $training_date)
            ->where('training_type', $training_type)
            ->delete();

        // Create new records
        foreach ($validated['members'] as $memberData) {
            \App\Models\MemberPerformanceRecord::create([
                'member_id' => $memberData['member_id'],
                'training_center_id' => $training_center_id,
                'training_date' => $training_date,
                'training_type' => $training_type,
                'attended' => isset($memberData['attended']) && $memberData['attended'] == '1',
                'kas' => isset($memberData['kas']) && $memberData['kas'] == '1',
                'endurance' => $memberData['endurance'] ?? null,
                'strength' => $memberData['strength'] ?? null,
                'technique' => $memberData['technique'] ?? null,
                'updated_by' => auth()->user()->id,
            ]);
        }

        return redirect()->route('training-center-report.show', $training_center_id)->with([
            'error' => false,
            'message' => 'Laporan TC berhasil diperbarui!'
        ]);
    }
}

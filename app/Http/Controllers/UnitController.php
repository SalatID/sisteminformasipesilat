<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Coach;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::with('pj')->orderBy('name', 'asc')->get();
        $coaches = Coach::orderBy('name', 'asc')->get();
        return view('pages.admin.master-data.unit.list', compact('units', 'coaches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'training_day' => ['required', 'string'],
            'pj_id' => ['required', 'uuid', 'exists:coachs,id'],
            'training_hours_start' => ['required', 'date_format:H:i'],
            'training_hours_end' => ['required', 'date_format:H:i'],
            'paid_fee_type' => ['required', 'string'],
            'paid_periode' => ['required', 'string'],
            'school_pic_name' => ['required', 'string'],
            'school_pic_number' => ['required', 'string'],
            'school_level' => ['required', 'string'],
            'school_pic_occupation' => ['required', 'string'],
            'joined_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $credentials['is_active'] = $request->has('is_active') ? true : false;
        $credentials['created_by'] = auth()->user()->id;

        if (Unit::create($credentials)) {
            return redirect()->back()->with([
                'error' => false,
                'message' => 'Unit berhasil ditambahkan'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal menambahkan Unit'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $decrypted = Crypt::decryptString($id);
            $unit = Unit::with('pj')->find($decrypted);
            if ($unit) {
                return response()->json($unit);
            }
            return response()->json(['error' => true, 'message' => 'Unit tidak ditemukan'], 404);
        } catch (DecryptException $e) {
            return response()->json(['error' => true, 'message' => 'Decrypt Failed'], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $decrypted = Crypt::decryptString($id);
            $unit = Unit::with('pj')->find($decrypted);
            if ($unit) {
                return response()->json($unit);
            }
            return response()->json(['error' => true, 'message' => 'Unit tidak ditemukan'], 404);
        } catch (DecryptException $e) {
            return response()->json(['error' => true, 'message' => 'Decrypt Failed'], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        try {
            $decrypted = Crypt::decryptString($id);
            $unit = Unit::find($decrypted);

            if (!$unit) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'Unit tidak ditemukan'
                ]);
            }

            $credentials = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'training_day' => ['required', 'string'],
                'pj_id' => ['required', 'uuid', 'exists:coachs,id'],
                'training_hours_start' => ['required', 'date_format:H:i'],
                'training_hours_end' => ['required', 'date_format:H:i'],
                'paid_fee_type' => ['required', 'string'],
                'paid_periode' => ['required', 'string'],
                'school_pic_name' => ['required', 'string'],
                'school_pic_number' => ['required', 'string'],
                'school_level' => ['required', 'string'],
                'school_pic_occupation' => ['required', 'string'],
                'joined_date' => ['required', 'date'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $credentials['is_active'] = $request->has('is_active') ? true : false;
            $credentials['updated_by'] = auth()->user()->id;

            if ($unit->update($credentials)) {
                return redirect()->route('unit.index')->with([
                    'error' => false,
                    'message' => 'Unit berhasil diperbarui'
                ]);
            }

            return redirect()->back()->with([
                'error' => true,
                'message' => 'Gagal memperbarui Unit'
            ]);
        } catch (DecryptException $e) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Decrypt Failed'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $decrypted = Crypt::decryptString($id);
            $unit = Unit::find($decrypted);

            if (!$unit) {
                return redirect()->back()->with([
                    'error' => true,
                    'message' => 'Unit tidak ditemukan'
                ]);
            }

            $unit->deleted_by = auth()->user()->id;
            $unit->save();
            $unit->delete();

            return redirect()->back()->with([
                'error' => false,
                'message' => 'Unit berhasil dihapus'
            ]);
        } catch (DecryptException $e) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Decrypt Failed'
            ]);
        }
    }

    /**
     * Toggle unit active status
     */
    public function toggleStatus($id, Request $request)
    {
        try {
            $decrypted = Crypt::decryptString($id);
            $unit = Unit::find($decrypted);

            if (!$unit) {
                return response()->json([
                    'error' => true,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }

            $unit->is_active = $request->input('is_active', !$unit->is_active);
            $unit->updated_by = auth()->user()->id;
            $unit->save();

            return response()->json([
                'error' => false,
                'message' => $unit->is_active ? 'Unit diaktifkan' : 'Unit dinonaktifkan',
                'is_active' => $unit->is_active
            ]);
        } catch (DecryptException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Decrypt Failed'
            ], 400);
        }
    }
}

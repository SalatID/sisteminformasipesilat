<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrainingCenterController extends Controller
{
    /**
     * List all training centers
     */
    public function index()
    {
        $centers = \App\Models\TrainingCenter::orderBy('name', 'asc')
            ->withCount('members')
            ->paginate(15);
        
        return view('pages.admin.training-center.index', compact('centers'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('pages.admin.training-center.form');
    }

    /**
     * Store new training center
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'training_days' => ['required', 'array', 'min:2', 'max:2'],
            'training_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'training_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ], [
            'training_days.required' => 'Hari training harus dipilih (2 hari)',
            'training_days.min' => 'Minimal harus memilih 2 hari training',
            'training_days.max' => 'Maksimal hanya 2 hari training yang dapat dipilih',
            'training_time.required' => 'Jam training harus diisi',
            'training_time.regex' => 'Format jam training tidak valid. Gunakan format HH:MM-HH:MM (contoh: 10:00-12:00)',
        ]);

        if (\App\Models\TrainingCenter::create($validated)) {
            return redirect()->route('training-center.index')->with([
                'error' => false,
                'message' => 'Training Center berhasil ditambahkan'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal menambahkan Training Center'
        ])->withInput();
    }

    /**
     * Show training center
     */
    public function show($id)
    {
        $center = \App\Models\TrainingCenter::with('members')->find($id);

        if (!$center) {
            return redirect()->route('training-center.index')->with([
                'error' => true,
                'message' => 'Training Center tidak ditemukan'
            ]);
        }

        return view('pages.admin.training-center.show', compact('center'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $center = \App\Models\TrainingCenter::find($id);

        if (!$center) {
            return redirect()->route('training-center.index')->with([
                'error' => true,
                'message' => 'Training Center tidak ditemukan'
            ]);
        }

        return view('pages.admin.training-center.form', compact('center'));
    }

    /**
     * Update training center
     */
    public function update(Request $request, $id)
    {
        $center = \App\Models\TrainingCenter::find($id);

        if (!$center) {
            return redirect()->route('training-center.index')->with([
                'error' => true,
                'message' => 'Training Center tidak ditemukan'
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'training_days' => ['required', 'array', 'min:2', 'max:2'],
            'training_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'training_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]-([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ], [
            'training_days.required' => 'Hari training harus dipilih (2 hari)',
            'training_days.min' => 'Minimal harus memilih 2 hari training',
            'training_days.max' => 'Maksimal hanya 2 hari training yang dapat dipilih',
            'training_time.required' => 'Jam training harus diisi',
            'training_time.regex' => 'Format jam training tidak valid. Gunakan format HH:MM-HH:MM (contoh: 10:00-12:00)',
        ]);

        if ($center->update($validated)) {
            return redirect()->route('training-center.show', $id)->with([
                'error' => false,
                'message' => 'Training Center berhasil diperbarui'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal memperbarui Training Center'
        ])->withInput();
    }

    /**
     * Delete training center
     */
    public function destroy($id)
    {
        $center = \App\Models\TrainingCenter::find($id);

        if (!$center) {
            return redirect()->route('training-center.index')->with([
                'error' => true,
                'message' => 'Training Center tidak ditemukan'
            ]);
        }

        if ($center->delete()) {
            return redirect()->route('training-center.index')->with([
                'error' => false,
                'message' => 'Training Center berhasil dihapus'
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal menghapus Training Center'
        ]);
    }
}

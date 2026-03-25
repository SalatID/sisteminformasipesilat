<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberTrainingCenterController extends Controller
{
    /**
     * Attach training center to member
     */
    public function attach(Request $request, $memberId)
    {
        $member = \App\Models\Member::find($memberId);

        if (!$member) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $validated = $request->validate([
            'training_center_id' => ['required', 'uuid', 'exists:training_centers,id'],
            'joined_date' => ['required', 'date'],
        ]);

        // Check if already attached
        if ($member->trainingCenters()->where('training_center_id', $validated['training_center_id'])->exists()) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat sudah terdaftar di training center ini'
            ]);
        }

        // Attach with pivot data
        $member->trainingCenters()->attach($validated['training_center_id'], [
            'joined_date' => $validated['joined_date']
        ]);

        $center = \App\Models\TrainingCenter::find($validated['training_center_id']);

        return redirect()->back()->with([
            'error' => false,
            'message' => 'Pesilat berhasil ditambahkan ke ' . $center->name
        ]);
    }

    /**
     * Detach training center from member
     */
    public function detach($memberId, $trainingCenterId)
    {
        $member = \App\Models\Member::find($memberId);

        if (!$member) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Pesilat tidak ditemukan'
            ]);
        }

        $center = \App\Models\TrainingCenter::find($trainingCenterId);

        if (!$center) {
            return redirect()->back()->with([
                'error' => true,
                'message' => 'Training Center tidak ditemukan'
            ]);
        }

        $member->trainingCenters()->detach($trainingCenterId);

        return redirect()->back()->with([
            'error' => false,
            'message' => 'Pesilat berhasil dihapus dari ' . $center->name
        ]);
    }

    /**
     * Get all training centers for member (API endpoint for AJAX)
     */
    public function getMemberCenters($memberId)
    {
        $member = \App\Models\Member::with('trainingCenters')->find($memberId);

        if (!$member) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        return response()->json($member->trainingCenters);
    }

    /**
     * Get available training centers (not attached to member)
     */
    public function getAvailableCenters($memberId)
    {
        $member = \App\Models\Member::find($memberId);

        if (!$member) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        // Get training centers not attached to this member
        $attachedIds = $member->trainingCenters()->pluck('training_centers.id')->toArray();
        
        $available = \App\Models\TrainingCenter::whereNotIn('id', $attachedIds)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($available);
    }
}

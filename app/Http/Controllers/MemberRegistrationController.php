<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class MemberRegistrationController extends Controller
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
     * Show public registration form
     */
    public function create()
    {
        $ts_list = \App\Models\Ts::orderBy('ts_seq', 'asc')->get();
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();
        
        return view('pages.public.member-registration', compact('ts_list', 'units'));
    }

    /**
     * Store self-registered member (pending approval)
     */
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

        // Handle picture upload
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

        // Set as self-registered and pending approval
        $validated['is_self_registered'] = true;
        $validated['registration_status'] = 'pending';
        $validated['joined_date'] = Carbon::parse($validated['joined_date'])->format('Y-m-d');

        // Generate member_id
        $validated['member_id'] = $this->generateMemberId($validated['joined_date']);

        if (\App\Models\Member::create($validated)) {
            return redirect()->route('member.registration.success')->with([
                'error' => false,
                'message' => 'Pendaftaran Anda berhasil dikirim! Menunggu verifikasi admin.',
                'member_id' => $validated['member_id'],
            ]);
        }

        return redirect()->back()->with([
            'error' => true,
            'message' => 'Gagal mengirim pendaftaran. Silakan coba lagi.',
        ])->withInput();
    }

    /**
     * Show registration success page
     */
    public function success(Request $request)
    {
        $memberId = $request->session()->get('member_id');
        return view('pages.public.member-registration-success', compact('memberId'));
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
}

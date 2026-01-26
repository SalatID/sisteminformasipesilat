@extends('layout.index_admin')
@section('title', 'Tanda Terima Kontribusi Unit')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Tanda Terima Kontribusi</li>
    </ol>
@endsection
@section('content')
    @if((!($existingContribution->is_transfer??false) && (($existingContribution->created_by??'') == auth()->user()->id) )||  auth()->user()->hasRole('super-admin'))
    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="POST" action="{{ route('receipt.contribution.unit.index') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="unit_id" class="form-label">Unit</label>
                                <select class="form-control" id="unit_id" name="unit_id" required>
                                    <option value="">Pilih Unit</option>
                                    @foreach ($units as $unitItem)
                                        <option value="{{ $unitItem->id }}" {{ request('unit_id') == $unitItem->id ? 'selected' : '' }}>
                                            {{ $unitItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="month" class="form-label">Bulan</label>
                                        <select class="form-control" id="month" name="month" required>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="year" class="form-label">Tahun</label>
                                        <select class="form-control" id="year" name="year" required>
                                            @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="contribution_amount" class="form-label">Uang Kontribusi</label>
                                <input type="number" class="form-control" id="contribution_amount" name="contribution_amount" value="{{ request('contribution_amount') ?? $contributionAmount }}" required>
                            </div>

                            <!-- Image Upload Field -->
                            <div class="col-md-3 mb-3">
                                <label for="contribution_receipt_img" class="form-label">Tanda Terima Kontribusi</label>
                                <input type="file" class="form-control" id="contribution_receipt_img" name="contribution_receipt_img" accept="image/*" required>
                                <small class="form-text text-muted">Format: JPG, PNG, GIF (Max: 2MB)</small>
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary mr-2">
                                    <i class="fas fa-search"></i> Hitung
                                </button>
                                <a href="{{ route('receipt.contribution.unit.index') }}" class="btn btn-sm btn-secondary  mr-2">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                                <button type="button" class="btn btn-success btn-sm" id="exportToImage">
                                    <i class="fas fa-image"></i> Export ke Gambar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($unitData)
    <div class="row">
        <div class="col-12">
            <div class="card" id="receiptContainer">
                <div class="card-body" >
                    <!-- Header -->
                    <div class="mb-4">
                        <h4><strong>Bulan:</strong> {{ \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F') }} {{ $year }}</h4>
                        <h4><strong>Unit:</strong> {{ $unit->name }}</h4>
                    </div>

                    <!-- Attendance Table -->
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>Absensi</strong></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th width="50">No</th>
                                        <th>Nama Pelatih</th>
                                        <th width="80">TS</th>
                                        <th width="150">Week 1</th>
                                        <th width="150">Week 2</th>
                                        <th width="150">Week 3</th>
                                        <th width="150">Week 4</th>
                                        <th width="150">Week 5</th>
                                        <th width="80">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($i = 1)
                                    @php($weekTotals = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0])
                                    @foreach ($coachAttendance as $data)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td>{{ $data['coach']->name }}</td>
                                            <td class="text-center">{{ $data['coach']->ts->alias ?? '-' }}</td>
                                            @foreach ($data['weeks'] as $weekNum => $weekData)
                                                <td class="text-center">
                                                    @if(count($weekData) > 0)
                                                        @foreach ($weekData as $attendanceData)
                                                            @if($attendanceData['status'] != 'training')
                                                                {{ $attendanceData['date'] }}<br>
                                                                <span class="badge {{ App\Models\Attendance::mapAttendanceStatusToClass($attendanceData['status']) }}" style="font-size: 90%;">
                                                                    {{ App\Models\Attendance::mapAttendanceStatus($attendanceData['status']) }}
                                                                </span>
                                                                <br><small>({{ $attendanceData['reason'] ?? 'Tidak ada keterangan' }})</small>
                                                            @else
                                                                {{ $attendanceData['date'] }}
                                                                @php($weekTotals[$weekNum]++)
                                                            @endif
                                                            @if (!$loop->last)
                                                                <hr style="margin: 5px 0;">
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-center"><strong>{{ $data['total_attendance'] }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr class="text-center">
                                        <th colspan="3" class="text-right">Total per Week:</th>
                                        @foreach ([1, 2, 3, 4, 5] as $weekNum)
                                            <th>{{ $weekTotals[$weekNum] }}</th>
                                        @endforeach
                                        <th>{{ $totalAttendance }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Contribution Table -->
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>Kontribusi</strong></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th width="50">No</th>
                                        <th>Nama Pelatih</th>
                                        <th width="80">TS</th>
                                        <th width="100">Kehadiran</th>
                                        <th width="100">Pengali</th>
                                        <th width="80">PJ</th>
                                        <th width="120">Nilai Akhir</th>
                                        <th width="150">Nominal Pertemuan</th>
                                        <th width="150">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($i = 1)
                                    @foreach ($coachAttendance as $data)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td>{{ $data['coach']->name }}</td>
                                            <td class="text-center">{{ $data['coach']->ts->alias ?? '-' }}</td>
                                            <td class="text-center">{{ $data['total_attendance'] }}</td>
                                            <td class="text-center">{{ $data['multiplier'] }}</td>
                                            <td class="text-center">{{ $data['is_pj']=="0" ? '' : $data['is_pj'] }}</td>
                                            <td class="text-center">{{ number_format($data['final_value'], 0) }}</td>
                                            <td class="text-right">Rp {{ number_format($nominalPerMeeting, 0, ',', '.') }}</td>
                                            <td class="text-right">Rp {{ number_format($data['total_amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th colspan="3" class="text-right">Total:</th>
                                        <th class="text-center">{{ $totalAttendance }}</th>
                                        <th class="text-center"></th>
                                        <th class="text-center"></th>
                                        <th class="text-center">{{ number_format($totalFinalValue, 0) }}</th>
                                        <th></th>
                                        <th class="text-right">
                                            <strong>Rp {{ number_format($totalContribution, 0, ',', '.') }}</strong>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="row">
                        <div class="col-md-6">
                            @if(isset($existingContribution) && $existingContribution && $existingContribution->contribution_receipt_img)
                                <div class="mb-3">
                                    <strong>Tanda Terima Kontribusi:</strong><br>
                                    <img src="{{ asset($existingContribution->contribution_receipt_img) }}"
                                        alt="Tanda Terima Kontribusi" 
                                        class="img-fluid img-thumbnail mt-2 w-100" 
                                        style="max-width: 100%; height: auto;">
                                </div>
                            @endif
                            <p>
                                35% Kas Komwil Dapat Di Transfer Ke Rekeing<br>
                                Bank BNI a.n Mursalat Asyidiq<br>
                                <b>1990539150</b>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Uang Kontribusi:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($contributionAmount, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td>65% PJ:</td>
                                    <td class="text-right">Rp {{ number_format($pjShare, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>20% KAS:</td>
                                    <td class="text-right">Rp {{ number_format($kasShare, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>15% Tabungan:</td>
                                    <td class="text-right">Rp {{ number_format($savingsShare, 0, ',', '.') }}</td>
                                </tr>
                                 <tr class="bg-success">
                                    <td><strong>Total Transfer Ke Komwil:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($savingsShare + $kasShare, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Nominal Pertemuan:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($nominalPerMeeting, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Selisih:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($difference, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> Silakan pilih unit, bulan, dan tahun untuk melihat tanda terima kontribusi
            </div>
        </div>
    </div>
    @endif

@endsection
@section('script')
    <!-- html2canvas library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        // Export div to image
        document.getElementById('exportToImage')?.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            const element = document.getElementById('receiptContainer');
            
            // Store original width and set fixed width for export
            const originalWidth = element.style.width;
            const originalMaxWidth = element.style.maxWidth;
            element.style.width = '1200px';
            element.style.maxWidth = '1200px';
            
            // Wait a bit for the layout to adjust
            setTimeout(() => {
                html2canvas(element, {
                    scale: 2, // Higher quality
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    width: 1200,
                    windowWidth: 1200
                }).then(canvas => {
                    // Restore original width
                    element.style.width = originalWidth;
                    element.style.maxWidth = originalMaxWidth;
                    
                    // Convert canvas to blob
                    canvas.toBlob(function(blob) {
                        // Create download link
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        const fileName = 'Tanda_Terima_Kontribusi_{{ $unit->name??'' }}_{{ \Carbon\Carbon::create()->month($month)->format("F") }}_{{ $year }}.png';
                        
                        link.download = fileName;
                        link.href = url;
                        link.click();
                        
                        // Cleanup
                        URL.revokeObjectURL(url);
                        
                        // Restore button state
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }, 'image/png');
                }).catch(error => {
                    // Restore original width on error
                    element.style.width = originalWidth;
                    element.style.maxWidth = originalMaxWidth;
                    
                    console.error('Error exporting image:', error);
                    alert('Gagal mengexport gambar. Silakan coba lagi.');
                    
                    // Restore button state
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            }, 100);
        });
    </script>
@endsection

@extends('layout.index_admin')
@section('title', 'Rekap Kehadiran Unit Bulanan')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Rekap Kehadiran Unit Bulanan</li>
    </ol>
@endsection
@section('content')

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('report.unit.attendance.index') }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="month" class="form-label">Bulan</label>
                                <select class="form-control" id="month" name="month">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="year" class="form-label">Tahun</label>
                                <select class="form-control" id="year" name="year">
                                    @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary mr-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="{{ route('report.unit.attendance.index') }}" class="btn btn-sm btn-secondary mr-2">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                                <button class="btn btn-success btn-sm" type="button" id="exportToImageBtn">
                                    <i class="fas fa-download"></i> Download as Image
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 table-responsive" id="tableContainer">
            <div class="col-md-12 mb-3">
                <h4>Rekap Kehadiran Bulan {{ \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F') }} {{ $year }}</h4>
            </div>
            <table class="table table-striped table-bordered" id="attendanceReportTable">
                <thead>
                    <tr class="text-center">
                        <th>#</th>
                        <th>Nama Unit</th>
                        <th width="200">Week 1</th>
                        <th width="200">Week 2</th>
                        <th width="200">Week 3</th>
                        <th width="200">Week 4</th>
                        <th width="200">Week 5</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report as $index => $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-left">{{ $row['unit_name'] }}</td>
                            @foreach ($row['weeks'] as $weekNum => $weekData)
                                @php
                                    $isEmpty = empty($weekData);
                                    $shouldShowRed = $isEmpty && isset($weekStatus[$weekNum]) && $weekStatus[$weekNum]['should_check'];
                                @endphp
                                <td class="text-center" style="{{ $shouldShowRed ? 'background-color: #f8d7da; color: #721c24;' : '' }}">
                                    @if ($isEmpty)
                                        @if ($shouldShowRed)
                                            <strong>Belum Absensi</strong>
                                        @else
                                            -
                                        @endif
                                    @else
                                        @foreach ($weekData as $attendance)
                                            @if ($attendance->attendance_status != 'training')
                                                <a href="{{ route('attendance.coach.show', \Illuminate\Support\Facades\Crypt::encryptString($attendance->id)) }}">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->toDateString() }}
                                                </a>
                                                <br>
                                                <span class="badge {{ App\Models\Attendance::mapAttendanceStatusToClass($attendance->attendance_status) }}">
                                                    {{ App\Models\Attendance::mapAttendanceStatus($attendance->attendance_status) }}
                                                </span>
                                                @if ($attendance->reason)
                                                    <br><small>({{ $attendance->reason }})</small>
                                                @endif
                                            @else
                                                <a href="{{ route('attendance.coach.show', \Illuminate\Support\Facades\Crypt::encryptString($attendance->id)) }}">
                                                    {{ \Carbon\Carbon::parse($attendance->attendance_date)->toDateString() }}
                                                </a>
                                            @endif
                                            @if (!$loop->last)
                                                <hr style="margin: 5px 0;">
                                            @endif
                                        @endforeach
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Data tidak ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        $('#attendanceReportTable').DataTable({
            pageLength: 25,
            ordering: false,
            paging: false,
            searching: false,
            info: false
        });

        // Function to export table to image
        function exportTableToImage() {
            const tableContainer = document.getElementById('tableContainer');
            const button = document.getElementById('exportToImageBtn');
            const table = document.getElementById('attendanceReportTable');
            
            // Store original styles
            const originalContainerStyle = tableContainer.style.cssText;
            const originalTableStyle = table.style.cssText;
            const originalOverflow = tableContainer.style.overflow;
            
            // Hide the button temporarily
            button.style.display = 'none';
            
            // Set fixed width for consistent rendering across devices
            tableContainer.style.overflow = 'visible';
            tableContainer.style.width = '1200px';
            tableContainer.style.maxWidth = 'none';
            table.style.width = '100%';
            
            // Wait a bit for styles to apply
            setTimeout(() => {
                html2canvas(tableContainer, {
                    scale: 2,
                    backgroundColor: '#ffffff',
                    logging: false,
                    useCORS: true,
                    width: 1200,
                    windowWidth: 1200
                }).then(canvas => {
                    // Convert canvas to image
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Create download link
                    const link = document.createElement('a');
                    link.download = 'rekap-kehadiran-{{ $month }}-{{ $year }}.png';
                    link.href = imgData;
                    link.click();
                    
                    // Restore original styles
                    tableContainer.style.cssText = originalContainerStyle;
                    table.style.cssText = originalTableStyle;
                    button.style.display = '';
                }).catch(error => {
                    console.error('Error generating image:', error);
                    alert('Gagal mengekspor tabel ke gambar');
                    
                    // Restore original styles
                    tableContainer.style.cssText = originalContainerStyle;
                    table.style.cssText = originalTableStyle;
                    button.style.display = '';
                });
            }, 100);
        }

        // Attach click event to export button
        $('#exportToImageBtn').on('click', function() {
            exportTableToImage();
        });
    </script>

@endsection
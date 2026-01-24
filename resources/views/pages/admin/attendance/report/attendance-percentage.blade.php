@extends('layout.index_admin')
@section('title', 'Presentase Kehadiran')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Presentase Kehadiran</li>
    </ol>
@endsection
@section('content')

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('report.attendance.percentage.index') }}">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="start_period" class="form-label">Periode Awal</label>
                                <input type="month" class="form-control" id="start_period" name="start_period" value="{{ $startPeriod }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="end_period" class="form-label">Periode Akhir</label>
                                <input type="month" class="form-control" id="end_period" name="end_period" value="{{ $endPeriod }}">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="ts_id" class="form-label">Tingkatan Sabuk</label>
                                <select class="form-control" id="ts_id" name="ts_id">
                                    <option value="">Semua Sabuk</option>
                                    @foreach ($tsList as $ts)
                                        <option value="{{ $ts->id }}" {{ $tsFilter == $ts->id ? 'selected' : '' }}>
                                            {{ $ts->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary mr-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="{{ route('report.attendance.percentage.index') }}" class="btn btn-sm btn-secondary mr-2">
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

    <!-- Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card" id="container-img-export">
                <div class="card-header">
                    <h3 class="card-title">Data Presentase Kehadiran Pelatih ({{ \Carbon\Carbon::parse($startPeriod)->locale('id')->translatedFormat('F Y') }} - {{ \Carbon\Carbon::parse($endPeriod)->locale('id')->translatedFormat('F Y') }})</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="attendancePercentageTable">
                            <thead>
                                <tr class="text-center bg-primary text-white">
                                    <th rowspan="2" style="vertical-align: middle; min-width: 40px;">#</th>
                                    <th rowspan="2" style="vertical-align: middle; min-width: 150px;">Nama Pelatih</th>
                                    <th rowspan="2" style="vertical-align: middle; min-width: 100px;">Tingkatan Sabuk</th>
                                    @foreach ($months as $month)
                                        <th colspan="3" style="min-width: 240px;">{{ \Carbon\Carbon::parse($month . '-01')->locale('id')->translatedFormat('F Y') }}</th>
                                    @endforeach
                                    <th rowspan="2" class="bg-success text-white" style="vertical-align: middle; min-width: 80px;">Total</th>
                                </tr>
                                <tr class="text-center bg-light">
                                    @foreach ($months as $month)
                                        <th style="min-width: 80px;">Kehadiran Unit</th>
                                        <th style="min-width: 80px;">Kehadiran Kalideres</th>
                                        <th style="min-width: 80px;">Total Hadir</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($coaches as $index => $coach)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $coach['nama_pelatih'] }}</td>
                                        <td class="text-center">{{ $coach['tingkatan_sabuk'] }}</td>
                                        @foreach ($months as $month)
                                            @php
                                                $data = $coach['months'][$month] ?? null;
                                            @endphp
                                            @if ($data)
                                                <td class="text-center">{{ $data['kehadiran_di_unit']==0?"-":$data['kehadiran_di_unit'] }}</td>
                                                <td class="text-center">{{ $data['kehadiran_di_kalideres']==0?"-":$data['kehadiran_di_kalideres'] }}</td>
                                                <td class="text-center bg-warning"><strong>{{ $data['kehadiran_di_unit'] + $data['kehadiran_di_kalideres'] }}</strong></td>
                                            @else
                                                <td class="text-center text-muted">-</td>
                                                <td class="text-center text-muted">-</td>
                                                <td class="text-center text-muted bg-warning">-</td>
                                            @endif
                                        @endforeach
                                        <td class="text-center bg-success"><strong>{{ array_sum(array_map(fn($m) => ($m['kehadiran_di_unit'] ?? 0) + ($m['kehadiran_di_kalideres'] ?? 0), $coach['months'])) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 3 + (count($months) * 3) }}" class="text-center">Tidak ada data kehadiran untuk periode yang dipilih</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            
            const element = document.getElementById('container-img-export') || document.body;
            
            // Get element's actual width
            const elementWidth = element.scrollWidth;
            
            // Wait a bit for the layout to adjust
            setTimeout(() => {
                html2canvas(element, {
                    scale: 2, // Higher quality
                    logging: false,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                }).then(canvas => {
                    
                    // Convert canvas to blob
                    canvas.toBlob(function(blob) {
                        // Create download link
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        const fileName = `Presentase_Kehadiran_${formatPeriod('{{ $startPeriod }}')}_${formatPeriod('{{ $endPeriod }}')}.png`;

                        link.download = `Presentase_Kehadiran_${formatPeriod('{{ $startPeriod }}')}_${formatPeriod('{{ $endPeriod }}')}.png`;
                        
                        function formatPeriod(period) {
                            const [year, month] = period.split('-');
                            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            return `${months[parseInt(month) - 1]}_${year}`;
                                                }
                        
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

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
<style>
    .table th, .table td {
        vertical-align: middle;
        white-space: nowrap;
        padding: 8px 5px;
        font-size: 0.85rem;
        border: 1px solid #dee2e6;
    }
    .badge {
        font-size: 0.8rem;
        padding: 0.4em 0.6em;
        min-width: 45px;
    }
    #attendancePercentageTable {
        font-size: 0.85rem;
        border-collapse: collapse;
    }
    #attendancePercentageTable thead tr:first-child th {
        background-color: #007bff !important;
        color: white !important;
        font-weight: bold;
        border: 1px solid #0056b3;
    }
    #attendancePercentageTable thead tr:nth-child(2) th {
        background-color: #f8f9fa;
        font-weight: 600;
        border: 1px solid #dee2e6;
    }
    #attendancePercentageTable thead th.bg-success {
        background-color: #28a745 !important;
        color: white !important;
    }
    .table-responsive {
        overflow-x: auto;
    }
    #attendancePercentageTable_wrapper {
        overflow-x: auto;
    }
</style>
</style>
@endpush

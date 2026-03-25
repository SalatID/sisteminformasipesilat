@extends('layout.index_admin')
@section("title","Detail Pelatih")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('coach.index') }}">Manajemen Pelatih</a></li>
    <li class="breadcrumb-item active">Detail Pelatih</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Coach Information Card -->
        <div class="card mb-3">
            <div class="card-header bg-primary">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle"></i> Informasi Pelatih
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row mb-2">
                            <div class="col-sm-4">
                                <strong>Nama Pelatih:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ $coach->name }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4">
                                <strong>Tingkat Sabuk:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span class="badge bg-info">{{ $coach->ts->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <a href="{{ route('coach.edit', $coach->id) }}" class="btn btn-warning btn-sm me-2">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('coach.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Container -->
        <div class="card">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="coachTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab" aria-controls="attendance" aria-selected="true">
                            <i class="fas fa-calendar-check"></i> Riwayat Kehadiran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contribution-tab" data-toggle="tab" href="#contribution" role="tab" aria-controls="contribution" aria-selected="false">
                            <i class="fas fa-file-invoice-dollar"></i> Riwayat Kontribusi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="exam-tab" data-toggle="tab" href="#exam" role="tab" aria-controls="exam" aria-selected="false">
                            <i class="fas fa-certificate"></i> Riwayat Ujian
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="coachTabsContent">
                    <!-- Attendance History Tab -->
                    <div class="tab-pane fade show active" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                        <!-- Attendance Date Filter -->
                        <div class="card card-outline card-primary mb-3">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-filter"></i> Filter Tanggal</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('coach.show', $coach->id) }}#attendance" class="row g-2">
                                    <div class="col-md-3">
                                        <label for="attendance_start_date" class="form-label">Dari Tanggal</label>
                                        <input type="date" class="form-control" id="attendance_start_date" name="attendance_start_date" value="{{ $filterData['attendance_start_date'] }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="attendance_end_date" class="form-label">Sampai Tanggal</label>
                                        <input type="date" class="form-control" id="attendance_end_date" name="attendance_end_date" value="{{ $filterData['attendance_end_date'] }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contribution_start_date" class="form-label" style="opacity: 0;">-</label>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contribution_end_date" class="form-label" style="opacity: 0;">-</label>
                                        <a href="{{ route('coach.show', $coach->id) }}" class="btn btn-secondary btn-sm w-100">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Attendance Summary -->
                            <div class="col-sm-12 col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-calendar-check"></i> Total Kehadiran
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 150px; overflow-y: auto;">
                                        <div class="text-center">
                                            <h2 class="text-success mb-2">{{ $totalAttendance }}</h2>
                                            <small class="text-muted">Periode: {{ $filterData['attendance_start_date'] }} s/d {{ $filterData['attendance_end_date'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attendance by Unit Summary -->
                            <div class="col-sm-12 col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-sitemap"></i> Kehadiran per Unit
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 150px; overflow-y: auto;">
                                        @if(empty($attendanceByUnit))
                                            <small class="text-muted">Tidak ada data</small>
                                        @else
                                            @foreach($attendanceByUnit as $unit => $count)
                                                <div class="d-flex justify-content-between mb-2">
                                                    <small>{{ $unit }}</small>
                                                    <span class="badge bg-info">{{ $count }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($attendanceHistory->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Belum ada riwayat kehadiran untuk pelatih ini.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Tanggal</th>
                                            <th class="text-center">Unit</th>
                                            <th class="text-center">Status Kehadiran</th>
                                            <th class="text-center">Tanggal Dicatat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach($attendanceHistory as $record)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center">{{ $record->attendance->attendance_date->format('d-m-Y') }}</td>
                                            <td class="text-center">{{ $record->attendance->unit->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ 
                                                    $record->attendance->attendance_status == 'training' ? 'success' : 
                                                    ($record->attendance->attendance_status == 'cancelled' ? 'warning' : 'info')
                                                }}">
                                                    {{ \App\Models\Attendance::$attendanceStatusMap[$record->attendance->attendance_status] ?? $record->attendance->attendance_status }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ $record->created_at->format('d-m-Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Attendance Pagination -->
                            <div class="d-flex justify-content-center mt-3">
                                {{ $attendanceHistory->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- Contribution History Tab -->
                    <div class="tab-pane fade" id="contribution" role="tabpanel" aria-labelledby="contribution-tab">
                        <!-- Contribution Periode Filter -->
                        <div class="card card-outline card-primary mb-3">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-filter"></i> Filter Periode</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('coach.show', $coach->id) }}#contribution" class="row g-2">
                                    <div class="col-md-4">
                                        <label for="contribution_periode" class="form-label">Periode (YYYY-MM)</label>
                                        <select class="form-control" id="contribution_periode" name="contribution_periode">
                                            @foreach($availablePeriodes as $periode)
                                                <option value="{{ $periode }}" {{ $filterData['contribution_periode'] == $periode ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::parse($periode . '-01')->format('F Y') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contribution_periode" class="form-label" style="opacity: 0;">-</label>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contribution_periode" class="form-label" style="opacity: 0;">-</label>
                                        <a href="{{ route('coach.show', $coach->id) }}" class="btn btn-secondary btn-sm w-100">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mb-3">
                            
                            <!-- Contribution Summary -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-warning">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-file-invoice-dollar"></i> Total Kontribusi
                                        </h5>
                                    </div>
                                    <div class="card-body" style="height: 150px; overflow-y: auto;">
                                        <div class="text-center">
                                            <h3 class="text-warning mb-2">Rp {{ number_format($totalContribution, 0, ',', '.') }}</h3>
                                            <small class="text-muted">Periode: {{ $filterData['contribution_periode'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($contributionHistory->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Belum ada riwayat kontribusi untuk pelatih ini.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Periode</th>
                                            <th class="text-center">Unit</th>
                                            <th class="text-center">Jumlah Kehadiran</th>
                                            <th class="text-center">Multiplier</th>
                                            <th class="text-right">Nilai Total (Rp)</th>
                                            <th class="text-center">Tanggal Dicatat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach($contributionHistory as $record)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center">{{ $record->contribution->periode }}</td>
                                            <td class="text-center">{{ $record->contribution->unit->name ?? '-' }}</td>
                                            <td class="text-center">{{ $record->attendance }}</td>
                                            <td class="text-center">{{ number_format($record->multiplier, 2) }}</td>
                                            <td class="text-right">{{ number_format($record->total, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $record->created_at->format('d-m-Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Contribution Pagination -->
                            <div class="d-flex justify-content-center mt-3">
                                {{ $contributionHistory->links() }}
                            </div>
                        @endif
                    </div>

                    <!-- Exam History Tab -->
                    <div class="tab-pane fade" id="exam" role="tabpanel" aria-labelledby="exam-tab">
                        <!-- Add Exam Info -->
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> 
                            Untuk menambah atau mengelola riwayat ujian, silakan kunjungi halaman 
                            <a href="{{ route('coach.edit', $coach->id) }}" class="alert-link">Edit Pelatih</a>.
                        </div>

                        <!-- Exam History Table -->
                        @if($examHistory->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Belum ada riwayat ujian untuk pelatih ini.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Tanggal Ujian (Mulai)</th>
                                            <th class="text-center">Tanggal Ujian (Selesai)</th>
                                            <th class="text-center">Lokasi Ujian</th>
                                            <th class="text-center">Penyelenggara</th>
                                            <th class="text-center">TS Sebelum</th>
                                            <th class="text-center">TS Sesudah</th>
                                            <th class="text-center">Catatan</th>
                                            <th class="text-center">Tanggal Dicatat</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach($examHistory as $record)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center">{{ $record->exam_date->format('d-m-Y') }}</td>
                                            <td class="text-center">{{ $record->exam_end_date->format('d-m-Y') }}</td>
                                            <td class="text-center">{{ $record->exam_location }}</td>
                                            <td class="text-center">{{ $record->organizer ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($record->tsBefore)
                                                    <span class="badge bg-info">{{ $record->tsBefore->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($record->tsAfter)
                                                    <span class="badge bg-success">{{ $record->tsAfter->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ Str::limit($record->notes, 30) ?? '-' }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($record->created_at)->format('d-m-Y H:i') }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('coach.exam.destroy', [$coach->id, $record->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat ujian ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Exam Pagination -->
                            <div class="d-flex justify-content-center mt-3">
                                {{ $examHistory->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border-bottom: 3px solid transparent;
    }
    .nav-tabs .nav-link:hover {
        border-bottom-color: #0d6efd;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
    }
</style>
@endsection

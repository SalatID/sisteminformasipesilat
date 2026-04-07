@extends('layout.index_admin')
@section("title", "Detail Pesilat - " . $member->name)
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item active">{{ $member->name }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Member Information Card -->
        <div class="card card-primary card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield"></i> Informasi Pesilat
                </h3>
                <div class="card-tools">
                    <a href="{{ route('member.edit', $member->id) }}" class="btn btn-small btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('member.index') }}" class="btn btn-small btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($member->picture)
                    <div class="col-md-3">
                        <div class="text-center">
                            <img src="{{ asset($member->picture) }}" 
                                 alt="{{ $member->name }}" 
                                 style="max-width: 200px; max-height: 280px; border-radius: 8px; border: 2px solid #dee2e6;">
                        </div>
                    </div>
                    <div class="col-md-9">
                    @else
                    <div class="col-md-12">
                    @endif
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <strong>Nama Pesilat:</strong>
                                <p>{{ $member->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>ID Member:</strong>
                                <p><span class="badge bg-info">{{ $member->member_id }}</span></p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <strong>Tempat Lahir:</strong>
                                <p>{{ $member->birth_place ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Tanggal Lahir:</strong>
                                <p>{{ $member->birth_date ? $member->birth_date->format('d-m-Y') : '-' }}</p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <strong>Tingkat Sabuk (TS):</strong>
                                <p><span class="badge bg-success">{{ $member->ts->name ?? '-' }}</span></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Unit:</strong>
                                <p><span class="badge bg-primary">{{ $member->unit->name ?? 'Tidak ada unit' }}</span></p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <strong>Tanggal Bergabung:</strong>
                                <p>{{ \Carbon\Carbon::parse($member->joined_date)->format('d-m-Y') }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Jenis Kelamin:</strong>
                                <p>
                                    @if($member->gender === 'male')
                                        <span class="badge bg-info"><i class="fas fa-mars"></i> Laki-laki</span>
                                    @elseif($member->gender === 'female')
                                        <span class="badge bg-danger"><i class="fas fa-venus"></i> Perempuan</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Tingkat Sekolah:</strong>
                                <p>{{ $member->school_level ?? '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Terdaftar Sejak:</strong>
                                <p>{{ \Carbon\Carbon::parse($member->created_at)->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Status Card -->
        @if($member->is_self_registered)
        <div class="card mb-3 @if($member->isPending()) border-warning @elseif($member->isApproved()) border-success @else border-danger @endif">
            <div class="card-header @if($member->isPending()) bg-warning @elseif($member->isApproved()) bg-success @else bg-danger @endif text-white">
                <h3 class="card-title m-0">
                    <i class="fas fa-clipboard-list"></i> Status Pendaftaran
                </h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        <p>
                            @if($member->isPending())
                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> Menunggu Verifikasi</span>
                            @elseif($member->isApproved())
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Disetujui</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Ditolak</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Pendaftaran:</strong>
                        <p>{{ \Carbon\Carbon::parse($member->created_at)->format('d-m-Y H:i:s') }}</p>
                    </div>
                </div>

                @if($member->isApproved() || $member->isRejected())
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Diverifikasi Oleh:</strong>
                        <p>{{ $member->approver->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Verifikasi:</strong>
                        <p>{{ $member->approved_at ? \Carbon\Carbon::parse($member->approved_at)->format('d-m-Y H:i:s') : '-' }}</p>
                    </div>
                </div>
                @endif

                @if($member->isRejected() && $member->rejection_reason)
                <div class="alert alert-danger mb-0">
                    <strong>Alasan Penolakan:</strong>
                    <p class="mb-0 mt-2">{{ $member->rejection_reason }}</p>
                </div>
                @endif

                @if($member->isPending())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> Pendaftaran ini sedang menunggu verifikasi dari admin.
                    <div class="mt-3">
                        <!-- Approve Form -->
                        <form action="{{ route('member.registration.approve', $member->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui pendaftaran ini?')">
                                <i class="fas fa-check"></i> Setujui Pendaftaran
                            </button>
                        </form>

                        <!-- Reject Button -->
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                data-bs-target="#rejectModal">
                            <i class="fas fa-times"></i> Tolak Pendaftaran
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Identification Documents Card -->
        <div class="card card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-passport"></i> Data Identitas
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- KTP -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-primary">
                                <h5 class="card-title mb-0">KTP (Kartu Tanda Penduduk)</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nomor KTP:</strong></p>
                                <p class="text-monospace">{{ $member->citizen_number ?? '-' }}</p>
                                @if($member->citizen_img)
                                    <p class="mt-2"><strong>Dokumen:</strong></p>
                                    <img src="{{ asset($member->citizen_img) }}" 
                                         alt="KTP" 
                                         class="img-fluid" 
                                         style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                @else
                                    <p class="text-muted small"><i class="fas fa-info-circle"></i> Dokumen belum diupload</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Keluarga -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success">
                                <h5 class="card-title mb-0">Kartu Keluarga</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nomor Kartu Keluarga:</strong></p>
                                <p class="text-monospace">{{ $member->family_card_number ?? '-' }}</p>
                                @if($member->family_card_img)
                                    <p class="mt-2"><strong>Dokumen:</strong></p>
                                    <img src="{{ asset($member->family_card_img) }}" 
                                         alt="Kartu Keluarga" 
                                         class="img-fluid" 
                                         style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                @else
                                    <p class="text-muted small"><i class="fas fa-info-circle"></i> Dokumen belum diupload</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- BPJS -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-warning">
                            <div class="card-header bg-warning">
                                <h5 class="card-title mb-0">BPJS Kesehatan</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nomor BPJS:</strong></p>
                                <p class="text-monospace">{{ $member->bpjs_number ?? '-' }}</p>
                                @if($member->bpjs_img)
                                    <p class="mt-2"><strong>Dokumen:</strong></p>
                                    <img src="{{ asset($member->bpjs_img) }}" 
                                         alt="BPJS" 
                                         class="img-fluid" 
                                         style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                @else
                                    <p class="text-muted small"><i class="fas fa-info-circle"></i> Dokumen belum diupload</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <!-- Total Attendance Card -->
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Kehadiran</span>
                        <span class="info-box-number">
                            {{ isset($attendance_summary['total']) ? $attendance_summary['total'] : 0 }}
                        </span>
                        <small class="text-muted">
                            (Bulan {{ \Carbon\Carbon::now()->format('m/Y') }})
                        </small>
                    </div>
                </div>
            </div>

            <!-- Attendance by Unit Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title m-0">
                            <i class="fas fa-home"></i> Kehadiran per Unit
                        </h5>
                    </div>
                    <div class="card-body p-2">
                        @if(isset($attendance_summary['by_unit']) && !empty($attendance_summary['by_unit']))
                            @foreach($attendance_summary['by_unit'] as $unit => $count)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><span class="badge bg-primary">{{ $unit }}</span></span>
                                    <strong>{{ $count }}</strong>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted small">Belum ada data kehadiran</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Performance Average Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title m-0">
                            <i class="fas fa-chart-bar"></i> Rerata Performa
                        </h5>
                    </div>
                    <div class="card-body p-2">
                        @if(isset($performance_average) && $performance_average['total_records'] > 0)
                            <div class="mb-2">
                                <small class="d-flex justify-content-between">
                                    <span>Endurance:</span>
                                    <strong>{{ round($performance_average['avg_endurance'], 1) }}</strong>
                                </small>
                            </div>
                            <div class="mb-2">
                                <small class="d-flex justify-content-between">
                                    <span>Strength:</span>
                                    <strong>{{ round($performance_average['avg_strength'], 1) }}</strong>
                                </small>
                            </div>
                            <div class="mb-2">
                                <small class="d-flex justify-content-between">
                                    <span>Technique:</span>
                                    <strong>{{ round($performance_average['avg_technique'], 1) }}</strong>
                                </small>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted d-block">
                                ({{ $performance_average['total_records'] }} records)
                            </small>
                        @else
                            <p class="text-muted small">Belum ada data performa</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="card">
            <div class="card-header p-0 pt-3">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                            <i class="fas fa-calendar-alt"></i> Riwayat Kehadiran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="exam-tab" data-toggle="tab" href="#exam" role="tab">
                            <i class="fas fa-certificate"></i> Riwayat Ujian
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Attendance History Tab -->
                    <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                        <div class="mb-3">
                            <form method="GET" action="{{ route('member.show', $member->id) }}" id="filterForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="attendance_month" class="form-label">Filter Periode:</label>
                                        <input type="month" 
                                               class="form-control" 
                                               id="attendance_month" 
                                               name="attendance_month"
                                               value="{{ request('attendance_month', now()->format('Y-m')) }}"
                                               onchange="document.getElementById('filterForm').submit();">
                                    </div>
                                </div>
                            </form>
                        </div>

                        @if($attendances->isEmpty())
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Belum ada riwayat kehadiran untuk periode ini.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Tanggal Kehadiran</th>
                                            <th class="text-center">Unit</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Tanggal Dicatat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach($attendances as $record)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($record->attendance->attendance_date)->format('d-m-Y') }}</td>
                                            <td class="text-center">
                                                @if($record->attendance->unit)
                                                    <span class="badge bg-primary">{{ $record->attendance->unit->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Hadir</span>
                                            </td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($record->created_at)->format('d-m-Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- Exam History Tab -->
                    <div class="tab-pane fade" id="exam" role="tabpanel">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> Untuk menambah atau mengelola riwayat ujian, silakan kunjungi 
                            <a href="{{ route('member.edit', $member->id) }}" class="alert-link">halaman Edit Pesilat</a>.
                        </div>

                        @if($exams->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> Belum ada riwayat ujian untuk pesilat ini.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Tanggal Ujian</th>
                                            <th class="text-center">Lokasi</th>
                                            <th class="text-center">Penyelenggara</th>
                                            <th class="text-center">TS Sebelum</th>
                                            <th class="text-center">TS Sesudah</th>
                                            <th class="text-center">Catatan</th>
                                            <th class="text-center">Tanggal Dicatat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach($exams as $record)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td class="text-center">
                                                {{ $record->exam_date->format('d-m-Y') }} 
                                                s/d 
                                                {{ $record->exam_end_date->format('d-m-Y') }}
                                            </td>
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
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Training Centers Section -->
        <div class="row mt-4">
            <div class="col-12">
                @include('pages.admin.member.training-centers-section')
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (for pending registrations) -->
@if($member->is_self_registered && $member->isPending())
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Tolak Pendaftaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('member.registration.reject', $member->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Anda akan menolak pendaftaran <strong>{{ $member->name }}</strong></p>
                    <p class="text-muted small">Silakan berikan alasan penolakan untuk memberikan feedback kepada pendaftar.</p>
                    <div class="form-group">
                        <label for="rejection_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('rejection_reason') is-invalid @enderror" 
                                  id="rejection_reason" 
                                  name="rejection_reason" 
                                  rows="4"
                                  placeholder="Jelaskan alasan penolakan pendaftaran (contoh: Data tidak lengkap, Dokumen tidak valid, dll)"
                                  required></textarea>
                        @error('rejection_reason')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check"></i> Tolak Pendaftaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    .info-box-icon {
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>
@endsection

@extends('layout.index_admin')
@section('title', 'Detail Absensi')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Detail Absensi</li>
    </ol>
@endsection
@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Absensi</h3>
                    <div class="card-tools">
                        <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <strong>Nama Unit</strong>
                            <p class="mb-0">{{ $attendance->unit->name }}</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Tanggal Latihan</strong>
                            <p class="mb-0">{{ \Carbon\Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Status Latihan</strong>
                            <p class="mb-0">
                                <span class="badge {{ $attendance::mapAttendanceStatusToClass($attendance->attendance_status) }}">
                                    {{ $attendance::mapAttendanceStatus($attendance->attendance_status) }}
                                </span>
                                @if($attendance->reason)
                                    <br><small class="text-muted">Alasan: {{ $attendance->reason }}</small>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <strong>Jumlah Anggota Baru</strong>
                            <p class="mb-0">{{ $attendance->new_member_cnt }} orang</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Jumlah Anggota Lama</strong>
                            <p class="mb-0">{{ $attendance->old_member_cnt }} orang</p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Total Anggota</strong>
                            <p class="mb-0"><strong>{{ $attendance->new_member_cnt + $attendance->old_member_cnt }} orang</strong></p>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Dibuat Oleh</strong>
                            <p class="mb-0">{{ $attendance->reportMaker->name ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Pelatih yang Hadir -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5><strong>Pelatih yang Hadir</strong></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr class="text-center">
                                            <th width="50">No</th>
                                            <th>Nama Pelatih</th>
                                            <th width="100">TS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($attendance->attendanceDetails as $key => $detail)
                                            <tr>
                                                <td class="text-center">{{ $key + 1 }}</td>
                                                <td>{{ $detail->coach->name }}</td>
                                                <td class="text-center">{{ $detail->coach->ts->alias ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">Tidak ada pelatih yang hadir</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Foto Attachment -->
                    @if($attendance->attendance_image)
                        @php
                            $images = array_filter(explode(',', $attendance->attendance_image));
                        @endphp
                        <div class="row">
                            <div class="col-12">
                                <h5><strong>Foto Absensi</strong></h5>
                                <div class="list-group">
                                    @foreach($images as $index => $imageUrl)
                                        @php
                                            $imageUrl = trim($imageUrl);
                                        @endphp
                                        <a href="{{ $imageUrl }}" target="_blank" class="list-group-item list-group-item-action">
                                            <i class="fas fa-image"></i> Foto Absensi {{ $index + 1 }}
                                            <i class="fas fa-external-link-alt float-right mt-1"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-12">
                                <h5><strong>Foto Absensi</strong></h5>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle"></i> Tidak ada foto absensi
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection

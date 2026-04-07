@extends('layout.index_admin')
@section("title", $training_center->name . " - Laporan TC")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('training-center-report.index') }}">Buat Laporan TC</a></li>
    <li class="breadcrumb-item active">{{ $training_center->name }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Laporan Performa {{ $training_center->name }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('training-center-report.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($grouped->count() === 1 && $grouped->first()->count() === 1)
                    <!-- Single date/type view - show summary -->
                    @php($date = $grouped->keys()->first())
                    @php($type = $grouped->first()->keys()->first())
                    @php($totalMembers = $records->count())
                    @php($totalAttended = $records->where('attended', true)->count())
                    @php($totalKas = $records->where('kas', true)->count())
                    
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tanggal</span>
                                    <span class="info-box-number">{{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon {{ $type === 'online' ? 'bg-primary' : 'bg-secondary' }}">
                                    <i class="fas {{ $type === 'online' ? 'fa-video' : 'fa-map-marker-alt' }}"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Jenis</span>
                                    <span class="info-box-number">{{ ucfirst($type) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Hadir</span>
                                    <span class="info-box-number">{{ $totalAttended }}/{{ $totalMembers }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-money-bill"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Kas</span>
                                    <span class="info-box-number">{{ $totalKas }}/{{ $totalMembers }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @forelse($grouped as $date => $typeGroup)
                    <div class="card card-primary mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calendar"></i> 
                                {{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d MMMM Y') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @foreach($typeGroup as $type => $records)
                                <div class="mb-4">
                                    <h6 class="mb-3">
                                        @if($type === 'online')
                                            <span class="badge bg-info"><i class="fas fa-video"></i> Online</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-map-marker-alt"></i> Offline</span>
                                        @endif
                                    </h6>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 30px;">#</th>
                                                    <th>Nama Pesilat</th>
                                                    <th class="text-center">Absensi</th>
                                                    <th class="text-center">Kas</th>
                                                    <th class="text-center">Endurance</th>
                                                    <th class="text-center">Strength</th>
                                                    <th class="text-center">Technique</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php($i = 1)
                                                @foreach($records as $record)
                                                <tr>
                                                    <td class="text-center">{{ $i++ }}</td>
                                                    <td>
                                                        <strong>{{ $record->member->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $record->member->member_id }}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($record->attended)
                                                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                                        @else
                                                            <span class="badge bg-danger"><i class="fas fa-times"></i></span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($record->kas)
                                                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                                        @else
                                                            <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $record->endurance ?? '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $record->strength ?? '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $record->technique ?? '-' }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Edit Button -->
                                    @php($firstRecord = $records->first())
                                    <div class="mt-3">
                                        <a href="{{ route('training-center-report.edit', $training_center->id) }}" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit Laporan
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Belum ada data laporan untuk pusat pelatihan ini.
                        <a href="{{ route('training-center-report.create') }}" class="alert-link">Buat laporan baru</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

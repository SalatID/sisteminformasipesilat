@extends('layout.index_admin')
@section("title", "Detail Training Center - " . $center->name)
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('training-center.index') }}">Training Center</a></li>
    <li class="breadcrumb-item active">{{ $center->name }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-dumbbell"></i> Detail Training Center
                </h3>
                <div class="card-tools">
                    <a href="{{ route('training-center.edit', $center->id) }}" class="btn btn-small btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('training-center.index') }}" class="btn btn-small btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Nama Training Center:</strong>
                        <p>{{ $center->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Kontak Person:</strong>
                        <p>{{ $center->contact_person ?? '-' }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Telepon:</strong>
                        <p>{{ $center->phone ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong>
                        <p>{{ $center->email ?? '-' }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Hari Training:</strong>
                        <p>
                            @if($center->training_days && count($center->training_days) > 0)
                                @php
                                    $dayLabels = [
                                        'Monday' => 'Senin',
                                        'Tuesday' => 'Selasa',
                                        'Wednesday' => 'Rabu',
                                        'Thursday' => 'Kamis',
                                        'Friday' => 'Jumat',
                                        'Saturday' => 'Sabtu',
                                        'Sunday' => 'Minggu'
                                    ];
                                @endphp
                                @foreach($center->training_days as $day)
                                    <span class="badge bg-secondary">{{ $dayLabels[$day] ?? $day }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Jam Training:</strong>
                        <p>
                            @if($center->training_time)
                                <span class="badge bg-primary">{{ $center->training_time }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Alamat:</strong>
                        <p>{{ $center->address ?? '-' }}</p>
                    </div>
                </div>

                <hr>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Tanggal Dibuat:</strong>
                        <p>{{ $center->created_at->format('d-m-Y H:i') }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Terakhir Diupdate:</strong>
                        <p>{{ $center->updated_at->format('d-m-Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Daftar Pesilat ({{ $center->members->count() }})
                </h3>
            </div>
            <div class="card-body">
                @if($center->members->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Belum ada pesilat yang terdaftar di training center ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Nama</th>
                                    <th class="text-center">ID Member</th>
                                    <th class="text-center">TS</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Tanggal Bergabung Training Center</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @foreach($center->members as $member)
                                <tr>
                                    <td class="text-center">{{ $i++ }}</td>
                                    <td>{{ $member->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $member->member_id }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $member->ts->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">{{ $member->unit->name ?? '-' }}</td>
                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($member->pivot->joined_date)->format('d-m-Y') }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('member.show', $member->id) }}" class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
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
@endsection

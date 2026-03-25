@extends('layout.index_admin')
@section("title","Verifikasi Pendaftaran Pesilat")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item active">Verifikasi Pendaftaran</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-check"></i> Pendaftaran Pesilat Menunggu Verifikasi
                </h3>
                <div class="card-tools">
                    <a href="{{ route('member.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if ($pending_members->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Tidak ada pendaftaran yang menunggu verifikasi.
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
                                    <th class="text-center">Tingkat Pendidikan</th>
                                    <th class="text-center">Tanggal Daftar</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = ($pending_members->currentPage() - 1) * $pending_members->perPage() + 1)
                                @foreach ($pending_members as $member)
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
                                    <td class="text-center">{{ $member->school_level ?? '-' }}</td>
                                    <td class="text-center">
                                        {{ $member->created_at->format('d-m-Y H:i') }}
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#viewModal{{ $member->id }}">
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>

                                <!-- View & Action Modal -->
                                <div class="modal fade" id="viewModal{{ $member->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detail Pendaftaran - {{ $member->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Nama:</strong>
                                                        <p>{{ $member->name }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>ID Member:</strong>
                                                        <p><span class="badge bg-secondary">{{ $member->member_id }}</span></p>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Tingkat Sabuk:</strong>
                                                        <p><span class="badge bg-info">{{ $member->ts->name ?? '-' }}</span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Tanggal Bergabung:</strong>
                                                        <p>{{ $member->joined_date->format('d-m-Y') }}</p>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Unit:</strong>
                                                        <p>{{ $member->unit->name ?? '-' }}</p>
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

                                                <div class="row mb-3">
                                                    <div class="col-md-12">
                                                        <strong>Tingkat Pendidikan:</strong>
                                                        <p>{{ $member->school_level ?? '-' }}</p>
                                                    </div>
                                                </div>

                                                @if($member->picture)
                                                <div class="row mb-3">
                                                    <div class="col-md-12">
                                                        <strong>Foto Profil:</strong>
                                                        <div class="mt-2">
                                                            <img src="{{ asset($member->picture) }}" 
                                                                 alt="{{ $member->name }}" 
                                                                 style="max-width: 200px; max-height: 280px; border-radius: 8px; border: 2px solid #dee2e6;">
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif

                                                <hr>

                                                <div class="row mb-3">
                                                    <div class="col-md-12">
                                                        <strong>Tanggal Pendaftaran:</strong>
                                                        <p>{{ $member->created_at->format('d-m-Y H:i:s') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <!-- Approve Form -->
                                                <form action="{{ route('member.registration.approve', $member->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" onclick="return confirm('Setujui pendaftaran ini?')">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                </form>

                                                <!-- Reject Button -->
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal{{ $member->id }}">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>

                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Tutup
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $member->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tolak Pendaftaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('member.registration.reject', $member->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <p>Anda akan menolak pendaftaran <strong>{{ $member->name }}</strong></p>
                                                    <div class="form-group">
                                                        <label for="rejection_reason{{ $member->id }}" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                        <textarea class="form-control @error('rejection_reason') is-invalid @enderror" 
                                                                  id="rejection_reason{{ $member->id }}" 
                                                                  name="rejection_reason" 
                                                                  rows="3"
                                                                  placeholder="Jelaskan alasan penolakan pendaftaran"
                                                                  required></textarea>
                                                        @error('rejection_reason')
                                                            <span class="invalid-feedback">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-times"></i> Tolak Pendaftaran
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $pending_members->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

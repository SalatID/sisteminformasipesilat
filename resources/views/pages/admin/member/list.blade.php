@extends('layout.index_admin')
@section("title","Daftar Pesilat")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active">Manajemen Pesilat</li>
    <li class="breadcrumb-item active">Daftar Pesilat</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center my-3">
        <div>
            @php($pending_count = \App\Models\Member::pending()->where('is_self_registered', true)->count())
            @if($pending_count > 0)
                <a href="{{ route('member.registrations.pending') }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-hourglass-half"></i> Verifikasi Pendaftaran
                    <span class="badge bg-danger">{{ $pending_count }}</span>
                </a>
            @endif
        </div>
        <a href="{{ route('member.create') }}" class="btn btn-success btn-sm"> 
            <i class="fas fa-plus"></i> Tambah Pesilat
        </a>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pesilat (Member)</h3>
            </div>
            <div class="card-body">
                @if($members->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Tidak ada data pesilat.
                    </div>
                @else
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Nama Pesilat</th>
                                <th class="text-center">ID Member</th>
                                <th class="text-center">Tingkat Sabuk (TS)</th>
                                <th class="text-center">Unit</th>
                                <th class="text-center">Tanggal Bergabung</th>
                                <th class="text-center">Ujian Terakhir</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($i = ($members->currentPage() - 1) * $members->perPage() + 1)
                            @foreach ($members as $member)
                            <tr>
                                <td class="text-center">{{ $i++ }}</td>
                                <td class="text-center">{{ $member->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $member->member_id }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $member->ts->name ?? '-' }}</span>
                                </td>
                                <td class="text-center">{{ $member->unit->name ?? '-' }}</td>
                                <td class="text-center">{{ $member->joined_date->format('d-m-Y') }}</td>
                                <td class="text-center">
                                    @if($member->exams && $member->exams->count() > 0)
                                        @php ($latestExam = $member->exams->first())
                                        <div class="small">
                                            <span class="d-block">{{ $latestExam->exam_date->format('d-m-Y') }} s.d {{ $latestExam->exam_end_date->format('d-m-Y') }}</span>
                                            {{ $latestExam->exam_location }} - {{ $latestExam->organizer }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('member.show', $member->id) }}" class="btn btn-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('member.edit', $member->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('member.destroy', $member->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesilat ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $members->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

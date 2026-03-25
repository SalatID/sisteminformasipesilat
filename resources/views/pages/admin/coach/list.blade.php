@extends('layout.index_admin')
@section("title","Daftar Pelatih")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active">Manajemen Pelatih</li>
    <li class="breadcrumb-item active">Daftar Pelatih</li>
  </ol>
@endsection
@section('content')
<div class="row">
    <div class="col-12 d-flex justify-content-end my-3">
        <a href="{{ route('coach.create') }}" class="btn btn-success btn-sm"> 
            <i class="fas fa-plus"></i> Tambah Pelatih
        </a>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pelatih</h3>
            </div>
            <div class="card-body">
                @if($coachs->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Tidak ada data pelatih.
                    </div>
                @else
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Nama Pelatih</th>
                                <th class="text-center">Tingkat Sabuk (TS)</th>
                                <th class="text-center">Ujian Terakhir</th>
                                <th class="text-center">Tanggal Dibuat</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($i = ($coachs->currentPage() - 1) * $coachs->perPage() + 1)
                            @foreach ($coachs as $coach)
                            <tr>
                                <td class="text-center">{{ $i++ }}</td>
                                <td class="text-center">{{ $coach->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $coach->ts->name ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($coach->exams && $coach->exams->count() > 0)
                                        @php ($latestExam = $coach->exams->first())
                                        <div class="small">
                                            <span class="d-block">{{ $latestExam->exam_date->format('d-m-Y') }} s.d {{ $latestExam->exam_end_date->format('d-m-Y') }}</span>
                                            {{ $latestExam->exam_location }} - {{ $latestExam->organizer }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $coach->created_at->format('d-m-Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('coach.show', $coach->id) }}" class="btn btn-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('coach.edit', $coach->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('coach.destroy', $coach->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelatih ini?');">
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
                    <div class="row mt-4">
                        <div class="col-12">
                            {{ $coachs->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

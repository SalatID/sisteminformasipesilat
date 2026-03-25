@extends('layout.index_admin')
@section("title","Daftar Training Center")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active">Manajemen Training Center</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-12 d-flex justify-content-end my-3">
        <a href="{{ route('training-center.create') }}" class="btn btn-success btn-sm"> 
            <i class="fas fa-plus"></i> Tambah Training Center
        </a>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Training Center</h3>
            </div>
            <div class="card-body">

                @if($centers->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Tidak ada data training center.
                    </div>
                @else
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">Nama Training Center</th>
                                <th class="text-center">Alamat</th>
                                <th class="text-center">Hari Training</th>
                                <th class="text-center">Jam Training</th>
                                <th class="text-center">Kontak Person</th>
                                <th class="text-center">Telepon</th>
                                <th class="text-center">Jumlah Member</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $i = ($centers->currentPage() - 1) * $centers->perPage() + 1;
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
                            @foreach ($centers as $center)
                            <tr>
                                <td class="text-center">{{ $i++ }}</td>
                                <td>{{ $center->name }}</td>
                                <td>{{ Str::limit($center->address, 50) ?? '-' }}</td>
                                <td class="text-center">
                                    @if($center->training_days && count($center->training_days) > 0)
                                        
                                        <small>
                                            @foreach($center->training_days as $day)
                                                <span class="badge bg-secondary">{{ $dayLabels[$day] ?? $day }}</span>
                                            @endforeach
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($center->training_time)
                                        <span class="badge bg-primary">{{ $center->training_time }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $center->contact_person ?? '-' }}</td>
                                <td class="text-center">{{ $center->phone ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $center->members_count }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('training-center.show', $center->id) }}" class="btn btn-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('training-center.edit', $center->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('training-center.destroy', $center->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus training center ini?');">
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
                        {{ $centers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layout.index_admin')
@section("title","Laporan TC")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item active">Buat Laporan TC</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i> Daftar Laporan TC
                </h3>
                <div class="card-tools">
                    <a href="{{ route('training-center-report.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Tambah Laporan
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if ($reports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 30px;">#</th>
                                    <th>Pusat Pelatihan</th>
                                    <th class="text-center">Tanggal Latihan</th>
                                    <th class="text-center">Jenis Latihan</th>
                                    <th class="text-center">Pesilat</th>
                                    <th class="text-center">Hadir</th>
                                    <th class="text-center">Kas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @foreach($reports->groupBy(function($record) { return $record->training_center_id . '|' . $record->training_date; }) as $groupKey => $groupRecords)
                                    @php($firstRecord = $groupRecords->first())
                                    <tr>
                                        <td class="text-center">{{ $i++ }}</td>
                                        <td>
                                            <strong>{{ $firstRecord->trainingCenter->name ?? '-' }}</strong>
                                        </td>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($firstRecord->training_date)->format('d-m-Y') }}
                                        </td>
                                        <td class="text-center">
                                            @if($firstRecord->training_type === 'online')
                                                <span class="badge bg-info"><i class="fas fa-video"></i> Online</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fas fa-map-marker-alt"></i> Offline</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $groupRecords->count() }} Pesilat</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $groupRecords->where('attended', true)->count() }} Hadir</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">{{ $groupRecords->where('kas', true)->count() }} Kas</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('training-center-report.show', ['id' => $firstRecord->training_center_id, 'date' => $firstRecord->training_date, 'type' => $firstRecord->training_type]) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $reports->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Belum ada laporan TC. <a href="{{ route('training-center-report.create') }}" class="alert-link">Buat laporan baru</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

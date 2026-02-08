@extends('layout.index_admin')
@section('title', 'Ringkasan Kontribusi Pelatih')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Ringkasan Kontribusi Pelatih</li>
    </ol>
@endsection
@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-sm">
            <div class="card-body">
                <form id="filterForm" method="GET" action="{{ route('report.contribution.percoach') }}">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="start_period" class="form-label">Periode Awal</label>
                            <input type="month" class="form-control" id="start_period" name="start_period" value="{{ $startPeriod }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="end_period" class="form-label">Periode Akhir</label>
                            <input type="month" class="form-control" id="end_period" name="end_period" value="{{ $endPeriod }}">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary mr-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="{{ route('report.contribution.percoach') }}" class="btn btn-sm btn-secondary mr-2">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan Kontribusi Pelatih ({{ \Carbon\Carbon::parse($startPeriod)->locale('id')->translatedFormat('F Y') }} - {{ \Carbon\Carbon::parse($endPeriod)->locale('id')->translatedFormat('F Y') }})</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th style="width:40px">#</th>
                            <th>Nama Pelatih</th>
                            <th>TS</th>
                            @foreach ($months as $month)
                                <th class="text-right">{{ \Carbon\Carbon::parse($month . '-01')->locale('id')->translatedFormat('M Y') }}</th>
                            @endforeach
                            <th class="text-right bg-success">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coaches as $index => $coach)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $coach['nama_pelatih'] }}</td>
                                <td>{{ $coach['tingkatan_sabuk'] }}</td>
                                @php $rowTotal = 0; @endphp
                                @foreach ($months as $month)
                                    @php $val = $coach['months'][$month] ?? 0; $rowTotal += $val; @endphp
                                    <td class="text-right">{{ $val ? number_format($val, 0, ',', '.') : '-' }}</td>
                                @endforeach
                                <td class="text-right bg-success"><strong>{{ $rowTotal ? number_format($rowTotal, 0, ',', '.') : '-' }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($months) + 1 }}" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

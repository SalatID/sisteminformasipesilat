@extends('layout.index_admin')
@section('title', $pageTitle)
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">{{ $pageTitle }}</li>
    </ol>
@endsection
@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-sm">
            <div class="card-body">
                <form id="filterForm" method="GET" action="{{ route('report.contribution.top') }}">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="start_period" class="form-label">Periode Awal</label>
                            <input type="month" class="form-control" id="start_period" name="start_period" value="{{ $startPeriod }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="end_period" class="form-label">Periode Akhir</label>
                            <input type="month" class="form-control" id="end_period" name="end_period" value="{{ $endPeriod }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="type" class="form-label">Tipe Laporan</label>
                            <select class="form-control" id="type" name="type">
                                <option value="greatest" {{ $type === 'greatest' ? 'selected' : '' }}>10 Teratas</option>
                                <option value="lowest" {{ $type === 'lowest' ? 'selected' : '' }}>10 Terendah</option>
                            </select>
                        </div>
                        <div class="col-md-5 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary mr-2">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="{{ route('report.contribution.top') }}" class="btn btn-sm btn-secondary">
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
                <h3 class="card-title">{{ $pageTitle }} ({{ \Carbon\Carbon::parse($startPeriod)->locale('id')->translatedFormat('F Y') }} - {{ \Carbon\Carbon::parse($endPeriod)->locale('id')->translatedFormat('F Y') }})</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th style="width:40px">#</th>
                            <th>Nama Pelatih</th>
                            <th>TS</th>
                            @foreach ($months as $month)
                                <th class="text-right" style="min-width: 100px;">{{ \Carbon\Carbon::parse($month . '-01')->locale('id')->translatedFormat('M Y') }}</th>
                            @endforeach
                            <th class="text-right bg-success">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coaches as $index => $coach)
                            <tr>
                                <td class="text-center"><strong>{{ $loop->iteration }}</strong></td>
                                <td>{{ $coach['nama_pelatih'] }}</td>
                                <td>{{ $coach['tingkatan_sabuk'] }}</td>
                                @foreach ($months as $month)
                                    @php $val = $coach['months'][$month] ?? 0; @endphp
                                    <td class="text-right">{{ $val ? number_format($val, 0, ',', '.') : '-' }}</td>
                                @endforeach
                                <td class="text-right bg-success">
                                    <strong>{{ $coach['total_contribution'] ? number_format($coach['total_contribution'], 0, ',', '.') : '-' }}</strong>
                                </td>
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

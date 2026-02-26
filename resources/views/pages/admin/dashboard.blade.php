@extends('layout.index_admin')
@section('title', 'Dashboard')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
@endsection

<style>
    
    @media (min-width: 992px) {
        .chart-container {
            height: 350px;
        }
    }
    
    .chart-container canvas {
        max-height: 100% !important;
    }
    
    /* .card {
        display: flex;
        flex-direction: column;
    } */
    
    /* .card-body {
        flex: 1;
    } */
    
    @media (max-width: 991px) {
        .card.h-100 {
            height: auto !important;
        }
    }
</style>

@section('content')


    <div class="container-fluid py-3" id="dashboardContent">

        {{-- Header + Filter --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5>Selamat Datang {{ auth()->user()->fullname }}, di {{ env('APP_NAME') }}</h5>
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label mb-1">Dari</label>
                    <input type="date" name="start_period" class="form-control form-control-sm"
                        value="{{ request('start_period') }}">
                </div>
                <div>
                    <label class="form-label mb-1">Sampai</label>
                    <input type="date" name="end_period" class="form-control form-control-sm"
                        value="{{ request('end_period') }}">
                </div>
                {{-- <div>
        <label class="form-label mb-1">Tingkatan Sabuk</label>
        <select name="ts_id" class="form-control form-control-sm">
          <option value="">Semua</option>
          @foreach ($tsOptions ?? [] as $ts)
            <option value="{{ $ts->id }}" @selected(request('ts_id') == $ts->id)>{{ $ts->name }}</option>
          @endforeach
        </select>
      </div> --}}
                <div>
                    <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>

        {{-- KPI / Quick Stats (opsional) --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $kpi['total_training'] ?? '-' }}<sup style="font-size: 20px"> kali</sup></h3>

                        <p>Total Latihan (Periode)</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-stats-bars"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $kpi['total_holiday_or_cancelled'] ?? '-' }}<sup style="font-size: 20px"> kali</sup></h3>
                        <p>Total Libur (Periode)</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-calendar"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $kpi['active_units'] ?? '-' }}<sup style="font-size: 20px"> unit</sup></h3>
                        <p>Total Unit Aktif (Periode)</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-home"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $kpi['active_coaches'] ?? '-' }}<sup style="font-size: 20px"> orang</sup></h3>
                        <p>Total Pelatih Aktif (Periode)</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- 8 Cards: Top 10 (tabbed) --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="absensi-tab" data-toggle="tab" data-target="#absensi" type="button" role="tab" aria-controls="absensi" aria-selected="true">Absensi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="kontribusi-tab" data-toggle="tab" data-target="#kontribusi" type="button" role="tab" aria-controls="kontribusi" aria-selected="false">Kontribusi</button>
                </li>
            </ul>
            <button type="button" class="btn btn-success btn-sm" id="exportTabBtn">
                <i class="fa fa-download"></i> Export to Image
            </button>
        </div>

        <div class="tab-content" id="dashboardTabsContent">
            <div class="tab-pane fade show active" id="absensi" role="tabpanel" aria-labelledby="absensi-tab">
                <div class="row g-3">

                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-info d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Rata-rata Anggota Unit Per Bulan (6 Bulan Terakhir)</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="unitMembersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 1) Top 10 pelatih hadir di unit --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Pelatih — Hadir di Unit</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-center">Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topCoachesUnit ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->hadir_unit }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2) Top 10 pelatih hadir di Almaka --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Pelatih — Hadir di Almaka</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-center">Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topCoachesAlmaka ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->hadir_almaka }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Asisten Pelatih — Hadir di Unit</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-center">Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topAssCoachesUnit ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->hadir_unit }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2) Top 10 pelatih hadir di Almaka --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 AsistenPelatih — Hadir di Almaka</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-center">Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topAssCoachesAlmaka ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->hadir_almaka }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3) Top 10 unit paling banyak pelatih --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Pelatih Terbanyak</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Pelatih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsMostCoaches ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->total_coach }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4) Top 10 unit paling sedikit pelatih --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Pelatih Tersedikit</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Pelatih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsLeastCoaches ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->total_coach }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5) Top 10 unit anggota terbanyak --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Anggota Terbanyak (Rata-rata)</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Anggota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsMostMembersAvg ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">
                                                        {{ number_format(floor($row->avg_peserta), 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 6) Top 10 unit anggota tersedikit --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Anggota Tersedikit (Rata-rata)</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Anggota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsLeastMembers ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ number_format(floor($row->avg_peserta), 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 7) Top 10 unit paling sering latihan --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Paling Sering Latihan</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Latihan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsMostTraining ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->total_latihan }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 8) Top 10 unit paling banyak liburnya --}}
                    <div class="col-12 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top 10 Unit — Paling Sering Libur</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Unit</th>
                                                <th class="text-center">Total Libur</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topUnitsMostNoTraining ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 280px;">{{ $row->name }}</td>
                                                    <td class="text-center fw-semibold">{{ $row->total_tidak_latihan }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="kontribusi" role="tabpanel" aria-labelledby="kontribusi-tab">
                <div class="row g-3">
                    {{-- Monthly Contributions Chart --}}
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Kontribusi Bulanan - Pelatih & Komwil (6 Bulan Terakhir)</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthlyContributionsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Total Monthly Contributions Chart --}}
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-info d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Total Kontribusi Bulanan (6 Bulan Terakhir)</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="totalContributionsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Average Unit Members Monthly Chart --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Pelatih — Kontribusi Terbesar</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-right">Total Kontribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topContributionGreatestResults ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 200px;">{{ $row->nama_pelatih }}</td>
                                                    <td class="text-right fw-semibold">{{ number_format($row->total_contribution, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top 10 Lowest Contributions --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Pelatih — Kontribusi Terendah</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Pelatih</th>
                                                <th class="text-right">Total Kontribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topContributionLowestResults ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 200px;">{{ $row->nama_pelatih }}</td>
                                                    <td class="text-right fw-semibold">{{ number_format($row->total_contribution, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top 10 Greatest Contributions --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Asisten Pelatih — Kontribusi Terbesar</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Asisten Pelatih</th>
                                                <th class="text-right">Total Kontribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topAssistantContributionGreatestResults ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 200px;">{{ $row->nama_pelatih }}</td>
                                                    <td class="text-right fw-semibold">{{ number_format($row->total_contribution, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top 10 Lowest Contributions --}}
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">Top 10 Asisten Pelatih — Kontribusi Terendah</div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:56px;">No</th>
                                                <th>Nama Asisten Pelatih</th>
                                                <th class="text-right">Total Kontribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topAssistantContributionLowestResults ?? [] as $i => $row)
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="text-truncate" style="max-width: 200px;">{{ $row->nama_pelatih }}</td>
                                                    <td class="text-right fw-semibold">{{ number_format($row->total_contribution, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize Unit Members Monthly Chart - ensure chart library is ready
        document.addEventListener('DOMContentLoaded', function() {
            @if(!empty($unitMembersMonthly) && count($unitMembersMonthly) > 0)
                var canvasElement = document.getElementById('unitMembersChart');
                if (canvasElement && typeof Chart !== 'undefined') {
                    var ctx = canvasElement.getContext('2d');
                    var chartData = @json($unitMembersMonthly);
                    
                    var labels = chartData.map(item => {
                        const [year, month] = item.month.split('-');
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        return monthNames[parseInt(month) - 1] + ' ' + year;
                    });
                    
                    var data = chartData.map(item => parseFloat(item.avg_members).toFixed(2));
                    
                    try {
                        var unitMembersChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Rata-rata Anggota Unit',
                                    data: data,
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 5,
                                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointHoverRadius: 7
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        enabled: true,
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                const value = parseInt(context.parsed.y);
                                                label += value.toLocaleString('id-ID');
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Jumlah Anggota'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return parseInt(value).toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch(e) {
                        console.error('Chart initialization error:', e);
                    }
                }
            @endif

            // Initialize Monthly Contributions Chart
            @if(!empty($monthlyContributions) && count($monthlyContributions) > 0)
                var monthlyCtx = document.getElementById('monthlyContributionsChart');
                if (monthlyCtx && typeof Chart !== 'undefined') {
                    var monthlyCtxReal = monthlyCtx.getContext('2d');
                    var monthlyData = @json($monthlyContributions);
                    
                    var monthlyLabels = monthlyData.map(item => {
                        const [year, month] = item.month.split('-');
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        return monthNames[parseInt(month) - 1] + ' ' + year;
                    });
                    
                    var coachData = monthlyData.map(item => parseFloat(item.coach_contribution).toFixed(0));
                    var komwilData = monthlyData.map(item => parseFloat(item.komwil_contribution).toFixed(0));
                    
                    try {
                        var monthlyContributionsChart = new Chart(monthlyCtxReal, {
                            type: 'line',
                            data: {
                                labels: monthlyLabels,
                                datasets: [
                                    {
                                        label: 'Kontribusi Pelatih',
                                        data: coachData,
                                        borderColor: 'rgba(75, 192, 75, 1)',
                                        backgroundColor: 'rgba(75, 192, 75, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 5,
                                        pointBackgroundColor: 'rgba(75, 192, 75, 1)',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointHoverRadius: 7
                                    },
                                    {
                                        label: 'Kontribusi Komwi',
                                        data: komwilData,
                                        borderColor: 'rgba(255, 159, 64, 1)',
                                        backgroundColor: 'rgba(255, 159, 64, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 5,
                                        pointBackgroundColor: 'rgba(255, 159, 64, 1)',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        pointHoverRadius: 7
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        enabled: true,
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                const value = parseInt(context.parsed.y);
                                                label += value.toLocaleString('id-ID');
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Total Kontribusi'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return parseInt(value).toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch(e) {
                        console.error('Monthly contributions chart initialization error:', e);
                    }
                }
            @endif

            // Initialize Total Contributions Chart
            @if(!empty($totalMonthlyContributions) && count($totalMonthlyContributions) > 0)
                var totalCtx = document.getElementById('totalContributionsChart');
                if (totalCtx && typeof Chart !== 'undefined') {
                    var totalCtxReal = totalCtx.getContext('2d');
                    var totalData = @json($totalMonthlyContributions);
                    
                    var totalLabels = totalData.map(item => {
                        const [year, month] = item.month.split('-');
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        return monthNames[parseInt(month) - 1] + ' ' + year;
                    });
                    
                    var totalContributionData = totalData.map(item => parseFloat(item.total_contribution).toFixed(0));
                    
                    try {
                        var totalContributionsChart = new Chart(totalCtxReal, {
                            type: 'line',
                            data: {
                                labels: totalLabels,
                                datasets: [{
                                    label: 'Total Kontribusi',
                                    data: totalContributionData,
                                    borderColor: 'rgba(153, 102, 255, 1)',
                                    backgroundColor: 'rgba(153, 102, 255, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 5,
                                    pointBackgroundColor: 'rgba(153, 102, 255, 1)',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointHoverRadius: 7
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    tooltip: {
                                        enabled: true,
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                const value = parseInt(context.parsed.y);
                                                label += value.toLocaleString('id-ID');
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Total Kontribusi'
                                        },
                                        ticks: {
                                            callback: function(value) {
                                                return parseInt(value).toLocaleString('id-ID');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch(e) {
                        console.error('Total contributions chart initialization error:', e);
                    }
                }
            @endif

            // Export current screen to image
            document.getElementById('exportTabBtn').addEventListener('click', function() {
                const dashboardContent = document.getElementById('dashboardContent');
                if (!dashboardContent) {
                    alert('Dashboard content not found');
                    return;
                }

                const btn = this;
                btn.style.display = 'none';

                // Dynamically load html2canvas
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                script.onload = function() {
                    // Set fixed width for consistent export
                    const originalWidth = dashboardContent.style.width;
                    const originalOverflow = dashboardContent.style.overflow;
                    const originalMaxWidth = dashboardContent.style.maxWidth;
                    
                    dashboardContent.style.width = '1800px';
                    dashboardContent.style.maxWidth = '1800px';
                    dashboardContent.style.overflow = 'visible';

                    html2canvas(dashboardContent, {
                        allowTaint: true,
                        useCORS: true,
                        scale: 2,
                        logging: false,
                        backgroundColor: '#ffffff',
                        windowWidth: 1800,
                        windowHeight: document.documentElement.scrollHeight
                    }).then(canvas => {
                        // Restore original styles
                        dashboardContent.style.width = originalWidth;
                        dashboardContent.style.maxWidth = originalMaxWidth;
                        dashboardContent.style.overflow = originalOverflow;

                        const link = document.createElement('a');
                        const timestamp = new Date().toLocaleString('id-ID').replace(/[:\s/]/g, '-');
                        link.href = canvas.toDataURL('image/png');
                        link.download = `Dashboard-${timestamp}.png`;
                        link.click();
                        
                        btn.style.display = 'block';
                    }).catch(err => {
                        // Restore original styles on error
                        dashboardContent.style.width = originalWidth;
                        dashboardContent.style.maxWidth = originalMaxWidth;
                        dashboardContent.style.overflow = originalOverflow;

                        console.error('Export error:', err);
                        alert('Failed to export image');
                        btn.style.display = 'block';
                    });
                };
                document.head.appendChild(script);
            });
        });
    </script>
@endsection


@extends('layout.index_admin')
@section('title', 'Dashboard')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
@endsection
@section('content')


    <div class="container-fluid py-3">

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

        {{-- 8 Cards: Top 10 --}}
        <div class="row g-3">

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
@endsection

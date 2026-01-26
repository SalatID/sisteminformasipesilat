@extends('layout.index_admin')
@section('title', 'Riwayat Kontribusi')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Riwayat Kontribusi</li>
    </ol>
@endsection
@section('content')

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('receipt.contribution.history') }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="unit_id" class="form-label">Unit</label>
                                <select class="form-control" id="unit_id" name="unit_id">
                                    <option value="">Semua Unit</option>
                                    @foreach ($units as $unitItem)
                                        <option value="{{ $unitItem->id }}" {{ request('unit_id') == $unitItem->id ? 'selected' : '' }}>
                                            {{ $unitItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="periode" class="form-label">Periode (YYYY-MM)</label>
                                <input type="text" class="form-control" id="periode" name="periode" 
                                    value="{{ request('periode') }}" placeholder="2026-01">
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <a href="{{ route('receipt.contribution.history') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Contributions List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Kontribusi</h3>
                    <div class="card-tools">
                        <a href="{{ route('receipt.contribution.unit.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Buat Kontribusi Baru
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="text-center" width="50">#</th>
                                <th>Unit</th>
                                <th class="text-center" width="120">Periode</th>
                                <th class="text-right" width="150">Uang Kontribusi</th>
                                <th class="text-right" width="150">65% PJ</th>
                                <th class="text-right" width="150">20% Kas</th>
                                <th class="text-right" width="150">15% Tabungan</th>
                                <th class="text-center" width="100">Revisi</th>
                                <th class="text-center" width="100">Bukti</th>
                                <th class="text-center" width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($i = 1)
                            @forelse ($contributions as $index => $contribution)
                                <tr>
                                    <td class="text-center">{{ $i++ }}</td>
                                    <td>{{ $contribution->unit->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">
                                            {{ \Carbon\Carbon::parse($contribution->periode . '-01')->format('F Y') }}
                                        </span>
                                    </td>
                                    <td class="text-right">Rp {{ number_format($contribution->contribution_amount, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($contribution->pj_share, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($contribution->kas_share, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($contribution->saving_share, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($contribution->is_transfer)
                                        <span class="badge badge-success">Approved</span>
                                        @else
                                        <span class="badge badge-secondary">{{ $contribution->revision_count ?? 0 }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($contribution->contribution_receipt_img)
                                            <a href="{{ asset( $contribution->contribution_receipt_img) }}" target="_blank" class="btn btn-xs btn-info">
                                                <i class="fas fa-image"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php($periode = explode('-', $contribution->periode))
                                        @php($year = $periode[0])
                                        @php($month = $periode[1])
                                        @can("receipt-contribution-unit_show")
                                        <a href="{{ route('receipt.contribution.unit.index', [
                                            'unit_id' => $contribution->unit_id,
                                            'month' => $month,
                                            'year' => $year,
                                            'contribution_amount' => $contribution->contribution_amount
                                        ]) }}" title="Lihat Detail">
                                            <i class="fas fa-search text-success"></i>
                                        </a>
                                        @endcan
                                        @can("receipt-contribution-unit_delete")
                                        <a href="#" data-id="{{ $contribution->id }}" onclick="deleteContribution(this)" title="Hapus Kontribusi">
                                            <i class="fas fa-trash text-danger"></i>
                                        </a>
                                        @endcan
                                        @can("receipt-contribution-unit_approve")
                                            @if(!$contribution->is_transfer)
                                            <a href="{{ route('receipt.contribution.unit.approve', $contribution->id) }}">
                                                <i class="fas fa-check text-primary"></i>
                                            </a>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Tidak ada data kontribusi</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        function deleteContribution(t) {
            if (confirm('Apakah Anda yakin ingin menghapus data kontribusi ini?')) {
                id = $(t).data('id');
                // Implement delete functionality
                $.ajax({
                    url: '/receipt/contribution/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.error) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan saat menghapus data');
                    }
                });
            }
        }
    </script>
@endsection

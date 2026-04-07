@extends('layout.index_admin')
@section("title","Buat Laporan TC")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('training-center-report.index') }}">Buat Laporan TC</a></li>
    <li class="breadcrumb-item active">Buat Baru</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Laporan Performa Pelatihan
                </h3>
                <div class="card-tools">
                    <a href="{{ route('training-center-report.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Validasi Gagal!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('training-center-report.add') }}" method="POST">
                    @csrf

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Pilih Pusat Pelatihan dan Tanggal untuk membuat laporan performa anggota
                    </div>

                    <!-- Training Center -->
                    <div class="form-group mb-3">
                        <label for="training_center_id" class="form-label">Pusat Pelatihan <span class="text-danger">*</span></label>
                        <select class="form-control @error('training_center_id') is-invalid @enderror" 
                                id="training_center_id"
                                name="training_center_id" 
                                required>
                            <option value="">-- Pilih Pusat Pelatihan --</option>
                            @foreach($training_centers as $center)
                                <option value="{{ $center->id }}" {{ old('training_center_id') == $center->id ? 'selected' : '' }}>
                                    {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('training_center_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Training Date -->
                    <div class="form-group mb-3">
                        <label for="training_date" class="form-label">Tanggal Pelatihan <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('training_date') is-invalid @enderror" 
                               id="training_date" 
                               name="training_date"
                               value="{{ old('training_date') }}"
                               required>
                        @error('training_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Training Type -->
                    <div class="form-group mb-3">
                        <label for="training_type" class="form-label">Jenis Pelatihan <span class="text-danger">*</span></label>
                        <select class="form-control @error('training_type') is-invalid @enderror" 
                                id="training_type"
                                name="training_type" 
                                required>
                            <option value="">-- Pilih Jenis Pelatihan --</option>
                            <option value="offline" {{ old('training_type') == 'offline' ? 'selected' : '' }}>
                                <i class="fas fa-map-marker-alt"></i> Offline (Tatap Muka)
                            </option>
                            <option value="online" {{ old('training_type') == 'online' ? 'selected' : '' }}>
                                <i class="fas fa-video"></i> Online (Virtual)
                            </option>
                        </select>
                        @error('training_type')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right"></i> Lanjut ke Laporan Performa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Laporan Performa Pelatihan
                </h3>
                <div class="card-tools">
                    <a href="{{ route('training-center-report.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Validasi Gagal!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ $training_center ? route('training-center-report.store') : route('training-center-report.create') }}" method="POST">
                    @csrf

                    <!-- Selection Form -->
                    <div class="card card-info mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search"></i> Pilih Pusat Pelatihan & Tanggal
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Training Center -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="training_center_id" class="form-label">Pusat Pelatihan <span class="text-danger">*</span></label>
                                        <select class="form-control @error('training_center_id') is-invalid @enderror" 
                                                id="training_center_id"
                                                name="training_center_id" 
                                                required>
                                            <option value="">-- Pilih Pusat Pelatihan --</option>
                                            @foreach($training_centers as $tc)
                                                <option value="{{ $tc->id }}" {{ $training_center && $training_center->id === $tc->id ? 'selected' : '' }}>
                                                    {{ $tc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('training_center_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Training Date -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="training_date" class="form-label">Tanggal Pelatihan <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('training_date') is-invalid @enderror" 
                                               id="training_date"
                                               name="training_date" 
                                               value="{{ $training_date ?? old('training_date') }}"
                                               required>
                                        @error('training_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Training Type -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="training_type" class="form-label">Jenis Pelatihan <span class="text-danger">*</span></label>
                                        <select class="form-control @error('training_type') is-invalid @enderror" 
                                                id="training_type"
                                                name="training_type" 
                                                required>
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="online" {{ $training_type === 'online' ? 'selected' : '' }}>
                                                <i class="fas fa-video"></i> Online
                                            </option>
                                            <option value="offline" {{ $training_type === 'offline' ? 'selected' : '' }}>
                                                <i class="fas fa-map-marker-alt"></i> Offline
                                            </option>
                                        </select>
                                        @error('training_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" name="action" value="preview" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Lihat Data Anggota
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info Boxes (shown when training_center is selected) -->
                    @if($training_center)
                        <div class="row mb-4 g-3">
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-dumbbell"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Pusat Pelatihan</span>
                                        <span class="info-box-number d-block text-truncate">{{ $training_center->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Tanggal Pelatihan</span>
                                        <span class="info-box-number">{{ \Carbon\Carbon::parse($training_date)->format('d-m-Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-users"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Jumlah Anggota</span>
                                        <span class="info-box-number">{{ $members->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Input Table -->
                        @if($members->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Tidak ada anggota yang terdaftar di Pusat Pelatihan ini.
                            </div>
                        @else
                            <div class="card card-primary mb-4">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-input-numeric"></i> Input Performa Anggota
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <input type="hidden" name="training_center_id" value="{{ $training_center->id }}">
                                    <input type="hidden" name="training_date" value="{{ $training_date }}">
                                    <input type="hidden" name="training_type" value="{{ $training_type }}">

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover table-sm" id="performanceTable">
                                            <thead class="table-dark sticky-top">
                                                <tr>
                                                    <th class="text-center" style="min-width: 30px;">#</th>
                                                    <th style="min-width: 180px;">Nama Pesilat</th>
                                                    <th class="text-center d-none d-sm-table-cell" style="min-width: 90px;">
                                                        <i class="fas fa-check-circle"></i> <span class="d-none d-lg-inline">Absensi</span><span class="d-lg-none">Hadir</span>
                                                    </th>
                                                    <th class="text-center" style="min-width: 90px;">
                                                        <i class="fas fa-money-bill"></i> <span class="d-lg-inline">Kas</span>
                                                    </th>
                                                    <th class="text-center d-none d-md-table-cell" style="min-width: 90px;">
                                                        <i class="fas fa-dumbbell"></i> <span class="d-lg-inline">Endurance</span><span class="d-none d-lg-inline">End.</span>
                                                    </th>
                                                    <th class="text-center d-none d-md-table-cell" style="min-width: 90px;">
                                                        <i class="fas fa-fist-raised"></i> <span class="d-lg-inline">Strength</span><span class="d-none d-lg-inline">Str.</span>
                                                    </th>
                                                    <th class="text-center" style="min-width: 90px;">
                                                        <i class="fas fa-karate"></i> <span class="d-lg-inline">Technique</span><span class="d-none d-lg-inline">Tech.</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php($i = 1)
                                                @foreach($members as $member)
                                                @php($record = $existingRecords[$member->id] ?? null)
                                                <tr>
                                                    <td class="text-center">{{ $i++ }}</td>
                                                    <td>
                                                        <strong class="d-block">{{ $member->name }}</strong>
                                                        <small class="text-muted">{{ $member->member_id }}</small>
                                                    </td>
                                                    <td class="text-center d-none d-sm-table-cell">
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="members[{{ $loop->index }}][member_id]" 
                                                                   value="{{ $member->id }}"
                                                                   id="attended_{{ $member->id }}"
                                                                   {{ $record && $record->attended ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="attended_{{ $member->id }}">
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="members[{{ $loop->index }}][kas]" 
                                                                   id="kas_{{ $member->id }}"
                                                                   {{ $record && $record->kas ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="kas_{{ $member->id }}">
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center d-none d-md-table-cell">
                                                        <input type="number" 
                                                               name="members[{{ $loop->index }}][endurance]" 
                                                               class="form-control form-control-sm text-center" 
                                                               min="0" 
                                                               max="100"
                                                               placeholder="0-100"
                                                               value="{{ $record ? $record->endurance : '' }}">
                                                    </td>
                                                    <td class="text-center d-none d-md-table-cell">
                                                        <input type="number" 
                                                               name="members[{{ $loop->index }}][strength]" 
                                                               class="form-control form-control-sm text-center" 
                                                               min="0" 
                                                               max="100"
                                                               placeholder="0-100"
                                                               value="{{ $record ? $record->strength : '' }}">
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number" 
                                                               name="members[{{ $loop->index }}][technique]" 
                                                               class="form-control form-control-sm text-center" 
                                                               min="0" 
                                                               max="100"
                                                               placeholder="0-100"
                                                               value="{{ $record ? $record->technique : '' }}">
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Hidden fields to maintain checkbox state -->
                                    @php($i = 0)
                                    @foreach($members as $member)
                                    <input type="hidden" name="members[{{ $i }}][member_id]" value="{{ $member->id }}" id="hidden_member_{{ $member->id }}">
                                    <input type="hidden" name="members[{{ $i }}][attended]" value="0" id="attended_value_{{ $member->id }}">
                                    <input type="hidden" name="members[{{ $i }}][kas]" value="0" id="kas_value_{{ $member->id }}">
                                    @php($i++)
                                    @endforeach
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 d-flex flex-column flex-md-row gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-success btn-lg flex-grow-1 flex-md-grow-0" style="min-width: 200px;">
                                    <i class="fas fa-save"></i> <span class="d-none d-sm-inline">Simpan Laporan</span><span class="d-sm-none">Simpan</span>
                                </button>
                                <a href="{{ route('training-center-report.index') }}" class="btn btn-secondary btn-lg flex-grow-1 flex-md-grow-0" style="min-width: 120px;">
                                    <i class="fas fa-times"></i> <span class="d-sm-inline">Batal</span>
                                </a>
                            </div>
                        @endif
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .info-box-icon {
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        min-width: 60px;
    }

    .info-box-content {
        flex: 1;
    }

    .info-box-number {
        font-size: 1.1rem;
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .info-box {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .info-box-icon {
            margin-right: 0.75rem;
        }

        .info-box-text {
            font-size: 0.85rem;
        }

        .info-box-number {
            font-size: 0.95rem;
        }

        table.table {
            font-size: 0.85rem;
        }

        .table > :not(caption) > * > * {
            padding: 0.35rem;
        }

        .form-control-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.25rem;
        }
    }

    @media (max-width: 768px) {
        .d-flex.flex-md-row {
            flex-direction: column;
        }

        .flex-md-grow-0 {
            flex-grow: 1 !important;
        }
    }

    .sticky-top {
        top: 0;
        z-index: 10;
    }
</style>

<script>
// Update hidden attended field when checkbox changes
document.querySelectorAll('input[name*="[member_id]"]').forEach(checkbox => {
    const memberId = checkbox.value;
    const attendedValue = document.getElementById('attended_value_' + memberId);
    
    checkbox.addEventListener('change', function() {
        if (attendedValue) {
            attendedValue.value = this.checked ? '1' : '0';
        }
    });
});

// Update hidden kas field when checkbox changes
document.querySelectorAll('input[id^="kas_"]').forEach(checkbox => {
    const memberId = checkbox.id.replace('kas_', '');
    const kasValue = document.getElementById('kas_value_' + memberId);
    
    checkbox.addEventListener('change', function() {
        if (kasValue) {
            kasValue.value = this.checked ? '1' : '0';
        }
    });
});

// Initialize hidden fields on page load
document.addEventListener('DOMContentLoaded', function() {
    @php($i = 0)
    @foreach($members as $member)
    @php($record = $existingRecords[$member->id] ?? null)
    const attendedCheckbox = document.getElementById('attended_{{ $member->id }}');
    const kasCheckbox = document.getElementById('kas_{{ $member->id }}');
    const attendedValue = document.getElementById('attended_value_{{ $member->id }}');
    const kasValue = document.getElementById('kas_value_{{ $member->id }}');
    
    if (attendedCheckbox && attendedValue) {
        attendedValue.value = attendedCheckbox.checked ? '1' : '0';
    }
    if (kasCheckbox && kasValue) {
        kasValue.value = kasCheckbox.checked ? '1' : '0';
    }
    @php($i++)
    @endforeach
});
</script>
@endsection

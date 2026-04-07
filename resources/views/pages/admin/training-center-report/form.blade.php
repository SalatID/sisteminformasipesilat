@extends('layout.index_admin')
@section("title","Laporan Performa - " . $training_center->name)
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('training-center-report.index') }}">Buat Laporan TC</a></li>
    <li class="breadcrumb-item active">{{ $training_center->name }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
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
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary">
                                <i class="fas fa-dumbbell"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pusat Pelatihan</span>
                                <span class="info-box-number">{{ $training_center->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-calendar"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tanggal Pelatihan</span>
                                <span class="info-box-number">{{ Carbon\Carbon::parse($training_date)->format('d-m-Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
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

                @if($members->isEmpty())
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Tidak ada anggota yang terdaftar di Pusat Pelatihan ini.
                    </div>
                @else
                    <form action="{{ route('training-center-report.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="training_center_id" value="{{ $training_center->id }}">
                        <input type="hidden" name="training_date" value="{{ $training_date }}">
                        <input type="hidden" name="training_type" value="{{ $training_type }}">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="performanceTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="width: 30px;">#</th>
                                        <th style="width: 200px;">Nama Pesilat</th>
                                        <th class="text-center" style="width: 80px;">
                                            <i class="fas fa-check-circle"></i> Absensi
                                        </th>
                                        <th class="text-center" style="width: 80px;">
                                            <i class="fas fa-money-bill"></i> Kas
                                        </th>
                                        <th class="text-center" style="width: 80px;">
                                            <i class="fas fa-dumbbell"></i> Endurance
                                        </th>
                                        <th class="text-center" style="width: 80px;">
                                            <i class="fas fa-fist-raised"></i> Strength
                                        </th>
                                        <th class="text-center" style="width: 80px;">
                                            <i class="fas fa-karate"></i> Technique
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
                                            <strong>{{ $member->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $member->member_id }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
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
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="members[{{ $loop->index }}][kas]" 
                                                       id="kas_{{ $member->id }}"
                                                       {{ $record && $record->kas ? 'checked' : '' }}>
                                                <label class="form-check-label" for="kas_{{ $member->id }}">
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="members[{{ $loop->index }}][endurance]" 
                                                   class="form-control form-control-sm text-center" 
                                                   min="5" 
                                                   max="8"
                                                   placeholder="5-8"
                                                   value="{{ $record ? $record->endurance : '' }}">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="members[{{ $loop->index }}][strength]" 
                                                   class="form-control form-control-sm text-center" 
                                                   min="5" 
                                                   max="8"
                                                   placeholder="5-8"
                                                   value="{{ $record ? $record->strength : '' }}">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   name="members[{{ $loop->index }}][technique]" 
                                                   class="form-control form-control-sm text-center" 
                                                   min="5" 
                                                   max="8"
                                                   placeholder="5-8"
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

                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-center">
                                <button type="submit" class="col btn btn-success btn-lg w-50">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="{{ route('training-center-report.index') }}" class="col btn btn-secondary btn-lg w-25">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </div>
                        </div>
                    </form>
                @endif
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

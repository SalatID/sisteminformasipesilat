@extends('layout.index_admin')
@section("title", isset($coach) ? "Edit Pelatih" : "Tambah Pelatih")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('coach.index') }}">Manajemen Pelatih</a></li>
    <li class="breadcrumb-item active">{{ isset($coach) ? "Edit Pelatih" : "Tambah Pelatih" }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <!-- Coach Data Form Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ isset($coach) ? "Edit Data Pelatih" : "Tambah Data Pelatih Baru" }}</h3>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Validasi Gagal!</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ isset($coach) ? route('coach.update', $coach->id) : route('coach.store') }}" method="POST">
                    @csrf
                    @if(isset($coach))
                        @method('PUT')
                    @endif

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Nama Pelatih <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $coach->name ?? '') }}"
                               placeholder="Masukkan nama pelatih"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="ts_id" class="form-label">Tingkat Sabuk (TS) <span class="text-danger">*</span></label>
                        <select class="form-control @error('ts_id') is-invalid @enderror" 
                                id="ts_id" 
                                name="ts_id" 
                                required>
                            <option value="">-- Pilih Tingkat Sabuk --</option>
                            @foreach($ts_list as $ts)
                                <option value="{{ $ts->id }}" 
                                    {{ old('ts_id', $coach->ts_id ?? '') == $ts->id ? 'selected' : '' }}>
                                    {{ $ts->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('ts_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save"></i> {{ isset($coach) ? 'Perbarui' : 'Simpan' }}
                        </button>
                        <a href="{{ isset($coach) ? route('coach.show', $coach->id) : route('coach.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Exam History Section (Only for Edit) -->
        @if(isset($coach))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-certificate"></i> Manajemen Riwayat Ujian
                </h3>
            </div>
            <div class="card-body">
                <!-- Add Exam Form -->
                <div class="card card-primary card-outline mb-3">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-plus"></i> Tambah Riwayat Ujian</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('coach.exam.store', $coach->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="exam_date" class="form-label">Tanggal Ujian (Mulai) <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('exam_date') is-invalid @enderror" 
                                               id="exam_date" 
                                               name="exam_date" 
                                               value="{{ old('exam_date') }}"
                                               required>
                                        @error('exam_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="exam_end_date" class="form-label">Tanggal Ujian (Selesai) <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('exam_end_date') is-invalid @enderror" 
                                               id="exam_end_date" 
                                               name="exam_end_date" 
                                               value="{{ old('exam_end_date') }}"
                                               required>
                                        @error('exam_end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="exam_location" class="form-label">Lokasi Ujian <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('exam_location') is-invalid @enderror" 
                                               id="exam_location" 
                                               name="exam_location" 
                                               value="{{ old('exam_location') }}"
                                               placeholder="Masukkan lokasi ujian"
                                               required>
                                        @error('exam_location')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="organizer" class="form-label">Penyelenggara Ujian</label>
                                        <input type="text" 
                                               class="form-control @error('organizer') is-invalid @enderror" 
                                               id="organizer" 
                                               name="organizer" 
                                               value="{{ old('organizer') }}"
                                               placeholder="Masukkan nama penyelenggara (opsional)">
                                        @error('organizer')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="ts_before" class="form-label">Tingkat Sabuk Sebelum Ujian</label>
                                        <select class="form-control @error('ts_before') is-invalid @enderror" 
                                                id="ts_before" 
                                                name="ts_before">
                                            <option value="">-- Pilih Tingkat Sabuk --</option>
                                            @foreach($ts_list as $ts)
                                                <option value="{{ $ts->id }}" 
                                                    {{ old('ts_before') == $ts->id ? 'selected' : '' }}>
                                                    {{ $ts->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('ts_before')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="ts_after" class="form-label">Tingkat Sabuk Sesudah Ujian</label>
                                        <select class="form-control @error('ts_after') is-invalid @enderror" 
                                                id="ts_after" 
                                                name="ts_after">
                                            <option value="">-- Pilih Tingkat Sabuk --</option>
                                            @foreach($ts_list as $ts)
                                                <option value="{{ $ts->id }}" 
                                                    {{ old('ts_after') == $ts->id ? 'selected' : '' }}>
                                                    {{ $ts->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('ts_after')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" 
                                                  name="notes" 
                                                  rows="2"
                                                  placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Exam History Table -->
                @if($exams->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Belum ada riwayat ujian untuk pelatih ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Tanggal Ujian (Mulai)</th>
                                    <th class="text-center">Tanggal Ujian (Selesai)</th>
                                    <th class="text-center">Lokasi Ujian</th>
                                    <th class="text-center">Penyelenggara</th>
                                    <th class="text-center">TS Sebelum</th>
                                    <th class="text-center">TS Sesudah</th>
                                    <th class="text-center">Catatan</th>
                                    <th class="text-center">Tanggal Dicatat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @foreach($exams as $record)
                                <tr>
                                    <td class="text-center">{{ $i++ }}</td>
                                    <td class="text-center">{{ $record->exam_date->format('d-m-Y') }}</td>
                                    <td class="text-center">{{ $record->exam_end_date->format('d-m-Y') }}</td>
                                    <td class="text-center">{{ $record->exam_location }}</td>
                                    <td class="text-center">{{ $record->organizer ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($record->tsBefore)
                                            <span class="badge bg-info">{{ $record->tsBefore->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($record->tsAfter)
                                            <span class="badge bg-success">{{ $record->tsAfter->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ Str::limit($record->notes, 30) ?? '-' }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($record->created_at)->format('d-m-Y H:i') }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('coach.exam.destroy', [$coach->id, $record->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat ujian ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@extends('layout.index_admin')
@section("title", isset($member) ? "Edit Pesilat" : "Tambah Pesilat")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('member.index') }}">Manajemen Pesilat</a></li>
    <li class="breadcrumb-item active">{{ isset($member) ? "Edit Pesilat" : "Tambah Pesilat" }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <!-- Member Data Form Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ isset($member) ? "Edit Data Pesilat" : "Tambah Data Pesilat Baru" }}</h3>
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

                <form action="{{ isset($member) ? route('member.update', $member->id) : route('member.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($member))
                        @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Nama Pesilat <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $member->name ?? '') }}"
                                       placeholder="Masukkan nama pesilat"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="ts_id" class="form-label">Tingkat Sabuk (TS) <span class="text-danger">*</span></label>
                                <select class="form-control @error('ts_id') is-invalid @enderror" 
                                        id="ts_id" 
                                        name="ts_id" 
                                        required>
                                    <option value="">-- Pilih Tingkat Sabuk --</option>
                                    @foreach($ts_list as $ts)
                                        <option value="{{ $ts->id }}" 
                                            {{ old('ts_id', $member->ts_id ?? '') == $ts->id ? 'selected' : '' }}>
                                            {{ $ts->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ts_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="joined_date" class="form-label">Tanggal Bergabung <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('joined_date') is-invalid @enderror" 
                                       id="joined_date" 
                                       name="joined_date" 
                                       value="{{ old('joined_date', isset($member) ? $member->joined_date->format('Y-m-d') : '') }}"
                                       required>
                                @error('joined_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="unit_id" class="form-label">Unit</label>
                                <select class="form-control @error('unit_id') is-invalid @enderror" 
                                        id="unit_id" 
                                        name="unit_id">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" 
                                            {{ old('unit_id', $member->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unit_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="gender" class="form-label">Jenis Kelamin</label>
                                <select class="form-control @error('gender') is-invalid @enderror" 
                                        id="gender" 
                                        name="gender">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="male" {{ old('gender', $member->gender ?? '') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ old('gender', $member->gender ?? '') == 'female' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="school_level" class="form-label">Tingkat Sekolah</label>
                                <select class="form-control @error('school_level') is-invalid @enderror" 
                                        id="school_level" 
                                        name="school_level">
                                    <option value="">-- Pilih Tingkat Sekolah --</option>
                                    <option value="SD" {{ old('school_level', $member->school_level ?? '') == 'SD' ? 'selected' : '' }}>SD</option>
                                    <option value="SMP" {{ old('school_level', $member->school_level ?? '') == 'SMP' ? 'selected' : '' }}>SMP</option>
                                    <option value="SMA/K" {{ old('school_level', $member->school_level ?? '') == 'SMA/K' ? 'selected' : '' }}>SMA/K</option>
                                    <option value="Kuliah" {{ old('school_level', $member->school_level ?? '') == 'Kuliah' ? 'selected' : '' }}>Kuliah</option>
                                    <option value="Bekerja" {{ old('school_level', $member->school_level ?? '') == 'Bekerja' ? 'selected' : '' }}>Bekerja</option>
                                </select>
                                @error('school_level')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="picture" class="form-label">Foto Profil</label>
                                <input type="file" 
                                       class="form-control @error('picture') is-invalid @enderror" 
                                       id="picture" 
                                       name="picture"
                                       accept="image/*">
                                <small class="text-muted">Format: JPG, PNG, GIF (Max: 2MB)</small>
                                @error('picture')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @if(isset($member) && $member->picture)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label>Foto Saat Ini:</label>
                            <div>
                                <img src="{{ asset($member->picture) }}" alt="{{ $member->name }}" style="max-width: 150px; max-height: 150px;">
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Identification Documents Section -->
                    <div class="card card-primary card-outline mb-3">
                        <div class="card-header">
                            <h5 class="card-title"><i class="fas fa-passport"></i> Data Identitas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="citizen_number" class="form-label">Nomor KTP (Kartu Tanda Penduduk) <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('citizen_number') is-invalid @enderror" 
                                               id="citizen_number" 
                                               name="citizen_number" 
                                               value="{{ old('citizen_number', $member->citizen_number ?? '') }}"
                                               placeholder="Masukkan nomor KTP"
                                               required>
                                        @error('citizen_number')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="citizen_img" class="form-label">Foto KTP</label>
                                        <input type="file" 
                                               class="form-control @error('citizen_img') is-invalid @enderror" 
                                               id="citizen_img" 
                                               name="citizen_img"
                                               accept="image/*">
                                        <small class="text-muted">Format: JPG, PNG, GIF (Max: 2MB)</small>
                                        @error('citizen_img')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @if(isset($member) && $member->citizen_img)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Foto KTP Saat Ini:</label>
                                    <div>
                                        <img src="{{ asset($member->citizen_img) }}" alt="KTP" style="max-width: 200px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                            @endif

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="family_card_number" class="form-label">Nomor Kartu Keluarga <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('family_card_number') is-invalid @enderror" 
                                               id="family_card_number" 
                                               name="family_card_number" 
                                               value="{{ old('family_card_number', $member->family_card_number ?? '') }}"
                                               placeholder="Masukkan nomor kartu keluarga"
                                               required>
                                        @error('family_card_number')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="family_card_img" class="form-label">Foto Kartu Keluarga</label>
                                        <input type="file" 
                                               class="form-control @error('family_card_img') is-invalid @enderror" 
                                               id="family_card_img" 
                                               name="family_card_img"
                                               accept="image/*">
                                        <small class="text-muted">Format: JPG, PNG, GIF (Max: 2MB)</small>
                                        @error('family_card_img')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @if(isset($member) && $member->family_card_img)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Foto Kartu Keluarga Saat Ini:</label>
                                    <div>
                                        <img src="{{ asset($member->family_card_img) }}" alt="Kartu Keluarga" style="max-width: 200px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                            @endif

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="bpjs_number" class="form-label">Nomor BPJS Kesehatan <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('bpjs_number') is-invalid @enderror" 
                                               id="bpjs_number" 
                                               name="bpjs_number" 
                                               value="{{ old('bpjs_number', $member->bpjs_number ?? '') }}"
                                               placeholder="Masukkan nomor BPJS kesehatan"
                                               required>
                                        @error('bpjs_number')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="bpjs_img" class="form-label">Foto Kartu BPJS</label>
                                        <input type="file" 
                                               class="form-control @error('bpjs_img') is-invalid @enderror" 
                                               id="bpjs_img" 
                                               name="bpjs_img"
                                               accept="image/*">
                                        <small class="text-muted">Format: JPG, PNG, GIF (Max: 2MB)</small>
                                        @error('bpjs_img')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            @if(isset($member) && $member->bpjs_img)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Foto Kartu BPJS Saat Ini:</label>
                                    <div>
                                        <img src="{{ asset($member->bpjs_img) }}" alt="BPJS" style="max-width: 200px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save"></i> {{ isset($member) ? 'Perbarui' : 'Simpan' }}
                        </button>
                        <a href="{{ isset($member) ? route('member.show', $member->id) : route('member.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Exam History Section (Only for Edit) -->
        @if(isset($member))
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
                        <form action="{{ route('member.exam.store', $member->id) }}" method="POST">
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
                        <i class="fas fa-info-circle"></i> Belum ada riwayat ujian untuk pesilat ini.
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
                                        <form action="{{ route('member.exam.destroy', [$member->id, $record->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat ujian ini?');">
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

        <!-- Training Centers Management Section -->
        @if(isset($member))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-dumbbell"></i> Manajemen Training Center
                </h3>
            </div>
            <div class="card-body">
                <!-- Add Training Center Form -->
                <div class="card card-primary card-outline mb-3">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-plus"></i> Tambah Training Center</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('member.training-center.attach', $member->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="training_center_id" class="form-label">Pilih Training Center <span class="text-danger">*</span></label>
                                        <select class="form-control @error('training_center_id') is-invalid @enderror" 
                                                id="training_center_id" 
                                                name="training_center_id" 
                                                required>
                                            <option value="">-- Pilih Training Center --</option>
                                            @foreach($available_training_centers as $center)
                                                <option value="{{ $center->id }}">{{ $center->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('training_center_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="training_center_joined_date" class="form-label">Tanggal Bergabung <span class="text-danger">*</span></label>
                                        <input type="date" 
                                               class="form-control @error('joined_date') is-invalid @enderror" 
                                               id="training_center_joined_date" 
                                               name="joined_date" 
                                               value="{{ old('joined_date') }}"
                                               required>
                                        @error('joined_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Tambah
                            </button>
                        </form>
                    </div>
                </div>
                @if($member->trainingCenters->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Pesilat belum terdaftar di training center manapun.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Nama Training Center</th>
                                    <th class="text-center">Hari Training</th>
                                    <th class="text-center">Jam Training</th>
                                    <th class="text-center">Tanggal Bergabung</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @foreach($member->trainingCenters as $center)
                                <tr>
                                    <td class="text-center">{{ $i++ }}</td>
                                    <td>
                                        <a href="{{ route('training-center.show', $center->id) }}" target="_blank">
                                            {{ $center->name }}
                                        </a>
                                    </td>
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
                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($center->pivot->joined_date)->format('d-m-Y') }}
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('member.training-center.detach', [$member->id, $center->id]) }}" 
                                              method="POST" 
                                              style="display: inline;" 
                                              onsubmit="return confirm('Hapus pesilat dari training center ini?');">
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

@section('script')
<script>
    // Only show member ID generation info for new members
    const isEditMode = {{ isset($member) ? 'true' : 'false' }};
    if (!isEditMode) {
        document.getElementById('joined_date').addEventListener('change', function() {
            const joinedDate = this.value;
            if (joinedDate) {
                // Show info that member_id will be auto-generated on submit
                const memberIdInput = document.getElementById('member_id');
                memberIdInput.placeholder = 'Akan di-generate saat disimpan (YYMMSEQ)';
            }
        });
    }
</script>
@endsection

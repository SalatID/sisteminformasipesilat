@extends('layout.index_admin')
@section("title", isset($center) ? "Edit Training Center" : "Tambah Training Center")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('training-center.index') }}">Training Center</a></li>
    <li class="breadcrumb-item active">{{ isset($center) ? "Edit" : "Tambah" }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ isset($center) ? "Edit Training Center" : "Tambah Training Center Baru" }}</h3>
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

                <form action="{{ isset($center) ? route('training-center.update', $center->id) : route('training-center.store') }}" method="POST">
                    @csrf
                    @if(isset($center))
                        @method('PUT')
                    @endif

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Nama Training Center <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $center->name ?? '') }}"
                               placeholder="Masukkan nama training center"
                               required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" 
                                  name="address" 
                                  rows="3"
                                  placeholder="Masukkan alamat training center">{{ old('address', $center->address ?? '') }}</textarea>
                        @error('address')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="contact_person" class="form-label">Kontak Person</label>
                                <input type="text" 
                                       class="form-control @error('contact_person') is-invalid @enderror" 
                                       id="contact_person" 
                                       name="contact_person" 
                                       value="{{ old('contact_person', $center->contact_person ?? '') }}"
                                       placeholder="Nama kontak person">
                                @error('contact_person')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone" class="form-label">Telepon</label>
                                <input type="text" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $center->phone ?? '') }}"
                                       placeholder="Nomor telepon">
                                @error('phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $center->email ?? '') }}"
                               placeholder="Alamat email">
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Hari Training <span class="text-danger">*</span> (2 hari per minggu)</label>
                        <div class="row">
                            @php
                                $days = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                                $selectedDays = old('training_days', $center->training_days ?? []);
                            @endphp
                            @foreach($days as $dayEn => $dayId)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input training-day-check" 
                                               type="checkbox" 
                                               id="day_{{ $dayEn }}" 
                                               name="training_days[]" 
                                               value="{{ $dayEn }}"
                                               {{ in_array($dayEn, $selectedDays) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $dayEn }}">
                                            {{ $dayId }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('training_days')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-2">Maksimal pilih 2 hari</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="training_time" class="form-label">Jam Training <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('training_time') is-invalid @enderror" 
                               id="training_time" 
                               name="training_time" 
                               value="{{ old('training_time', $center->training_time ?? '') }}"
                               placeholder="Contoh: 10:00-12:00 atau 14:00-16:00"
                               required>
                        @error('training_time')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Format: HH:MM-HH:MM (Contoh: 10:00-12:00)</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save"></i> {{ isset($center) ? 'Perbarui' : 'Simpan' }}
                        </button>
                        <a href="{{ route('training-center.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.training-day-check');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checked = document.querySelectorAll('.training-day-check:checked');
            if (checked.length > 2) {
                this.checked = false;
                alert('Maksimal hanya 2 hari training yang dapat dipilih!');
            }
        });
    });
});
</script>
@endsection

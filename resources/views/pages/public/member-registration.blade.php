<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Pesilat - Sistem Informasi Pesilat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .registration-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
        }

        .registration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .registration-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .registration-header p {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
        }

        .registration-body {
            padding: 40px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .invalid-feedback {
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .row > .col-md-6 {
            margin-bottom: 15px;
        }

        .registration-footer {
            text-align: center;
            padding: 20px 40px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .registration-footer p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .registration-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .registration-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="p-4">
    <div class="registration-container">
        <div class="registration-header">
            <h1>Pendaftaran Pesilat</h1>
            <p>PPS Satria Muda Indonesia Kowmil Jakarta Barat</p>
        </div>

        <div class="registration-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Validasi Gagal!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> <strong>Catatan:</strong> Data Anda akan diverifikasi oleh admin sebelum disetujui.
            </div>

            <form action="{{ route('member.registration.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Nama -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           placeholder="Masukkan nama lengkap Anda"
                           required>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- TS dan Tanggal Bergabung -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="ts_id" class="form-label">Tingkat Sabuk (TS) <span class="text-danger">*</span></label>
                            <select class="form-select @error('ts_id') is-invalid @enderror" 
                                    id="ts_id" 
                                    name="ts_id" 
                                    required>
                                <option value="">-- Pilih Tingkat Sabuk --</option>
                                @foreach($ts_list as $ts)
                                    <option value="{{ $ts->id }}" {{ old('ts_id') == $ts->id ? 'selected' : '' }}>
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
                                   value="{{ old('joined_date') }}"
                                   required>
                            @error('joined_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Unit dan Jenis Kelamin -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="unit_id" class="form-label">Unit</label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" 
                                    id="unit_id" 
                                    name="unit_id">
                                <option value="">-- Pilih Unit --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
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
                            <select class="form-select @error('gender') is-invalid @enderror" 
                                    id="gender" 
                                    name="gender">
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('gender')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Tingkat Sekolah -->
                <div class="form-group mb-3">
                    <label for="school_level" class="form-label">Tingkat Pendidikan</label>
                    <select class="form-select @error('school_level') is-invalid @enderror" 
                            id="school_level" 
                            name="school_level">
                        <option value="">-- Pilih Tingkat Pendidikan --</option>
                        <option value="SD" {{ old('school_level') == 'SD' ? 'selected' : '' }}>SD</option>
                        <option value="SMP" {{ old('school_level') == 'SMP' ? 'selected' : '' }}>SMP</option>
                        <option value="SMA/K" {{ old('school_level') == 'SMA/K' ? 'selected' : '' }}>SMA/K</option>
                        <option value="Kuliah" {{ old('school_level') == 'Kuliah' ? 'selected' : '' }}>Kuliah</option>
                        <option value="Bekerja" {{ old('school_level') == 'Bekerja' ? 'selected' : '' }}>Bekerja</option>
                    </select>
                    @error('school_level')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Foto -->
                <div class="form-group mb-3">
                    <label for="picture" class="form-label">Foto Profil</label>
                    <input type="file" 
                           class="form-control @error('picture') is-invalid @enderror" 
                           id="picture" 
                           name="picture"
                           accept="image/*">
                    <small class="form-text">Format: JPG, PNG, GIF (Max: 2MB)</small>
                    @error('picture')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Identification Documents -->
                <hr class="my-4">
                <h5 class="mb-3"><i class="fas fa-passport"></i> Data Identitas</h5>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-lightbulb"></i> <strong>Catatan:</strong> Data KTP, Kartu Keluarga, dan Kartu BPJS akan digunakan untuk mempermudah proses pendaftaran ketika mengikuti kejuaraan.
                </div>

                <!-- KTP -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="citizen_number" class="form-label">Nomor KTP <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('citizen_number') is-invalid @enderror" 
                                   id="citizen_number" 
                                   name="citizen_number" 
                                   value="{{ old('citizen_number') }}"
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
                            <small class="form-text">Format: JPG, PNG, GIF (Max: 2MB)</small>
                            @error('citizen_img')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Kartu Keluarga -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="family_card_number" class="form-label">Nomor Kartu Keluarga <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('family_card_number') is-invalid @enderror" 
                                   id="family_card_number" 
                                   name="family_card_number" 
                                   value="{{ old('family_card_number') }}"
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
                            <small class="form-text">Format: JPG, PNG, GIF (Max: 2MB)</small>
                            @error('family_card_img')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- BPJS -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="bpjs_number" class="form-label">Nomor BPJS Kesehatan <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('bpjs_number') is-invalid @enderror" 
                                   id="bpjs_number" 
                                   name="bpjs_number" 
                                   value="{{ old('bpjs_number') }}"
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
                            <small class="form-text">Format: JPG, PNG, GIF (Max: 2MB)</small>
                            @error('bpjs_img')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit">
                    Daftar
                </button>
            </form>
        </div>

        {{-- <div class="registration-footer">
            <p>Sudah memiliki akun? <a href="{{ route('login') }}">Login di sini</a></p>
        </div> --}}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

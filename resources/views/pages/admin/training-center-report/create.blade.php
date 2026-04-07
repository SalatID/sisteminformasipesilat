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

                <form action="{{ route('training-center-report.store') }}" method="POST" id="reportForm">
                    @csrf

                    <!-- Selection Section -->
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
                                </div>

                                <!-- Training Date -->
                                <div class="col-md-4">
                                    <div class="form-group">
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
                                            <option value="offline" {{ old('training_type') == 'offline' ? 'selected' : '' }}>Offline (Tatap Muka)</option>
                                            <option value="online" {{ old('training_type') == 'online' ? 'selected' : '' }}>Online (Virtual)</option>
                                        </select>
                                        @error('training_type')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="button" id="loadMembersBtn" class="btn btn-primary" disabled>
                                    <i class="fas fa-sync-alt"></i> <span id="loadBtnText">Muat Data Anggota</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center mb-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data anggota...</p>
                    </div>

                    <!-- Training Center Info (shown after loading) -->
                    <div id="trainingCenterInfo" style="display: none;">
                        <div class="row mb-4 g-3">
                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-dumbbell"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Pusat Pelatihan</span>
                                        <span class="info-box-number d-block text-truncate" id="tcName"></span>
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
                                        <span class="info-box-number" id="tcDate"></span>
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
                                        <span class="info-box-number" id="memberCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Members Performance Table (dynamically populated) -->
                    <div id="membersTableContainer" style="display: none;">
                        <div class="card card-primary mb-4">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-edit"></i> Input Performa Anggota
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="noMembersAlert" class="alert alert-warning" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i> Tidak ada anggota yang terdaftar di Pusat Pelatihan ini.
                                </div>

                                <div class="table-responsive" id="membersTableWrapper">
                                    <table class="table table-bordered table-striped table-hover table-sm" id="performanceTable">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th class="text-center" style="min-width: 30px;">#</th>
                                                <th style="min-width: 180px;">Nama Pesilat</th>
                                                <th class="text-center" style="min-width: 90px;">
                                                    <i class="fas fa-check-circle"></i> Absensi
                                                </th>
                                                <th class="text-center" style="min-width: 90px;">
                                                    <i class="fas fa-money-bill"></i> Kas
                                                </th>
                                                <th class="text-center d-none d-md-table-cell" style="min-width: 90px;">
                                                    <i class="fas fa-dumbbell"></i> Endurance
                                                </th>
                                                <th class="text-center d-none d-md-table-cell" style="min-width: 90px;">
                                                    <i class="fas fa-fist-raised"></i> Strength
                                                </th>
                                                <th class="text-center" style="min-width: 90px;">
                                                    <i class="fas fa-karate"></i> Technique
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="membersTableBody">
                                            <!-- Rows will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 d-flex flex-column flex-md-row gap-2">
                            <button type="submit" class="btn btn-success btn-lg flex-grow-1 flex-md-grow-0" style="min-width: 200px;">
                                <i class="fas fa-save"></i> <span class="d-none d-sm-inline">Simpan Laporan</span><span class="d-sm-none">Simpan</span>
                            </button>
                            <a href="{{ route('training-center-report.index') }}" class="btn btn-secondary btn-lg flex-grow-1 flex-md-grow-0" style="min-width: 120px;">
                                <i class="fas fa-times"></i> <span class="d-sm-inline">Batal</span>
                            </a>
                        </div>
                    </div>
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

    .gap-2 {
        gap: 0.5rem;
    }
</style>

<script>
    // Enable/disable load button based on form completion
    const trainingCenterId = document.getElementById('training_center_id');
    const trainingDate = document.getElementById('training_date');
    const trainingType = document.getElementById('training_type');
    const loadMembersBtn = document.getElementById('loadMembersBtn');

    function checkFormCompletion() {
        if (trainingCenterId.value && trainingDate.value && trainingType.value) {
            loadMembersBtn.disabled = false;
        } else {
            loadMembersBtn.disabled = true;
        }
    }

    trainingCenterId.addEventListener('change', checkFormCompletion);
    trainingDate.addEventListener('change', checkFormCompletion);
    trainingType.addEventListener('change', checkFormCompletion);

    // Load members via AJAX
    loadMembersBtn.addEventListener('click', function() {
        const centerId = trainingCenterId.value;
        const date = trainingDate.value;
        const type = trainingType.value;

        // Show loading indicator
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('trainingCenterInfo').style.display = 'none';
        document.getElementById('membersTableContainer').style.display = 'none';

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]').value;

        // Make AJAX request
        fetch('{{ route("training-center-report.get-members") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                training_center_id: centerId,
                training_date: date
            })
        })
        .then(response => response.json())
        .then(data => {
            // Hide loading indicator
            document.getElementById('loadingIndicator').style.display = 'none';

            if (data.success) {
                // Update training center info
                document.getElementById('tcName').textContent = data.training_center.name;
                document.getElementById('tcDate').textContent = formatDate(date);
                document.getElementById('memberCount').textContent = data.members.length;
                document.getElementById('trainingCenterInfo').style.display = 'block';

                // Check if there are members
                if (data.members.length === 0) {
                    document.getElementById('noMembersAlert').style.display = 'block';
                    document.getElementById('membersTableWrapper').style.display = 'none';
                } else {
                    document.getElementById('noMembersAlert').style.display = 'none';
                    document.getElementById('membersTableWrapper').style.display = 'block';
                    
                    // Populate members table
                    populateMembersTable(data.members, data.existing_records);
                }

                // Show members table container
                document.getElementById('membersTableContainer').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loadingIndicator').style.display = 'none';
            alert('Terjadi kesalahan saat memuat data anggota. Silakan coba lagi.');
        });
    });

    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }

    function populateMembersTable(members, existingRecords) {
        const tbody = document.getElementById('membersTableBody');
        tbody.innerHTML = ''; // Clear existing rows

        members.forEach((member, index) => {
            const record = existingRecords[member.id] || null;
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>
                    <strong class="d-block">${escapeHtml(member.name)}</strong>
                    <small class="text-muted">${escapeHtml(member.member_id)}</small>
                </td>
                <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input attended-checkbox" 
                               type="checkbox" 
                               name="members[${index}][attended]" 
                               value="1"
                               id="attended_${member.id}"
                               ${record && record.attended ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center">
                    <div class="form-check form-check-inline m-0">
                        <input class="form-check-input kas-checkbox" 
                               type="checkbox" 
                               name="members[${index}][kas]" 
                               value="1"
                               id="kas_${member.id}"
                               ${record && record.kas ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center d-none d-md-table-cell">
                    <input type="number" 
                           name="members[${index}][endurance]" 
                           class="form-control form-control-sm text-center" 
                           min="0" 
                           max="100"
                           placeholder="0-100"
                           value="${record && record.endurance ? record.endurance : ''}">
                </td>
                <td class="text-center d-none d-md-table-cell">
                    <input type="number" 
                           name="members[${index}][strength]" 
                           class="form-control form-control-sm text-center" 
                           min="0" 
                           max="100"
                           placeholder="0-100"
                           value="${record && record.strength ? record.strength : ''}">
                </td>
                <td class="text-center">
                    <input type="number" 
                           name="members[${index}][technique]" 
                           class="form-control form-control-sm text-center" 
                           min="0" 
                           max="100"
                           placeholder="0-100"
                           value="${record && record.technique ? record.technique : ''}">
                </td>
                <input type="hidden" name="members[${index}][member_id]" value="${member.id}">
            `;
            
            tbody.appendChild(row);
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>
@endsection

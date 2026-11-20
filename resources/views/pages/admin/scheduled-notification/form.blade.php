@extends('layout.index_admin')
@section("title", isset($schedule->id) ? 'Edit Jadwal Notifikasi' : 'Tambah Jadwal Notifikasi')
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('scheduled-notification.index') }}">Jadwal Notifikasi</a></li>
    <li class="breadcrumb-item active">{{ isset($schedule->id) ? 'Edit' : 'Tambah' }}</li>
</ol>
@endsection
@section('content')
@php
    $editing = isset($schedule->id);
    $frequency = old('frequency', $schedule->frequency ?? 'weekly');
    $monthlyMode = old('monthly_mode', $schedule->monthly_mode ?? 'date');
    $selectedDays = array_map('intval', (array) old('days_of_week', $schedule->days_of_week ?? []));
    $selectedDates = array_map('intval', (array) old('dates_of_month', $schedule->dates_of_month ?? []));
    $selectedWeeks = array_map('intval', (array) old('weeks_of_month', $schedule->weeks_of_month ?? []));
    $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $startDate = old('start_date', isset($schedule->start_date) ? \Illuminate\Support\Carbon::parse($schedule->start_date)->format('Y-m-d') : now()->toDateString());
    $endDate = old('end_date', isset($schedule->end_date) && $schedule->end_date ? \Illuminate\Support\Carbon::parse($schedule->end_date)->format('Y-m-d') : '');
    $targetRows = old('targets', $targets ?? []);
    if ($targetRows === []) {
        $targetRows = [['chat_id' => '', 'label' => '']];
    }
@endphp
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $editing ? 'Edit Jadwal: '.$schedule->name.' ('.$schedule->code.')' : 'Jadwal Notifikasi Baru' }}</h3>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger mx-3 mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST"
                  action="{{ $editing ? route('scheduled-notification.update', $schedule->id) : route('scheduled-notification.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Nama Jadwal <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="120"
                                   value="{{ old('name', $schedule->name ?? '') }}"
                                   placeholder="Contoh: TC GOR Gondrong">
                            <small class="text-muted">Kode jadwal dibuat otomatis dari nama dan tidak berubah saat edit.</small>
                        </div>
                    </div>

                    <h5 class="mt-2">Chat Tujuan <span class="text-danger">*</span></h5>
                    <p class="text-muted mb-2">
                        Satu pesan yang sama akan dikirim ke <strong>semua</strong> chat di bawah ini.
                        Tambahkan baris bila perlu mengirim ke beberapa grup / nomor.
                    </p>
                    @error('targets')
                        <div class="text-danger mb-2">{{ $message }}</div>
                    @enderror
                    <div id="target-rows">
                        @foreach ($targetRows as $index => $row)
                            <div class="row target-row align-items-start">
                                <div class="col-md-5 form-group">
                                    <input type="text" name="targets[{{ $index }}][chat_id]"
                                           class="form-control @error("targets.{$index}.chat_id") is-invalid @enderror"
                                           value="{{ $row['chat_id'] ?? '' }}" maxlength="100"
                                           placeholder="1203634...@g.us">
                                    @error("targets.{$index}.chat_id")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5 form-group">
                                    <input type="text" name="targets[{{ $index }}][label]"
                                           class="form-control" value="{{ $row['label'] ?? '' }}" maxlength="120"
                                           placeholder="Label, mis. Grup GOR Gondrong">
                                </div>
                                <div class="col-md-2 form-group">
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-target"
                                            title="Hapus baris">&times;</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-target">
                        + Tambah Chat
                    </button>
                    <small class="text-muted d-block mb-3">
                        Akhiri dengan <code>@g.us</code> (grup), <code>@c.us</code> (pribadi), atau <code>@s.whatsapp.net</code>.
                    </small>

                    <hr>
                    <h5>Pola Jadwal</h5>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Pengulangan <span class="text-danger">*</span></label>
                            <select name="frequency" id="frequency" class="form-control" required>
                                @foreach (['once' => 'Sekali', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $value => $label)
                                    <option value="{{ $value }}" @selected($frequency === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Jam Kirim <span class="text-danger">*</span></label>
                            <input type="time" name="time" class="form-control" required
                                   value="{{ old('time', substr((string) ($schedule->time ?? '15:30'), 0, 5)) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                            <small class="text-muted">Kosongkan bila tanpa batas.</small>
                        </div>
                    </div>

                    <div class="form-group" id="days-group">
                        <label>Hari (untuk mingguan)</label><br>
                        @foreach ($dayNames as $value => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]"
                                       id="day-{{ $value }}" value="{{ $value }}" @checked(in_array($value, $selectedDays, true))>
                                <label class="form-check-label" for="day-{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="row" id="monthly-group">
                        <div class="col-md-4 form-group">
                            <label>Mode Bulanan</label>
                            <select name="monthly_mode" id="monthly_mode" class="form-control">
                                <option value="date" @selected($monthlyMode === 'date')>Tanggal tertentu (mis. tiap tgl 1 &amp; 15)</option>
                                <option value="week" @selected($monthlyMode === 'week')>Minggu ke- (mis. Selasa minggu ke-1)</option>
                            </select>
                        </div>
                        <div class="col-md-8 form-group" id="dates-group">
                            <label>Tanggal (1&ndash;31)</label>
                            <input type="text" name="dates_of_month" id="dates_of_month" class="form-control"
                                   value="{{ implode(',', $selectedDates) }}" placeholder="1,15">
                            <small class="text-muted">Pisahkan dengan koma. Tanggal yang tidak ada (mis. 31 di bulan pendek) dilewati.</small>
                        </div>
                        <div class="col-md-8 form-group" id="weeks-group">
                            <label>Minggu ke- (1&ndash;5, 5 = minggu terakhir)</label>
                            <input type="text" name="weeks_of_month" id="weeks_of_month" class="form-control"
                                   value="{{ implode(',', $selectedWeeks) }}" placeholder="1,3">
                            <small class="text-muted">Kombinasikan dengan hari di atas, mis. Selasa minggu ke-1.</small>
                        </div>
                    </div>

                    <hr>
                    <h5>Isi Pesan</h5>
                    <p class="text-muted small mb-2">
                        Variabel tersedia:
                        <code>{nama_jadwal}</code> <code>{hari}</code> <code>{tanggal}</code> <code>{alasan}</code>
                        (<code>{alasan}</code> hanya terisi otomatis di pesan libur).
                    </p>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Pesan Latihan <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" class="form-control" rows="8" required
                                      maxlength="4000">{{ old('message', $schedule->message ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Pesan Libur</label>
                            <textarea name="holiday_message" id="holiday_message" class="form-control" rows="8"
                                      maxlength="4000"
                                      placeholder="Kosongkan untuk memakai template bawaan">{{ old('holiday_message', $schedule->holiday_message ?? '') }}</textarea>
                        </div>
                    </div>

                    <button type="button" id="preview-btn" class="btn btn-outline-info btn-sm mb-3">
                        <i class="fas fa-eye"></i> Pratinjau Pesan
                    </button>
                    <div class="row" id="preview-box" style="display:none">
                        <div class="col-md-6">
                            <label>Pratinjau Latihan</label>
                            <pre id="preview-training" class="bg-light p-3 border rounded" style="white-space:pre-wrap"></pre>
                        </div>
                        <div class="col-md-6">
                            <label>Pratinjau Libur</label>
                            <pre id="preview-holiday" class="bg-light p-3 border rounded" style="white-space:pre-wrap"></pre>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                               @checked(old('is_active', $schedule->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Jadwal aktif</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ $editing ? 'Perbarui' : 'Simpan' }}</button>
                    <a href="{{ route('scheduled-notification.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Baris chat tujuan dinamis.
    const targetRows = document.getElementById('target-rows');
    const addTarget = document.getElementById('add-target');

    function reindexTargets() {
        Array.from(targetRows.querySelectorAll('.target-row')).forEach(function (row, index) {
            row.querySelectorAll('input').forEach(function (input) {
                const field = input.name.replace(/^targets\[\d+\]/, '');
                input.name = 'targets[' + index + ']' + field;
            });
        });
    }

    if (addTarget) {
        addTarget.addEventListener('click', function () {
            const template = targetRows.querySelector('.target-row');
            const clone = template.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            clone.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            clone.querySelectorAll('.invalid-feedback').forEach(function (el) { el.remove(); });
            targetRows.appendChild(clone);
            reindexTargets();
            clone.querySelector('input').focus();
        });
    }

    targetRows.addEventListener('click', function (event) {
        const button = event.target.closest('.btn-remove-target');
        if (!button) return;

        const rows = targetRows.querySelectorAll('.target-row');

        if (rows.length <= 1) {
            rows[0].querySelectorAll('input').forEach(function (input) { input.value = ''; });
            return;
        }

        button.closest('.target-row').remove();
        reindexTargets();
    });

    const frequency = document.getElementById('frequency');
    const monthlyMode = document.getElementById('monthly_mode');
    const daysGroup = document.getElementById('days-group');
    const monthlyGroup = document.getElementById('monthly-group');
    const datesGroup = document.getElementById('dates-group');
    const weeksGroup = document.getElementById('weeks-group');

    function refreshPattern() {
        const f = frequency.value;
        const mode = monthlyMode.value;

        monthlyGroup.style.display = f === 'monthly' ? '' : 'none';
        datesGroup.style.display = mode === 'date' ? '' : 'none';
        weeksGroup.style.display = mode === 'week' ? '' : 'none';
        daysGroup.style.display = (f === 'weekly' || (f === 'monthly' && mode === 'week')) ? '' : 'none';
    }

    frequency.addEventListener('change', refreshPattern);
    monthlyMode.addEventListener('change', refreshPattern);
    refreshPattern();

    const btn = document.getElementById('preview-btn');
    const box = document.getElementById('preview-box');

    btn.addEventListener('click', function () {
        const body = new URLSearchParams({
            message: document.getElementById('message').value,
            holiday_message: document.getElementById('holiday_message').value,
            name: document.querySelector('input[name="name"]').value,
            frequency: frequency.value,
            time: document.querySelector('input[name="time"]').value,
            start_date: document.querySelector('input[name="start_date"]').value,
        });

        fetch('{{ route('scheduled-notification.preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: body.toString(),
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('preview-training').textContent = data.training;
            document.getElementById('preview-holiday').textContent = data.holiday;
            box.style.display = '';
        })
        .catch(() => alert('Gagal memuat pratinjau.'));
    });
});
</script>
@endsection

@extends('layout.index_admin')
@section("title", "Detail Jadwal: ".$schedule->name)
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('scheduled-notification.index') }}">Jadwal Notifikasi</a></li>
    <li class="breadcrumb-item active">{{ $schedule->name }}</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $schedule->name }}</h3>
                <div class="card-tools">
                    <span class="badge bg-{{ $schedule->is_active ? 'success' : 'secondary' }}">
                        {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kode</dt><dd class="col-sm-8"><code>{{ $schedule->code }}</code></dd>
                    <dt class="col-sm-4">Pola</dt><dd class="col-sm-8">{{ $schedule->scheduleSummary() }}</dd>
                    <dt class="col-sm-4">Jam</dt><dd class="col-sm-8">{{ $schedule->timeLabel() }} WIB</dd>
                    <dt class="col-sm-4">Mulai</dt><dd class="col-sm-8">{{ $schedule->start_date?->format('d/m/Y') ?? '-' }}</dd>
                    <dt class="col-sm-4">Selesai</dt><dd class="col-sm-8">{{ $schedule->end_date?->format('d/m/Y') ?? 'Tanpa batas' }}</dd>
                    <dt class="col-sm-4">Chat Tujuan ({{ count($schedule->targetList()) }})</dt>
                    <dd class="col-sm-8">
                        <ul class="list-unstyled mb-0">
                            @foreach ($schedule->targetList() as $target)
                                <li>
                                    {{ $target['label'] !== '' ? $target['label'] : '-' }}
                                    @if ($target['label'] !== '')
                                        <small class="text-muted">{{ $target['chat_id'] }}</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="{{ route('scheduled-notification.edit', $schedule->id) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('scheduled-notification.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <form action="{{ route('scheduled-notification.toggle', $schedule->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-{{ $schedule->is_active ? 'secondary' : 'success' }} btn-sm">
                        <i class="fas fa-{{ $schedule->is_active ? 'pause' : 'play' }}"></i>
                        {{ $schedule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <form action="{{ route('scheduled-notification.test', $schedule->id) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Kirim pesan percobaan ke {{ count($schedule->chatIds()) }} chat tujuan?');">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-paper-plane"></i> Kirim Tes</button>
                </form>
                <form action="{{ route('scheduled-notification.replan', $schedule->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-sync"></i> Susun Ulang</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Pratinjau Pesan</h3></div>
            <div class="card-body">
                <h6>Latihan</h6>
                <pre class="bg-light p-2 border rounded" style="white-space:pre-wrap">{{ $previewTraining }}</pre>
                <h6 class="mt-3">Libur</h6>
                <pre class="bg-light p-2 border rounded" style="white-space:pre-wrap">{{ $previewHoliday }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kemunculan Mendatang</h3></div>
            <div class="card-body">
                @if($upcoming->isEmpty())
                    <div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> Belum ada kemunculan mendatang.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Kode</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcoming as $run)
                                    @include('pages.admin.scheduled-notification.partials.run-row', ['run' => $run, 'schedule' => $schedule, 'showCancel' => true])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Riwayat</h3></div>
            <div class="card-body">
                @if($history->isEmpty())
                    <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle"></i> Belum ada riwayat.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Kode</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $run)
                                    @include('pages.admin.scheduled-notification.partials.run-row', ['run' => $run, 'schedule' => $schedule, 'showCancel' => true])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="POST" id="cancelForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Batalkan Kemunculan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Kemunculan: <strong id="cancelCode">-</strong></p>
                    <div class="form-group mb-0">
                        <label>Alasan <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="cancelReason" class="form-control" required minlength="3" maxlength="255"
                               placeholder="Contoh: hujan deras">
                        <input type="hidden" name="run_id" id="cancelRunId">
                        <small class="text-muted">Alasan ikut dikirim pada pengumuman libur.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Batalkan &amp; Umumkan Libur</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@section('style')
<style>
    .modal-backdrop { z-index: 1040; }
    #cancelModal { z-index: 1050; }
</style>
@endsection
@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('cancelModal');
    const form = document.getElementById('cancelForm');
    const code = document.getElementById('cancelCode');
    const runId = document.getElementById('cancelRunId');
    const reason = document.getElementById('cancelReason');

    document.querySelectorAll('.js-cancel-run').forEach(function (button) {
        button.addEventListener('click', function () {
            code.textContent = button.dataset.code;
            runId.value = button.dataset.run;
            reason.value = '';
            form.action = button.dataset.action;

            if (window.jQuery) {
                window.jQuery(modal).modal('show');
            } else {
                modal.style.display = 'block';
                modal.classList.add('show');
            }
        });
    });
});
</script>
@endsection

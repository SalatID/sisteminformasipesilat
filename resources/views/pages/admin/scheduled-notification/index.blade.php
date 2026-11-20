@extends('layout.index_admin')
@section("title","Jadwal Notifikasi")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active">Jadwal Notifikasi WhatsApp</li>
</ol>
@endsection
@section('content')
<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center my-3">
        <form method="GET" action="{{ route('scheduled-notification.index') }}" class="form-inline">
            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm mr-2"
                   placeholder="Cari nama / kode / chat">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
        </form>
        <a href="{{ route('scheduled-notification.create') }}" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Tambah Jadwal
        </a>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Jadwal Notifikasi</h3>
            </div>
            <div class="card-body">
                @if($schedules->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Belum ada jadwal notifikasi.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Nama</th>
                                    <th class="text-center">Kode</th>
                                    <th>Pola</th>
                                    <th class="text-center">Jam</th>
                                    <th>Target Chat</th>
                                    <th class="text-center">Menunggu</th>
                                    <th class="text-center">Terkirim</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($schedules->currentPage() - 1) * $schedules->perPage() + 1; @endphp
                                @foreach ($schedules as $schedule)
                                <tr>
                                    <td class="text-center">{{ $i++ }}</td>
                                    <td>{{ $schedule->name }}</td>
                                    <td class="text-center"><code>{{ $schedule->code }}</code></td>
                                    <td>{{ $schedule->scheduleSummary() }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $schedule->timeLabel() }}</span></td>
                                    <td>
                                        @foreach ($schedule->targetList() as $target)
                                            <div>{{ $target['label'] !== '' ? $target['label'] : $target['chat_id'] }}</div>
                                            @if ($target['label'] !== '')
                                                <small class="text-muted">{{ $target['chat_id'] }}</small>
                                            @endif
                                        @endforeach
                                        @if (count($schedule->targetList()) > 1)
                                            <span class="badge bg-info">{{ count($schedule->targetList()) }} chat</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary">{{ $schedule->pending_runs_count }}</span></td>
                                    <td class="text-center"><span class="badge bg-success">{{ $schedule->sent_runs_count }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $schedule->is_active ? 'success' : 'secondary' }}">
                                            {{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('scheduled-notification.show', $schedule->id) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('scheduled-notification.edit', $schedule->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('scheduled-notification.toggle', $schedule->id) }}" method="POST" style="display:inline">
                                                @csrf
                                                <button type="submit" class="btn btn-{{ $schedule->is_active ? 'secondary' : 'success' }} btn-sm"
                                                        title="{{ $schedule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="fas fa-{{ $schedule->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('scheduled-notification.destroy', $schedule->id) }}" method="POST" style="display:inline"
                                                  onsubmit="return confirm('Hapus jadwal ini? Kemunculan yang masih menunggu akan dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        {{ $schedules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

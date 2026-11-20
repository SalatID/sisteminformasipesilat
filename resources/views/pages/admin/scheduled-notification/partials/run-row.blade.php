@php
    $today = \Illuminate\Support\Carbon::now(config('notifications.schedules.timezone', config('app.timezone')))->startOfDay();
    $isToday = $run->occurrence_at->copy()->startOfDay()->equalTo($today);
@endphp
<tr>
    <td>
        <div>{{ \App\Services\Notification\Schedule\OccurrenceCalculator::dayName($run->occurrence_at) }}</div>
        <small class="text-muted">{{ \App\Services\Notification\Schedule\OccurrenceCalculator::dateLabel($run->occurrence_at) }}</small>
    </td>
    <td><code>{{ $run->public_code }}</code></td>
    <td class="text-center">
        <span class="badge bg-{{ $run->statusBadge() }}">{{ $run->statusLabel() }}</span>
        @if($run->status === \App\Models\ScheduledNotificationRun::STATUS_CANCELLED)
            <div><small class="text-muted">{{ $run->cancel_reason }}</small></div>
            @if($run->announced_at === null)
                <div><small class="text-danger">Pengumuman gagal</small></div>
            @endif
        @endif
        @if($run->status === \App\Models\ScheduledNotificationRun::STATUS_FAILED && $run->last_error)
            <div><small class="text-danger">{{ \Illuminate\Support\Str::limit($run->last_error, 60) }}</small></div>
        @endif
        @if($run->attempts > 0 && in_array($run->status, [\App\Models\ScheduledNotificationRun::STATUS_FAILED, \App\Models\ScheduledNotificationRun::STATUS_SENT], true))
            <div><small class="text-muted">{{ $run->attempts }}x percobaan</small></div>
        @endif
    </td>
    <td class="text-center">
        @if($run->status === \App\Models\ScheduledNotificationRun::STATUS_PENDING)
            <button type="button" class="btn btn-danger btn-sm js-cancel-run"
                    title="Batalkan kemunculan ini"
                    data-code="{{ $run->public_code }}"
                    data-run="{{ $run->id }}"
                    data-action="{{ route('scheduled-notification.cancel-run', $schedule->id) }}">
                <i class="fas fa-ban"></i>
            </button>
        @elseif($run->status === \App\Models\ScheduledNotificationRun::STATUS_CANCELLED)
            @if($run->announced_at === null)
                <form action="{{ route('scheduled-notification.reannounce', [$schedule->id, $run->id]) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" title="Kirim ulang pengumuman libur">
                        <i class="fas fa-bullhorn"></i>
                    </button>
                </form>
            @else
                <span class="text-muted" title="Pengumuman libur sudah terkirim"><i class="fas fa-check-circle text-success"></i></span>
            @endif
        @elseif(in_array($run->status, [\App\Models\ScheduledNotificationRun::STATUS_FAILED, \App\Models\ScheduledNotificationRun::STATUS_MISSED], true))
            <form action="{{ route('scheduled-notification.retry', [$schedule->id, $run->id]) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Kirim ulang kemunculan ini sekarang?');">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" title="Kirim ulang">
                    <i class="fas fa-redo"></i>
                </button>
            </form>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
</tr>

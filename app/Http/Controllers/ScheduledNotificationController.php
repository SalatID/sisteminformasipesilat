<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledNotificationRequest;
use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use App\Services\Notification\Schedule\CancelOutcome;
use App\Services\Notification\Schedule\CancelScheduleService;
use App\Services\Notification\Schedule\HolidayAnnouncer;
use App\Services\Notification\Schedule\MessageTemplate;
use App\Services\Notification\Schedule\OccurrenceCalculator;
use App\Services\Notification\Schedule\SchedulePlanner;
use App\Services\Notification\Schedule\ScheduleRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CRUD jadwal notifikasi WhatsApp (Fase 3).
 *
 * Semua aksi memakai pola redirect + flash `error`/`message` yang sama dengan
 * controller lain di aplikasi ini.
 */
class ScheduledNotificationController extends Controller
{
    public function __construct(
        private readonly SchedulePlanner $planner,
        private readonly ScheduleRunner $runner,
        private readonly CancelScheduleService $cancelService,
        private readonly HolidayAnnouncer $announcer,
        private readonly MessageTemplate $template,
        private readonly OccurrenceCalculator $calculator,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $schedules = ScheduledNotification::query()
            ->withCount([
                'runs as pending_runs_count' => fn ($query) => $query->where('status', ScheduledNotificationRun::STATUS_PENDING),
                'runs as sent_runs_count' => fn ($query) => $query->where('status', ScheduledNotificationRun::STATUS_SENT),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('chat_label', 'like', '%'.$search.'%')
                        ->orWhere('chat_id', 'like', '%'.$search.'%')
                        ->orWhereHas('targets', function ($targets) use ($search) {
                            $targets->where('chat_id', 'like', '%'.$search.'%')
                                ->orWhere('label', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.scheduled-notification.index', compact('schedules', 'search', 'status'));
    }

    public function create(): View
    {
        return view('pages.admin.scheduled-notification.form', [
            'schedule' => new ScheduledNotification([
                'frequency' => 'weekly',
                'monthly_mode' => 'date',
                'time' => '15:30',
                'start_date' => now()->toDateString(),
                'is_active' => true,
            ]),
            'targets' => [['chat_id' => '', 'label' => '']],
        ]);
    }

    public function store(ScheduledNotificationRequest $request): RedirectResponse
    {
        $payload = $request->payload();
        $targets = $payload['targets'] ?? [];
        unset($payload['targets']);

        $payload['code'] = ScheduledNotification::generateCode($payload['name']);
        $payload['created_by'] = auth()->id();

        try {
            $schedule = DB::transaction(function () use ($payload, $targets) {
                $schedule = ScheduledNotification::create($payload);
                $schedule->syncTargets($targets);
                $schedule->save();

                return $schedule;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with([
                'error' => true,
                'message' => 'Gagal menyimpan jadwal: '.$e->getMessage(),
            ]);
        }

        $planned = $this->planner->plan($schedule);

        return redirect()->route('scheduled-notification.show', $schedule->id)->with([
            'error' => false,
            'message' => "Jadwal {$schedule->name} berhasil dibuat ({$schedule->code}) dengan {$planned} kemunculan terdekat.",
        ]);
    }

    public function show(string $id): View|RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $runs = $schedule->runs()
            ->orderBy('occurrence_at')
            ->get();

        $upcoming = $runs->filter(fn (ScheduledNotificationRun $run) => $run->occurrence_at->gte(now()->startOfDay()))
            ->take(10);

        $history = $runs->filter(fn (ScheduledNotificationRun $run) => $run->occurrence_at->lt(now()->startOfDay()))
            ->sortByDesc('occurrence_at')
            ->take(10);

        return view('pages.admin.scheduled-notification.show', [
            'schedule' => $schedule,
            'upcoming' => $upcoming,
            'history' => $history,
            'previewTraining' => $this->template->render(
                $this->template->trainingMessage($schedule),
                $this->template->sampleValues($schedule),
            ),
            'previewHoliday' => $this->template->render(
                $this->template->holidayMessage($schedule),
                $this->template->sampleValues($schedule),
            ),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        return view('pages.admin.scheduled-notification.form', [
            'schedule' => $schedule,
            'targets' => $schedule->targetList(),
        ]);
    }

    public function update(ScheduledNotificationRequest $request, string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        // Kode sengaja tidak ikut diubah (K9).
        $payload = $request->payload();
        $targets = $payload['targets'] ?? [];
        unset($payload['targets']);

        $schedule->fill($payload);

        try {
            DB::transaction(function () use ($schedule, $targets) {
                $schedule->save();
                $schedule->syncTargets($targets);
                $schedule->save();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with([
                'error' => true,
                'message' => 'Gagal memperbarui jadwal: '.$e->getMessage(),
            ]);
        }

        $result = $this->planner->refresh($schedule);

        return redirect()->route('scheduled-notification.show', $schedule->id)->with([
            'error' => false,
            'message' => "Jadwal diperbarui: {$result['deleted']} kemunculan lama dibersihkan, {$result['created']} kemunculan baru dibuat.",
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $cancelled = $this->planner->cancelPending($schedule, 'jadwal dihapus', 'system');
        $schedule->delete();

        return redirect()->route('scheduled-notification.index')->with([
            'error' => false,
            'message' => "Jadwal {$schedule->name} dihapus ({$cancelled} kemunculan menunggu dibatalkan).",
        ]);
    }

    /**
     * Aktif/nonaktifkan jadwal.
     */
    public function toggle(string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $schedule->is_active = ! $schedule->is_active;
        $schedule->save();

        if (! $schedule->is_active) {
            $cancelled = $this->planner->cancelPending($schedule, 'jadwal dinonaktifkan', 'system');

            return back()->with([
                'error' => false,
                'message' => "Jadwal dinonaktifkan ({$cancelled} kemunculan menunggu dibatalkan).",
            ]);
        }

        // planner->refresh membuang pending lama lalu membuat ulang; plan()
        // saja tidak cukup karena run pending sudah berstatus `cancelled`.
        $result = $this->planner->refresh($schedule);

        return back()->with([
            'error' => false,
            'message' => "Jadwal diaktifkan kembali ({$result['deleted']} kemunculan basi, {$result['created']} kemunculan baru dibuat).",
        ]);
    }

    /**
     * Pratinjau pesan untuk form (dipanggil via fetch, tanpa menyimpan).
     */
    public function preview(Request $request): JsonResponse
    {
        $message = (string) $request->input('message', '');
        $holiday = (string) $request->input('holiday_message', '');

        if (trim($holiday) === '') {
            $holiday = (string) config('notifications.schedules.default_holiday_message');
        }

        $schedule = new ScheduledNotification([
            'name' => (string) $request->input('name', 'TC GOR Gondrong'),
            'frequency' => (string) $request->input('frequency', 'weekly'),
            'time' => (string) $request->input('time', '15:30'),
            'start_date' => (string) $request->input('start_date', now()->toDateString()),
        ]);

        $values = $this->template->sampleValues($schedule);

        return response()->json([
            'training' => $this->template->render($message, $values),
            'holiday' => $this->template->render($holiday, $values),
            'sample' => $values,
            'unknown' => [
                'training' => $this->template->unknownVariables($message),
                'holiday' => $this->template->unknownVariables($holiday),
            ],
        ]);
    }

    /**
     * Kirim pesan percobaan ke chat jadwal (memakai mode test bila aktif).
     */
    public function test(string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $text = $this->template->render(
            $this->template->trainingMessage($schedule),
            $this->template->sampleValues($schedule),
        );

        $result = $this->announcer->sendTest($schedule, $text);

        $targets = $schedule->chatIds();
        $targetLabel = implode(', ', $targets);

        return back()->with([
            'error' => ! $result->success,
            'message' => $result->success
                ? count($targets).' pesan percobaan terkirim ke: '.$targetLabel.'.'
                : 'Pesan percobaan gagal dikirim ke '.$targetLabel.': '.($result->error ?? 'tanpa keterangan'),
        ]);
    }

    /**
     * Batalkan satu kemunculan lewat UI (pengumuman libur ikut dikirim).
     */
    public function cancelRun(Request $request, string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'run_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan minimal 3 karakter.',
        ]);

        $outcome = $this->cancelService->cancelById(
            $validated['run_id'],
            $validated['reason'],
            auth()->user()?->email ?? 'web',
        );

        if ($outcome->isCancelled()) {
            return back()->with([
                'error' => false,
                'message' => $outcome->announcementSent
                    ? 'Kemunculan dibatalkan dan pengumuman libur sudah dikirim.'
                    : 'Kemunculan dibatalkan, tetapi pengumuman libur gagal dikirim. Coba "Kirim ulang pengumuman".',
            ]);
        }

        return back()->with([
            'error' => true,
            'message' => $this->cancelErrorMessage($outcome),
        ]);
    }

    /**
     * Kirim ulang pengumuman libur untuk run yang dibatalkan.
     */
    public function reannounce(string $id, string $runId): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $run = $schedule->runs()->find($runId);

        if (! $run) {
            return back()->with(['error' => true, 'message' => 'Kemunculan tidak ditemukan pada jadwal ini.']);
        }

        if ($run->status !== ScheduledNotificationRun::STATUS_CANCELLED) {
            return back()->with(['error' => true, 'message' => 'Hanya kemunculan yang dibatalkan yang bisa diumumkan ulang.']);
        }

        $result = $this->announcer->announce($run, (string) $run->cancel_reason);

        return back()->with([
            'error' => ! $result->success,
            'message' => $result->success
                ? 'Pengumuman libur berhasil dikirim.'
                : 'Pengumuman libur gagal dikirim: '.($result->error ?? 'tanpa keterangan'),
        ]);
    }

    /**
     * Kirim ulang satu kemunculan yang gagal.
     */
    public function retryRun(string $id, string $runId): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $run = $schedule->runs()->find($runId);

        if (! $run) {
            return back()->with(['error' => true, 'message' => 'Kemunculan tidak ditemukan pada jadwal ini.']);
        }

        if (! in_array($run->status, [ScheduledNotificationRun::STATUS_FAILED, ScheduledNotificationRun::STATUS_MISSED], true)) {
            return back()->with(['error' => true, 'message' => 'Hanya kemunculan gagal/terlewat yang bisa dikirim ulang.']);
        }

        // Pengiriman ulang hanya ke target yang belum menerima pesan, supaya
        // chat yang sudah terkirim tidak menerima pesan dobel.
        $alreadySent = array_values(array_filter((array) $run->sent_targets));

        if ($alreadySent !== []) {
            $remaining = array_values(array_diff($schedule->chatIds(), $alreadySent));

            if ($remaining === []) {
                $run->forceFill([
                    'status' => ScheduledNotificationRun::STATUS_SENT,
                    'last_error' => null,
                    'next_attempt_at' => null,
                ])->save();

                return back()->with(['error' => false, 'message' => 'Semua chat target sudah menerima pesan ini.']);
            }

            $run->forceFill(['error_targets' => $remaining])->save();
        }

        // Run dari percobaan sebelumnya (sebagian sudah terkirim) tidak lagi
        // pending; sent_targets harus tetap tersimpan saat run direset.
        $carriedSentTargets = $run->sent_targets;

        $run->forceFill([
            'status' => ScheduledNotificationRun::STATUS_PENDING,
            'next_attempt_at' => now()->subMinute(),
            'attempts' => 0,
            'last_error' => null,
            'sent_targets' => $carriedSentTargets,
        ])->save();

        $report = $this->runner->run(['schedule_id' => $schedule->id, 'force' => true, 'limit' => 1]);

        return back()->with([
            'error' => $report['sent'] < 1,
            'message' => $report['sent'] >= 1
                ? 'Kemunculan berhasil dikirim ulang.'
                : 'Kirim ulang gagal. Periksa kolom error pada kemunculan tersebut.',
        ]);
    }

    /**
     * Susun ulang kemunculan mendatang (mis. setelah horizon planner lewat).
     */
    public function replan(string $id): RedirectResponse
    {
        $schedule = $this->find($id);

        if (! $schedule) {
            return $this->notFound();
        }

        $result = $this->planner->refresh($schedule);

        return back()->with([
            'error' => ! $schedule->is_active,
            'message' => $schedule->is_active
                ? "Kemunculan disusun ulang: {$result['created']} baru, {$result['deleted']} dibersihkan."
                : 'Jadwal sedang nonaktif; aktifkan dulu untuk menyusun kemunculan.',
        ]);
    }

    private function find(string $id): ?ScheduledNotification
    {
        return ScheduledNotification::query()->find($id);
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('scheduled-notification.index')->with([
            'error' => true,
            'message' => 'Jadwal notifikasi tidak ditemukan',
        ]);
    }

    private function cancelErrorMessage(CancelOutcome $outcome): string
    {
        return match ($outcome->status) {
            'already' => 'Kemunculan ini sudah dibatalkan sebelumnya.',
            'past' => 'Kemunculan ini sudah lewat (sebelum hari ini).',
            'inactive' => 'Jadwal sedang nonaktif.',
            'not_found' => 'Kemunculan tidak ditemukan.',
            default => 'Gagal membatalkan kemunculan: '.($outcome->error ?? $outcome->status),
        };
    }
}

<?php

namespace Tests\Feature;

use App\Models\ScheduledNotification;
use App\Models\ScheduledNotificationRun;
use App\Models\User;
use App\Services\Notification\Schedule\SchedulePlanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsesScheduledNotificationDatabase;
use Tests\TestCase;

/**
 * Fase 3: UI CRUD jadwal notifikasi (index/create/store/show/edit/update/
 * delete, toggle, pratinjau, kirim tes, batal run, kirim ulang).
 */
class ScheduledNotificationCrudTest extends TestCase
{
    use UsesScheduledNotificationDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareScheduledNotificationDatabase();

        config([
            'notifications.providers' => ['whatsapp'],
            'notifications.test_mode' => false,
            'notifications.schedules.timezone' => 'Asia/Jakarta',
            'notifications.schedules.default_holiday_message' => "*Latihan Libur*\n\n{nama_jadwal}\n{hari}, {tanggal}\n\nAlasan: {alasan}",
            'notifications.schedules.default_training_message' => "*Pengingat Latihan*\n{nama_jadwal}\n{hari}, {tanggal}",
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
            'notifications.whatsapp.chat_id' => '120363000000000000@g.us',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));

        $this->actingAs($this->user());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function user(): User
    {
        return User::create([
            'fullname' => 'Admin Uji',
            'email' => 'admin'.uniqid().'@example.test',
            'password' => bcrypt('secret'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'TC GOR Gondrong',
            'chat_id' => '120363404437691633@g.us',
            'chat_label' => 'Grup GOR Gondrong',
            'message' => "*Pengingat Latihan*\n{nama_jadwal}\n{hari}, {tanggal}",
            'holiday_message' => "*Latihan Libur*\n{nama_jadwal}\n{hari}, {tanggal}\n{hari}, {alasan}",
            'frequency' => 'weekly',
            'time' => '12:00',
            'start_date' => '2026-11-20',
            'end_date' => null,
            'days_of_week' => [2, 4],
            'monthly_mode' => 'date',
            'dates_of_month' => null,
            'weeks_of_month' => null,
            'is_active' => 1,
        ], $overrides);
    }

    private function fakeWaha(int $status = 201): void
    {
        Http::fake(['waha.test/*' => Http::response(['id' => ['_serialized' => 'x']], $status)]);
    }

    // ------------------------------------------------------------------
    // Tampilan
    // ------------------------------------------------------------------

    public function test_index_lists_schedules(): void
    {
        ScheduledNotification::create($this->payload(['code' => 'TCGOR']) + []);

        $this->get(route('scheduled-notification.index'))
            ->assertOk()
            ->assertSee('TC GOR Gondrong')
            ->assertSee('TCGOR');
    }

    public function test_index_filters_by_search_and_status(): void
    {
        ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        ScheduledNotification::create($this->payload([
            'code' => 'TCBDK',
            'name' => 'TC Cibedug',
            'is_active' => 0,
        ]));

        $this->get(route('scheduled-notification.index', ['q' => 'Cibedug']))
            ->assertOk()
            ->assertSee('TC Cibedug')
            ->assertDontSee('TC GOR Gondrong');

        $this->get(route('scheduled-notification.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('TC Cibedug')
            ->assertDontSee('TC GOR Gondrong');
    }

    public function test_create_form_renders(): void
    {
        $this->get(route('scheduled-notification.create'))
            ->assertOk()
            ->assertSee('Nama Jadwal')
            ->assertSee('{nama_jadwal}', false);
    }

    public function test_edit_form_renders_existing_values(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));

        $this->get(route('scheduled-notification.edit', $schedule->id))
            ->assertOk()
            ->assertSee('TC GOR Gondrong')
            ->assertSee('TCGOR');
    }

    // ------------------------------------------------------------------
    // Simpan
    // ------------------------------------------------------------------

    public function test_store_creates_schedule_with_generated_code_and_planned_runs(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('error', false);

        $schedule = ScheduledNotification::firstOrFail();

        $this->assertSame('TCGOR', $schedule->code);
        $this->assertSame('120363404437691633@g.us', $schedule->chat_id);
        $this->assertTrue($schedule->is_active);
        $this->assertGreaterThan(0, $schedule->runs()->count(), 'planner membuat kemunculan');
    }

    public function test_store_trims_duplicate_days_and_uppercases_nothing(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'days_of_week' => [4, 2, 4],
        ]))->assertSessionHasNoErrors();

        $schedule = ScheduledNotification::firstOrFail();

        $this->assertSame([2, 4], $schedule->days_of_week);
    }

    public function test_store_rejects_invalid_chat_id(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'chat_id' => 'bukan-chat-id',
        ]))->assertSessionHasErrors('chat_id');

        $this->assertSame(0, ScheduledNotification::count());
    }

    public function test_store_rejects_unknown_template_variable(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'holiday_message' => '{nama_jadwal} {tanggal_libur}',
        ]))->assertSessionHasErrors('holiday_message');

        $this->assertSame(0, ScheduledNotification::count());
    }

    public function test_store_requires_days_for_weekly(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'days_of_week' => [],
        ]))->assertSessionHasErrors('days_of_week');
    }

    public function test_store_requires_dates_for_monthly_date_mode(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'frequency' => 'monthly',
            'monthly_mode' => 'date',
            'dates_of_month' => '',
        ]))->assertSessionHasErrors('dates_of_month');
    }

    public function test_store_monthly_week_mode_creates_runs(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'frequency' => 'monthly',
            'monthly_mode' => 'week',
            'dates_of_month' => null,
            'weeks_of_month' => [1],
            'days_of_week' => [2],
        ]))->assertSessionHasNoErrors();

        $schedule = ScheduledNotification::firstOrFail();

        $this->assertSame('week', $schedule->monthly_mode);
        $this->assertSame([1], $schedule->weeks_of_month);
        $this->assertGreaterThan(0, $schedule->runs()->count());
    }

    // ------------------------------------------------------------------
    // Multi-chat
    // ------------------------------------------------------------------

    /**
     * @return array<int, array{chat_id: string, label: string}>
     */
    private function targets(): array
    {
        return [
            ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup GOR Gondrong'],
            ['chat_id' => '628111222333@c.us', 'label' => 'Pelatih'],
            ['chat_id' => '628999888777@c.us', 'label' => ''],
        ];
    }

    public function test_store_saves_all_chat_targets(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'targets' => $this->targets(),
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $schedule = ScheduledNotification::firstOrFail();

        $this->assertSame(3, $schedule->targets()->count());
        $this->assertSame(
            ['120363404437691633@g.us', '628111222333@c.us', '628999888777@c.us'],
            $schedule->chatIds(),
        );

        // Target utama tersinkron dengan kolom lama.
        $this->assertSame('120363404437691633@g.us', $schedule->chat_id);
        $this->assertSame('Grup GOR Gondrong', $schedule->chat_label);
    }

    public function test_store_accepts_legacy_single_chat_id(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $schedule = ScheduledNotification::firstOrFail();

        $this->assertSame(['120363404437691633@g.us'], $schedule->chatIds());
    }

    public function test_store_rejects_invalid_target_chat_id(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'targets' => [
                ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup'],
                ['chat_id' => 'bukan-chat-id', 'label' => 'Salah'],
            ],
        ]))->assertSessionHasErrors('targets.1.chat_id');

        $this->assertSame(0, ScheduledNotification::count());
    }

    public function test_store_rejects_duplicate_targets(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'targets' => [
                ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup'],
                ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup lagi'],
            ],
        ]))->assertSessionHasErrors('targets.1.chat_id');

        $this->assertSame(0, ScheduledNotification::count());
    }

    public function test_store_requires_at_least_one_target(): void
    {
        $this->post(route('scheduled-notification.store'), $this->payload([
            'chat_id' => null,
            'chat_label' => null,
            'targets' => [
                ['chat_id' => '', 'label' => ''],
            ],
        ]))->assertSessionHasErrors('targets');

        $this->assertSame(0, ScheduledNotification::count());
    }

    public function test_update_replaces_target_list(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $this->put(route('scheduled-notification.update', $schedule->id), $this->payload([
            'targets' => [
                ['chat_id' => '628111222333@c.us', 'label' => 'Pelatih Utama'],
                ['chat_id' => '628555666777@c.us', 'label' => 'Asisten'],
            ],
        ]))->assertRedirect(route('scheduled-notification.show', $schedule->id));

        $schedule->refresh();

        $this->assertSame(['628111222333@c.us', '628555666777@c.us'], $schedule->chatIds());
        $this->assertSame('628111222333@c.us', $schedule->chat_id);
        $this->assertSame('Pelatih Utama', $schedule->chat_label);
    }

    public function test_edit_form_renders_every_target_row(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $this->get(route('scheduled-notification.edit', $schedule->id))
            ->assertOk()
            ->assertSee('targets[0][chat_id]', false)
            ->assertSee('targets[1][chat_id]', false)
            ->assertSee('targets[2][chat_id]', false)
            ->assertSee('628999888777@c.us');
    }

    public function test_show_lists_every_target(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets([
            ['chat_id' => '120363404437691633@g.us', 'label' => 'Grup GOR Gondrong'],
            ['chat_id' => '628111222333@c.us', 'label' => 'Pelatih'],
            ['chat_id' => '628999888777@c.us', 'label' => 'Asisten Tanpa Label'],
        ]);
        $schedule->save();

        $this->get(route('scheduled-notification.show', $schedule->id))
            ->assertOk()
            ->assertSee('Grup GOR Gondrong')
            ->assertSee('120363404437691633@g.us')
            ->assertSee('Pelatih')
            ->assertSee('628111222333@c.us')
            ->assertSee('Asisten Tanpa Label')
            ->assertSee('628999888777@c.us')
            ->assertSee('Chat Tujuan (3)');
    }

    public function test_index_marks_schedule_with_multiple_chats(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $this->get(route('scheduled-notification.index'))
            ->assertOk()
            ->assertSee('3 chat');
    }

    public function test_index_search_finds_schedule_by_secondary_chat(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        ScheduledNotification::create($this->payload(['code' => 'TCBDK', 'name' => 'TC Cibedug']));

        $this->get(route('scheduled-notification.index', ['q' => '628999888777']))
            ->assertOk()
            ->assertSee('TC GOR Gondrong')
            ->assertDontSee('TC Cibedug');
    }

    public function test_test_button_sends_to_all_targets(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $sent = [];
        Http::fake(function ($request) use (&$sent) {
            $sent[] = $request['chatId'];

            return Http::response(['key' => ['id' => 'msg-'.count($sent)]], 201);
        });

        $this->post(route('scheduled-notification.test', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('error', false);

        sort($sent);

        $this->assertSame(
            ['120363404437691633@g.us', '628111222333@c.us', '628999888777@c.us'],
            $sent,
        );
    }

    // ------------------------------------------------------------------
    // Ubah
    // ------------------------------------------------------------------

    public function test_update_keeps_code_and_refreshes_runs(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        app(SchedulePlanner::class)->plan($schedule);

        $before = $schedule->runs()->where('status', ScheduledNotificationRun::STATUS_PENDING)->count();
        $this->assertGreaterThan(0, $before);

        $this->put(route('scheduled-notification.update', $schedule->id), $this->payload([
            'name' => 'TC GOR Gondrong Baru',
            'days_of_week' => [3],
        ]))->assertRedirect(route('scheduled-notification.show', $schedule->id));

        $schedule->refresh();

        $this->assertSame('TCGOR', $schedule->code, 'kode tidak berubah');
        $this->assertSame('TC GOR Gondrong Baru', $schedule->name);
        $this->assertSame([3], $schedule->days_of_week);

        $pending = $schedule->runs()->where('status', ScheduledNotificationRun::STATUS_PENDING)->get();
        $this->assertNotEmpty($pending);
        foreach ($pending as $run) {
            $this->assertSame(3, $run->occurrence_at->dayOfWeekIso, 'hanya hari Rabu');
        }
    }

    public function test_toggle_deactivates_cancels_pending_and_reactivates_plans(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        app(SchedulePlanner::class)->plan($schedule);
        $this->assertGreaterThan(0, $schedule->runs()->where('status', 'pending')->count());

        $this->post(route('scheduled-notification.toggle', $schedule->id))->assertSessionHas('error', false);

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
        $this->assertSame(0, $schedule->runs()->where('status', 'pending')->count());

        $this->post(route('scheduled-notification.toggle', $schedule->id))->assertSessionHas('error', false);

        $schedule->refresh();
        $this->assertTrue($schedule->is_active);

        $statuses = $schedule->runs()->pluck('status')->all();
        $this->assertContains(ScheduledNotificationRun::STATUS_PENDING, $statuses, 'planner membuat kemunculan baru: '.json_encode($statuses));
    }

    public function test_destroy_soft_deletes_and_cancels_pending(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        app(SchedulePlanner::class)->plan($schedule);

        $this->delete(route('scheduled-notification.destroy', $schedule->id))
            ->assertRedirect(route('scheduled-notification.index'));

        $this->assertSoftDeleted('scheduled_notifications', ['id' => $schedule->id]);
        $this->assertSame(0, ScheduledNotificationRun::where('status', 'pending')->count());
    }

    // ------------------------------------------------------------------
    // Pratinjau & kirim tes
    // ------------------------------------------------------------------

    public function test_preview_renders_variables(): void
    {
        $this->postJson(route('scheduled-notification.preview'), [
            'name' => 'TC GOR Gondrong',
            'message' => '{nama_jadwal} - {hari}, {tanggal}',
            'holiday_message' => '{nama_jadwal} libur: {alasan}',
            'frequency' => 'weekly',
            'time' => '12:00',
            'start_date' => '2026-11-20',
        ])->assertOk()
            ->assertJsonPath('unknown.training', [])
            ->assertJsonPath('unknown.holiday', [])
            ->assertJsonFragment(['hari' => 'Jumat'])
            ->assertSee('TC GOR Gondrong libur: hujan deras', false);
    }

    public function test_preview_reports_unknown_variables(): void
    {
        $this->postJson(route('scheduled-notification.preview'), [
            'message' => '{tidak_ada}',
        ])->assertOk()->assertJsonPath('unknown.training', ['tidak_ada']);
    }

    public function test_test_button_sends_message_to_chat(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $this->fakeWaha();

        $this->post(route('scheduled-notification.test', $schedule->id))
            ->assertSessionHas('error', false);

        Http::assertSent(function ($request) use ($schedule) {
            return $request['chatId'] === $schedule->chat_id
                && str_contains((string) $request['text'], 'TC GOR Gondrong');
        });
    }

    public function test_test_button_reports_failure(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $this->fakeWaha(500);

        $this->post(route('scheduled-notification.test', $schedule->id))
            ->assertSessionHas('error', true);
    }

    // ------------------------------------------------------------------
    // Aksi per kemunculan
    // ------------------------------------------------------------------

    public function test_cancel_run_from_ui_cancels_and_announces(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 12:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_PENDING,
        ]);
        $this->fakeWaha();

        $this->post(route('scheduled-notification.cancel-run', $schedule->id), [
            'run_id' => $run->id,
            'reason' => 'hujan deras',
        ])->assertSessionHas('error', false);

        $run->refresh();
        $this->assertSame('cancelled', $run->status);
        $this->assertSame('web', $run->cancel_source);
        $this->assertNotNull($run->announced_at);
    }

    public function test_cancel_run_requires_reason(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 12:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_PENDING,
        ]);

        $this->post(route('scheduled-notification.cancel-run', $schedule->id), [
            'run_id' => $run->id,
            'reason' => '',
        ])->assertSessionHasErrors('reason');

        $this->assertSame('pending', $run->refresh()->status);
    }

    public function test_reannounce_resends_holiday_message(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-24 12:00:00',
            'public_code' => 'TCGOR-20261124',
            'status' => ScheduledNotificationRun::STATUS_CANCELLED,
            'cancel_reason' => 'hujan deras',
            'cancel_source' => 'webhook',
            'announced_at' => null,
        ]);
        $this->fakeWaha();

        $this->post(route('scheduled-notification.reannounce', [$schedule->id, $run->id]))
            ->assertSessionHas('error', false);

        $this->assertNotNull($run->refresh()->announced_at);
        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Latihan Libur'));
    }

    public function test_retry_run_resends_failed_run(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-20 08:59:00',
            'public_code' => 'TCGOR-20261120',
            'status' => ScheduledNotificationRun::STATUS_FAILED,
            'attempts' => 3,
            'last_error' => 'timeout',
        ]);
        $this->fakeWaha();

        $this->post(route('scheduled-notification.retry', [$schedule->id, $run->id]))
            ->assertSessionHas('error', false);

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->status);
        $this->assertNull($run->last_error);
    }

    public function test_cancel_run_rejects_unknown_run(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));

        $this->post(route('scheduled-notification.cancel-run', $schedule->id), [
            'run_id' => '00000000-0000-0000-0000-000000000000',
            'reason' => 'hujan deras',
        ])->assertSessionHas('error', true);
    }

    public function test_retry_run_only_resends_to_targets_that_have_not_received(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-20 08:59:00',
            'public_code' => 'TCGOR-20261120',
            'status' => ScheduledNotificationRun::STATUS_FAILED,
            'attempts' => 3,
            'last_error' => 'sebagian gagal',
            // Dua target sudah menerima pesan pada percobaan sebelumnya.
            'sent_targets' => ['120363404437691633@g.us', '628111222333@c.us'],
            'error_targets' => ['628999888777@c.us'],
        ]);

        $sent = [];
        Http::fake(function ($request) use (&$sent) {
            $sent[] = $request['chatId'];

            return Http::response(['key' => ['id' => 'msg-'.count($sent)]], 201);
        });

        $this->post(route('scheduled-notification.retry', [$schedule->id, $run->id]))
            ->assertSessionHas('error', false);

        $this->assertSame(['628999888777@c.us'], $sent, 'hanya target yang gagal dikirimi ulang');

        $run->refresh();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->status);
        $this->assertNull($run->error_targets);
        $this->assertNull($run->sent_targets);
    }

    public function test_retry_run_marks_sent_when_all_targets_already_received(): void
    {
        $schedule = ScheduledNotification::create($this->payload(['code' => 'TCGOR']));
        $schedule->syncTargets($this->targets());
        $schedule->save();

        $run = ScheduledNotificationRun::create([
            'scheduled_notification_id' => $schedule->id,
            'occurrence_at' => '2026-11-20 08:59:00',
            'public_code' => 'TCGOR-20261120',
            'status' => ScheduledNotificationRun::STATUS_FAILED,
            'attempts' => 3,
            'sent_targets' => $schedule->chatIds(),
        ]);

        Http::fake();

        $this->post(route('scheduled-notification.retry', [$schedule->id, $run->id]))
            ->assertSessionHas('error', false);

        Http::assertNothingSent();
        $this->assertSame(ScheduledNotificationRun::STATUS_SENT, $run->refresh()->status);
    }

    // ------------------------------------------------------------------
    // Not found
    // ------------------------------------------------------------------

    public function test_unknown_schedule_redirects_with_error(): void
    {
        $this->get(route('scheduled-notification.show', '00000000-0000-0000-0000-000000000000'))
            ->assertRedirect(route('scheduled-notification.index'))
            ->assertSessionHas('error', true);
    }
}

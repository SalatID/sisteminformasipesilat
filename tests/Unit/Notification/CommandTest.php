<?php

namespace Tests\Unit\Notification;

use App\Services\Notification\NotificationManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'notifications.providers' => ['telegram', 'whatsapp'],
            'notifications.test_mode' => true,
            'notifications.test_chat_id' => '6289654157132@c.us',
            'notifications.mention.enabled' => true,
            'notifications.send_delay_seconds' => 0,
            'notifications.interval_hours' => 3,
            'notifications.telegram.bot_token' => 't',
            'notifications.telegram.chat_id' => '-1',
            'notifications.whatsapp.url' => 'https://waha.test',
            'notifications.whatsapp.api_key' => 'k',
            'notifications.whatsapp.session' => 'sessionsmi',
        ]);
    }

    public function test_signature_keeps_backward_compatible_argument_and_new_options(): void
    {
        $definition = Artisan::all()['attendance:notify']->getDefinition();

        $this->assertTrue($definition->hasArgument('attendance_id'));
        $this->assertTrue($definition->hasOption('type'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function test_invalid_type_fails(): void
    {
        $code = Artisan::call('attendance:notify', ['--type' => 'ngawur']);

        $this->assertSame(1, $code);
    }

    public function test_dry_run_sends_zero_requests(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        Artisan::call('attendance:notify', ['--type' => 'all', '--dry-run' => true, '--force' => true]);

        Http::assertNothingSent();
    }

    public function test_unknown_attendance_id_returns_failure(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $code = Artisan::call('attendance:notify', [
            'attendance_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $this->assertSame(1, $code);
        Http::assertNothingSent();
    }

    public function test_no_active_provider_sends_nothing(): void
    {
        config(['notifications.providers' => []]);
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $code = Artisan::call('attendance:notify', ['--force' => true]);

        $this->assertSame(0, $code);
        Http::assertNothingSent();
    }

    public function test_command_is_scheduled_every_three_hours(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'attendance:notify'));

        $this->assertCount(1, $events);
        $this->assertSame('0 */3 * * *', $events->first()->expression);
    }
}

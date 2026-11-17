<?php

namespace App\Console\Support;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Event jadwal yang menjalankan perintah artisan di dalam proses yang sama
 * (tanpa proc_open), tetapi tetap mempertahankan semantik Event Laravel:
 *
 *   - withoutOverlapping()   → CacheEventMutex standar,
 *   - onOneServer()          → di Laravel 12 ditangani ScheduleRunCommand
 *                              lewat Schedule::serverShouldRun() (mutexName),
 *   - evenInMaintenanceMode(), environments(),
 *   - appendOutputTo()       → ditiru: satu blok log per eksekusi.
 *
 * Diturunkan dari CallbackEvent karena hanya CallbackEvent yang merilis
 * mutex overlap setelah selesai (Event::finish() → removeMutex() yang di
 * CallbackEvent mensyaratkan ->name()/->description()). Karena itu event ini
 * mensyaratkan ->name() juga, sama seperti bawaan Laravel.
 */
class InProcessCommandEvent extends CallbackEvent
{
    private string $artisanCommand;

    /** @var array<string, mixed> */
    private array $artisanArguments;

    private ?string $logPath;

    public function __construct(
        $mutex,
        string $artisanCommand,
        array $artisanArguments = [],
        ?string $logPath = null,
        $timezone = null,
    ) {
        // Callback induk tidak dipakai; eksekusi ada di execute().
        parent::__construct($mutex, static fn () => null, [], $timezone);

        $this->artisanCommand = $artisanCommand;
        $this->artisanArguments = $artisanArguments;
        $this->logPath = $logPath;
    }

    /**
     * Jalankan artisan in-process lalu tulis output ke file log.
     */
    protected function execute($container)
    {
        try {
            $exitCode = Artisan::call($this->artisanCommand, $this->artisanArguments);
            $output = Artisan::output();

            $this->result = $exitCode;
            $this->writeLog($output);

            return $exitCode === 0 ? 0 : 1;
        } catch (Throwable $e) {
            $this->exception = $e;
            $this->writeLog('ERROR: ' . $e->getMessage());

            return 1;
        }
    }

    public function appendOutputTo($location)
    {
        $this->logPath = $location;

        return $this;
    }

    public function sendOutputTo($location, $append = false)
    {
        $this->logPath = $location;

        return $this;
    }

    public function getSummaryForDisplay()
    {
        return $this->description ?? $this->buildLabel();
    }

    public function mutexName()
    {
        if (! is_null($this->mutexNameResolver)) {
            return parent::mutexName();
        }

        return 'framework/schedule-' . sha1($this->buildLabel());
    }

    private function buildLabel(): string
    {
        $args = [];

        foreach ($this->artisanArguments as $key => $value) {
            $args[] = str_starts_with((string) $key, '-') ? "{$key}={$value}" : "{$value}";
        }

        return trim('php artisan ' . $this->artisanCommand . ' ' . implode(' ', $args));
    }

    private function writeLog(string $output): void
    {
        if ($this->logPath === null || $output === '') {
            return;
        }

        $dir = dirname($this->logPath);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $this->logPath,
            PHP_EOL . 'Running [' . $this->buildLabel() . ']' . PHP_EOL . $output . PHP_EOL,
            FILE_APPEND,
        );
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\Notification\Reply\HolidayDecision;
use App\Services\Notification\Reply\HolidayTargetResolver;
use App\Services\Notification\Reply\ReplyResponder;
use App\Services\Notification\Reply\WaReplyCommand;
use Illuminate\Console\Command;

/**
 * Simulasi balasan admin TANPA menyentuh WAHA dan TANPA menulis database.
 *
 * Dipakai untuk UAT Fase 4: memastikan pesan pengingat yang asli terbaca,
 * nomor baris menunjuk unit yang benar, dan balasan bot sudah sesuai.
 *
 * Contoh:
 *   php artisan attendance:holiday-preview --quoted-file=pengingat.txt --text="nomor 1
 *   Diliburkan Sekolah
 *   Alasan : Sedang ujian"
 */
class HolidayReplyPreviewCommand extends Command
{
    protected $signature = 'attendance:holiday-preview
                            {--quoted= : Teks pesan notifikasi yang dikutip (boleh \\n)}
                            {--quoted-file= : Berkas berisi teks pesan notifikasi}
                            {--text= : Teks balasan admin}
                            {--text-file= : Berkas berisi teks balasan admin}';

    protected $description = 'Simulasi balasan "tandai libur" tanpa mengirim WhatsApp dan tanpa menulis database';

    public function handle(
        WaReplyCommand $command,
        HolidayTargetResolver $resolver,
        ReplyResponder $responder,
    ): int {
        $quoted = $this->input('quoted');
        $text = $this->input('text');

        if ($quoted === '') {
            $this->error('Isi --quoted atau --quoted-file dengan teks pesan pengingat.');

            return Command::FAILURE;
        }

        if ($text === '') {
            $this->error('Isi --text atau --text-file dengan balasan admin.');

            return Command::FAILURE;
        }

        $this->line('── Balasan admin ─────────────────────────────');
        $this->line($text);

        $batch = $command->parseBatch($text);

        if (! $batch->ok) {
            $this->line('── Hasil ─────────────────────────────────────');

            // Tanpa awalan /libur, pesan apa pun dianggap obrolan biasa dan
            // diabaikan diam-diam oleh webhook. Pratinjau menampilkan hal sama.
            if ($batch->error === 'missing_prefix') {
                $this->warn('BUKAN PERINTAH — tidak ada awalan /libur, bot akan diam.');
                $this->line('Tambahkan baris pertama: '.WaReplyCommand::PREFIX);

                return Command::SUCCESS;
            }

            $this->warn('DITOLAK saat parsing: '.$batch->error);
            $this->line('── Balasan bot ───────────────────────────────');
            $this->line($responder->forParseError(
                $batch->errorReply(),
                $batch->blockNumber(),
                $batch->blockCount(),
            ));

            return Command::SUCCESS;
        }

        $multi = $batch->count() > 1;
        $succeeded = [];
        $failed = [];

        foreach ($batch->items as $index => $parsed) {
            if ($multi) {
                $this->newLine();
                $this->line('── Blok '.($index + 1).' (Nomor '.$parsed->number.') ───────────────');
            }

            $resolution = $resolver->resolve($quoted, (int) $parsed->number);

            if (! $resolution->ok || $resolution->target === null) {
                if (! $multi) {
                    $this->line('── Hasil ─────────────────────────────────────');
                    $this->warn('DITOLAK saat mencocokkan nomor: '.$resolution->error);
                    $this->line('── Balasan bot ───────────────────────────────');
                    $this->line($responder->forResolveError($resolution, (int) $parsed->number));

                    return Command::SUCCESS;
                }

                $this->warn('DITOLAK saat mencocokkan nomor: '.$resolution->error);
                $failed[] = [
                    'number' => (int) $parsed->number,
                    'message' => $responder->forResolveError($resolution, (int) $parsed->number),
                ];

                continue;
            }

            $target = $resolution->target;
            $existing = Attendance::query()
                ->where('unit_id', $target->unit->id)
                ->whereDate('attendance_date', $target->date->toDateString())
                ->first();

            $decision = HolidayDecision::decide(
                $existing?->attendance_status,
                $existing?->reason,
                (string) $parsed->status,
                (string) $parsed->reason,
                (bool) $target->unit->is_active,
            );

            if (! $multi) {
                $this->line('── Hasil ─────────────────────────────────────');
            }

            $this->line('Tipe pesan     : '.(string) $resolution->type);
            $this->line('Baris          : '.$target->number.' dari '.$resolution->totalLines);
            $this->line('Unit           : '.$target->unitName());
            $this->line('Tanggal        : '.$target->date->toDateString().' ('.$target->dateLabel().')');
            $this->line('Pukul          : '.$target->pukulLabel());
            $this->line('Status baru    : '.$parsed->status.' ('.$parsed->statusLabel.')');
            $this->line('Alasan         : '.$parsed->reason);
            $this->line('Baris absensi  : '.($existing === null ? 'belum ada' : $existing->attendance_status.' / '.($existing->reason ?? '-')));
            $this->line('Keputusan      : '.$decision);

            if ($decision === HolidayDecision::REJECT_TRAINING) {
                if (! $multi) {
                    $this->line('── Balasan bot ───────────────────────────────');
                    $this->warn($responder->alreadyTraining($target));

                    return Command::SUCCESS;
                }

                $failed[] = ['number' => (int) $parsed->number, 'message' => $responder->alreadyTraining($target)];

                continue;
            }

            if ($decision === HolidayDecision::REJECT_INACTIVE_UNIT) {
                if (! $multi) {
                    $this->line('── Balasan bot ───────────────────────────────');
                    $this->warn($responder->inactiveUnit($target));

                    return Command::SUCCESS;
                }

                $failed[] = ['number' => (int) $parsed->number, 'message' => $responder->inactiveUnit($target)];

                continue;
            }

            $succeeded[] = [
                'number' => (int) $parsed->number,
                'target' => $target,
                'statusLabel' => (string) $parsed->statusLabel,
                'reason' => (string) $parsed->reason,
                'truncated' => $parsed->truncated,
                'unchanged' => $decision === HolidayDecision::UNCHANGED,
            ];

            if (! $multi) {
                $this->line('── Balasan bot ───────────────────────────────');
                $this->line($responder->results($succeeded, []));
                $this->newLine();
                $this->comment('Tidak ada data yang diubah (mode pratinjau).');

                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->line('── Balasan bot ───────────────────────────────');
        $this->line($responder->results($succeeded, $failed));
        $this->newLine();
        $this->comment('Tidak ada data yang diubah (mode pratinjau).');

        return Command::SUCCESS;
    }

    private function input(string $key): string
    {
        $file = (string) $this->option($key.'-file');

        if ($file !== '') {
            if (! is_file($file)) {
                $this->error("Berkas tidak ditemukan: {$file}");

                return '';
            }

            return (string) file_get_contents($file);
        }

        return (string) $this->option($key);
    }
}

<?php

namespace App\Services\Notification\Schedule;

use App\Models\ScheduledNotificationRun;

/**
 * Menyusun teks balasan bot untuk `/cancel-schedule`.
 */
class CancelResponder
{
    public function formatHelp(): string
    {
        return "Format belum sesuai.\n\n".
            "Balas pesan bot, lalu tulis:\n".
            CancelScheduleParser::PREFIX."\n".
            "Alasan : hujan deras\n\n".
            "Atau sebutkan kode kemunculan:\n".
            CancelScheduleParser::PREFIX." TCGOR-20261125\n".
            'Alasan : hujan deras';
    }

    public function missingReason(): string
    {
        return "Alasan wajib diisi (minimal 3 karakter).\n\nContoh:\n".
            CancelScheduleParser::PREFIX."\nAlasan : hujan deras";
    }

    public function reasonTooShort(): string
    {
        return 'Alasan terlalu pendek. Tulis minimal 3 karakter.';
    }

    public function reasonTooLong(): string
    {
        return 'Alasan maksimal 255 karakter.';
    }

    public function duplicateReason(): string
    {
        return 'Alasan ditulis lebih dari sekali. Kirim satu alasan saja.';
    }

    public function unknownLine(string $line): string
    {
        return 'Baris "'.$line."\" tidak dikenali.\n\n".$this->formatHelp();
    }

    public function notFound(): string
    {
        return "Kode jadwal tidak ditemukan.\n\n".$this->formatHelp();
    }

    public function missingQuote(): string
    {
        return 'Balas (reply) pesan bot yang ingin dibatalkan, atau sebutkan kodenya: '.
            CancelScheduleParser::PREFIX.' TCGOR-20261125';
    }

    public function mismatch(): string
    {
        return 'Kode dan pesan yang dibalas menunjuk jadwal yang berbeda. Kirim salah satu saja.';
    }

    public function already(): string
    {
        return 'Jadwal ini sudah dibatalkan sebelumnya.';
    }

    public function past(ScheduledNotificationRun $run): string
    {
        return sprintf(
            'Jadwal tersebut sudah lewat (%s).',
            OccurrenceCalculator::dateLabel($run->occurrence_at),
        );
    }

    public function inactive(): string
    {
        return 'Jadwal sedang nonaktif.';
    }

    public function systemError(): string
    {
        return 'Maaf, jadwal gagal dibatalkan. Silakan coba lagi atau hubungi admin.';
    }

    public function forParseError(CancelScheduleCommand $command): string
    {
        return match ($command->error) {
            'missing_reason' => $this->missingReason(),
            'reason_too_short' => $this->reasonTooShort(),
            'reason_too_long' => $this->reasonTooLong(),
            'duplicate_reason' => $this->duplicateReason(),
            'unknown_line' => $this->formatHelp(),
            default => $this->formatHelp(),
        };
    }

    public function forResolution(CancelResolution $resolution): string
    {
        return match ($resolution->status) {
            'not_found' => $this->notFound(),
            'missing_quote' => $this->missingQuote(),
            'mismatch' => $this->mismatch(),
            'already' => $this->already(),
            'past' => $resolution->run !== null ? $this->past($resolution->run) : $this->formatHelp(),
            'inactive' => $this->inactive(),
            default => $this->formatHelp(),
        };
    }

    public function forOutcome(CancelOutcome $outcome): string
    {
        return match ($outcome->status) {
            'already' => $this->already(),
            'past' => $outcome->run !== null ? $this->past($outcome->run) : $this->formatHelp(),
            'inactive' => $this->inactive(),
            'not_found' => $this->notFound(),
            'missing_quote' => $this->missingQuote(),
            'mismatch' => $this->mismatch(),
            default => $this->systemError(),
        };
    }

    /**
     * @param  array<int, ScheduledNotificationRun>  $runs
     */
    public function scheduleOnly(?string $scheduleName, array $runs): string
    {
        if ($runs === []) {
            return 'Tidak ada kemunculan jadwal terdekat untuk jadwal tersebut.';
        }

        $lines = ['Kemunculan terdekat'.($scheduleName ? ' untuk '.$scheduleName : '').':'];

        foreach ($runs as $run) {
            $lines[] = sprintf(
                '- %s (%s, %s)',
                $run->public_code,
                OccurrenceCalculator::dayName($run->occurrence_at),
                OccurrenceCalculator::dateLabel($run->occurrence_at),
            );
        }

        $lines[] = '';
        $lines[] = 'Kirim ulang dengan kode lengkap, contoh:';
        $lines[] = CancelScheduleParser::PREFIX.' '.$runs[0]->public_code;
        $lines[] = 'Alasan : hujan deras';

        return implode("\n", $lines);
    }

    public function success(ScheduledNotificationRun $run, string $reason, bool $announcementSent): string
    {
        $scheduleName = (string) ($run->schedule?->name ?? '');

        $text = "Sudah saya batalkan:\n".sprintf(
            '%s, %s, %s',
            $scheduleName,
            OccurrenceCalculator::dayName($run->occurrence_at),
            OccurrenceCalculator::dateLabel($run->occurrence_at),
        )."\nAlasan : ".$reason;

        $text .= $announcementSent
            ? "\n\nPengumuman libur sudah dikirim."
            : "\n\nJadwal sudah dibatalkan, tetapi pengumuman libur gagal dikirim.\nSilakan coba \"Kirim ulang pengumuman\" di halaman detail jadwal.";

        return $text;
    }
}

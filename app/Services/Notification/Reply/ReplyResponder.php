<?php

namespace App\Services\Notification\Reply;

/**
 * Menyusun semua teks balasan bot di grup.
 *
 * Semua teks memakai format kanonik `*bold*` milik `AttendanceMessageBuilder`,
 * sehingga `WhatsAppChannel` mengirimnya apa adanya.
 */
class ReplyResponder
{
    private const STATUS_HELP = "- Diliburkan Sekolah\n- Diliburkan Pelatih";

    /**
     * Balasan sukses, mengikuti contoh pemilik proses:
     *
     *   sudah saya update,
     *   SMP/SMA/SMK Cengkareng 1, Selasa, 17 November 2026, Diliburkan Sekolah Karena Sedang Ujian
     */
    public function success(
        HolidayTarget $target,
        string $statusLabel,
        string $reason,
        bool $reasonTruncated = false,
        bool $unchanged = false,
    ): string {
        $prefix = $unchanged
            ? 'sudah saya update sebelumnya,'
            : 'sudah saya update,';

        $reasonText = $reasonTruncated ? $reason.' (dipotong)' : $reason;

        return $prefix."\n".sprintf(
            '%s, %s, %s Karena %s',
            $target->unitName(),
            $target->dateLabel(),
            $statusLabel,
            $reasonText,
        );
    }

    /**
     * Pengirim tidak berwenang.
     *
     * @return array{text: string, mentions: array<int, string>}
     */
    public function unauthorized(?string $actorId, ?string $pushName = null): array
    {
        $mention = ActorIdentity::mentionText($actorId);
        $label = $mention ?? ($pushName !== null && $pushName !== '' ? $pushName : (string) $actorId);

        return [
            'text' => 'Anda '.$label.' tidak boleh mengubah status absensi',
            'mentions' => $mention !== null ? [(string) $actorId] : [],
        ];
    }

    public function formatHelp(): string
    {
        return "Format tidak dikenali.\n\n".
            "Contoh balasan:\n".
            "nomor 1\n".
            "Diliburkan Sekolah\n".
            "Alasan : Sedang ujian\n\n".
            "Status yang tersedia:\n".
            self::STATUS_HELP;
    }

    public function notReplyingToNotification(): string
    {
        return 'Balas pesan *Pengingat Absensi Latihan* yang ingin diubah, '.
            'lalu tulis nomor baris, status libur, dan alasannya.';
    }

    public function numberNotFound(int $number, int $totalLines): string
    {
        return sprintf(
            'Nomor %d tidak ada pada pesan tersebut. Pesan itu hanya memuat %d baris.',
            $number,
            $totalLines,
        );
    }

    public function notUnitLine(int $number): string
    {
        return sprintf('Nomor %d bukan baris unit latihan.', $number);
    }

    public function unitNotFound(): string
    {
        return 'Unit pada pesan tersebut tidak ditemukan di aplikasi.';
    }

    public function dateUnreadable(): string
    {
        return 'Tanggal latihan pada pesan tersebut tidak bisa dibaca.';
    }

    public function alreadyTraining(HolidayTarget $target): string
    {
        return sprintf(
            'Latihan %s tanggal %s sudah ada laporan absensi; tidak bisa ditandai libur.'."\n".
            'Hubungi admin untuk mengubah laporan tersebut terlebih dahulu.',
            $target->unitName(),
            $target->dateLabel(),
        );
    }

    public function inactiveUnit(HolidayTarget $target): string
    {
        return sprintf('Unit %s sudah tidak aktif.', $target->unitName());
    }

    public function missingNumber(): string
    {
        return "Nomor baris belum ditulis.\n\n".$this->formatHelp();
    }

    public function missingStatus(): string
    {
        return "Status libur belum ditulis.\n\nStatus yang tersedia:\n".self::STATUS_HELP;
    }

    public function missingReason(): string
    {
        return 'Alasan wajib diisi. Contoh: Alasan : Sedang ujian';
    }

    public function reasonTooShort(): string
    {
        return 'Alasan terlalu pendek. Tulis minimal '.WaReplyCommand::MIN_REASON_LENGTH.' karakter.';
    }

    public function unknownLine(string $line): string
    {
        return 'Baris "'.$line."\" tidak dikenali.\n\n".$this->formatHelp();
    }

    public function duplicate(string $what): string
    {
        return ucfirst($what).' ditulis lebih dari sekali. Kirim satu perintah saja per balasan.';
    }

    public function numberNotPositive(): string
    {
        return 'Nomor baris harus 1 atau lebih.';
    }

    public function systemError(): string
    {
        return 'Maaf, status libur gagal disimpan. Silakan coba lagi atau hubungi admin.';
    }

    /**
     * Petakan kode error hasil parsing ke pesan bot.
     */
    public function forParseError(ParsedReply $reply): string
    {
        return match ($reply->error) {
            'missing_number' => $this->missingNumber(),
            'missing_status' => $this->missingStatus(),
            'missing_reason' => $this->missingReason(),
            'reason_too_short' => $this->reasonTooShort(),
            'number_not_positive' => $this->numberNotPositive(),
            'duplicate_number' => $this->duplicate('nomor'),
            'duplicate_status' => $this->duplicate('status'),
            'duplicate_reason' => $this->duplicate('alasan'),
            'unknown_line' => $this->unknownLine((string) ($reply->context['line'] ?? '')),
            'empty' => $this->formatHelp(),
            default => $this->formatHelp(),
        };
    }

    /**
     * Petakan kode error hasil resolusi nomor ke pesan bot.
     */
    public function forResolveError(TargetResolution $resolution, int $number): string
    {
        return match ($resolution->error) {
            'number_not_found' => $this->numberNotFound($number, $resolution->totalLines),
            'not_unit_line' => $this->notUnitLine($number),
            'unit_not_found' => $this->unitNotFound(),
            'date_unreadable' => $this->dateUnreadable(),
            default => $this->notReplyingToNotification(),
        };
    }

    /**
     * Petakan kode error penyimpanan ke pesan bot.
     */
    public function forFlagError(FlagOutcome $outcome, HolidayTarget $target): string
    {
        return match ($outcome->error) {
            'reject_training' => $this->alreadyTraining($target),
            'reject_inactive_unit' => $this->inactiveUnit($target),
            default => $this->systemError(),
        };
    }
}

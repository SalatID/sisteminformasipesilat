<?php

namespace App\Services\Notification;

use App\Models\Attendance;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim laporan absensi satu unit ke provider yang belum sukses, dan
 * menandai `is_notif_send` HANYA jika semua provider aktif sukses.
 *
 * Memperbaiki T-08: kode lama menandai terkirim tanpa memeriksa hasil.
 */
class AttendanceReportDispatcher
{
    public function __construct(
        private readonly NotificationManager $manager,
        private readonly AttendanceMessageBuilder $builder,
    ) {
    }

    /**
     * Provider aktif yang belum pernah sukses untuk attendance ini.
     *
     * @return array<int, string>
     */
    public function pendingProviders(Attendance $attendance): array
    {
        $active = $this->manager->activeProviders();
        $sent = (array) ($attendance->notif_sent_channels ?? []);

        return array_values(array_diff($active, $sent));
    }

    /**
     * Kirim laporan; kembalikan ringkasan pengiriman.
     *
     * @return array{success: bool, results: array<int, ChannelResult>, skipped: bool}
     */
    public function dispatch(Attendance $attendance, bool $force = false): array
    {
        $pending = $this->pendingProviders($attendance);

        if ($pending === [] && !$force) {
            return ['success' => true, 'results' => [], 'skipped' => true];
        }

        $targets = $force
            ? array_values(array_unique(array_merge($pending, $this->manager->activeProviders())))
            : $pending;

        if ($targets === []) {
            return ['success' => true, 'results' => [], 'skipped' => true];
        }

        $supportMentions = $this->manager->supportsMentions();

        // Varian ber-tag untuk WhatsApp; Telegram selalu memakai varian polos.
        $report = $this->builder->buildAttendanceReport($attendance, $supportMentions);
        $plainReport = $this->builder->buildAttendanceReport($attendance, false, false);

        $results = $this->manager->send(
            $report['text'],
            $report['mentions'],
            $targets,
            $plainReport['text'],
        );

        $succeeded = $this->manager->succeededProviders($results);
        $failed = array_values(array_diff($targets, $succeeded));

        // Simpan provider yang sukses (per provider, bukan per attendance).
        $sentNow = array_values(array_unique(array_merge((array) ($attendance->notif_sent_channels ?? []), $succeeded)));

        $allActiveDone = array_diff($this->manager->activeProviders(), $sentNow) === [];

        $attendance->notif_sent_channels = $sentNow;
        $attendance->is_notif_send = $allActiveDone;   // T-08: hanya true bila semua provider aktif sukses
        $attendance->save();

        Log::channel('cron')->info('Attendance report dispatched', [
            'attendance_id' => $attendance->id,
            'targets' => $targets,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'is_notif_send' => $attendance->is_notif_send,
        ]);

        return [
            'success' => $failed === [],
            'results' => $results,
            'skipped' => false,
        ];
    }
}

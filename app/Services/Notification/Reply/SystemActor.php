<?php

namespace App\Services\Notification\Reply;

use App\Models\User;

/**
 * Penulis perubahan saat libur ditandai lewat WhatsApp.
 *
 * Balasan grup tidak punya sesi login, tetapi `attendances.created_by` dan
 * `attendances.report_maker_id` bersifat NOT NULL. Keputusan pemilik proses
 * (18 Nov 2026): isi dengan UUID user ber-role `super-admin`.
 */
class SystemActor
{
    private ?string $resolved = null;

    private bool $attempted = false;

    public function id(): ?string
    {
        if ($this->attempted) {
            return $this->resolved;
        }

        $this->attempted = true;

        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
            ->orderBy('created_at')
            ->first()
            ?? User::query()->orderBy('created_at')->first();

        $this->resolved = $user?->id;

        return $this->resolved;
    }
}

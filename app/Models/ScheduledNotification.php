<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledNotification extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const FREQUENCIES = ['once', 'daily', 'weekly', 'monthly'];

    public const MONTHLY_MODES = ['date', 'week'];

    protected $fillable = [
        'code',
        'name',
        'chat_id',
        'chat_label',
        'message',
        'holiday_message',
        'frequency',
        'time',
        'days_of_week',
        'monthly_mode',
        'dates_of_month',
        'weeks_of_month',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'dates_of_month' => 'array',
        'weeks_of_month' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(ScheduledNotificationRun::class);
    }

    /**
     * Semua chat tujuan. `chat_id`/`chat_label` pada tabel utama adalah
     * target utama (urutan pertama) untuk kompatibilitas tampilan lama.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(ScheduledNotificationTarget::class)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    /**
     * Daftar chat beserta label, urut sesuai form.
     *
     * @return array<int, array{chat_id: string, label: string}>
     */
    public function targetList(): array
    {
        $targets = $this->relationLoaded('targets')
            ? $this->targets
            : $this->targets()->get();

        $list = $targets
            ->filter(fn (ScheduledNotificationTarget $target) => trim((string) $target->chat_id) !== '')
            ->map(fn (ScheduledNotificationTarget $target) => [
                'chat_id' => (string) $target->chat_id,
                'label' => (string) ($target->label ?? ''),
            ])
            ->values()
            ->all();

        if ($list !== []) {
            return $list;
        }

        // Fallback untuk data lama sebelum tabel target diisi.
        $chatId = trim((string) $this->chat_id);

        return $chatId === ''
            ? []
            : [['chat_id' => $chatId, 'label' => (string) ($this->chat_label ?? '')]];
    }

    /**
     * @return array<int, string>
     */
    public function chatIds(): array
    {
        return array_values(array_unique(array_map(
            fn (array $target) => $target['chat_id'],
            $this->targetList(),
        )));
    }

    /**
     * Simpan daftar target (ganti seluruh isi) dan sinkronkan kolom utama.
     *
     * @param  array<int, array{chat_id: string, label?: string|null}>  $targets
     */
    public function syncTargets(array $targets): void
    {
        $normalized = [];
        $seen = [];

        foreach ($targets as $target) {
            $chatId = trim((string) ($target['chat_id'] ?? ''));

            if ($chatId === '' || isset($seen[$chatId])) {
                continue;
            }

            $seen[$chatId] = true;
            $label = trim((string) ($target['label'] ?? ''));

            $normalized[] = [
                'chat_id' => $chatId,
                'label' => $label === '' ? null : mb_substr($label, 0, 120),
            ];
        }

        $this->targets()->delete();

        foreach ($normalized as $index => $target) {
            $this->targets()->create([
                'chat_id' => $target['chat_id'],
                'label' => $target['label'],
                'sort_order' => $index,
            ]);
        }

        if ($normalized !== []) {
            $this->chat_id = $normalized[0]['chat_id'];
            $this->chat_label = $normalized[0]['label'];
        }
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeLabel(): string
    {
        return substr((string) $this->time, 0, 5);
    }

    /**
     * Ringkasan pola untuk tabel daftar.
     */
    public function scheduleSummary(): string
    {
        return match ($this->frequency) {
            'once' => 'Sekali'.($this->start_date ? ' · '.$this->start_date->format('d/m/Y') : ''),
            'daily' => 'Harian',
            'weekly' => 'Mingguan · '.$this->daysLabel(),
            'monthly' => $this->monthly_mode === 'week'
                ? 'Bulanan · minggu '.$this->weeksLabel().' · '.$this->daysLabel()
                : 'Bulanan · tanggal '.implode(', ', (array) $this->dates_of_month),
            default => '-',
        };
    }

    public function daysLabel(): string
    {
        $names = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];

        $days = array_values(array_filter(array_map(
            fn ($day) => $names[(int) $day] ?? null,
            (array) $this->days_of_week,
        )));

        return $days === [] ? '-' : implode(', ', $days);
    }

    public function weeksLabel(): string
    {
        $weeks = array_map(fn ($week) => (int) $week === 5 ? 'terakhir' : (string) $week, (array) $this->weeks_of_month);

        return $weeks === [] ? '-' : implode(', ', $weeks);
    }

    /**
     * Kode otomatis dari nama: 5 karakter alfanumerik pertama.
     *
     * Contoh: "TC GOR Gondrong" -> "TCGOR". Jika sudah dipakai, ditambah
     * sufiks 2, 3, dst. Kode tidak pernah diubah saat edit (K9).
     */
    public static function generateCode(string $name, ?string $ignoreId = null): string
    {
        $base = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $name));
        $base = substr($base, 0, 5);

        if (strlen($base) < 4) {
            $base = str_pad($base, 4, 'X');
        }

        $candidate = $base;
        $suffix = 2;

        while (self::codeExists($candidate, $ignoreId)) {
            $suffixText = (string) $suffix;
            $candidate = substr($base, 0, 10 - strlen($suffixText)).$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private static function codeExists(string $code, ?string $ignoreId): bool
    {
        return self::withTrashed()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}

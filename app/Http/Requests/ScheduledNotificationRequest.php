<?php

namespace App\Http\Requests;

use App\Models\ScheduledNotification;
use App\Services\Notification\Schedule\MessageTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi form jadwal notifikasi (Fase 3).
 *
 * Kode tidak divalidasi di sini: kode dibuat otomatis saat membuat jadwal dan
 * tidak pernah diubah saat edit (K9).
 */
class ScheduledNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'chat_id' => ['nullable', 'string', 'max:100', 'regex:/@(g\.us|c\.us|s\.whatsapp\.net)$/'],
            'chat_label' => ['nullable', 'string', 'max:120'],

            // Multi-chat: satu jadwal boleh dikirim ke banyak chat sekaligus.
            // `chat_id` tunggal tetap diterima agar pemanggil lama tidak rusak.
            'targets' => ['nullable', 'array', 'max:50'],
            'targets.*.chat_id' => ['nullable', 'string', 'max:100', 'regex:/@(g\.us|c\.us|s\.whatsapp\.net)$/'],
            'targets.*.label' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4000'],
            'holiday_message' => ['nullable', 'string', 'max:4000'],
            'frequency' => ['required', 'in:'.implode(',', ScheduledNotification::FREQUENCIES)],
            'time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            'days_of_week' => ['required_if:frequency,weekly', 'array', 'max:7'],
            'days_of_week.*' => ['integer', 'between:1,7'],

            'monthly_mode' => ['nullable', 'in:'.implode(',', ScheduledNotification::MONTHLY_MODES)],

            'dates_of_month' => ['array', 'max:31'],
            'dates_of_month.*' => ['integer', 'between:1,31'],
            'weeks_of_month' => ['array', 'max:5'],            'weeks_of_month.*' => ['integer', 'between:1,5'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chat_id.regex' => 'Chat ID harus diakhiri @g.us (grup) atau @c.us (pribadi).',
            'targets.*.chat_id.regex' => 'Chat ID harus diakhiri @g.us (grup) atau @c.us (pribadi).',
            'days_of_week.required_if' => 'Pilih minimal satu hari untuk jadwal mingguan/bulanan.',
            'dates_of_month.required_if' => 'Isi minimal satu tanggal untuk jadwal bulanan (mode tanggal).',
            'time.date_format' => 'Jam harus berformat HH:MM, contoh 15:30.',
        ];
    }

    /**
     * Normalisasi input sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $days = $this->intList($this->input('days_of_week'));
        $dates = $this->intList($this->input('dates_of_month'));
        $weeks = $this->intList($this->input('weeks_of_month'));

        $this->merge([
            'days_of_week' => $days,
            'dates_of_month' => $dates,
            'weeks_of_month' => $weeks,
            'monthly_mode' => $this->input('monthly_mode') ?: 'date',
            'is_active' => $this->boolean('is_active'),
            'chat_id' => trim((string) $this->input('chat_id')),
            'targets' => $this->normalizeTargets(),
        ]);
    }

    /**
     * Terima array `targets[]` (form baru) maupun teks multi-baris/berkoma
     * (mis. "1203@g.us, 62812@c.us") dari pemanggil lama.
     *
     * @return array<int, array{chat_id: string, label: string}>
     */
    private function normalizeTargets(): array
    {
        $raw = $this->input('targets');

        if (is_string($raw)) {
            $raw = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $raw = array_map(fn ($chatId) => ['chat_id' => $chatId], $raw);
        }

        if (! is_array($raw)) {
            $raw = [];
        }

        $targets = [];

        foreach ($raw as $item) {
            if (is_string($item)) {
                $item = ['chat_id' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $chatId = trim((string) ($item['chat_id'] ?? ''));
            $label = trim((string) ($item['label'] ?? ''));

            // Baris kosong ditambahkan di form; abaikan tanpa memicu error.
            if ($chatId === '' && $label === '') {
                continue;
            }

            $targets[] = ['chat_id' => $chatId, 'label' => $label];
        }

        // Pemanggil lama yang hanya mengirim chat_id tunggal.
        $single = trim((string) $this->input('chat_id'));

        if ($targets === [] && $single !== '') {
            $targets[] = ['chat_id' => $single, 'label' => trim((string) $this->input('chat_label'))];
        }

        return $targets;
    }

    /**
     * Terima array maupun teks "1,15" dari form.
     *
     * @return array<int, int>
     */
    private function intList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $numbers = array_values(array_unique(array_map(
            fn ($item) => (int) trim((string) $item),
            $value,
        )));

        sort($numbers);

        return $numbers;
    }

    /**
     * Aturan lintas-field yang tidak bisa ditangani `rules()` sederhana.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $frequency = (string) $this->input('frequency');
            $mode = (string) $this->input('monthly_mode');

            $targets = $this->normalizeTargets();

            if ($targets === []) {
                $validator->errors()->add('targets', 'Isi minimal satu chat tujuan.');
            }

            $seen = [];

            foreach ($targets as $index => $target) {
                $chatId = (string) $target['chat_id'];

                if (isset($seen[$chatId])) {
                    $validator->errors()->add(
                        "targets.{$index}.chat_id",
                        'Chat ID ini sudah ada di daftar tujuan.',
                    );
                }

                $seen[$chatId] = true;
            }

            if ($frequency === 'weekly' && $this->intList($this->input('days_of_week')) === []) {
                $validator->errors()->add('days_of_week', 'Pilih minimal satu hari.');
            }

            if ($frequency === 'monthly' && $mode === 'date' && $this->intList($this->input('dates_of_month')) === []) {
                $validator->errors()->add('dates_of_month', 'Isi minimal satu tanggal.');
            }

            if ($frequency === 'monthly' && $mode === 'week') {
                if ($this->intList($this->input('weeks_of_month')) === []) {
                    $validator->errors()->add('weeks_of_month', 'Pilih minimal satu minggu ke berapa.');
                }

                if ($this->intList($this->input('days_of_week')) === []) {
                    $validator->errors()->add('days_of_week', 'Pilih minimal satu hari.');
                }
            }

            $template = new MessageTemplate;

            foreach (['message' => 'Pesan latihan', 'holiday_message' => 'Pesan libur'] as $field => $label) {
                $unknown = $template->unknownVariables((string) $this->input($field));

                if ($unknown !== []) {
                    $validator->errors()->add(
                        $field,
                        $label.' memakai variabel tidak dikenal: {'.implode('}, {', $unknown).'}. '.
                        'Variabel yang tersedia: {'.implode('}, {', MessageTemplate::VARIABLES).'}.',
                    );
                }
            }
        });
    }

    /**
     * Payload siap disimpan (tanpa `code`).
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();

        $targets = $this->normalizeTargets();
        $primary = $targets[0] ?? ['chat_id' => '', 'label' => ''];

        return [
            'name' => $data['name'],
            'chat_id' => $primary['chat_id'],
            'chat_label' => $primary['label'] !== '' ? $primary['label'] : null,
            'targets' => $targets,
            'message' => $data['message'],
            'holiday_message' => ($data['holiday_message'] ?? null) ?: null,
            'frequency' => $data['frequency'],
            'time' => $data['time'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'days_of_week' => $data['days_of_week'] ?? [],
            'monthly_mode' => $data['monthly_mode'] ?? 'date',
            'dates_of_month' => $data['dates_of_month'] ?? null,
            'weeks_of_month' => $data['weeks_of_month'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}

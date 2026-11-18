<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notification\Channels\WhatsAppChannel;
use App\Services\Notification\Reply\ActorIdentity;
use App\Services\Notification\Reply\HolidayFlagService;
use App\Services\Notification\Reply\HolidayTargetResolver;
use App\Services\Notification\Reply\ReplyResponder;
use App\Services\Notification\Reply\WaReplyCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menerima event `message` dari WAHA lalu memproses balasan admin yang
 * menandai sesi latihan sebagai libur.
 *
 * Alur (revisi setelah uji produksi):
 *   secret → HMAC → event `message` → bukan pesan bot → punya awalan /libur?
 *   (tidak = DIAM) → parse perintah → (tidak valid = kirim bantuan) →
 *   whitelist → cocokkan nomor baris dengan pesan yang dikutip → simpan →
 *   balas di grup.
 *
 * Prinsip: bot hanya bicara bila admin menulis perintah `/libur`. Obrolan
 * biasa, termasuk balasan bebas ke pesan pengingat, diabaikan tanpa balasan
 * apa pun.
 *
 * Controller ini tidak pernah melempar error ke WAHA: WAHA akan mengulang
 * (retry) setiap respons non-2xx, dan pengulangan itu tidak diinginkan untuk
 * kesalahan yang sifatnya permanen (mis. format salah).
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppChannel $whatsApp,
        private readonly WaReplyCommand $command,
        private readonly HolidayTargetResolver $resolver,
        private readonly HolidayFlagService $flagService,
        private readonly ReplyResponder $responder,
    ) {}

    public function __invoke(Request $request, string $secret): JsonResponse
    {
        if (! (bool) config('notifications.reply.enabled', true)) {
            return $this->ignored('reply_disabled');
        }

        $expected = (string) config('notifications.whatsapp.webhook_secret');

        if ($expected === '' || ! hash_equals($expected, $secret)) {
            return response()->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        if (! $this->validSignature($request)) {
            return response()->json(['ok' => false, 'error' => 'invalid_signature'], 403);
        }

        $payload = (array) $request->json()->all();
        $event = (string) ($payload['event'] ?? '');

        if ($event !== 'message') {
            return $this->ignored('event_ignored');
        }

        $message = (array) ($payload['payload'] ?? []);

        if (($message['fromMe'] ?? false) === true) {
            return $this->ignored('from_me');
        }

        $body = trim((string) ($message['body'] ?? ''));

        if ($body === '') {
            return $this->ignored('empty_body');
        }

        // Tanpa awalan /libur, pesan apa pun (obrolan, balasan bebas ke pesan
        // bot, format nyaris benar) dianggap pesan biasa: diabaikan diam-diam.
        if (! $this->command->hasPrefix($body)) {
            return $this->ignored('not_a_command');
        }

        $batch = $this->command->parseBatch($body);

        if (! $this->isIdempotentFirstSeen((string) ($message['id'] ?? ''))) {
            return $this->ignored('duplicate');
        }

        $chatId = (string) ($message['from'] ?? '');

        if ($chatId === '') {
            return $this->ignored('missing_chat');
        }

        if (! $batch->ok) {
            // Admin menulis /libur tetapi isinya belum lengkap → bantu perbaiki.
            $this->send($chatId, $this->responder->forParseError(
                $batch->errorReply(),
                $batch->blockNumber(),
                $batch->blockCount(),
            ));

            return $this->handled('invalid_format', $batch->error);
        }

        $pushName = $this->pushName($message);
        $candidates = ActorIdentity::candidates($message);

        if (! ActorIdentity::anyAllowed($candidates, $this->allowedUsers())) {
            Log::channel('cron')->warning('Libur via WhatsApp ditolak: pengirim tidak berwenang', [
                'chat' => $chatId,
                'candidates' => $candidates,
                'push_name' => $pushName,
            ]);

            $reply = $this->responder->unauthorized(ActorIdentity::preferred($candidates), $pushName);
            $this->send($chatId, $reply['text'], $reply['mentions']);

            return $this->handled('unauthorized');
        }

        $quotedText = (string) ($message['replyTo']['body'] ?? '');

        if ($quotedText === '') {
            $this->send($chatId, $this->responder->missingQuote());

            return $this->handled('not_reply');
        }

        $actorId = (string) (ActorIdentity::preferred($candidates) ?? '');
        $succeeded = [];
        $failed = [];
        $lastAction = null;

        // Setiap nomor diproses sendiri-sendiri: satu nomor gagal tidak
        // membatalkan nomor lain. Semua hasil dilaporkan dalam SATU balasan.
        foreach ($batch->items as $parsed) {
            $number = (int) $parsed->number;
            $resolution = $this->resolver->resolve($quotedText, $number);

            if (! $resolution->ok || $resolution->target === null) {
                $failed[] = [
                    'number' => $number,
                    'message' => $this->responder->forResolveError($resolution, $number),
                    'status' => 'resolve_failed',
                    'code' => (string) $resolution->error,
                ];

                continue;
            }

            $outcome = $this->flagService->flag(
                $resolution->target,
                (string) $parsed->status,
                (string) $parsed->reason,
                $actorId,
            );

            if (! $outcome->ok) {
                $failed[] = [
                    'number' => $number,
                    'message' => $this->responder->forFlagError($outcome, $resolution->target),
                    'status' => 'flag_rejected',
                    'code' => (string) $outcome->error,
                ];

                continue;
            }

            $succeeded[] = [
                'number' => $number,
                'target' => $resolution->target,
                'statusLabel' => (string) $parsed->statusLabel,
                'reason' => (string) $parsed->reason,
                'truncated' => $parsed->truncated,
                'unchanged' => $outcome->action === 'unchanged',
            ];

            $lastAction = (string) $outcome->action;
        }

        $this->send($chatId, $this->responder->results($succeeded, $failed));

        if ($succeeded === []) {
            // Satu nomor tetap memakai status lama (kontrak webhook tidak berubah).
            if ($batch->count() === 1) {
                return $this->handled($failed[0]['status'], $failed[0]['code']);
            }

            return $this->handled('all_failed', $batch->count().' perintah');
        }

        $detail = $batch->count() === 1 ? (string) $lastAction : $batch->count().' perintah';

        return $this->handled('flagged', $detail);
    }

    /**
     * @return array<int, string>
     */
    private function allowedUsers(): array
    {
        return (array) config('notifications.reply.allowed_users', []);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function pushName(array $message): ?string
    {
        foreach (['_data.notifyName', '_data.pushName', 'notifyName', 'pushName'] as $path) {
            $value = data_get($message, $path);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function validSignature(Request $request): bool
    {
        $key = (string) config('notifications.whatsapp.webhook_hmac_key');

        if ($key === '') {
            return true;
        }

        $signature = (string) $request->header('X-Webhook-Hmac', '');

        if ($signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $request->getContent(), $key), $signature);
    }

    /**
     * WAHA mengulang webhook bila respons bukan 2xx; kunci ini mencegah satu
     * balasan diproses dua kali saat WAHA mengirim ulang event yang sama.
     */
    private function isIdempotentFirstSeen(string $messageId): bool
    {
        if ($messageId === '') {
            return true;
        }

        return Cache::add('wa-holiday-reply:'.sha1($messageId), true, now()->addMinutes(10));
    }

    /**
     * @param  array<int, string>  $mentions
     */
    private function send(string $chatId, string $text, array $mentions = []): void
    {
        try {
            $result = $this->whatsApp->sendTo($chatId, $text, $mentions);

            if (! $result->success) {
                Log::channel('cron')->error('Balasan libur gagal dikirim', [
                    'chat' => $chatId,
                    'error' => $result->error,
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('cron')->error('Balasan libur gagal dikirim (exception)', [
                'chat' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ignored(string $reason): JsonResponse
    {
        return response()->json(['ok' => true, 'ignored' => $reason]);
    }

    private function handled(string $status, ?string $detail = null): JsonResponse
    {
        return response()->json(array_filter([
            'ok' => true,
            'status' => $status,
            'detail' => $detail,
        ]));
    }
}

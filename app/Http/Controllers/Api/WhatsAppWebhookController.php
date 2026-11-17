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
 * Alur (lihat docs/rencana-implementasi-libur-via-balasan-wa.md):
 *   secret → HMAC → event `message` → bukan pesan bot → whitelist →
 *   parse perintah → cocokkan nomor baris dengan pesan yang dikutip →
 *   simpan → balas di grup.
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

        if (! $this->isIdempotentFirstSeen((string) ($message['id'] ?? ''))) {
            return $this->ignored('duplicate');
        }

        $chatId = (string) ($message['from'] ?? '');

        if ($chatId === '') {
            return $this->ignored('missing_chat');
        }

        $actorId = (string) ($message['participant'] ?? $message['from'] ?? '');
        $pushName = isset($message['_data']['notifyName']) ? (string) $message['_data']['notifyName'] : null;
        $quotedText = (string) ($message['replyTo']['body'] ?? '');

        $isReplyToBot = $this->resolver->reminderLines($quotedText) !== []
            || (bool) preg_match('/^\*Absensi Latihan\*/mu', $quotedText);

        // Obrolan biasa di grup: jangan diganggu.
        if (! $isReplyToBot && ! $this->command->looksLikeCommand($body)) {
            return $this->ignored('not_a_command');
        }

        if (! ActorIdentity::isAllowed($actorId, $this->allowedUsers())) {
            Log::channel('cron')->warning('Libur via WhatsApp ditolak: pengirim tidak berwenang', [
                'actor' => $actorId,
                'chat' => $chatId,
            ]);

            $reply = $this->responder->unauthorized($actorId, $pushName);
            $this->send($chatId, $reply['text'], $reply['mentions']);

            return $this->handled('unauthorized');
        }

        if ($quotedText === '') {
            $this->send($chatId, $this->responder->notReplyingToNotification());

            return $this->handled('not_reply');
        }

        $parsed = $this->command->parse($body);

        if (! $parsed->ok) {
            $this->send($chatId, $this->responder->forParseError($parsed));

            return $this->handled('invalid_format', $parsed->error);
        }

        $resolution = $this->resolver->resolve($quotedText, (int) $parsed->number);

        if (! $resolution->ok || $resolution->target === null) {
            $this->send($chatId, $this->responder->forResolveError($resolution, (int) $parsed->number));

            return $this->handled('resolve_failed', $resolution->error);
        }

        $outcome = $this->flagService->flag(
            $resolution->target,
            (string) $parsed->status,
            (string) $parsed->reason,
            $actorId,
        );

        if (! $outcome->ok) {
            $this->send($chatId, $this->responder->forFlagError($outcome, $resolution->target));

            return $this->handled('flag_rejected', $outcome->error);
        }

        $this->send($chatId, $this->responder->success(
            $resolution->target,
            (string) $parsed->statusLabel,
            (string) $parsed->reason,
            $parsed->truncated,
            $outcome->action === 'unchanged',
        ));

        return $this->handled('flagged', $outcome->action);
    }

    /**
     * @return array<int, string>
     */
    private function allowedUsers(): array
    {
        return (array) config('notifications.reply.allowed_users', []);
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

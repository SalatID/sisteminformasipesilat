<?php

namespace App\Services\Notification;

class ChannelResult
{
    public function __construct(
        public readonly string $provider,
        public readonly bool $success,
        public readonly ?int $httpStatus = null,
        public readonly ?string $error = null,
        public readonly ?string $externalId = null,
    ) {
    }

    public static function success(string $provider, ?int $httpStatus = 200, ?string $externalId = null): self
    {
        return new self($provider, true, $httpStatus, null, $externalId);
    }

    public static function failure(string $provider, ?int $httpStatus = null, ?string $error = null): self
    {
        return new self($provider, false, $httpStatus, $error);
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'success' => $this->success,
            'http_status' => $this->httpStatus,
            'error' => $this->error,
            'external_id' => $this->externalId,
        ];
    }
}

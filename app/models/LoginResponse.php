<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Login response DTO.
 */
final class LoginResponse
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly string $id,
        public readonly string $username,
        public readonly string $token
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return new self(
            (string) ($payload['code'] ?? ''),
            (string) ($payload['message'] ?? ''),
            (string) ($data['id'] ?? ''),
            (string) ($data['username'] ?? ''),
            (string) ($data['token'] ?? '')
        );
    }
}

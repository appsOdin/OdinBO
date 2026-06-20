<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article stock DTO.
 */
final class Stock
{
    public function __construct(
        public readonly string $NAME,
        public readonly float $AVAILABLE
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['NAME'] ?? ''),
            (float) ($data['AVAILABLE'] ?? 0)
        );
    }
}

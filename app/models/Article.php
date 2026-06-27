<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article DTO.
 */
final class Article
{
    public function __construct(
        public readonly string $ID,
        public readonly string $DESCRIPTION,
        public readonly float $PRICE,
        public readonly string $NOTAS
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['ID'] ?? ''),
            (string) ($data['DESCRIPTION'] ?? ''),
            (float) ($data['PRICE'] ?? 0),
            (string) ($data['NOTAS'] ?? '')
        );
    }
}

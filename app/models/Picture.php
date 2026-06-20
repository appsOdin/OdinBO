<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article picture DTO.
 */
final class Picture
{
    public function __construct(
        public readonly string $picture,
        public readonly string $ext
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $picture = (string) ($data['picture'] ?? $data['PICTURE'] ?? $data['Picture'] ?? '');
        $ext = (string) ($data['ext'] ?? $data['EXT'] ?? $data['Ext'] ?? '');

        return new self(
            trim($picture),
            trim($ext)
        );
    }

    public function toDataUri(): string
    {
        if ($this->picture === '' || $this->ext === '') {
            return '';
        }

        $normalizedExt = ltrim(strtolower($this->ext), '.');
        $mimeByExt = [
            'jpg' => 'jpeg',
            'jpeg' => 'jpeg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            'bmp' => 'bmp',
            'svg' => 'svg+xml',
            'svg+xml' => 'svg+xml',
        ];

        $mime = $mimeByExt[$normalizedExt] ?? $normalizedExt;

        return 'data:image/' . $mime . ';base64,' . preg_replace('/\s+/', '', $this->picture);
    }
}

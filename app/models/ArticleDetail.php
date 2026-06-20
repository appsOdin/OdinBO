<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article detail DTO.
 */
final class ArticleDetail
{
    /**
     * @param array<int, Picture> $Pictures
     * @param array<int, Stock> $Stocks
     */
    public function __construct(
        public readonly ?Article $Article,
        public readonly array $Pictures,
        public readonly array $Stocks
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $articleRows = is_array($payload['atricle'] ?? null)
            ? $payload['atricle']
            : (is_array($payload['article'] ?? null)
                ? $payload['article']
                : (is_array($payload['Article'] ?? null)
                    ? $payload['Article']
                    : []));

        $firstArticleRow = is_array($articleRows[0] ?? null) ? $articleRows[0] : null;

        $picturesRows = is_array($payload['pictures'] ?? null)
            ? $payload['pictures']
            : (is_array($payload['Pictures'] ?? null) ? $payload['Pictures'] : []);
        $pictures = array_map(
            static fn (array $row): Picture => Picture::fromArray($row),
            array_values(array_filter($picturesRows, static fn ($row): bool => is_array($row)))
        );

        $stocksRows = is_array($payload['stocks'] ?? null)
            ? $payload['stocks']
            : (is_array($payload['Stocks'] ?? null) ? $payload['Stocks'] : []);
        $stocks = array_map(
            static fn (array $row): Stock => Stock::fromArray($row),
            array_values(array_filter($stocksRows, static fn ($row): bool => is_array($row)))
        );

        return new self(
            $firstArticleRow !== null ? Article::fromArray($firstArticleRow) : null,
            $pictures,
            $stocks
        );
    }
}

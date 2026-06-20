<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Article use-cases.
 */
final class ArticleService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllArticles(string $search = ''): array
    {
        return $this->apiService->post('/api/Article/GetAllArticles', [
            'search' => $search,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getArticleDetail(string $articleId): array
    {
        return $this->apiService->post('/api/Article/GetArticleDetail', [
            'search' => $articleId,
        ]);
    }
}

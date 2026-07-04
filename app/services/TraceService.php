<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Consumes trace-related API endpoints.
 */
final class TraceService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrace(int $page, int $reg): array
    {
        return $this->apiService->getWithBody('/api/Trace/GetTrace', [
            'page' => $page,
            'reg'  => $reg,
        ]);
    }
}

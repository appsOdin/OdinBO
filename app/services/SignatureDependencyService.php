<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Signature dependency management use-cases.
 */
final class SignatureDependencyService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->apiService->get('/api/Signature_Dependency/GetAllSignatureDependency');
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsers(): array
    {
        return $this->apiService->get('/api/Signature_Dependency/GetAllUsersSignatureDependency');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function add(array $payload): array
    {
        return $this->apiService->post('/api/Signature_Dependency/AddSignatureDependency', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(array $payload): array
    {
        return $this->apiService->put('/api/Signature_Dependency/UpdateSignatureDependency', $payload);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

/**
 * User use-cases.
 */
final class UserService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllUsers(): array
    {
        return $this->apiService->get('/api/User/GetAllUsers');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createUser(array $payload): array
    {
        return $this->apiService->post('/api/User/CreateUser', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateUser(array $payload): array
    {
        return $this->apiService->put('/api/User/UpdateUser', $payload);
    }
}

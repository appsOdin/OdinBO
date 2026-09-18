<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Role/permission use-cases.
 */
final class PermissionService
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getScreenModules(): array
    {
        return $this->apiService->get('/api/Permission/getScreenModules');
    }

    /**
     * @return array<string, mixed>
     */
    public function getPermissionsByScreen(string $keyScreen, int $roleId): array
    {
        return $this->apiService->get('/api/Permission/getPermissionByScreen/' . rawurlencode($keyScreen) . '/' . $roleId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function addRolePermission(array $payload): array
    {
        return $this->apiService->post('/api/Permission/AddRole_Permission', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllRolePermissions(): array
    {
        return $this->apiService->get('/api/Permission/getAllRole_Permission');
    }

    /**
     * @return array<string, mixed>
     */
    public function deletePermission(int $roleId, string $permissionKey): array
    {
        return $this->apiService->delete('/api/Permission/DeletPermission/' . $roleId . '/' . rawurlencode($permissionKey));
    }
}

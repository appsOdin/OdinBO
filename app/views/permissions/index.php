<?php
/** @var array<int, array<string, mixed>> $rolePermissions */
$rolePermissions = $rolePermissions ?? [];
$apiMessage = $apiMessage ?? '';
$apiHttpCode = $apiHttpCode ?? 200;
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Ver permisos</h2>
        <p class="text-muted m-0">Permisos asignados por rol.</p>
    </div>
</section>

<?php if ($apiHttpCode !== 200 && $apiHttpCode !== 403 && $apiMessage !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($apiMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="text" id="searchRolePermissions" class="form-control" placeholder="Buscar por rol o permiso">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="rolePermissionsTable">
                <thead>
                <tr>
                    <th>Rol</th>
                    <th>Permisos</th>
                    <th style="width:120px;">Cantidad</th>
                </tr>
                </thead>
                <tbody id="rolePermissionsTableBody">
                <?php foreach ($rolePermissions as $entry): ?>
                    <?php
                    $role = is_array($entry['role'] ?? null) ? $entry['role'] : [];
                    $roleName = (string) ($role['rolename'] ?? '');
                    $permissions = is_array($entry['permissions'] ?? null) ? $entry['permissions'] : [];
                    $searchTextParts = [$roleName];
                    foreach ($permissions as $permission) {
                        $searchTextParts[] = (string) ($permission['key_name'] ?? '');
                        $searchTextParts[] = (string) ($permission['permission_name'] ?? '');
                    }
                    $searchText = strtolower(implode(' ', $searchTextParts));
                    $roleId = (string) ($role['id'] ?? '');
                    ?>
                    <tr data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" data-role="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?>" data-role-id="<?= htmlspecialchars($roleId, ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="permissions-cell">
                            <?php if ($permissions === []): ?>
                            <span class="text-muted no-permissions-text">Sin permisos</span>
                            <?php endif; ?>
                            <?php foreach ($permissions as $permission): ?>
                            <?php
                                $keyName = (string) ($permission['key_name'] ?? '');
                                $permissionName = (string) ($permission['permission_name'] ?? '');
                            ?>
                            <span class="badge text-bg-secondary me-1 mb-1 permission-badge d-inline-flex align-items-center gap-1"
                                  data-key-name="<?= htmlspecialchars($keyName, ENT_QUOTES, 'UTF-8') ?>"
                                  data-permission-name="<?= htmlspecialchars($permissionName, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($permissionName, ENT_QUOTES, 'UTF-8') ?>
                                <button type="button"
                                        class="btn-delete-permission"
                                        data-permission-key="<?= htmlspecialchars($keyName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-permission-name="<?= htmlspecialchars($permissionName, ENT_QUOTES, 'UTF-8') ?>"
                                        title="Eliminar permiso"
                                        aria-label="Eliminar permiso"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;background:#dc3545;color:#fff;border:none;font-size:10px;line-height:1;padding:0;">
                                    &times;
                                </button>
                            </span>
                            <?php endforeach; ?>
                        </td>
                        <td class="permission-count"><?= count($permissions) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rolePermissions === []): ?>
                    <tr id="rolePermissionsEmptyRow">
                        <td colspan="3" class="text-center text-muted py-4">No hay registros para mostrar.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="rolePermissionsPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="rolePermissionsPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="rolePermissionsPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="rolePermissionsPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="rolePermissionsPageIndicator">Pagina 1 de 1</small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="rolePermissionsNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
        </div>
    </div>
</div>

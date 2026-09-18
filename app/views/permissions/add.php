<?php
/** @var array<int, array<string, mixed>> $roles */
/** @var array<int, array<string, mixed>> $modules */
$roles = $roles ?? [];
$modules = $modules ?? [];
$apiMessage = $apiMessage ?? '';
$apiHttpCode = $apiHttpCode ?? 200;
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Agregar permisos</h2>
        <p class="text-muted m-0">Asigne permisos a un rol segun el modulo y la pantalla.</p>
    </div>
</section>

<?php if ($apiHttpCode !== 200 && $apiHttpCode !== 403 && $apiMessage !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($apiMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="permissionRole" class="form-label">Rol</label>
                <select id="permissionRole" class="form-select">
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars((string) ($role['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($role['rolename'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="permissionModule" class="form-label">Modulo</label>
                <select id="permissionModule" class="form-select">
                    <option value="">Seleccione un modulo</option>
                    <?php foreach ($modules as $module): ?>
                    <option value="<?= htmlspecialchars((string) ($module['key_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($module['module_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="permissionScreen" class="form-label">Pantalla</label>
                <select id="permissionScreen" class="form-select">
                    <option value="">Seleccione una pantalla</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" id="btnSearchPermissions" class="btn btn-primary w-100">Buscar</button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" id="permissionsResultCard" style="display:none;">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle" id="permissionsTable">
                <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th>Key Name</th>
                    <th>Permiso</th>
                </tr>
                </thead>
                <tbody id="permissionsTableBody"></tbody>
            </table>
        </div>
        <div id="permissionsEmptyMessage" class="text-muted text-center py-3" style="display:none;">
            No hay permisos disponibles para esta pantalla.
        </div>
        <button type="button" id="btnSavePermissions" class="btn btn-success mt-2">Guardar</button>
    </div>
</div>

<script>
    window.APP_PERMISSIONS_MODULES = <?= json_encode($modules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

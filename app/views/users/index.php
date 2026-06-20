<?php
/** @var array<int, \App\Models\User> $users */
$users = $users ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Usuarios</h2>
        <p class="text-muted m-0">Gestion completa de usuarios.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Nuevo Usuario</button>
</section>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="text" id="searchUsers" class="form-control" placeholder="Buscar por nombre, apellido, usuario o correo">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="usersTable">
                <thead>
                <tr>
                    <th data-sort="name">Nombre</th>
                    <th data-sort="lastname">Apellido</th>
                    <th data-sort="username">Usuario</th>
                    <th data-sort="email">Correo</th>
                    <th data-sort="rolename">Rol</th>
                    <th data-sort="state">Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody id="usersTableBody">
                <?php foreach ($users as $user): ?>
                    <tr
                        data-id="<?= htmlspecialchars($user->id, ENT_QUOTES, 'UTF-8') ?>"
                        data-name="<?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>"
                        data-lastname="<?= htmlspecialchars($user->lastname, ENT_QUOTES, 'UTF-8') ?>"
                        data-username="<?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?>"
                        data-email="<?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?>"
                        data-role="<?= htmlspecialchars($user->rolename, ENT_QUOTES, 'UTF-8') ?>"
                        data-roleid="<?= $user->rolename === 'ADMIN' ? 1 : ($user->rolename === 'USER' ? 2 : 3) ?>"
                        data-state="<?= $user->state ?>"
                    >
                        <td><?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user->lastname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($user->rolename, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($user->state === 1): ?>
                                <span class="badge text-bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary btn-edit-user" type="button">Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="paginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="usersPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="usersPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="usersPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="usersPageIndicator">Pagina 1 de 1</small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="usersNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/create-modal.php'; ?>
<?php require __DIR__ . '/edit-modal.php'; ?>

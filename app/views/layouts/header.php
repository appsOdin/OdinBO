<?php
/** @var array{id: string, username: string}|null $authUser */
$authUser = $authUser ?? null;
?>
<nav class="navbar navbar-expand-lg topbar border-bottom">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none" id="btnToggleSidebar" type="button">Menu</button>
        <div class="ms-auto d-flex align-items-center gap-2 topbar-actions">
            <label for="themeSelect" class="text-muted small m-0 d-none d-sm-inline">Tema</label>
            <select id="themeSelect" class="form-select form-select-sm theme-select" aria-label="Seleccion de tema">
                <option value="default">Default</option>
                <option value="flat">Flat</option>
                <option value="dark">Dark</option>
            </select>
            <span class="text-muted small d-none d-md-inline">Usuario autenticado</span>
            <span class="badge text-bg-primary topbar-user-badge" title="<?= htmlspecialchars((string) ($authUser['username'] ?? 'Invitado'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($authUser['username'] ?? 'Invitado'), ENT_QUOTES, 'UTF-8') ?></span>
            <form method="POST" action="<?= base_url('logout') ?>" class="m-0">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-sm btn-danger topbar-logout-btn" type="submit">Salir</button>
            </form>
        </div>
    </div>
</nav>

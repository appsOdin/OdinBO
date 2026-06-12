<?php
/** @var array{id: string, username: string}|null $authUser */
$authUser = $authUser ?? null;
?>
<nav class="navbar navbar-expand-lg topbar border-bottom">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none" id="btnToggleSidebar" type="button">Menu</button>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-muted small">Usuario autenticado</span>
            <span class="badge text-bg-primary"><?= htmlspecialchars((string) ($authUser['username'] ?? 'Invitado'), ENT_QUOTES, 'UTF-8') ?></span>
            <form method="POST" action="<?= base_url('logout') ?>" class="m-0">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-sm btn-danger" type="submit">Salir</button>
            </form>
        </div>
    </div>
</nav>

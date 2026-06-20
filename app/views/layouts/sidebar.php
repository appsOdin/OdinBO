<aside class="sidebar" id="sidebarNav">
    <div class="sidebar-brand px-3 py-4">
        <h5 class="m-0">OdinBO</h5>
        <small>Panel de Control</small>
    </div>
    <ul class="nav flex-column px-2 gap-1">
        <li class="nav-item">
            <a href="<?= base_url('dashboard') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/dashboard') ? 'active' : '' ?>">Dashboard</a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('users') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/users') ? 'active' : '' ?>">Usuarios</a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('articles') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/articles') ? 'active' : '' ?>">Articulos</a>
        </li>
    </ul>
</aside>

<?php
$rolename = (string) ($authUser['rolename'] ?? '');
$menuOptions = match ($rolename) {
    'ADMIN' => MENU_OPTIONS_ADMIN,
    'USER' => MENU_OPTIONS_USER,
    'SUBSCRIBER' => MENU_OPTIONS_SUBSCRIBER,
    default => MENU_OPTIONS_USER,
};
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="sidebar" id="sidebarNav">
    <div class="sidebar-brand px-3 py-4">
        <h5 class="m-0">OdinBO</h5>
        <small>Panel de Control</small>
    </div>
    <ul class="nav flex-column px-2 gap-1">
        <?php foreach ($menuOptions as $item): ?>
        <li class="nav-item">
            <a href="<?= base_url(htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8')) ?>"
               class="nav-link <?= str_contains($currentUri, '/' . $item['path']) ? 'active' : '' ?>">
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</aside>

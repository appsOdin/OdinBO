<?php
$rolename = (string) ($authUser['rolename'] ?? '');
$menuOptions = match ($rolename) {
    'ADMIN' => MENU_OPTIONS_ADMIN,
    'USER' => MENU_OPTIONS_USER,
    'SUBSCRIBER' => MENU_OPTIONS_SUBSCRIBER,
    default => MENU_OPTIONS_USER,
};
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

$isActivePath = static function (string $path) use ($currentUri): bool {
    return $path !== '' && str_contains($currentUri, '/' . $path);
};

$collapseIndex = 0;
?>
<aside class="sidebar" id="sidebarNav">
    <div class="sidebar-brand px-3 py-4">
        <h5 class="m-0">OdinBO</h5>
        <small>Panel de Control</small>
    </div>
    <nav class="sidebar-nav px-2">
        <?php foreach ($menuOptions as $item): ?>
        <?php $children = is_array($item['children'] ?? null) ? $item['children'] : []; ?>
        <?php if ($children !== []): ?>
        <?php
            $collapseId = 'sidebarCollapse' . $collapseIndex++;
            $isParentActive = false;
            foreach ($children as $child) {
                if ($isActivePath((string) ($child['path'] ?? ''))) {
                    $isParentActive = true;
                    break;
                }
            }
        ?>
        <div class="sidebar-group">
            <a href="#<?= $collapseId ?>"
               class="sidebar-group-toggle nav-link d-flex align-items-center justify-content-between <?= $isParentActive ? 'active' : '' ?>"
               data-bs-toggle="collapse"
               aria-expanded="<?= $isParentActive ? 'true' : 'false' ?>"
               aria-controls="<?= $collapseId ?>">
                <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="sidebar-group-hint">
                    <span class="sidebar-group-hint-text"><?= $isParentActive ? 'contraer' : 'expandir' ?></span>
                    <i class="sidebar-arrow"></i>
                </span>
            </a>
            <div class="collapse <?= $isParentActive ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <ul class="nav flex-column sidebar-submenu">
                    <?php foreach ($children as $child): ?>
                    <?php $childPath = (string) ($child['path'] ?? ''); ?>
                    <li class="nav-item">
                        <a href="<?= base_url(htmlspecialchars($childPath, ENT_QUOTES, 'UTF-8')) ?>"
                           class="nav-link sidebar-submenu-link <?= $isActivePath($childPath) ? 'active' : '' ?>">
                            <span class="sidebar-submenu-dot"></span>
                            <?= htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <?php $path = (string) ($item['path'] ?? ''); ?>
        <a href="<?= base_url(htmlspecialchars($path, ENT_QUOTES, 'UTF-8')) ?>"
           class="nav-link <?= $isActivePath($path) ? 'active' : '' ?>">
            <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

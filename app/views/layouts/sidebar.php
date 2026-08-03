<?php
$rolename = (string) ($authUser['rolename'] ?? '');
$menuOptions = match ($rolename) {
    'SUPER' => MENU_OPTIONS_SUPER,
    'USER' => MENU_OPTIONS_USER,
    'ADMIN' => MENU_OPTIONS_ADMIN,
    'GUEST' => MENU_OPTIONS_GUEST,
    default => MENU_OPTIONS_GUEST,
};
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Collect all explicit menu paths to avoid false-positive parent matches
$allMenuPaths = [];
foreach ($menuOptions as $_item) {
    if (isset($_item['path'])) $allMenuPaths[] = $_item['path'];
    foreach (($_item['children'] ?? []) as $_child) {
        if (isset($_child['path'])) $allMenuPaths[] = $_child['path'];
    }
}

$isActivePath = static function (string $path) use ($currentUri, $allMenuPaths): bool {
    if ($path === '' || !str_contains($currentUri, '/' . $path)) return false;
    // If a more specific menu path also matches, let that one be active instead
    foreach ($allMenuPaths as $other) {
        if ($other !== $path && str_starts_with($other, $path . '/') && str_contains($currentUri, '/' . $other)) {
            return false;
        }
    }
    return true;
};

$collapseIndex = 0;
?>
<aside class="sidebar" id="sidebarNav">
    <div class="sidebar-brand px-3 py-4">
        <div class="d-flex align-items-start justify-content-between gap-2">
           <div>
                <!-- Agregar logo aquí -->
                <!-- <img src="<?php //base_url('assets/img/logo.png') ?>" alt="OdinBO" style="max-height:40px;">
                <small>Panel de Control</small> -->
            </div>
            <button class="btn btn-sm btn-outline-light d-lg-none sidebar-close-btn" id="btnCloseSidebar" type="button" aria-label="Cerrar menu lateral">
                Cerrar
            </button>
        </div>
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

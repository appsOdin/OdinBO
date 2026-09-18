<?php
use App\Services\ServiceFactory;

$menu = ServiceFactory::sessionManager()->getMenu();
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Collect all explicit menu paths to avoid false-positive parent matches
$allMenuPaths = [];
foreach ($menu as $_item) {
    $_itemType = strtoupper((string) ($_item['key_module_type'] ?? ''));
    $_children = is_array($_item['children'] ?? null) ? $_item['children'] : [];

    if ($_itemType === 'ITEM' && $_children === [] && isset($_item['path'])) {
        $allMenuPaths[] = (string) $_item['path'];
    }

    foreach ($_children as $_child) {
        if (isset($_child['path'])) $allMenuPaths[] = (string) $_child['path'];
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
        <?php foreach ($menu as $item): ?>
        <?php
            $itemType = strtoupper((string) ($item['key_module_type'] ?? ''));
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            $isGroup = $itemType === 'GROUP' && $children !== [];
        ?>
        <?php if ($isGroup): ?>
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
                <span><?= htmlspecialchars((string) ($item['module_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
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
                            <?= htmlspecialchars((string) ($child['screen_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php elseif ($itemType === 'ITEM'): ?>
        <?php $path = (string) ($item['path'] ?? ''); ?>
        <a href="<?= base_url(htmlspecialchars($path, ENT_QUOTES, 'UTF-8')) ?>"
           class="nav-link <?= $isActivePath($path) ? 'active' : '' ?>">
            <?= htmlspecialchars((string) ($item['module_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

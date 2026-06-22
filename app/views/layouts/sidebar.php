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
?>
<aside class="sidebar" id="sidebarNav">
    <div class="sidebar-brand px-3 py-4">
        <h5 class="m-0">OdinBO</h5>
        <small>Panel de Control</small>
    </div>
    <ul class="nav flex-column px-2 gap-1">
        <?php foreach ($menuOptions as $item): ?>
        <?php $children = is_array($item['children'] ?? null) ? $item['children'] : []; ?>
        <?php if ($children !== []): ?>
        <?php
            $isParentActive = false;
            foreach ($children as $child) {
                $childPath = (string) ($child['path'] ?? '');
                if ($isActivePath($childPath)) {
                    $isParentActive = true;
                    break;
                }
            }
        ?>
        <li class="nav-item">
            <div class="nav-link <?= $isParentActive ? 'active' : '' ?>" style="cursor: default;">
                <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <ul class="nav flex-column mt-1 ms-2 gap-1">
                <?php foreach ($children as $child): ?>
                <?php $childPath = (string) ($child['path'] ?? ''); ?>
                <li class="nav-item">
                    <a href="<?= base_url(htmlspecialchars($childPath, ENT_QUOTES, 'UTF-8')) ?>"
                       class="nav-link py-1 <?= $isActivePath($childPath) ? 'active' : '' ?>">
                        <?= htmlspecialchars((string) ($child['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php else: ?>
        <?php $path = (string) ($item['path'] ?? ''); ?>
        <li class="nav-item">
            <a href="<?= base_url(htmlspecialchars($path, ENT_QUOTES, 'UTF-8')) ?>"
               class="nav-link <?= $isActivePath($path) ? 'active' : '' ?>">
                <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</aside>

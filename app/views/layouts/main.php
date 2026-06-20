<?php
/** @var string $content */
/** @var array{id: string, username: string}|null $authUser */
/** @var array<int, array{type: string, message: string}> $flashMessages */
$flashMessages = $flashMessages ?? [];
$authUser = $authUser ?? null;
$appJsFile = dirname(__DIR__, 3) . '/public/assets/js/app.js';
$appJsVersion = is_file($appJsFile) ? (string) filemtime($appJsFile) : (string) time();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div id="globalLoader" class="global-loader d-none">
    <div class="spinner-border text-light" role="status" aria-hidden="true"></div>
</div>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="content-shell">
        <?php require __DIR__ . '/header.php'; ?>
        <main class="p-3 p-lg-4">
            <?= $content ?>
        </main>
        <?php require __DIR__ . '/footer.php'; ?>
    </div>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
<script>
window.APP = {
    csrfToken: '<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>',
    flashMessages: <?= json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    loginUrl: '<?= base_url('login') ?>',
    logoutUrl: '<?= base_url('logout') ?>',
    usersListUrl: '<?= base_url('users/list') ?>',
    usersStoreUrl: '<?= base_url('users/store') ?>',
    usersUpdateUrl: '<?= base_url('users/update') ?>',
    articlesListUrl: '<?= base_url('articles/list') ?>',
    articlesDetailUrl: '<?= base_url('articles/detail') ?>'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= base_url('assets/js/app.js?v=' . urlencode($appJsVersion)) ?>"></script>
</body>
</html>

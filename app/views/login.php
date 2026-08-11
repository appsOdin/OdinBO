<?php
/** @var string $csrfToken */
/** @var array<int, array{type: string, message: string}> $flashMessages */
$flashMessages = $flashMessages ?? [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OdinBO</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/radiator.png') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <script>
    (function () {
        var savedTheme = localStorage.getItem('odinbo-theme');
        if (savedTheme === 'flat' || savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-gradient"></div>
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card auth-card shadow border-0">
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="OdinBO" style="max-height:140px;">
            </div>

            <div id="loginRedirectMessageContainer"></div>

            <?php foreach ($flashMessages as $flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <form action="<?= base_url('login') ?>" method="POST" novalidate id="loginForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="deviceInfo" id="deviceInfo">
                <div class="mb-3">
                    <label class="form-label" for="username">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script>
(function () {
    function getDeviceType() {
        var ua = navigator.userAgent;
        if (/tablet|ipad|playbook|silk/i.test(ua)) return 'tablet';
        if (/mobile|iphone|ipod|android|blackberry|mini|windows\sce|palm/i.test(ua)) return 'mobile';
        return 'desktop';
    }

    function getOsName() {
        var ua = navigator.userAgent;
        if (/windows/i.test(ua)) return 'Windows';
        if (/macintosh|mac os x/i.test(ua)) return 'macOS';
        if (/linux/i.test(ua)) return 'Linux';
        if (/android/i.test(ua)) return 'Android';
        if (/iphone|ipad|ipod/i.test(ua)) return 'iOS';
        return 'Unknown';
    }

    function getBrowserInfo() {
        var ua = navigator.userAgent;
        var name = 'Unknown', version = '';
        var map = [
            { name: 'Edg', label: 'Edge' },
            { name: 'OPR', label: 'Opera' },
            { name: 'Chrome', label: 'Chrome' },
            { name: 'Firefox', label: 'Firefox' },
            { name: 'Safari', label: 'Safari' }
        ];
        for (var i = 0; i < map.length; i++) {
            var idx = ua.indexOf(map[i].name + '/');
            if (idx !== -1) {
                name = map[i].label;
                version = ua.substring(idx + map[i].name.length + 1).split(' ')[0].split('.')[0];
                break;
            }
        }
        return { name: name, version: version };
    }

    function collectDeviceInfo() {
        var browser = getBrowserInfo();
        return {
            deviceType: getDeviceType(),
            os: getOsName(),
            browser: browser.name,
            browserVersion: browser.version,
            screen: screen.width + 'x' + screen.height,
            viewport: window.innerWidth + 'x' + window.innerHeight,
            devicePixelRatio: window.devicePixelRatio || 1,
            touch: navigator.maxTouchPoints > 0,
            cpuCores: navigator.hardwareConcurrency || null,
            memoryGB: navigator.deviceMemory || null,
            language: navigator.language || '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
            online: navigator.onLine,
            userAgent: navigator.userAgent
        };
    }

    var form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function () {
            var field = document.getElementById('deviceInfo');
            if (field) {
                field.value = JSON.stringify(collectDeviceInfo());
            }
        });
    }
})();
</script>
<script>
(function () {
    var storageKey = 'odinbo-login-redirect-message';
    var container = document.getElementById('loginRedirectMessageContainer');

    if (!container) {
        return;
    }

    var raw = '';
    try {
        raw = sessionStorage.getItem(storageKey) || '';
    } catch (error) {
        raw = '';
    }

    if (raw === '') {
        return;
    }

    try {
        sessionStorage.removeItem(storageKey);
    } catch (error) {
        // Ignore cleanup failures.
    }

    var payload;
    try {
        payload = JSON.parse(raw);
    } catch (error) {
        payload = null;
    }

    var message = payload && typeof payload.message === 'string' ? payload.message.trim() : '';
    var type = payload && typeof payload.type === 'string' ? payload.type.trim() : 'danger';

    if (message === '') {
        return;
    }

    var allowedTypes = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
    if (allowedTypes.indexOf(type) === -1) {
        type = 'danger';
    }

    var alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alert.setAttribute('role', 'alert');

    var text = document.createTextNode(message);
    alert.appendChild(text);

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Close');
    alert.appendChild(close);

    container.appendChild(alert);
})();
</script>
</body>
</html>

<?php
/** @var string $content */
/** @var array{id: string, username: string}|null $authUser */
/** @var array<int, array{type: string, message: string}> $flashMessages */
/** @var int $apiHttpCode */
$flashMessages = $flashMessages ?? [];
$authUser = $authUser ?? null;
$apiHttpCode = $apiHttpCode ?? 200;
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
    <script>
    (function () {
        var savedTheme = localStorage.getItem('odinbo-theme');
        if (savedTheme === 'flat' || savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
    </script>
    <script>
    (function () {
        if (window.OdinSessionGuard && window.OdinSessionGuard.initialized) {
            return;
        }

        var storageKey = 'odinbo-login-redirect-message';
        var handled = false;

        function isSessionClosedFlag(value) {
            return value === true || String(value).toLowerCase() === 'true';
        }

        function clearAuthClientState() {
            var localStorageKeys = ['token', 'user', 'auth', 'authToken', 'odinbo-auth', 'odinbo-user'];
            localStorageKeys.forEach(function (key) {
                try {
                    localStorage.removeItem(key);
                } catch (error) {
                    // Ignore local storage cleanup failures.
                }
            });

            try {
                var preservedRedirectMessage = sessionStorage.getItem(storageKey);
                sessionStorage.clear();
                if (preservedRedirectMessage) {
                    sessionStorage.setItem(storageKey, preservedRedirectMessage);
                }
            } catch (error) {
                // Ignore session storage cleanup failures.
            }

            ['token', 'user', 'auth', 'authToken'].forEach(function (cookieName) {
                try {
                    document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                } catch (error) {
                    // Ignore cookie cleanup failures.
                }
            });
        }

        function persistLoginRedirectMessage(message, type) {
            var payload = {
                message: String(message || '').trim(),
                type: String(type || 'danger').trim() || 'danger'
            };

            if (payload.message === '') {
                return;
            }

            try {
                sessionStorage.setItem(storageKey, JSON.stringify(payload));
            } catch (error) {
                // Ignore storage failures.
            }
        }

        function redirectToLogin() {
            var loginUrl = (window.APP && window.APP.loginUrl) ? window.APP.loginUrl : '/login';
            try {
                window.location.href = loginUrl;
            } catch (error) {
                window.location.assign('/login');
            }
        }

        function handle406Payload(payload) {
            var sessionClosed = isSessionClosedFlag(payload && payload.sessionClosed);
            if (!sessionClosed) {
                return false;
            }

            if (handled) {
                return true;
            }

            handled = true;
            var message = String((payload && payload.message) || '').trim();
            if (message === '') {
                message = 'No puedes ingresar porque estás fuera del horario laboral establecido. Tu sesión ha sido cerrada automáticamente. Por favor ingresa nuevamente durante tu horario permitido.';
            }

            persistLoginRedirectMessage(message, 'danger');
            clearAuthClientState();
            redirectToLogin();
            return true;
        }

        window.OdinSessionGuard = {
            initialized: true,
            handle406Payload: handle406Payload,
            isHandled: function () { return handled; }
        };

        if (typeof window.fetch === 'function' && !window.fetch.__odin406Wrapped) {
            var nativeFetch = window.fetch.bind(window);
            var wrappedFetch = async function () {
                var response = await nativeFetch.apply(null, arguments);

                try {
                    if (response && response.status === 406) {
                        var contentType = response.headers.get('content-type') || '';
                        if (contentType.toLowerCase().indexOf('application/json') !== -1) {
                            var payload = await response.clone().json();
                            handle406Payload(payload);
                        }
                    }
                } catch (error) {
                    // Never break normal request flow because of interceptor errors.
                }

                return response;
            };

            wrappedFetch.__odin406Wrapped = true;
            window.fetch = wrappedFetch;
        }
    })();
    </script>
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
            <?php if ($apiHttpCode === 403): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:16px;text-align:center;padding:32px;">
                <div style="width:72px;height:72px;border-radius:50%;background:#fff3cd;display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#e6a817" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                </div>
                <div>
                    <p style="margin:0 0 6px;font-size:1.25rem;font-weight:600;color:#212529;letter-spacing:-0.01em;">Acceso denegado</p>
                    <p style="margin:0 0 16px;font-size:0.9rem;color:#6c757d;">No tiene permisos para realizar esta accion.</p>
                    <a href="<?= htmlspecialchars(base_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:8px 24px;background:#212529;color:#fff;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:500;">
                        Aceptar
                    </a>
                </div>
            </div>
            <?php else: ?>
            <?= $content ?>
            <?php endif; ?>
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
    dashboardUrl: '<?= base_url('dashboard') ?>',
    usersListUrl: '<?= base_url('users/list') ?>',
    usersStoreUrl: '<?= base_url('users/store') ?>',
    usersUpdateUrl: '<?= base_url('users/update') ?>',
    articlesListUrl: '<?= base_url('articles/list') ?>',
    articlesDetailUrl: '<?= base_url('articles/detail') ?>',
    vacationGetSignersUrl: '<?= base_url('rrhh/solicitudes-vacaciones/signers') ?>',
    vacationGetFilesUrl: '<?= base_url('rrhh/solicitudes-vacaciones/files') ?>',
    vacationAddSignersUrl: '<?= base_url('rrhh/solicitudes-vacaciones/add-signers') ?>',
    vacationSaveSignatureUrl: '<?= base_url('rrhh/solicitud-vacaciones/save-signature') ?>',
    vacationDownloadFileUrl: '<?= base_url('rrhh/vacaciones/descargar') ?>',
    vacationRejectUrl: '<?= base_url('rrhh/solicitud-vacaciones/reject') ?>'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('assets/js/app.js?v=' . urlencode($appJsVersion)) ?>"></script>
</body>
</html>

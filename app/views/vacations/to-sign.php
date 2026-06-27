<?php
/** @var array<int, array<string, mixed>> $requests */
$requests = $requests ?? [];
$pendingCount = count($requests);
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">
            Solicitudes Para Firmar
            <?php if ($pendingCount > 0): ?>
                <span class="badge text-bg-warning ms-1"><?= $pendingCount ?></span>
            <?php endif; ?>
        </h2>
        <p class="text-muted m-0">Solicitudes asignadas a ti que aun no has firmado.</p>
    </div>
</section>

<div class="card border-0 shadow-sm vacation-card mb-3">
    <div class="card-body">
        <?php if ($requests === []): ?>
            <div class="alert alert-info m-0">No tienes solicitudes pendientes de firma.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Solicitante</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="toSignTableBody">
                    <?php foreach ($requests as $req): ?>
                        <?php
                        $id = (int) ($req['id'] ?? 0);
                        $stateKey = strtoupper((string) ($req['stateKey'] ?? ''));
                        $stateName = (string) ($req['stateName'] ?? 'Desconocido');
                        if ($stateKey === 'REJECTED') {
                            continue;
                        }
                        $stateBadge = match ($stateKey) {
                            'PENDING' => '<span class="badge text-bg-warning">Pendiente</span>',
                            'TOSIGNED' => '<span class="badge text-bg-info">Para Firmar</span>',
                            'APPROVED' => '<span class="badge text-bg-success">Aprobado</span>',
                            'SIGN' => '<span class="badge text-bg-success">Firmada</span>',
                            'REJECTED' => '<span class="badge text-bg-danger">Rechazado</span>',
                            default => '<span class="badge text-bg-secondary">' . htmlspecialchars($stateName, ENT_QUOTES, 'UTF-8') . '</span>',
                        };
                        $requestType = isset($req['requestType']) && $req['requestType'] !== null ? (int) $req['requestType'] : null;
                        $quantityLabel = $requestType === 1 ? 'h' : ($requestType === 0 ? 'd' : '');
                        $typeLabel = $requestType === 1 ? 'Permiso' : ($requestType === 0 ? 'Vacaciones' : null);
                        ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= htmlspecialchars((string) ($req['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['startDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['endDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($typeLabel !== null): ?>
                                    <span class="badge <?= $requestType === 1 ? 'text-bg-secondary' : 'text-bg-primary' ?>"><?= $typeLabel ?></span>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) ($req['quantity'] ?? 0) ?><?= $quantityLabel !== '' ? ' ' . $quantityLabel : '' ?></td>
                            <td><?= $stateBadge ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 vacation-actions">
                                    <a href="<?= base_url('rrhh/solicitud-vacaciones/detalle?id=' . $id) ?>" class="btn btn-sm btn-outline-primary">Ver Detalle</a>
                                    <button type="button"
                                            class="btn btn-sm btn-success btn-sign-to-sign"
                                            data-request-id="<?= $id ?>">
                                        Firmar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="toSignSignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Firmar Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Dibuja tu firma en el area de abajo y luego presiona "Firmar Documento".</p>
                <div class="signature-pad-wrapper mb-3">
                    <canvas id="toSignCanvas" class="signature-canvas" aria-label="Area de firma"></canvas>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="toSignClearBtn">Limpiar</button>
                    <button type="button" class="btn btn-primary" id="toSignSaveBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="toSignSpinner"></span>Firmar Documento
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/signature-pad.js') ?>"></script>
<script>
(() => {
    const initToSignModule = () => {
        const signModalEl = document.getElementById('toSignSignModal');
        const clearBtn = document.getElementById('toSignClearBtn');
        const saveBtn = document.getElementById('toSignSaveBtn');
        let currentRequestId = 0;

        const getModal = () => (signModalEl && window.bootstrap?.Modal)
            ? window.bootstrap.Modal.getOrCreateInstance(signModalEl)
            : null;

        signModalEl?.addEventListener('shown.bs.modal', () => {
            window.VacationSignaturePad?.init?.('toSignCanvas');
        });

        const csrfToken = window.APP?.csrfToken || '';
        const notify = async (message, icon = 'warning') => {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                await window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
                return;
            }

            alert(message);
        };

        const fetchJson = async (url, payload) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.toLowerCase().includes('application/json')) {
                return { code: String(response.status || 500), message: 'Respuesta invalida', data: null };
            }

            return response.json();
        };

        document.getElementById('toSignTableBody')?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const btn = target.closest('.btn-sign-to-sign');
            if (!(btn instanceof HTMLElement)) {
                return;
            }

            const requestId = Number(btn.dataset.requestId || 0);
            if (requestId <= 0) {
                return;
            }

            currentRequestId = requestId;
            getModal()?.show();
        });

        clearBtn?.addEventListener('click', () => {
            window.VacationSignaturePad?.clear?.();
        });

        saveBtn?.addEventListener('click', async () => {
            if (currentRequestId <= 0) {
                await notify('Solicitud invalida.');
                return;
            }

            if (window.VacationSignaturePad?.isEmpty?.()) {
                await notify('Por favor dibuja tu firma antes de continuar.');
                return;
            }

            const signature = window.VacationSignaturePad?.toDataUrl?.();
            if (!signature) {
                await notify('No fue posible obtener la firma.', 'error');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.querySelector('#toSignSpinner')?.classList.remove('d-none');

            try {
                const result = await fetchJson(window.APP.vacationSaveSignatureUrl, {
                    requestId: currentRequestId,
                    signature,
                    _csrf_token: csrfToken
                });

                if (String(result.code) !== '200') {
                    await notify(result.message || 'No fue posible guardar la firma.', 'error');
                    return;
                }

                getModal()?.hide();
                await notify('Firma registrada exitosamente.', 'success');
                window.location.reload();
            } finally {
                saveBtn.disabled = false;
                saveBtn.querySelector('#toSignSpinner')?.classList.add('d-none');
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToSignModule);
    } else {
        initToSignModule();
    }
})();
</script>

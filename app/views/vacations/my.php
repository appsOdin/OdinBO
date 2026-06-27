<?php
/** @var array<int, array<string, mixed>> $requests */
$requests = $requests ?? [];
$currentUserId = (string) ($authUser['id'] ?? '');
$currentUserRole = strtoupper((string) ($authUser['rolename'] ?? ''));
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Mis Solicitudes de Vacaciones</h2>
        <p class="text-muted m-0">Gestiona tus solicitudes y firma cuando corresponda.</p>
    </div>
    <a href="<?= base_url('rrhh/solicitud-vacaciones/crear') ?>" class="btn btn-primary">Nueva Solicitud</a>
</section>

<div class="card border-0 shadow-sm vacation-card mb-3">
    <div class="card-body">
        <?php if ($requests === []): ?>
            <div class="alert alert-info m-0">No tienes solicitudes de vacaciones registradas.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Descripcion</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $req): ?>
                        <?php
                        $id = (int) ($req['id'] ?? 0);
                        $stateKey = strtoupper((string) ($req['stateKey'] ?? ''));
                        $stateName = (string) ($req['stateName'] ?? 'Desconocido');
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
                        $isOwner = (string) ($req['userId'] ?? '') === $currentUserId;
                        ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['startDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['endDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($typeLabel !== null): ?>
                                    <span class="badge <?= $requestType === 1 ? 'text-bg-secondary' : 'text-bg-primary' ?>"><?= $typeLabel ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) ($req['quantity'] ?? 0) ?><?= $quantityLabel !== '' ? ' ' . $quantityLabel : '' ?></td>
                            <td><?= htmlspecialchars((string) ($req['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $stateBadge ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 vacation-actions">
                                    <a href="<?= base_url('rrhh/solicitud-vacaciones/detalle?id=' . $id) ?>" class="btn btn-sm btn-outline-primary">Ver Detalle</a>
                                    <?php if ($isOwner && $stateKey === 'APPROVED'): ?>
                                        <button type="button" class="btn btn-sm btn-success btn-sign-request" data-request-id="<?= $id ?>">Firmar</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                        <small class="text-success">Todos firmaron. Ahora puedes firmar tu.</small>
                                    <?php elseif ($stateKey === 'APPROVED'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" disabled>Aprobada</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                    <?php elseif ($stateKey === 'SIGN'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" disabled>Firmada</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                    <?php elseif ($stateKey === 'TOSIGNED'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                    <?php endif; ?>
                                    <?php if ($isOwner && !in_array($stateKey, ['REJECTED', 'CANCELLED', 'SIGN'], true) && in_array(strtoupper($currentUserRole), ['ADMIN', 'USER'], true)): ?>
                                        <button type="button" class="btn btn-sm btn-danger btn-reject-vacation" data-request-id="<?= $id ?>">Rechazar</button>
                                    <?php endif; ?>
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

<div class="modal fade" id="vacationSignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Firmar Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Dibuja tu firma y luego presiona "Firmar Documento".</p>
                <div class="signature-pad-wrapper mb-3">
                    <canvas id="vacationSignatureCanvas" class="signature-canvas" aria-label="Area de firma"></canvas>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="vacationClearSignature">Limpiar</button>
                    <button type="button" class="btn btn-primary" id="vacationSaveSignature">Firmar Documento</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vacationMyFilesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Archivos de la Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="vacationMyFilesBody">
                <div class="text-muted">Seleccione una solicitud para consultar archivos.</div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/signature-pad.js') ?>"></script>
<script>
(() => {
    const initVacationMyModule = () => {
        const signModalEl = document.getElementById('vacationSignModal');
        const filesModalEl = document.getElementById('vacationMyFilesModal');
        const filesBody = document.getElementById('vacationMyFilesBody');
        const saveButton = document.getElementById('vacationSaveSignature');
        const clearButton = document.getElementById('vacationClearSignature');
        let currentRequestId = 0;

        const getModalInstance = (element) => {
            if (!element || !window.bootstrap || !window.bootstrap.Modal) {
                return null;
            }

            return window.bootstrap.Modal.getOrCreateInstance(element);
        };

        const csrfToken = window.APP?.csrfToken || '';
        const notify = async (message, icon = 'warning') => {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                await window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
                return;
            }

            alert(message);
        };

    const escapeHtml = (value) => {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(String(value || '')));
        return p.innerHTML;
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

        document.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const signButton = target.closest('.btn-sign-request');
            if (signButton instanceof HTMLElement) {
                const requestId = Number(signButton.dataset.requestId || 0);
                const signModal = getModalInstance(signModalEl);
                if (!signModal || requestId <= 0) {
                    return;
                }

                currentRequestId = requestId;
                if (window.VacationSignaturePad?.init) {
                    window.VacationSignaturePad.init('vacationSignatureCanvas');
                }
                signModal.show();
                return;
            }

            const filesButton = target.closest('.btn-view-files');
            if (filesButton instanceof HTMLElement) {
                const requestId = Number(filesButton.dataset.requestId || 0);
                const filesModal = getModalInstance(filesModalEl);
                if (!filesModal || requestId <= 0 || !filesBody) {
                    return;
                }

                filesBody.innerHTML = '<div class="text-muted">Cargando archivos...</div>';
                filesModal.show();

                const result = await fetchJson(window.APP.vacationGetFilesUrl, {
                    requestId,
                    _csrf_token: csrfToken
                });

                if (String(result.code) !== '200') {
                    filesBody.innerHTML = `<div class="text-danger">${escapeHtml(result.message || 'No fue posible cargar archivos')}</div>`;
                    return;
                }

                const rows = Array.isArray(result.data) ? result.data : [];
                if (rows.length === 0) {
                    filesBody.innerHTML = '<div class="text-muted">No hay archivos asociados.</div>';
                    return;
                }

                const htmlRows = rows.map((item) => {
                    const fileId = Number(item.id || 0);
                    const downloadHref = fileId > 0
                        ? `${window.APP.vacationDownloadFileUrl}?fileId=${fileId}`
                        : '';
                    const downloadBtn = downloadHref
                        ? `<a href="${downloadHref}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Descargar</a>`
                        : '<span class="text-muted">-</span>';
                    return `
                        <tr>
                            <td>${escapeHtml(item.fileName || item.name || '')}</td>
                            <td>${escapeHtml(item.typeMime || '')}</td>
                            <td>${escapeHtml(item.sizeFormatted || item.size || '')}</td>
                            <td>${downloadBtn}</td>
                        </tr>
                    `;
                }).join('');

                filesBody.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm align-middle m-0">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Tipo</th>
                                    <th>Tamano</th>
                                    <th>Descargar</th>
                                </tr>
                            </thead>
                            <tbody>${htmlRows}</tbody>
                        </table>
                    </div>
                `;
            }
        });

        clearButton?.addEventListener('click', () => {
            window.VacationSignaturePad?.clear?.();
        });

        saveButton?.addEventListener('click', async () => {
            if (currentRequestId <= 0) {
                await notify('Solicitud invalida.');
                return;
            }

            if (window.VacationSignaturePad?.isEmpty?.()) {
                await notify('Por favor dibuja tu firma.');
                return;
            }

            const signature = window.VacationSignaturePad?.toDataUrl?.();
            if (!signature) {
                await notify('No fue posible obtener la firma.', 'error');
                return;
            }

            const result = await fetchJson(window.APP.vacationSaveSignatureUrl, {
                requestId: currentRequestId,
                signature,
                _csrf_token: csrfToken
            });

            if (String(result.code) !== '200') {
                await notify(result.message || 'No fue posible guardar la firma.', 'error');
                return;
            }

            const signModal = getModalInstance(signModalEl);
            await notify('Firma guardada exitosamente.', 'success');
            signModal?.hide();
            window.location.reload();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVacationMyModule);
    } else {
        initVacationMyModule();
    }
})();
</script>

<div class="modal fade" id="rejectVacationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectRequestId">
                <div class="mb-3">
                    <label for="rejectReason" class="form-label fw-semibold">Motivo del Rechazo <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectReason" rows="4" maxlength="500" required placeholder="Describa el motivo del rechazo..."></textarea>
                    <small class="text-muted d-block mt-1"><span id="rejectReasonCounter">0</span>/500 caracteres</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Rechazar Solicitud</button>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    const initRejectModule = () => {
        const modalEl = document.getElementById('rejectVacationModal');
        const requestIdInput = document.getElementById('rejectRequestId');
        const reasonInput = document.getElementById('rejectReason');
        const counter = document.getElementById('rejectReasonCounter');
        const confirmBtn = document.getElementById('confirmRejectBtn');
        const csrfToken = window.APP?.csrfToken || '';

        const getModal = () => (modalEl && window.bootstrap?.Modal)
            ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        const notify = async (message, icon = 'warning') => {
            if (window.Swal?.fire) {
                await window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
                return;
            }
            alert(message);
        };

        document.addEventListener('click', (event) => {
            const btn = event.target instanceof Element ? event.target.closest('.btn-reject-vacation') : null;
            if (!(btn instanceof HTMLElement)) return;
            const requestId = Number(btn.dataset.requestId || 0);
            if (requestId <= 0) return;
            if (requestIdInput) requestIdInput.value = String(requestId);
            if (reasonInput) reasonInput.value = '';
            if (counter) counter.textContent = '0';
            getModal()?.show();
        });

        reasonInput?.addEventListener('input', () => {
            if (counter) counter.textContent = String(reasonInput.value.length);
        });

        confirmBtn?.addEventListener('click', async () => {
            const requestId = Number(requestIdInput?.value || 0);
            const reason = (reasonInput?.value || '').trim();

            if (!reason) {
                await notify('Debe ingresar un motivo de rechazo.');
                return;
            }

            const confirmation = window.Swal?.fire
                ? await window.Swal.fire({
                    icon: 'warning',
                    title: '\u00bfRechazar solicitud?',
                    text: 'Esta accion no se puede revertir.',
                    showCancelButton: true,
                    confirmButtonText: 'S\u00ed, rechazar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                })
                : { isConfirmed: window.confirm('\u00bfEsta seguro que desea rechazar esta solicitud?') };

            if (!confirmation?.isConfirmed) return;

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Procesando...';

            try {
                const response = await fetch(window.APP.vacationRejectUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ requestId, rejectReason: reason, _csrf_token: csrfToken })
                });

                const ct = response.headers.get('content-type') || '';
                const result = ct.toLowerCase().includes('application/json')
                    ? await response.json()
                    : { success: false, message: 'Respuesta invalida' };

                if (result.success === true || String(result.code) === '200') {
                    getModal()?.hide();
                    await notify('Solicitud rechazada exitosamente.', 'success');
                    window.location.reload();
                } else {
                    await notify(result.errorMessage || result.message || 'No fue posible rechazar la solicitud.', 'error');
                }
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Rechazar Solicitud';
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRejectModule);
    } else {
        initRejectModule();
    }
})();
</script>

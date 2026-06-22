<?php
/** @var array<int, array<string, mixed>> $requests */
/** @var array<int, array<string, mixed>> $users */
$requests = $requests ?? [];
$users = $users ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Solicitudes de Vacaciones</h2>
        <p class="text-muted m-0">Listado general para Administrador y Usuario.</p>
    </div>
    <a href="<?= base_url('rrhh/solicitud-vacaciones/crear') ?>" class="btn btn-primary">Nueva Solicitud</a>
</section>

<div class="card border-0 shadow-sm vacation-card mb-3">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="text" id="searchVacationRequests" class="form-control" placeholder="Buscar por ID, usuario o descripcion">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="vacationRequestsTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Dias</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody id="vacationRequestsTableBody">
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
                    ?>
                    <tr
                        data-id="<?= $id ?>"
                        data-user="<?= htmlspecialchars((string) ($req['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-description="<?= htmlspecialchars((string) ($req['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars((string) ($req['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['startDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['endDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($req['quantity'] ?? 0) ?></td>
                        <td><?= $stateBadge ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2 vacation-actions">
                                <a href="<?= base_url('rrhh/solicitud-vacaciones/detalle?id=' . $id) ?>" class="btn btn-sm btn-outline-primary">Ver Detalle</a>
                                <?php if ($stateKey === 'PENDING'): ?>
                                    <button type="button" class="btn btn-sm btn-primary btn-add-signers" data-request-id="<?= $id ?>">Agregar Firmantes</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-info btn-view-signers" data-request-id="<?= $id ?>">Ver Firmantes</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                <?php endif; ?>
                                <?php if (!in_array($stateKey, ['REJECTED', 'CANCELLED', 'SIGN'], true)): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject-vacation" data-request-id="<?= $id ?>">Rechazar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="vacationSignersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Firmantes de la Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="vacationSignersBody">
                <div class="text-muted">Seleccione una solicitud para consultar firmantes.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vacationFilesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Archivos de la Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="vacationFilesBody">
                <div class="text-muted">Seleccione una solicitud para consultar archivos.</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vacationAddSignersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Firmantes y PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="vacationAddSignersForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="requestId" id="vacationRequestIdInput">

                    <div class="mb-3">
                        <label class="form-label" for="vacationSignerSelect">Seleccionar Firmantes</label>
                        <select class="form-select" name="signers[]" id="vacationSignerSelect" multiple required size="6">
                            <?php foreach ($users as $user): ?>
                                <?php
                                $userId = (string) ($user['id'] ?? '');
                                $name = trim((string) ($user['name'] ?? '') . ' ' . (string) ($user['lastname'] ?? ''));
                                ?>
                                <option value="<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($name !== '' ? $name : ((string) ($user['username'] ?? $userId)), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Mantenga Ctrl para seleccionar multiples.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="vacationPdfFile">Archivo PDF</label>
                        <input type="file" class="form-control" id="vacationPdfFile" name="pdfFile" accept="application/pdf,.pdf" required>
                        <small class="text-muted">Tamano maximo: 5 MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const initVacationAllModule = () => {
        const body = document.getElementById('vacationRequestsTableBody');
        const search = document.getElementById('searchVacationRequests');

        const signersModalEl = document.getElementById('vacationSignersModal');
        const filesModalEl = document.getElementById('vacationFilesModal');
        const addSignersModalEl = document.getElementById('vacationAddSignersModal');

        const signersBody = document.getElementById('vacationSignersBody');
        const filesBody = document.getElementById('vacationFilesBody');
        const addSignersForm = document.getElementById('vacationAddSignersForm');
        const requestIdInput = document.getElementById('vacationRequestIdInput');

        if (!body) {
            return;
        }

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

    const renderSigners = (rows) => {
        if (!signersBody) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            signersBody.innerHTML = '<div class="text-muted">No hay firmantes asociados.</div>';
            return;
        }

        const htmlRows = rows.map((item) => {
            const status = item.hasSigned
                ? '<span class="badge text-bg-success">Firmado</span>'
                : '<span class="badge text-bg-warning">Pendiente</span>';

            return `
                <tr>
                    <td>${escapeHtml(`${item.userName || ''} ${item.userLastname || ''}`)}</td>
                    <td>${escapeHtml(item.userEmail || '')}</td>
                    <td>${status}</td>
                </tr>
            `;
        }).join('');

        signersBody.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm align-middle m-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>${htmlRows}</tbody>
                </table>
            </div>
        `;
    };

    const renderFiles = (rows) => {
        if (!filesBody) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
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
    };

        search?.addEventListener('input', (event) => {
            const value = String(event.target.value || '').toLowerCase().trim();

            Array.from(body.querySelectorAll('tr')).forEach((row) => {
                const values = [
                    row.getAttribute('data-id') || '',
                    row.getAttribute('data-user') || '',
                    row.getAttribute('data-description') || ''
                ].join(' ').toLowerCase();

                row.style.display = values.includes(value) ? '' : 'none';
            });
        });

        body.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const signersButton = target.closest('.btn-view-signers');
            if (signersButton instanceof HTMLElement) {
                const requestId = Number(signersButton.dataset.requestId || 0);
                const signersModal = getModalInstance(signersModalEl);
                if (requestId <= 0 || !signersModal || !signersBody) {
                    return;
                }

                signersBody.innerHTML = '<div class="text-muted">Cargando firmantes...</div>';
                signersModal.show();

                const result = await fetchJson(window.APP.vacationGetSignersUrl, {
                    requestId,
                    _csrf_token: csrfToken
                });

                if (String(result.code) !== '200') {
                    signersBody.innerHTML = `<div class="text-danger">${escapeHtml(result.message || 'No fue posible cargar firmantes')}</div>`;
                    return;
                }

                renderSigners(result.data || []);
                return;
            }

            const filesButton = target.closest('.btn-view-files');
            if (filesButton instanceof HTMLElement) {
                const requestId = Number(filesButton.dataset.requestId || 0);
                const filesModal = getModalInstance(filesModalEl);
                if (requestId <= 0 || !filesModal || !filesBody) {
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

                renderFiles(result.data || []);
                return;
            }

            const addButton = target.closest('.btn-add-signers');
            if (addButton instanceof HTMLElement) {
                const requestId = Number(addButton.dataset.requestId || 0);
                const addSignersModal = getModalInstance(addSignersModalEl);
                if (requestId <= 0 || !addSignersModal || !requestIdInput) {
                    return;
                }

                requestIdInput.value = String(requestId);
                addSignersModal.show();
            }
        });

        addSignersForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

        const formData = new FormData(addSignersForm);
        const pdfFile = formData.get('pdfFile');

        if (!(pdfFile instanceof File) || pdfFile.size <= 0) {
            await notify('Debe seleccionar un archivo PDF.');
            return;
        }

        if (pdfFile.size > 5 * 1024 * 1024) {
            await notify('El archivo PDF no puede superar 5 MB.');
            return;
        }

        const response = await fetch(window.APP.vacationAddSignersUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const contentType = response.headers.get('content-type') || '';
        const result = contentType.toLowerCase().includes('application/json')
            ? await response.json()
            : { code: String(response.status || 500), message: 'Respuesta invalida' };

        if (String(result.code) !== '200') {
            await notify(result.message || 'No fue posible agregar firmantes.', 'error');
            return;
        }

            await notify('Firmantes agregados exitosamente.', 'success');
            window.location.reload();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVacationAllModule);
    } else {
        initVacationAllModule();
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

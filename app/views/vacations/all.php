<?php
/** @var array<int, array<string, mixed>> $requests */
/** @var array<int, array<string, mixed>> $users */
$requests = $requests ?? [];
$users = $users ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Solicitudes de Vacaciones</h2>
        <p class="text-muted m-0">Listado general para administradores y super usuarios.</p>
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
                    <th>Tipo</th>
                    <th>Cantidad</th>
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
                        'ADJUSTED' => '<span class="badge text-bg-warning">Ajustada</span>',
                        'ADJUSTMENT_ACCEPTED' => '<span class="badge text-bg-success">Ajuste Aprobado</span>',
                        'REJECTED' => '<span class="badge text-bg-danger">Rechazado</span>',
                        'ANNULLED' => '<span class="badge text-bg-dark">Anulada</span>',
                        'ANNULLED_APPROVED' => '<span class="badge text-bg-primary">Anulacion Aprobada</span>',
                        default => '<span class="badge text-bg-secondary">' . htmlspecialchars($stateName, ENT_QUOTES, 'UTF-8') . '</span>',
                    };
                    $requestType = isset($req['requestType']) && $req['requestType'] !== null ? (int) $req['requestType'] : null;
                    $quantityLabel = $requestType === 1 ? 'h' : ($requestType === 0 ? 'd' : '');
                    $typeLabel = $requestType === 1 ? 'Permiso' : ($requestType === 0 ? 'Vacaciones' : null);
                    ?>
                    <tr
                        data-id="<?= $id ?>"
                        data-user="<?= htmlspecialchars((string) ($req['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-description="<?= htmlspecialchars((string) ($req['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-start="<?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['startDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>"
                        data-end="<?= htmlspecialchars(date('d/m/Y', strtotime((string) ($req['endDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>"
                        data-type="<?= htmlspecialchars((string) ($typeLabel ?? '—'), ENT_QUOTES, 'UTF-8') ?>"
                        data-quantity="<?= (int) ($req['quantity'] ?? 0) ?>"
                        data-state="<?= htmlspecialchars((string) ($stateName ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
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
                                <?php if ($stateKey === 'PENDING'): ?>
                                    <button type="button" class="btn btn-sm btn-primary btn-add-signers" data-request-id="<?= $id ?>">Agregar Firmantes</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-info btn-view-signers" data-request-id="<?= $id ?>">Ver Firmantes</button>
                                <?php if ($stateKey !== 'PENDING'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="<?= $id ?>">Archivos</button>
                                <?php endif; ?>
                                <?php
                                $startDateTs = strtotime((string) ($req['startDate'] ?? ''));
                                $startDatePast = $startDateTs !== false && $startDateTs < strtotime(date('Y-m-d'));
                                if (!in_array($stateKey, ['REJECTED', 'ANNULLED', 'ANNULLED_APPROVED', 'SIGN','ADJUSTMENT_ACCEPTED'], true) && !$startDatePast): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject-vacation" data-request-id="<?= $id ?>">Rechazar</button>
                                <?php endif; ?>
                                <?php if (!in_array($stateKey, ['ANNULLED', 'ANNULLED_APPROVED', 'REJECTED','ADJUSTMENT_ACCEPTED'], true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-annul-vacation" data-request-id="<?= $id ?>">Anular</button>
                                <?php endif; ?>
                                <?php if ($stateKey === 'ANNULLED'): ?>
                                    <button type="button" class="btn btn-sm btn-success btn-approve-annulment" data-request-id="<?= $id ?>">Aprobar anulación</button>
                                <?php endif; ?>
                                <?php if ($stateKey === 'SIGN' && $requestType === 1): ?>
                                    <button type="button" class="btn btn-sm btn-warning btn-adjust-vacation" data-request-id="<?= $id ?>">Ajustar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="vacationRequestsPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="vacationRequestsPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="vacationRequestsPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="vacationRequestsPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="vacationRequestsPageIndicator">Pagina 1 de 1</small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="vacationRequestsNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
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
                                $name = trim((string) ($user['fullname'] ?? ''));
                                ?>
                                <option value="<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($name !== '' ? $name : $userId, ENT_QUOTES, 'UTF-8') ?>
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

<div class="modal fade" id="adjustVacationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar Solicitud de Permiso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="adjustRequestId">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">ID</label>
                        <input type="text" class="form-control" id="adjustInfoId" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Usuario</label>
                        <input type="text" class="form-control" id="adjustInfoUser" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Inicio</label>
                        <input type="text" class="form-control" id="adjustInfoStart" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fin</label>
                        <input type="text" class="form-control" id="adjustInfoEnd" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipo</label>
                        <input type="text" class="form-control" id="adjustInfoType" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="adjustRequestCant">Cantidad (horas)</label>
                        <input type="number" min="1" max="255" step="1" class="form-control" id="adjustRequestCant" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Estado</label>
                        <input type="text" class="form-control" id="adjustInfoState" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="adjustReason">Razon <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adjustReason" rows="3" maxlength="200" required placeholder="Indique la razon del ajuste..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmAdjustBtn">Ajustar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const initVacationAllModule = () => {
        const body = document.getElementById('vacationRequestsTableBody');
        const search = document.getElementById('searchVacationRequests');
        const paginationInfo = document.getElementById('vacationRequestsPaginationInfo');
        const perPageSelect = document.getElementById('vacationRequestsPerPage');
        const prevPageButton = document.getElementById('vacationRequestsPrevPage');
        const nextPageButton = document.getElementById('vacationRequestsNextPage');
        const pageIndicator = document.getElementById('vacationRequestsPageIndicator');

        const signersModalEl = document.getElementById('vacationSignersModal');
        const filesModalEl = document.getElementById('vacationFilesModal');
        const addSignersModalEl = document.getElementById('vacationAddSignersModal');
        const adjustModalEl = document.getElementById('adjustVacationModal');

        const signersBody = document.getElementById('vacationSignersBody');
        const filesBody = document.getElementById('vacationFilesBody');
        const addSignersForm = document.getElementById('vacationAddSignersForm');
        const requestIdInput = document.getElementById('vacationRequestIdInput');
        const adjustRequestIdInput = document.getElementById('adjustRequestId');
        const adjustInfoId = document.getElementById('adjustInfoId');
        const adjustInfoUser = document.getElementById('adjustInfoUser');
        const adjustInfoStart = document.getElementById('adjustInfoStart');
        const adjustInfoEnd = document.getElementById('adjustInfoEnd');
        const adjustInfoType = document.getElementById('adjustInfoType');
        const adjustInfoState = document.getElementById('adjustInfoState');
        const adjustRequestCantInput = document.getElementById('adjustRequestCant');
        const adjustReasonInput = document.getElementById('adjustReason');
        const confirmAdjustBtn = document.getElementById('confirmAdjustBtn');

        if (!body) {
            return;
        }

        const paginationState = {
            search: '',
            page: 1,
            perPage: Number(perPageSelect?.value || 8),
            allRows: Array.from(body.querySelectorAll('tr')),
            rows: []
        };

        const renderRows = () => {
            const totalRows = paginationState.rows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / paginationState.perPage));

            if (paginationState.page > totalPages) {
                paginationState.page = totalPages;
            }

            paginationState.allRows.forEach((row) => {
                row.style.display = 'none';
            });

            const start = (paginationState.page - 1) * paginationState.perPage;
            const end = start + paginationState.perPage;

            paginationState.rows.slice(start, end).forEach((row) => {
                row.style.display = '';
            });

            if (paginationInfo) {
                paginationInfo.textContent = `Mostrando ${totalRows === 0 ? 0 : start + 1} a ${Math.min(end, totalRows)} de ${totalRows} registros`;
            }

            if (pageIndicator) {
                pageIndicator.textContent = `Pagina ${paginationState.page} de ${totalPages}`;
            }

            if (prevPageButton) {
                prevPageButton.disabled = paginationState.page <= 1;
            }

            if (nextPageButton) {
                nextPageButton.disabled = paginationState.page >= totalPages;
            }
        };

        const applyFilter = (resetPage = false) => {
            if (resetPage) {
                paginationState.page = 1;
            }

            const term = paginationState.search.toLowerCase();
            paginationState.rows = term === ''
                ? paginationState.allRows
                : paginationState.allRows.filter((row) => {
                    const values = [
                        row.getAttribute('data-id') || '',
                        row.getAttribute('data-user') || '',
                        row.getAttribute('data-description') || ''
                    ].join(' ').toLowerCase();

                    return values.includes(term);
                });

            renderRows();
        };

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

    const sendAdjustRequest = async (payload) => {
        return fetchJson(window.APP.vacationAdjustUrl, payload);
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
            paginationState.search = String(event.target.value || '').trim();
            applyFilter(true);
        });

        perPageSelect?.addEventListener('change', () => {
            const newPerPage = Number(perPageSelect.value || 8);
            paginationState.perPage = Number.isFinite(newPerPage) && newPerPage > 0 ? newPerPage : 8;
            paginationState.page = 1;
            renderRows();
        });

        prevPageButton?.addEventListener('click', () => {
            if (paginationState.page <= 1) {
                return;
            }

            paginationState.page -= 1;
            renderRows();
        });

        nextPageButton?.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(paginationState.rows.length / paginationState.perPage));
            if (paginationState.page >= totalPages) {
                return;
            }

            paginationState.page += 1;
            renderRows();
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
                return;
            }

            const adjustButton = target.closest('.btn-adjust-vacation');
            if (adjustButton instanceof HTMLElement) {
                const requestId = Number(adjustButton.dataset.requestId || 0);
                const adjustModal = getModalInstance(adjustModalEl);
                const row = adjustButton.closest('tr');

                if (requestId <= 0 || !adjustModal || !(row instanceof HTMLTableRowElement)) {
                    return;
                }

                if (adjustRequestIdInput) {
                    adjustRequestIdInput.value = String(requestId);
                }
                if (adjustInfoId) {
                    adjustInfoId.value = String(row.getAttribute('data-id') || requestId);
                }
                if (adjustInfoUser) {
                    adjustInfoUser.value = String(row.getAttribute('data-user') || '');
                }
                if (adjustInfoStart) {
                    adjustInfoStart.value = String(row.getAttribute('data-start') || '');
                }
                if (adjustInfoEnd) {
                    adjustInfoEnd.value = String(row.getAttribute('data-end') || '');
                }
                if (adjustInfoType) {
                    adjustInfoType.value = String(row.getAttribute('data-type') || '');
                }
                if (adjustInfoState) {
                    adjustInfoState.value = String(row.getAttribute('data-state') || '');
                }
                if (adjustRequestCantInput) {
                    adjustRequestCantInput.value = String(row.getAttribute('data-quantity') || '1');
                }
                if (adjustReasonInput) {
                    adjustReasonInput.value = '';
                }

                adjustModal.show();
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

        confirmAdjustBtn?.addEventListener('click', async () => {
            const requestId = Number(adjustRequestIdInput?.value || 0);
            const reason = String(adjustReasonInput?.value || '').trim();
            const requestCant = Number(adjustRequestCantInput?.value || 0);

            if (requestId <= 0) {
                await notify('Solicitud invalida.');
                return;
            }

            if (!Number.isFinite(requestCant) || requestCant < 1) {
                await notify('La cantidad debe ser mayor o igual a 1.');
                return;
            }

            if (requestCant > 255) {
                await notify('La cantidad no puede superar 255.');
                return;
            }

            if (!reason) {
                await notify('Debe ingresar la razon del ajuste.');
                return;
            }

            confirmAdjustBtn.disabled = true;
            confirmAdjustBtn.textContent = 'Procesando...';

            try {
                const result = await sendAdjustRequest({
                    requestId,
                    reason,
                    requestCant: Math.floor(requestCant),
                    state: 'ADJUSTED',
                    sing: null,
                    _csrf_token: csrfToken
                });

                if (String(result.code) !== '200') {
                    await notify(result.data || result.message || 'No fue posible ajustar la solicitud.', 'error');
                    return;
                }

                getModalInstance(adjustModalEl)?.hide();
                await notify('Solicitud ajustada exitosamente.', 'success');
                window.location.reload();
            } finally {
                confirmAdjustBtn.disabled = false;
                confirmAdjustBtn.textContent = 'Ajustar';
            }
        });

        applyFilter(true);
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

<div class="modal fade" id="annulVacationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Anular Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="annulRequestId">
                <div class="mb-3">
                    <label for="annulReason" class="form-label fw-semibold">Motivo de la Anulacion <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="annulReason" rows="4" maxlength="500" required placeholder="Describa el motivo de la anulacion..."></textarea>
                </div>
                <p class="text-muted">La anulacion requiere firma digital.</p>
                <div class="signature-pad-wrapper mb-3">
                    <canvas id="annulSignatureCanvas" class="signature-canvas" aria-label="Area de firma para anulacion"></canvas>
                </div>
                <button type="button" class="btn btn-outline-secondary" id="annulClearSignature">Limpiar firma</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmAnnulBtn">Anular Solicitud</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/signature-pad.js') ?>"></script>
<script>
(() => {
    const initVacationStateModule = () => {
        const rejectModalEl = document.getElementById('rejectVacationModal');
        const rejectRequestIdInput = document.getElementById('rejectRequestId');
        const rejectReasonInput = document.getElementById('rejectReason');
        const rejectCounter = document.getElementById('rejectReasonCounter');
        const rejectConfirmBtn = document.getElementById('confirmRejectBtn');

        const annulModalEl = document.getElementById('annulVacationModal');
        const annulRequestIdInput = document.getElementById('annulRequestId');
        const annulReasonInput = document.getElementById('annulReason');
        const annulConfirmBtn = document.getElementById('confirmAnnulBtn');
        const annulClearBtn = document.getElementById('annulClearSignature');

        const csrfToken = window.APP?.csrfToken || '';

        const getModal = (el) => (el && window.bootstrap?.Modal)
            ? window.bootstrap.Modal.getOrCreateInstance(el)
            : null;

        const notify = async (message, icon = 'warning') => {
            if (window.Swal?.fire) {
                await window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
                return;
            }
            alert(message);
        };

        const sendAction = async (payload) => {
            const response = await fetch(window.APP.vacationRejectUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ ...payload, _csrf_token: csrfToken })
            });

            const ct = response.headers.get('content-type') || '';
            return ct.toLowerCase().includes('application/json')
                ? response.json()
                : { success: false, code: String(response.status || 500), message: 'Respuesta invalida' };
        };

        rejectReasonInput?.addEventListener('input', () => {
            if (rejectCounter) {
                rejectCounter.textContent = String(rejectReasonInput.value.length);
            }
        });

        annulModalEl?.addEventListener('shown.bs.modal', () => {
            window.VacationSignaturePad?.init?.('annulSignatureCanvas');
        });

        annulClearBtn?.addEventListener('click', () => {
            window.VacationSignaturePad?.clear?.();
        });

        document.addEventListener('click', async (event) => {
            const target = event.target instanceof Element ? event.target : null;
            if (!target) {
                return;
            }

            const rejectBtn = target.closest('.btn-reject-vacation');
            if (rejectBtn instanceof HTMLElement) {
                const requestId = Number(rejectBtn.dataset.requestId || 0);
                if (requestId <= 0) {
                    return;
                }
                if (rejectRequestIdInput) rejectRequestIdInput.value = String(requestId);
                if (rejectReasonInput) rejectReasonInput.value = '';
                if (rejectCounter) rejectCounter.textContent = '0';
                getModal(rejectModalEl)?.show();
                return;
            }

            const annulBtn = target.closest('.btn-annul-vacation');
            if (annulBtn instanceof HTMLElement) {
                const requestId = Number(annulBtn.dataset.requestId || 0);
                if (requestId <= 0) {
                    return;
                }
                if (annulRequestIdInput) annulRequestIdInput.value = String(requestId);
                if (annulReasonInput) annulReasonInput.value = '';
                window.VacationSignaturePad?.clear?.();
                getModal(annulModalEl)?.show();
                return;
            }

            const approveBtn = target.closest('.btn-approve-annulment');
            if (approveBtn instanceof HTMLElement) {
                const requestId = Number(approveBtn.dataset.requestId || 0);
                if (requestId <= 0) {
                    return;
                }

                const confirmation = window.Swal?.fire
                    ? await window.Swal.fire({
                        icon: 'warning',
                        title: 'Aprobar anulacion',
                        text: 'Se aprobara la anulacion de esta solicitud.',
                        showCancelButton: true,
                        confirmButtonText: 'Aprobar',
                        cancelButtonText: 'Cancelar'
                    })
                    : { isConfirmed: window.confirm('Se aprobara la anulacion de esta solicitud. Desea continuar?') };

                if (!confirmation?.isConfirmed) {
                    return;
                }

                const result = await sendAction({
                    requestId,
                    rejectReason: null,
                    state: 'ANNULLED_APPROVED',
                    sing: null,
                });

                if (result.success === true || String(result.code) === '200') {
                    await notify('Anulacion aprobada exitosamente.', 'success');
                    window.location.reload();
                } else {
                    await notify(result.errorMessage || result.message || 'No fue posible aprobar la anulacion.', 'error');
                }
            }
        });

        rejectConfirmBtn?.addEventListener('click', async () => {
            const requestId = Number(rejectRequestIdInput?.value || 0);
            const reason = String(rejectReasonInput?.value || '').trim();

            if (!reason) {
                await notify('Debe ingresar un motivo de rechazo.');
                return;
            }

            rejectConfirmBtn.disabled = true;
            rejectConfirmBtn.textContent = 'Procesando...';

            try {
                const result = await sendAction({
                    requestId,
                    rejectReason: reason,
                    state: 'REJECTED',
                    sing: null,
                });

                if (result.success === true || String(result.code) === '200') {
                    getModal(rejectModalEl)?.hide();
                    await notify('Solicitud rechazada exitosamente.', 'success');
                    window.location.reload();
                } else {
                    await notify(result.errorMessage || result.message || 'No fue posible rechazar la solicitud.', 'error');
                }
            } finally {
                rejectConfirmBtn.disabled = false;
                rejectConfirmBtn.textContent = 'Rechazar Solicitud';
            }
        });

        annulConfirmBtn?.addEventListener('click', async () => {
            const requestId = Number(annulRequestIdInput?.value || 0);
            const reason = String(annulReasonInput?.value || '').trim();

            if (!reason) {
                await notify('Debe ingresar un motivo de anulacion.');
                return;
            }

            if (window.VacationSignaturePad?.isEmpty?.()) {
                await notify('Debe ingresar la firma para anular la solicitud.');
                return;
            }

            const signature = window.VacationSignaturePad?.toDataUrl?.();
            if (!signature) {
                await notify('No fue posible obtener la firma.', 'error');
                return;
            }

            annulConfirmBtn.disabled = true;
            annulConfirmBtn.textContent = 'Procesando...';

            try {
                const result = await sendAction({
                    requestId,
                    rejectReason: reason,
                    state: 'ANNULLED',
                    sing: signature,
                });

                if (result.success === true || String(result.code) === '200') {
                    getModal(annulModalEl)?.hide();
                    await notify('Solicitud anulada exitosamente.', 'success');
                    window.location.reload();
                } else {
                    await notify(result.errorMessage || result.message || 'No fue posible anular la solicitud.', 'error');
                }
            } finally {
                annulConfirmBtn.disabled = false;
                annulConfirmBtn.textContent = 'Anular Solicitud';
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVacationStateModule);
    } else {
        initVacationStateModule();
    }
})();
</script>

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
        <ul class="nav nav-tabs mb-3" id="vacationRequestTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" role="tab" aria-selected="true" data-request-type="VACATION">Vacaciones</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" aria-selected="false" data-request-type="PERMISSION">Permisos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" aria-selected="false" data-request-type="MEDICAL_LEAVE">Incapacidades</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" aria-selected="false" data-request-type="LICENSE">Licencias</button>
            </li>
        </ul>

        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label class="form-label" for="searchVacationRequests">Buscar solicitudes</label>
                <input type="text" id="searchVacationRequests" class="form-control" placeholder="ID o usuario">
            </div>
            <div class="col-auto">
                <button type="button" id="searchVacationRequestsButton" class="btn btn-outline-primary">Buscar</button>
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
                    <th id="vacationRequestExtraTypeHeading" class="d-none"></th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody id="vacationRequestsTableBody"><tr><td colspan="8" class="text-muted">Cargando solicitudes...</td></tr></tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="vacationRequestsPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="vacationRequestsPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="vacationRequestsPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8">8</option>
                    <option value="10" selected>10</option>
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
                    <input type="hidden" name="requestType" id="vacationRequestTypeInput">

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
                        <label class="form-label" for="vacationPdfFile">Archivo PDF <span id="vacationPdfFileHint" class="text-muted"></span></label>
                        <input type="file" class="form-control" id="vacationPdfFile" name="pdfFile" accept="application/pdf,.pdf">
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

<div class="modal fade" id="vacationAddBoletaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Boleta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="vacationAddBoletaForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="requestId" id="vacationBoletaRequestIdInput">

                    <div class="mb-3">
                        <label class="form-label" for="vacationBoletaFile">Archivo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="vacationBoletaFile" name="file" accept="application/pdf,.pdf,image/jpeg,.jpg,.jpeg,image/png,.png" required>
                        <small class="text-muted">Formatos permitidos: PDF, JPG o PNG. Tamano maximo: 10 MB.</small>
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
                        <input type="number" min="0.01" max="255" step="any" class="form-control" id="adjustRequestCant" required>
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
        const searchButton = document.getElementById('searchVacationRequestsButton');
        const tabButtons = Array.from(document.querySelectorAll('#vacationRequestTabs [data-request-type]'));
        const paginationInfo = document.getElementById('vacationRequestsPaginationInfo');
        const perPageSelect = document.getElementById('vacationRequestsPerPage');
        const prevPageButton = document.getElementById('vacationRequestsPrevPage');
        const nextPageButton = document.getElementById('vacationRequestsNextPage');
        const pageIndicator = document.getElementById('vacationRequestsPageIndicator');
        const extraTypeHeading = document.getElementById('vacationRequestExtraTypeHeading');

        const signersModalEl = document.getElementById('vacationSignersModal');
        const filesModalEl = document.getElementById('vacationFilesModal');
        const addSignersModalEl = document.getElementById('vacationAddSignersModal');
        const addBoletaModalEl = document.getElementById('vacationAddBoletaModal');
        const adjustModalEl = document.getElementById('adjustVacationModal');

        const signersBody = document.getElementById('vacationSignersBody');
        const filesBody = document.getElementById('vacationFilesBody');
        const addSignersForm = document.getElementById('vacationAddSignersForm');
        const requestIdInput = document.getElementById('vacationRequestIdInput');
        const requestTypeInput = document.getElementById('vacationRequestTypeInput');
        const pdfFileInput = document.getElementById('vacationPdfFile');
        const pdfFileHint = document.getElementById('vacationPdfFileHint');
        const addBoletaForm = document.getElementById('vacationAddBoletaForm');
        const boletaRequestIdInput = document.getElementById('vacationBoletaRequestIdInput');
        const boletaFileInput = document.getElementById('vacationBoletaFile');
        let boletaUploadUrl = '';
        let boletaUploadLabel = 'archivo';
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
            requestType: 'VACATION',
            search: null,
            page: 1,
            perPage: Number(perPageSelect?.value || 10),
            totalRows: 0,
            requestVersion: 0
        };

        const getTotalPages = () => Math.max(1, Math.ceil(paginationState.totalRows / paginationState.perPage));

        const updatePagination = () => {
            const totalPages = getTotalPages();
            const firstRow = paginationState.totalRows === 0 ? 0 : (paginationState.page - 1) * paginationState.perPage + 1;
            const lastRow = Math.min(paginationState.page * paginationState.perPage, paginationState.totalRows);

            if (paginationInfo) {
                paginationInfo.textContent = `Mostrando ${firstRow} a ${lastRow} de ${paginationState.totalRows} registros`;
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

        const formatDate = (value) => {
            const parts = String(value || '').slice(0, 10).split('-');
            return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '';
        };

        const renderRequests = (rows) => {
            const showLicenseType = paginationState.requestType === 'LICENSE';
            const showMedicalLeaveType = paginationState.requestType === 'MEDICAL_LEAVE';
            const showExtraType = showLicenseType || showMedicalLeaveType;

            if (extraTypeHeading) {
                extraTypeHeading.classList.toggle('d-none', !showExtraType);
                extraTypeHeading.textContent = showLicenseType ? 'Tipo de licencia' : 'Tipo de incapacidad';
            }

            if (!Array.isArray(rows) || rows.length === 0) {
                body.innerHTML = `<tr><td colspan="${showExtraType ? 9 : 8}" class="text-muted">No hay solicitudes para mostrar.</td></tr>`;
                return;
            }

            const stateBadges = {
                PENDING: '<span class="badge text-bg-warning">Pendiente</span>',
                TOSIGNED: '<span class="badge text-bg-info">Para Firmar</span>',
                APPROVED: '<span class="badge text-bg-success">Aprobado</span>',
                SIGN: '<span class="badge text-bg-success">Firmada</span>',
                ADJUSTED: '<span class="badge text-bg-warning">Ajustada</span>',
                ADJUSTMENT_ACCEPTED: '<span class="badge text-bg-success">Ajuste Aprobado</span>',
                REJECTED: '<span class="badge text-bg-danger">Rechazado</span>',
                ANNULLED: '<span class="badge text-bg-dark">Anulada</span>',
                ANNULLED_APPROVED: '<span class="badge text-bg-primary">Anulacion Aprobada</span>'
            };

            body.innerHTML = rows.map((request) => {
                const id = Number(request.id || 0);
                const stateKey = String(request.stateKey || '').toUpperCase();
                const stateName = String(request.stateName || 'Desconocido');
                const startDate = String(request.startDate || '');
                const typeName = String(request.requestTypeName || '');
                const quantity = String(request.quantity ?? 0);
                const quantityUnit = paginationState.requestType === 'VACATION'
                    ? ' d'
                    : (paginationState.requestType === 'PERMISSION' ? ' h' : '');
                const extraType = showLicenseType
                    ? request.licenseTypeName
                    : (showMedicalLeaveType ? request.medicalLeaveTypeName : '');
                const today = new Date();
                const todayIso = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                const startDatePast = startDate.length >= 10 && startDate.slice(0, 10) < todayIso;
                const actions = [
                    `<a href="<?= htmlspecialchars(base_url('rrhh/solicitud-vacaciones/detalle'), ENT_QUOTES, 'UTF-8') ?>?id=${id}" class="btn btn-sm btn-outline-primary">Ver Detalle</a>`
                ];

                if (paginationState.requestType === 'VACATION' && stateKey === 'PENDING') {
                    actions.push(`<button type="button" class="btn btn-sm btn-primary btn-add-file-to-sign" data-request-id="${id}">Agregar boleta</button>`);
                }
                if (paginationState.requestType === 'PERMISSION') {
                    actions.push(`<button type="button" class="btn btn-sm btn-success btn-add-boleta" data-request-id="${id}">Comprobante</button>`);
                }
                actions.push(`<button type="button" class="btn btn-sm btn-outline-info btn-view-signers" data-request-id="${id}">Ver Firmantes</button>`);
                if (stateKey !== 'PENDING') {
                    actions.push(`<button type="button" class="btn btn-sm btn-outline-secondary btn-view-files" data-request-id="${id}">Archivos</button>`);
                }
                if (!['REJECTED', 'ANNULLED', 'ANNULLED_APPROVED', 'SIGN', 'ADJUSTMENT_ACCEPTED'].includes(stateKey) && !startDatePast) {
                    actions.push(`<button type="button" class="btn btn-sm btn-danger btn-reject-vacation" data-request-id="${id}">Rechazar</button>`);
                }
                if (stateKey === 'ANNULLED') {
                    actions.push(`<button type="button" class="btn btn-sm btn-success btn-approve-annulment" data-request-id="${id}">Aprobar anulación</button>`);
                }
                if (stateKey === 'SIGN' && paginationState.requestType === 'PERMISSION') {
                    actions.push(`<button type="button" class="btn btn-sm btn-warning btn-adjust-vacation" data-request-id="${id}">Ajustar</button>`);
                }

                const extraCell = showExtraType ? `<td>${escapeHtml(extraType || '—')}</td>` : '';
                return `
                    <tr data-id="${id}"
                        data-user="${escapeHtml(request.userName || '')}"
                        data-description="${escapeHtml(request.description || '')}"
                        data-start="${escapeHtml(formatDate(startDate))}"
                        data-end="${escapeHtml(formatDate(request.endDate))}"
                        data-type="${escapeHtml(typeName)}"
                        data-quantity="${escapeHtml(quantity)}"
                        data-state="${escapeHtml(stateName)}">
                        <td>${id}</td>
                        <td>${escapeHtml(request.userName || '')}</td>
                        <td>${escapeHtml(formatDate(startDate))}</td>
                        <td>${escapeHtml(formatDate(request.endDate))}</td>
                        <td><span class="badge ${paginationState.requestType === 'PERMISSION' ? 'text-bg-secondary' : 'text-bg-primary'}">${escapeHtml(typeName || '—')}</span></td>
                        ${extraCell}
                        <td>${escapeHtml(quantity)}${quantityUnit}</td>
                        <td>${stateBadges[stateKey] || `<span class="badge text-bg-secondary">${escapeHtml(stateName)}</span>`}</td>
                        <td><div class="d-flex flex-wrap gap-2 vacation-actions">${actions.join('')}</div></td>
                    </tr>
                `;
            }).join('');
        };

        const fetchRequests = async () => {
            const requestVersion = ++paginationState.requestVersion;
            const pageSize = paginationState.perPage;
            body.innerHTML = `<tr><td colspan="${paginationState.requestType === 'LICENSE' || paginationState.requestType === 'MEDICAL_LEAVE' ? 9 : 8}" class="text-muted">Cargando solicitudes...</td></tr>`;
            if (paginationInfo) {
                paginationInfo.textContent = '';
            }

            try {
                const result = await fetchJson('<?= htmlspecialchars(base_url('rrhh/solicitudes-vacaciones/list'), ENT_QUOTES, 'UTF-8') ?>', {
                    page_number: paginationState.page,
                    page_size: pageSize,
                    request_type: paginationState.requestType,
                    search: paginationState.search,
                    _csrf_token: csrfToken
                });

                if (requestVersion !== paginationState.requestVersion) {
                    return;
                }
                if (String(result.code) !== '200' || Number(result.http_code || 200) >= 400) {
                    const errorCode = String(result.code || result.http_code || '');
                    if (errorCode === '401' || errorCode === '406' || result.http_code === 401 || result.http_code === 406) {
                        window.location.href = '<?= htmlspecialchars(base_url('login'), ENT_QUOTES, 'UTF-8') ?>';
                        return;
                    }
                    throw new Error(result.message || 'No fue posible cargar las solicitudes.');
                }

                const resultData = result.data || {};
                paginationState.totalRows = Number(resultData.totalRows || 0);
                const totalPages = getTotalPages();
                if (paginationState.page > totalPages) {
                    paginationState.page = totalPages;
                    fetchRequests();
                    return;
                }
                renderRequests(resultData.requestList || []);
                updatePagination();
            } catch (error) {
                if (requestVersion !== paginationState.requestVersion) {
                    return;
                }
                const message = error instanceof Error ? error.message : 'No fue posible cargar las solicitudes.';
                body.innerHTML = `<tr><td colspan="${paginationState.requestType === 'LICENSE' || paginationState.requestType === 'MEDICAL_LEAVE' ? 9 : 8}" class="text-danger">${escapeHtml(message)}</td></tr>`;
                paginationState.totalRows = 0;
                updatePagination();
            }
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

        const searchRequests = () => {
            paginationState.search = String(search?.value || '').trim() || null;
            paginationState.page = 1;
            fetchRequests();
        };

        searchButton?.addEventListener('click', searchRequests);
        search?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                searchRequests();
            }
        });

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                tabButtons.forEach((tab) => {
                    const isActive = tab === button;
                    tab.classList.toggle('active', isActive);
                    tab.setAttribute('aria-selected', String(isActive));
                });
                paginationState.requestType = String(button.dataset.requestType || 'VACATION');
                paginationState.page = 1;
                fetchRequests();
            });
        });

        perPageSelect?.addEventListener('change', () => {
            const newPerPage = Number(perPageSelect.value || 10);
            paginationState.perPage = Number.isFinite(newPerPage) && newPerPage > 0 ? newPerPage : 10;
            paginationState.page = 1;
            fetchRequests();
        });

        prevPageButton?.addEventListener('click', () => {
            if (paginationState.page <= 1) {
                return;
            }

            paginationState.page -= 1;
            fetchRequests();
        });

        nextPageButton?.addEventListener('click', () => {
            if (paginationState.page >= getTotalPages()) {
                return;
            }

            paginationState.page += 1;
            fetchRequests();
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

                // requestType 1 = Permiso (PDF opcional); 0 = Vacaciones (PDF obligatorio)
                const requestType = String(addButton.dataset.requestType || '');
                const isPermiso = requestType === '1';
                if (requestTypeInput) {
                    requestTypeInput.value = requestType;
                }
                if (pdfFileInput) {
                    pdfFileInput.value = '';
                    pdfFileInput.required = !isPermiso;
                }
                if (pdfFileHint) {
                    pdfFileHint.textContent = isPermiso ? '(opcional)' : '(obligatorio)';
                }

                requestIdInput.value = String(requestId);
                addSignersModal.show();
                return;
            }

            const uploadFileButton = target.closest('.btn-add-boleta, .btn-add-file-to-sign');
            if (uploadFileButton instanceof HTMLElement) {
                const requestId = Number(uploadFileButton.dataset.requestId || 0);
                const addBoletaModal = getModalInstance(addBoletaModalEl);
                if (requestId <= 0 || !addBoletaModal || !boletaRequestIdInput) {
                    return;
                }

                const isFileToSign = uploadFileButton.classList.contains('btn-add-file-to-sign');
                boletaUploadUrl = isFileToSign
                    ? window.APP.vacationUploadFileToSignUrl
                    : window.APP.vacationUploadComprobanteUrl;
                boletaUploadLabel = isFileToSign ? 'boleta' : 'comprobante';

                if (boletaFileInput) {
                    boletaFileInput.value = '';
                }

                boletaRequestIdInput.value = String(requestId);
                addBoletaModal.show();
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
        const hasPdf = pdfFile instanceof File && pdfFile.size > 0;

        if (!hasPdf && pdfFileInput?.required) {
            await notify('Debe seleccionar un archivo PDF para solicitudes de vacaciones.');
            return;
        }

        if (hasPdf && pdfFile.size > 5 * 1024 * 1024) {
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

        addBoletaForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(addBoletaForm);
            const boletaFile = formData.get('file');
            const hasFile = boletaFile instanceof File && boletaFile.size > 0;

            if (!hasFile) {
                await notify('Debe seleccionar un archivo para la boleta.');
                return;
            }

            if (boletaFile.size > 10 * 1024 * 1024) {
                await notify('El archivo no puede superar 10 MB.');
                return;
            }

            if (!boletaUploadUrl) {
                await notify('No se pudo determinar el endpoint para subir el archivo.', 'error');
                return;
            }

            const response = await fetch(boletaUploadUrl, {
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
                const itemName = boletaUploadLabel === 'boleta' ? 'la boleta' : 'el comprobante';
                await notify(result.message || `No fue posible agregar ${itemName}.`, 'error');
                return;
            }

            const itemName = boletaUploadLabel === 'boleta' ? 'la boleta' : 'el comprobante';
            await notify(`Se agregó ${itemName} exitosamente.`, 'success');
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

            if (!Number.isFinite(requestCant) || requestCant <= 0) {
                await notify('La cantidad debe ser mayor a 0.');
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
                    requestCant,
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

        fetchRequests();
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

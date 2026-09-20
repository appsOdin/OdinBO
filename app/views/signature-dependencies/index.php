<?php
/** @var array<int, array<string, mixed>> $dependencies */
$dependencies = $dependencies ?? [];
$apiMessage = $apiMessage ?? '';
$apiHttpCode = $apiHttpCode ?? 200;
$csrfToken = $csrfToken ?? get_csrf_token();
?>
<input type="hidden" id="signatureDependencyCsrfToken" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Dependencia de Firmas</h2>
    </div>
    <button type="button" class="btn btn-primary" id="openAddSignatureDependencyModal">Agregar</button>
</section>

<?php if ($apiHttpCode !== 200 && $apiHttpCode !== 403 && $apiMessage !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($apiMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6 col-lg-4">
                <input type="text" id="searchSignatureDependencies" class="form-control" placeholder="Buscar solicitante o aprobador">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="signatureDependencyTable">
                <thead>
                <tr>
                    <th>Solicitante</th>
                    <th>Aprobador</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
                </thead>
                <tbody id="signatureDependencyTableBody">
                <?php foreach ($dependencies as $dependency): ?>
                    <?php
                    $id = (string) ($dependency['id'] ?? '');
                    $requestUserId = (string) ($dependency['request_user_id'] ?? '');
                    $requestUserName = (string) ($dependency['request_user_fullname'] ?? '');
                    $authorizeUserId = (string) ($dependency['authorize_user_id'] ?? '');
                    $authorizeUserName = (string) ($dependency['authorize_user_fullname'] ?? '');
                    $searchText = strtolower(implode(' ', [$requestUserId, $requestUserName, $authorizeUserId, $authorizeUserName]));
                    ?>
                    <tr
                        data-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                        data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        data-request-user-id="<?= htmlspecialchars($requestUserId, ENT_QUOTES, 'UTF-8') ?>"
                        data-request-user-name="<?= htmlspecialchars($requestUserName, ENT_QUOTES, 'UTF-8') ?>"
                        data-authorize-user-id="<?= htmlspecialchars($authorizeUserId, ENT_QUOTES, 'UTF-8') ?>"
                        data-authorize-user-name="<?= htmlspecialchars($authorizeUserName, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <td>
                            <?php if ($requestUserName !== ''): ?>
                                <div><?= htmlspecialchars($requestUserName, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($requestUserId !== ''): ?>
                                <small class="text-muted"><?= htmlspecialchars($requestUserId, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($authorizeUserName !== ''): ?>
                                <div><?= htmlspecialchars($authorizeUserName, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($authorizeUserId !== ''): ?>
                                <small class="text-muted"><?= htmlspecialchars($authorizeUserId, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-edit-signature-dependency"
                                    data-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
                                    data-request-user-id="<?= htmlspecialchars($requestUserId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-authorize-user-id="<?= htmlspecialchars($authorizeUserId, ENT_QUOTES, 'UTF-8') ?>">
                                Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($dependencies === []): ?>
                    <tr id="signatureDependencyEmptyRow">
                        <td colspan="3" class="text-center text-muted py-4">No hay dependencias de firmas registradas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="signatureDependencyPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="signatureDependencyPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="signatureDependencyPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="signatureDependencyPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="signatureDependencyPageIndicator">Pagina 1 de 1</small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="signatureDependencyNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="signatureDependencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title" id="signatureDependencyModalTitle">Agregar Dependencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="signatureDependencyId">

                <div class="mb-3">
                    <label class="form-label" for="signatureDependencyRequestUserSearch">Solicitante</label>
                    <div id="signatureDependencyRequestUserInfo" class="d-none border rounded p-2 bg-light">
                        <div id="signatureDependencyRequestUserName" class="fw-semibold">-</div>
                        <small id="signatureDependencyRequestUserIdText" class="text-muted d-block">-</small>
                    </div>
                    <div id="signatureDependencyRequestUserSelectWrapper">
                        <input type="text" class="form-control form-control-sm mb-2" id="signatureDependencyRequestUserSearch" placeholder="Buscar solicitante">
                        <select id="signatureDependencyRequestUserSelect" class="form-select" size="6" required>
                            <option value="">Cargando usuarios...</option>
                        </select>
                    </div>
                    <input type="hidden" id="signatureDependencyRequestUserHiddenId">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="signatureDependencyAuthorizeUserSearch">Aprobador</label>
                    <input type="text" class="form-control form-control-sm mb-2" id="signatureDependencyAuthorizeUserSearch" placeholder="Buscar aprobador">
                    <select id="signatureDependencyAuthorizeUserSelect" class="form-select" size="6" required>
                        <option value="">Cargando usuarios...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="signatureDependencySubmitButton">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const tableBody = document.getElementById('signatureDependencyTableBody');
    const searchInput = document.getElementById('searchSignatureDependencies');
    const perPageSelect = document.getElementById('signatureDependencyPerPage');
    const prevPageButton = document.getElementById('signatureDependencyPrevPage');
    const nextPageButton = document.getElementById('signatureDependencyNextPage');
    const pageIndicator = document.getElementById('signatureDependencyPageIndicator');
    const paginationInfo = document.getElementById('signatureDependencyPaginationInfo');
    const modalEl = document.getElementById('signatureDependencyModal');
    const modalTitle = document.getElementById('signatureDependencyModalTitle');
    const submitButton = document.getElementById('signatureDependencySubmitButton');
    const requestUserSelect = document.getElementById('signatureDependencyRequestUserSelect');
    const authorizeUserSelect = document.getElementById('signatureDependencyAuthorizeUserSelect');
    const requestUserSearch = document.getElementById('signatureDependencyRequestUserSearch');
    const authorizeUserSearch = document.getElementById('signatureDependencyAuthorizeUserSearch');
    const requestUserInfo = document.getElementById('signatureDependencyRequestUserInfo');
    const requestUserInfoName = document.getElementById('signatureDependencyRequestUserName');
    const requestUserInfoIdText = document.getElementById('signatureDependencyRequestUserIdText');
    const requestUserSelectWrapper = document.getElementById('signatureDependencyRequestUserSelectWrapper');
    const requestUserHiddenId = document.getElementById('signatureDependencyRequestUserHiddenId');
    const dependencyIdInput = document.getElementById('signatureDependencyId');
    const addButton = document.getElementById('openAddSignatureDependencyModal');
    const csrfTokenInput = document.getElementById('signatureDependencyCsrfToken');
    const csrfToken = csrfTokenInput?.value || window.APP?.csrfToken || '';

    if (!tableBody) {
        return;
    }

    const state = {
        search: '',
        page: 1,
        perPage: Number(perPageSelect?.value || 8),
        allRows: Array.from(tableBody.querySelectorAll('tr[data-search]')),
        rows: []
    };

    const getModalInstance = () => {
        if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    };

    const notify = async (message, icon = 'warning') => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            await window.Swal.fire({
                icon: icon === 'success' ? 'success' : 'error',
                text: message,
                confirmButtonText: 'Aceptar'
            });
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
        const isJson = contentType.toLowerCase().includes('application/json');

        if (!isJson) {
            return {
                code: String(response.status || 500),
                message: 'Respuesta invalida del servidor.',
                data: null,
                http_code: response.status || 500
            };
        }

        return response.json();
    };

    const renderTable = () => {
        const totalRows = state.rows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / state.perPage));

        if (state.page > totalPages) {
            state.page = totalPages;
        }

        state.allRows.forEach((row) => {
            row.style.display = 'none';
        });

        const start = (state.page - 1) * state.perPage;
        const end = start + state.perPage;
        state.rows.slice(start, end).forEach((row) => row.style.display = '');

        if (paginationInfo) {
            paginationInfo.textContent = `Mostrando ${totalRows === 0 ? 0 : start + 1} a ${Math.min(end, totalRows)} de ${totalRows} registros`;
        }

        if (pageIndicator) {
            pageIndicator.textContent = `Pagina ${state.page} de ${totalPages}`;
        }

        if (prevPageButton) {
            prevPageButton.disabled = state.page <= 1;
        }

        if (nextPageButton) {
            nextPageButton.disabled = state.page >= totalPages;
        }
    };

    const applySearch = () => {
        const term = state.search.trim().toLowerCase();
        state.rows = term === ''
            ? [...state.allRows]
            : state.allRows.filter((row) => {
                const value = (row.dataset.search || '').toLowerCase();
                return value.includes(term);
            });

        state.page = 1;
        renderTable();
    };

    const buildUserSelectOptions = (select, items, selectedValue = '') => {
        if (!select) {
            return;
        }

        const normalizedItems = Array.isArray(items) ? items : [];
        const currentValue = String(selectedValue || '');
        select.innerHTML = '';

        if (normalizedItems.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay usuarios disponibles';
            select.appendChild(option);
            return;
        }

        normalizedItems.forEach((user) => {
            const option = document.createElement('option');
            const userId = String(user && user.id !== undefined ? user.id : '');
            const fullname = String(user && user.fullname !== undefined ? user.fullname : userId);
            option.value = userId;
            option.textContent = fullname;
            if (currentValue !== '' && userId === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    };

    const setUserSelectFilter = (input, select) => {
        if (!input || !select) {
            return;
        }

        input.addEventListener('input', () => {
            const term = input.value.trim().toLowerCase();
            Array.from(select.options).forEach((option) => {
                const optionText = option.textContent || '';
                option.hidden = term !== '' && !optionText.toLowerCase().includes(term) && option.value !== '';
            });
        });
    };

    const loadUserLists = async (selectedRequestUser = '', selectedAuthorizeUser = '') => {
        const result = await fetchJson(window.APP.signatureDependencyUsersUrl, {
            _csrf_token: csrfToken
        });

        if (String(result.code) !== '200') {
            notify(result.message || 'No fue posible cargar los usuarios.', 'error');
            return false;
        }

        const payload = result.data || {};
        const requestUsers = Array.isArray(payload.request_users) ? payload.request_users : [];
        const authorizeUsers = Array.isArray(payload.authorize_user) ? payload.authorize_user : [];

        buildUserSelectOptions(requestUserSelect, requestUsers, selectedRequestUser);
        buildUserSelectOptions(authorizeUserSelect, authorizeUsers, selectedAuthorizeUser);
        return true;
    };

    const resetModalForm = () => {
        dependencyIdInput.value = '';
        requestUserSelect.value = '';
        if (requestUserHiddenId) {
            requestUserHiddenId.value = '';
        }
        authorizeUserSelect.value = '';
        if (requestUserInfo) {
            requestUserInfo.classList.add('d-none');
        }
        if (requestUserSelectWrapper) {
            requestUserSelectWrapper.classList.remove('d-none');
        }
        if (requestUserInfoName) {
            requestUserInfoName.textContent = '-';
        }
        if (requestUserInfoIdText) {
            requestUserInfoIdText.textContent = '-';
        }
        if (requestUserSearch) {
            requestUserSearch.value = '';
        }
        if (authorizeUserSearch) {
            authorizeUserSearch.value = '';
        }
        if (submitButton) {
            submitButton.textContent = 'Agregar';
        }
    };

    const openCreateModal = async () => {
        resetModalForm();
        if (modalTitle) {
            modalTitle.textContent = 'Agregar Dependencia';
        }
        if (submitButton) {
            submitButton.textContent = 'Agregar';
        }

        const modal = getModalInstance();
        if (modal) {
            modal.show();
        }

        await loadUserLists();
    };

    const setRequestUserEditState = (userId = '', userName = '') => {
        if (requestUserHiddenId) {
            requestUserHiddenId.value = String(userId || '').trim();
        }

        if (requestUserInfoName) {
            requestUserInfoName.textContent = userName || 'Sin nombre';
        }

        if (requestUserInfoIdText) {
            requestUserInfoIdText.textContent = userId || 'Sin ID';
        }

        if (requestUserInfo) {
            requestUserInfo.classList.remove('d-none');
        }
        if (requestUserSelectWrapper) {
            requestUserSelectWrapper.classList.add('d-none');
        }
    };

    const openEditModal = async (row) => {
        if (!row) {
            return;
        }

        resetModalForm();
        if (modalTitle) {
            modalTitle.textContent = 'Editar Dependencia';
        }
        if (submitButton) {
            submitButton.textContent = 'Guardar cambios';
        }

        const modal = getModalInstance();
        if (modal) {
            modal.show();
        }

        const requestUserId = String(row.dataset.requestUserId || '');
        const requestUserName = String(row.dataset.requestUserName || '');
        const authorizeUserId = String(row.dataset.authorizeUserId || '');
        dependencyIdInput.value = String(row.dataset.id || '');

        setRequestUserEditState(requestUserId, requestUserName);

        await loadUserLists(requestUserId, authorizeUserId);
    };

    const handleSubmit = async () => {
        const requestUserId = String(requestUserHiddenId?.value || requestUserSelect?.value || '').trim();
        const authorizeUserId = String(authorizeUserSelect?.value || '').trim();
        const id = Number(dependencyIdInput?.value || 0);

        if (requestUserId === '') {
            await notify('Debe seleccionar un solicitante.', 'error');
            return;
        }

        if (authorizeUserId === '') {
            await notify('Debe seleccionar un aprobador.', 'error');
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Procesando...';
        }

        try {
            const payload = {
                _csrf_token: csrfToken,
                request_user_id: requestUserId,
                authorize_user_id: authorizeUserId,
            };

            const url = id > 0
                ? window.APP.signatureDependencyUpdateUrl
                : window.APP.signatureDependencyStoreUrl;

            if (id > 0) {
                payload.id = id;
            }

            const result = await fetchJson(url, payload);
            if (String(result.code) !== '200') {
                await notify(result.message || 'No fue posible guardar la dependencia.', 'error');
                return;
            }

            const modal = getModalInstance();
            modal?.hide();
            await notify(result.data || result.message || 'Dependencia guardada exitosamente.', 'success');
            window.location.reload();
        } catch (error) {
            await notify('Ocurrio un error al guardar la dependencia.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = dependencyIdInput && dependencyIdInput.value !== '' ? 'Guardar cambios' : 'Agregar';
            }
        }
    };

    searchInput?.addEventListener('input', (event) => {
        state.search = String(event.target.value || '');
        applySearch();
    });

    perPageSelect?.addEventListener('change', () => {
        const nextPerPage = Number(perPageSelect.value || 8);
        state.perPage = Number.isFinite(nextPerPage) && nextPerPage > 0 ? nextPerPage : 8;
        state.page = 1;
        renderTable();
    });

    prevPageButton?.addEventListener('click', () => {
        if (state.page <= 1) {
            return;
        }
        state.page -= 1;
        renderTable();
    });

    nextPageButton?.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(state.rows.length / state.perPage));
        if (state.page >= totalPages) {
            return;
        }
        state.page += 1;
        renderTable();
    });

    addButton?.addEventListener('click', openCreateModal);
    submitButton?.addEventListener('click', handleSubmit);

    setUserSelectFilter(requestUserSearch, requestUserSelect);
    setUserSelectFilter(authorizeUserSearch, authorizeUserSelect);

    tableBody.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const editButton = target.closest('.btn-edit-signature-dependency');
        if (!editButton) {
            return;
        }

        const row = editButton.closest('tr');
        if (row) {
            await openEditModal(row);
        }
    });

    state.rows = [...state.allRows];
    renderTable();
})();
</script>

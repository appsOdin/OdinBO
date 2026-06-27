(() => {
    const globalLoader = document.getElementById('globalLoader');
    const toastContainer = document.getElementById('toastContainer');
    let isSessionExpiredHandled = false;

    const showLoader = () => globalLoader && globalLoader.classList.remove('d-none');
    const hideLoader = () => globalLoader && globalLoader.classList.add('d-none');

    const showToast = (type, message) => {
        if (!toastContainer) {
            return;
        }

        const color = type === 'success' ? 'success' : 'danger';
        const wrapper = document.createElement('div');
        wrapper.className = `toast align-items-center text-bg-${color} border-0`;
        wrapper.role = 'alert';
        wrapper.ariaLive = 'assertive';
        wrapper.ariaAtomic = 'true';
        wrapper.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        toastContainer.appendChild(wrapper);
        const toast = new bootstrap.Toast(wrapper, { delay: 3500 });
        toast.show();
        wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
    };

    const getHttpCode = (response, payload) => {
        // Check payload first: controllers may forward API error codes inside a 200 HTTP envelope.
        if (payload && typeof payload === 'object') {
            const fromHttpCode = Number(payload.http_code ?? 0);
            if (fromHttpCode === 401 || fromHttpCode === 403) {
                return fromHttpCode;
            }
            const fromCode = Number(payload.code ?? 0);
            if (fromCode === 401 || fromCode === 403) {
                return fromCode;
            }
        }
        // Fall back to the actual HTTP response status.
        if (response && response.status) {
            return response.status;
        }
        return 0;
    };

    const showForbiddenMessage = () => {
        const main = document.querySelector('main');
        if (!main) {
            return;
        }
        const dashboardUrl = window.APP?.dashboardUrl || '/dashboard';
        main.innerHTML = `
            <div style="
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 60vh;
                gap: 16px;
                text-align: center;
                padding: 32px;
            ">
                <div style="
                    width: 72px;
                    height: 72px;
                    border-radius: 50%;
                    background: #fff3cd;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#e6a817" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                </div>
                <div>
                    <p style="margin: 0 0 6px; font-size: 1.25rem; font-weight: 600; color: #212529; letter-spacing: -0.01em;">Acceso denegado</p>
                    <p style="margin: 0 0 16px; font-size: 0.9rem; color: #6c757d;">No tiene permisos para realizar esta accion.</p>
                    <a href="${dashboardUrl}"
                       style="display:inline-block;padding:8px 24px;background:#212529;color:#fff;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:500;">
                        Aceptar
                    </a>
                </div>
            </div>
        `;
    };

    const handleUnauthorized = async (message = 'Sesion expirada. Redirigiendo a login...') => {
        if (isSessionExpiredHandled) {
            return;
        }

        isSessionExpiredHandled = true;
        showToast('danger', message);

        try {
            const csrfToken = window.APP?.csrfToken || '';
            const logoutUrl = window.APP?.logoutUrl;

            if (logoutUrl && csrfToken) {
                await fetch(logoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _csrf_token: csrfToken })
                });
            }
        } catch (error) {
            // If logout request fails, continue with login redirect.
        }

        setTimeout(() => {
            try {
                window.location.href = window.APP?.loginUrl || '/login';
            } catch (error) {
                window.location.assign('/login');
            }
        }, 1200);
    };

    const fetchJson = async (url, payload) => {
        showLoader();
        try {
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
            const parsedPayload = isJson ? await response.json() : {
                code: String(response.status || ''),
                message: '',
                data: null,
                http_code: response.status,
            };

            const httpCode = getHttpCode(response, parsedPayload);
            const redirectedToLogin = response.redirected && typeof response.url === 'string' && response.url.toLowerCase().includes('/login');

            if (httpCode === 403) {
                showForbiddenMessage();
                return {
                    ...parsedPayload,
                    code: '403',
                    http_code: 403,
                };
            }

            if (httpCode === 401 || redirectedToLogin) {
                handleUnauthorized('Sesion expirada. Cerrando sesion y redirigiendo a login...');
                return {
                    ...parsedPayload,
                    code: '401',
                    http_code: 401,
                };
            }

            return parsedPayload;
        } finally {
            hideLoader();
        }
    };

    const consumeFlashMessages = () => {
        (window.APP?.flashMessages || []).forEach((flash) => {
            showToast(flash.type || 'success', flash.message || 'Operacion ejecutada');
        });
    };

    const initSidebar = () => {
        const toggleButton = document.getElementById('btnToggleSidebar');
        const sidebar = document.getElementById('sidebarNav');
        if (!toggleButton || !sidebar) {
            return;
        }

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    };

    const initTheme = () => {
        const themeSelect = document.getElementById('themeSelect');
        const root = document.documentElement;
        const storageKey = 'odinbo-theme';
        const allowedThemes = new Set(['default', 'flat', 'dark']);

        const applyTheme = (theme) => {
            const selected = allowedThemes.has(theme) ? theme : 'default';
            if (selected === 'default') {
                delete root.dataset.theme;
            } else {
                root.dataset.theme = selected;
            }
            return selected;
        };

        const savedTheme = localStorage.getItem(storageKey) || 'default';
        const appliedTheme = applyTheme(savedTheme);

        if (themeSelect instanceof HTMLSelectElement) {
            themeSelect.value = appliedTheme;
            themeSelect.addEventListener('change', (event) => {
                const value = event.target instanceof HTMLSelectElement ? event.target.value : 'default';
                const normalized = applyTheme(value);
                localStorage.setItem(storageKey, normalized);
            });
        }
    };

    const tableState = {
        search: '',
        page: 1,
        perPage: 8,
        sortField: 'name',
        sortDir: 'asc'
    };

    const getRoleName = (roleid) => {
        const map = { 1: 'ADMIN', 2: 'USER', 3: 'SUBSCRIBER' };
        return map[Number(roleid)] || 'USER';
    };

    const buildRow = (item) => {
        const tr = document.createElement('tr');
        tr.dataset.id = item.id || '';
        tr.dataset.name = item.name || '';
        tr.dataset.lastname = item.lastname || '';
        tr.dataset.username = item.username || '';
        tr.dataset.email = item.email || '';
        tr.dataset.role = item.rolename || getRoleName(item.roleid || 2);
        tr.dataset.roleid = String(item.roleid || 2);
        tr.dataset.state = String(item.state ?? 0);

        const stateBadge = Number(item.state) === 1
            ? '<span class="badge text-bg-success">Activo</span>'
            : '<span class="badge text-bg-danger">Inactivo</span>';

        tr.innerHTML = `
            <td>${item.name || ''}</td>
            <td>${item.lastname || ''}</td>
            <td>${item.username || ''}</td>
            <td>${item.email || ''}</td>
            <td>${item.rolename || getRoleName(item.roleid || 2)}</td>
            <td>${stateBadge}</td>
            <td><button class="btn btn-sm btn-outline-secondary btn-edit-user" type="button">Editar</button></td>
        `;
        return tr;
    };

    const escapeHtml = (value) => {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(value || ''));
        return p.innerHTML;
    };

    const formatCurrency = (value) => {
        const amount = Number(value || 0);
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(Number.isFinite(amount) ? amount : 0);
    };

    const initUsersModule = () => {
        const tableBody = document.getElementById('usersTableBody');
        if (!tableBody) {
            return;
        }

        const searchInput = document.getElementById('searchUsers');
        const paginationInfo = document.getElementById('paginationInfo');
        const perPageSelect = document.getElementById('usersPerPage');
        const prevPageButton = document.getElementById('usersPrevPage');
        const nextPageButton = document.getElementById('usersNextPage');
        const pageIndicator = document.getElementById('usersPageIndicator');
        const sortHeaders = document.querySelectorAll('th[data-sort]');

        tableState.perPage = Number(perPageSelect?.value || tableState.perPage);

        const getRows = () => Array.from(tableBody.querySelectorAll('tr'));

        const applyTableState = () => {
            const rows = getRows();
            const filtered = rows.filter((row) => {
                const values = [
                    row.dataset.name,
                    row.dataset.lastname,
                    row.dataset.username,
                    row.dataset.email
                ].join(' ').toLowerCase();
                return values.includes(tableState.search.toLowerCase());
            });

            filtered.sort((a, b) => {
                const field = tableState.sortField;
                const aVal = (a.dataset[field] || '').toLowerCase();
                const bVal = (b.dataset[field] || '').toLowerCase();
                if (aVal < bVal) {
                    return tableState.sortDir === 'asc' ? -1 : 1;
                }
                if (aVal > bVal) {
                    return tableState.sortDir === 'asc' ? 1 : -1;
                }
                return 0;
            });

            rows.forEach((row) => {
                row.style.display = 'none';
            });

            const totalPages = Math.max(1, Math.ceil(filtered.length / tableState.perPage));
            if (tableState.page > totalPages) {
                tableState.page = totalPages;
            }

            const start = (tableState.page - 1) * tableState.perPage;
            const end = start + tableState.perPage;
            filtered.slice(start, end).forEach((row) => {
                row.style.display = '';
            });

            if (paginationInfo) {
                paginationInfo.textContent = `Mostrando ${filtered.length === 0 ? 0 : start + 1} a ${Math.min(end, filtered.length)} de ${filtered.length} registros`;
            }

            if (pageIndicator) {
                pageIndicator.textContent = `Pagina ${tableState.page} de ${totalPages}`;
            }

            if (prevPageButton) {
                prevPageButton.disabled = tableState.page <= 1;
            }

            if (nextPageButton) {
                nextPageButton.disabled = tableState.page >= totalPages;
            }
        };

        searchInput?.addEventListener('input', (event) => {
            tableState.search = event.target.value || '';
            tableState.page = 1;
            applyTableState();
        });

        sortHeaders.forEach((header) => {
            header.addEventListener('click', () => {
                const field = header.dataset.sort;
                if (!field) {
                    return;
                }

                if (tableState.sortField === field) {
                    tableState.sortDir = tableState.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    tableState.sortField = field;
                    tableState.sortDir = 'asc';
                }
                applyTableState();
            });
        });

        perPageSelect?.addEventListener('change', () => {
            const nextPerPage = Number(perPageSelect.value || 8);
            tableState.perPage = Number.isFinite(nextPerPage) && nextPerPage > 0 ? nextPerPage : 8;
            tableState.page = 1;
            applyTableState();
        });

        prevPageButton?.addEventListener('click', () => {
            if (tableState.page <= 1) {
                return;
            }

            tableState.page -= 1;
            applyTableState();
        });

        nextPageButton?.addEventListener('click', () => {
            const totalRows = getRows().filter((row) => {
                const values = [
                    row.dataset.name,
                    row.dataset.lastname,
                    row.dataset.username,
                    row.dataset.email
                ].join(' ').toLowerCase();
                return values.includes(tableState.search.toLowerCase());
            }).length;

            const totalPages = Math.max(1, Math.ceil(totalRows / tableState.perPage));
            if (tableState.page >= totalPages) {
                return;
            }

            tableState.page += 1;
            applyTableState();
        });

        const createForm = document.getElementById('createUserForm');
        createForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = Object.fromEntries(new FormData(createForm).entries());

            const result = await fetchJson(window.APP.usersStoreUrl, formData);
            if (String(result.code) === '200') {
                showToast('success', result.message || 'Usuario creado correctamente');
                bootstrap.Modal.getInstance(document.getElementById('createUserModal'))?.hide();
                createForm.reset();
                await reloadUsers(tableBody, applyTableState);
            } else {
                showToast('danger', result.message || 'No fue posible crear el usuario');
            }
        });

        tableBody.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const editButton = target.closest('.btn-edit-user');
            if (!(editButton instanceof HTMLElement)) {
                return;
            }

            const row = editButton.closest('tr');
            const editModalEl = document.getElementById('editUserModal');
            const editId = document.getElementById('edit_id');
            const editName = document.getElementById('edit_name');
            const editLastname = document.getElementById('edit_lastname');
            const editUsername = document.getElementById('edit_username');
            const editEmail = document.getElementById('edit_email');
            const editRoleId = document.getElementById('edit_roleid');
            const editState = document.getElementById('edit_state');
            const editPassword = document.getElementById('edit_password');

            if (!row || !editModalEl || !editId || !editName || !editLastname || !editUsername || !editEmail || !editRoleId || !editState || !editPassword) {
                showToast('danger', 'No fue posible abrir el modal de edicion');
                return;
            }

            editId.value = row.dataset.id || '';
            editName.value = row.dataset.name || '';
            editLastname.value = row.dataset.lastname || '';
            editUsername.value = row.dataset.username || '';
            editEmail.value = row.dataset.email || '';
            editRoleId.value = row.dataset.roleid || '2';
            editState.value = row.dataset.state || '1';
            editPassword.value = '';

            const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
            editModal.show();
        });

        const editForm = document.getElementById('editUserForm');
        editForm?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = Object.fromEntries(new FormData(editForm).entries());
            const password = String(formData.password || '').trim();
            if (password === '') {
                delete formData.password;
            } else {
                formData.password = password;
            }

            const result = await fetchJson(window.APP.usersUpdateUrl, formData);

            if (String(result.code) === '200') {
                showToast('success', result.message || 'Usuario actualizado');
                bootstrap.Modal.getInstance(document.getElementById('editUserModal'))?.hide();
                await reloadUsers(tableBody, applyTableState);
            } else {
                showToast('danger', result.message || 'No fue posible actualizar el usuario');
            }
        });

        applyTableState();
    };

    const reloadUsers = async (tableBody, afterRender) => {
        showLoader();
        try {
            const response = await fetch(window.APP.usersListUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.toLowerCase().includes('application/json');
            const payload = isJson ? await response.json() : {
                code: String(response.status || ''),
                message: '',
                data: null,
                http_code: response.status,
            };

            const reloadHttpCode = getHttpCode(response, payload);
            const reloadRedirectedToLogin = response.redirected && typeof response.url === 'string' && response.url.toLowerCase().includes('/login');

            if (reloadHttpCode === 403) {
                showForbiddenMessage();
                return;
            }

            if (reloadHttpCode === 401 || reloadRedirectedToLogin) {
                handleUnauthorized('Sesion expirada. Cerrando sesion y redirigiendo a login...');
                return;
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];

            tableBody.innerHTML = '';
            rows.forEach((item) => {
                tableBody.appendChild(buildRow(item));
            });
            afterRender();
        } catch (error) {
            showToast('danger', 'No fue posible recargar el listado');
        } finally {
            hideLoader();
        }
    };

    const initArticlesModule = () => {
        const tableBody = document.getElementById('articlesTableBody');
        if (!tableBody) {
            return;
        }

        const searchForm = document.getElementById('articleSearchForm');
        const searchInput = document.getElementById('searchArticles');
        const clearButton = document.getElementById('clearArticleSearch');
        const perPageSelect = document.getElementById('articlesPerPage');
        const paginationInfo = document.getElementById('articlesPaginationInfo');
        const prevPageButton = document.getElementById('articlesPrevPage');
        const nextPageButton = document.getElementById('articlesNextPage');
        const pageIndicator = document.getElementById('articlesPageIndicator');

        const detailModalEl = document.getElementById('articleDetailModal');
        const imageModalEl = document.getElementById('articleImageModal');
        const detailSelected = document.getElementById('articleDetailSelected');
        const detailId = document.getElementById('articleDetailId');
        const detailDescription = document.getElementById('articleDetailDescription');
        const detailPrice = document.getElementById('articleDetailPrice');
        const detailNotes = document.getElementById('articleDetailNotes');
        const mainImage = document.getElementById('articleMainImage');
        const mainImageEmpty = document.getElementById('articleMainImageEmpty');
        const imageThumbs = document.getElementById('articleImageThumbs');
        const expandImageButton = document.getElementById('expandArticleImage');
        const expandedImage = document.getElementById('articleExpandedImage');
        const stocksTableBody = document.getElementById('articleStocksTableBody');

        let selectedImageDataUri = '';

        const state = {
            search: '',
            page: 1,
            perPage: Number(perPageSelect?.value || 8),
            rows: []
        };

        const buildArticleRow = (item) => {
            const id = escapeHtml(String(item.ID || ''));
            const description = escapeHtml(String(item.DESCRIPTION || ''));
            const price = formatCurrency(item.PRICE);

            return `
                <tr data-id="${id}" data-description="${description}" data-price="${escapeHtml(String(item.PRICE || 0))}">
                    <td>${id}</td>
                    <td>${description}</td>
                    <td>${price}</td>
                    <td><button class="btn btn-sm btn-outline-primary btn-article-detail" type="button">Detalle</button></td>
                </tr>
            `;
        };

        const parseInitialRows = () => Array.from(tableBody.querySelectorAll('tr')).map((row) => ({
            ID: row.dataset.id || '',
            DESCRIPTION: row.dataset.description || '',
            PRICE: Number(row.dataset.price || 0)
        }));

        const renderRows = () => {
            const totalRows = state.rows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / state.perPage));

            if (state.page > totalPages) {
                state.page = totalPages;
            }

            const start = (state.page - 1) * state.perPage;
            const end = start + state.perPage;
            const pageRows = state.rows.slice(start, end);

            tableBody.innerHTML = pageRows.map((item) => buildArticleRow(item)).join('');

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

        const normalizeDetail = (payload) => {
            const apiData = payload && typeof payload === 'object' ? payload : {};

            const article = apiData.Article
                || (Array.isArray(apiData.atricle) ? apiData.atricle[0] : null)
                || (Array.isArray(apiData.article) ? apiData.article[0] : null)
                || null;

            const pictures = Array.isArray(apiData.Pictures)
                ? apiData.Pictures
                : (Array.isArray(apiData.pictures) ? apiData.pictures : []);

            const stocks = Array.isArray(apiData.Stocks)
                ? apiData.Stocks
                : (Array.isArray(apiData.stocks) ? apiData.stocks : []);

            return { article, pictures, stocks };
        };

        const setMainImage = (dataUri) => {
            selectedImageDataUri = dataUri || '';

            if (!mainImage || !mainImageEmpty || !expandImageButton) {
                return;
            }

            if (selectedImageDataUri === '') {
                mainImage.classList.add('d-none');
                mainImage.removeAttribute('src');
                mainImageEmpty.classList.remove('d-none');
                expandImageButton.disabled = true;
                return;
            }

            mainImage.src = selectedImageDataUri;
            mainImage.classList.remove('d-none');
            mainImageEmpty.classList.add('d-none');
            expandImageButton.disabled = false;
        };

        const renderArticleDetail = (articleId, detailPayload) => {
            if (!detailModalEl || !detailId || !detailDescription || !detailPrice || !detailNotes || !imageThumbs || !stocksTableBody || !detailSelected) {
                return;
            }

            const normalized = normalizeDetail(detailPayload);
            const article = normalized.article || {};
            const pictures = normalized.pictures;
            const stocks = normalized.stocks;

            detailSelected.textContent = articleId ? `Seleccionado: ${articleId}` : '';
            detailId.textContent = article.ID || '-';
            detailDescription.textContent = article.DESCRIPTION || '-';
            detailPrice.textContent = formatCurrency(article.PRICE || 0);
            detailNotes.textContent = article.NOTAS || '-';

            imageThumbs.innerHTML = '';
            if (pictures.length === 0) {
                setMainImage('');
                imageThumbs.innerHTML = '<small class="text-muted">Sin imagenes disponibles.</small>';
            } else {
                const pictureDataUris = [];

                pictures.forEach((pictureItem) => {
                    const dataUri = pictureItem.dataUri || (pictureItem.picture && pictureItem.ext
                        ? `data:image/${String(pictureItem.ext).toLowerCase()};base64,${pictureItem.picture}`
                        : '');

                    if (dataUri !== '') {
                        pictureDataUris.push(dataUri);
                    }
                });

                setMainImage(pictureDataUris[0] || '');

                pictureDataUris.forEach((dataUri, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `btn p-0 border rounded overflow-hidden ${index === 0 ? 'border-primary' : 'border-light'}`;
                    button.style.width = '70px';
                    button.style.height = '70px';
                    button.innerHTML = `<img src="${dataUri}" alt="Miniatura" class="w-100 h-100" style="object-fit: cover;">`;
                    button.addEventListener('click', () => {
                        setMainImage(dataUri);
                        Array.from(imageThumbs.querySelectorAll('button')).forEach((thumbButton) => {
                            thumbButton.classList.remove('border-primary');
                            thumbButton.classList.add('border-light');
                        });
                        button.classList.remove('border-light');
                        button.classList.add('border-primary');
                    });

                    imageThumbs.appendChild(button);
                });

                if (pictureDataUris.length === 0) {
                    setMainImage('');
                    imageThumbs.innerHTML = '<small class="text-muted">Imagen invalida.</small>';
                }
            }

            stocksTableBody.innerHTML = '';
            if (stocks.length === 0) {
                stocksTableBody.innerHTML = '<tr><td colspan="2" class="text-muted">Sin inventario disponible.</td></tr>';
            } else {
                stocks.forEach((stockItem) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHtml(String(stockItem.NAME || ''))}</td>
                        <td>${formatCurrency(stockItem.AVAILABLE || 0)}</td>
                    `;
                    stocksTableBody.appendChild(tr);
                });
            }

            bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
        };

        const reloadArticles = async (searchValue, resetPage = false) => {
            if (resetPage) {
                state.page = 1;
            }

            const payload = { search: searchValue || '' };
            const result = await fetchJson(window.APP.articlesListUrl, payload);

            if (String(result.code) !== '200') {
                showToast('danger', result.message || 'No fue posible cargar los articulos');
                return;
            }

            state.rows = Array.isArray(result.data) ? result.data : [];
            renderRows();
        };

        const loadArticleDetail = async (articleId) => {
            if (!articleId) {
                showToast('danger', 'ID de articulo no valido');
                return;
            }

            const result = await fetchJson(window.APP.articlesDetailUrl, { search: articleId });
            if (String(result.code) !== '200') {
                showToast('danger', result.message || 'No fue posible consultar el detalle');
                return;
            }

            renderArticleDetail(articleId, result.data || {});
        };

        searchForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            state.search = String(searchInput?.value || '').trim();
            await reloadArticles(state.search, true);
        });

        clearButton?.addEventListener('click', async () => {
            state.search = '';
            if (searchInput) {
                searchInput.value = '';
            }
            await reloadArticles('', true);
        });

        perPageSelect?.addEventListener('change', () => {
            const newPerPage = Number(perPageSelect.value || 8);
            state.perPage = Number.isFinite(newPerPage) && newPerPage > 0 ? newPerPage : 8;
            state.page = 1;
            renderRows();
        });

        prevPageButton?.addEventListener('click', () => {
            if (state.page <= 1) {
                return;
            }

            state.page -= 1;
            renderRows();
        });

        nextPageButton?.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(state.rows.length / state.perPage));
            if (state.page >= totalPages) {
                return;
            }

            state.page += 1;
            renderRows();
        });

        tableBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const detailButton = target.closest('.btn-article-detail');
            if (!(detailButton instanceof HTMLElement)) {
                return;
            }

            const row = detailButton.closest('tr');
            const articleId = row?.dataset.id || '';
            await loadArticleDetail(articleId);
        });

        expandImageButton?.addEventListener('click', () => {
            if (!imageModalEl || !expandedImage || selectedImageDataUri === '') {
                return;
            }

            expandedImage.src = selectedImageDataUri;
            bootstrap.Modal.getOrCreateInstance(imageModalEl).show();
        });

        state.rows = parseInitialRows();
        renderRows();
    };

    document.addEventListener('DOMContentLoaded', () => {
        initTheme();
        initSidebar();
        consumeFlashMessages();
        initUsersModule();
        initArticlesModule();
    });
})();

(() => {
    const globalLoader = document.getElementById('globalLoader');
    const toastContainer = document.getElementById('toastContainer');

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

            return await response.json();
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

    const initUsersModule = () => {
        const tableBody = document.getElementById('usersTableBody');
        if (!tableBody) {
            return;
        }

        const searchInput = document.getElementById('searchUsers');
        const pagination = document.getElementById('pagination');
        const paginationInfo = document.getElementById('paginationInfo');
        const sortHeaders = document.querySelectorAll('th[data-sort]');

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

            if (pagination) {
                pagination.innerHTML = '';
                for (let i = 1; i <= totalPages; i += 1) {
                    const li = document.createElement('li');
                    li.className = `page-item ${i === tableState.page ? 'active' : ''}`;
                    li.innerHTML = `<button class="page-link" type="button">${i}</button>`;
                    li.addEventListener('click', () => {
                        tableState.page = i;
                        applyTableState();
                    });
                    pagination.appendChild(li);
                }
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
            const payload = await response.json();
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

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        consumeFlashMessages();
        initUsersModule();
    });
})();

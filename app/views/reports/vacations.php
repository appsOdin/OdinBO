<?php
/** @var array<int, array<string, mixed>> $rows */
/** @var array<int, array<string, mixed>> $signers */
$rows = $rows ?? [];
$signers = $signers ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Reporte de Vacaciones</h2>
        <p class="text-muted m-0">Historico filtrado por colaborador y exportacion a PDF.</p>
    </div>
</section>

<div class="card border-0 shadow-sm vacation-card mb-3">
    <div class="card-body">
        <input type="hidden" id="reportCsrfToken" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-3">
                <label for="reportUserFilter" class="form-label">Buscar usuario</label>
                <input type="text" class="form-control" id="reportUserFilter" placeholder="Filtrar por nombre o identificacion">
            </div>
            <div class="col-lg-4">
                <label for="reportUserSelect" class="form-label">Usuario</label>
                <select class="form-select" id="reportUserSelect">
                    <option value="">Seleccione un usuario</option>
                    <?php foreach ($signers as $signer): ?>
                        <?php
                        $id = (string) ($signer['id'] ?? '');
                        $name = trim((string) ($signer['fullname'] ?? ''));
                        ?>
                        <option value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($id . ' ' . $name), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($name !== '' ? $name : $id, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-5 d-flex gap-2">
                <button type="button" class="btn btn-primary" id="reportSearchBtn">Buscar</button>
                <button type="button" class="btn btn-outline-secondary" id="reportClearBtn">Limpiar</button>
                <button type="button" class="btn btn-outline-danger" id="reportExportPdfBtn">Exportar PDF</button>
            </div>
        </div>

        <div id="vacationReportWrap" class="table-responsive">
            <table class="table align-middle" id="vacationReportTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Identificación</th>
                    <th>Nombre</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                </tr>
                </thead>
                <tbody id="vacationReportBody">
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) ($row['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row['identification'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['fullName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['request_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($row['quantity'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(() => {
    const filterInput = document.getElementById('reportUserFilter');
    const userSelect = document.getElementById('reportUserSelect');
    const searchBtn = document.getElementById('reportSearchBtn');
    const clearBtn = document.getElementById('reportClearBtn');
    const exportBtn = document.getElementById('reportExportPdfBtn');
    const tableBody = document.getElementById('vacationReportBody');
    const csrfInput = document.getElementById('reportCsrfToken');

    const getCsrfToken = () => {
        const hiddenToken = String(csrfInput?.value || '').trim();
        if (hiddenToken !== '') {
            return hiddenToken;
        }

        return String(window.APP?.csrfToken || '').trim();
    };

    const notify = async (message, icon = 'warning') => {
        if (window.Swal?.fire) {
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

    const formatDate = (value) => {
        const text = String(value || '');
        if (text.length < 10) {
            return text;
        }
        return text.slice(8, 10) + '/' + text.slice(5, 7) + '/' + text.slice(0, 4);
    };

    filterInput?.addEventListener('input', () => {
        const criteria = String(filterInput.value || '').toLowerCase().trim();
        Array.from(userSelect?.options || []).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const searchable = String(option.dataset.search || '');
            option.hidden = criteria !== '' && !searchable.includes(criteria);
        });
    });

    const renderRows = (rows) => {
        if (!tableBody) {
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay registros para mostrar.</td></tr>';
            return;
        }

        tableBody.innerHTML = rows.map((row) => {
            return `
                <tr>
                    <td>${Number(row.id || 0)}</td>
                    <td>${escapeHtml(row.identification || '')}</td>
                    <td>${escapeHtml(row.fullName || '')}</td>
                    <td>${escapeHtml(formatDate(row.start_date || ''))}</td>
                    <td>${escapeHtml(formatDate(row.end_date || ''))}</td>
                    <td>${escapeHtml(row.request_type || '')}</td>
                    <td>${Number(row.quantity || 0)}</td>
                    <td>${escapeHtml(row.description || '')}</td>
                </tr>
            `;
        }).join('');
    };

    searchBtn?.addEventListener('click', async () => {
        const identification = String(userSelect?.value || '').trim();
        if (!identification) {
            await notify('Seleccione un usuario para consultar el reporte.');
            return;
        }

        const response = await fetch(window.APP.vacationReportListUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                identification,
                _csrf_token: getCsrfToken()
            })
        });

        const ct = response.headers.get('content-type') || '';
        const result = ct.toLowerCase().includes('application/json')
            ? await response.json()
            : { code: String(response.status || 500), message: 'Respuesta invalida', data: [] };

        if (String(result.code) !== '200') {
            await notify(result.message || 'No fue posible obtener el reporte.', 'error');
            return;
        }

        renderRows(result.data || []);
    });

    clearBtn?.addEventListener('click', () => {
        if (filterInput) {
            filterInput.value = '';
            filterInput.dispatchEvent(new Event('input'));
        }

        if (userSelect) {
            userSelect.value = '';
        }

        renderRows([]);
    });

    exportBtn?.addEventListener('click', () => {
        const printWindow = window.open('', '_blank', 'width=1200,height=800');
        if (!printWindow) {
            notify('No fue posible abrir la ventana de impresion.', 'error');
            return;
        }

        const tableHtml = document.getElementById('vacationReportWrap')?.innerHTML || '';
        const now = new Date();
        const generatedAt = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        printWindow.document.write(`
            <html>
            <head>
                <title>Reporte de vacaciones</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 24px; }
                    h2 { margin: 0 0 4px; }
                    p { margin: 0 0 16px; color: #6c757d; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ced4da; padding: 6px; font-size: 12px; text-align: left; }
                    th { background: #f1f3f5; }
                </style>
            </head>
            <body>
                <h2>Reporte de vacaciones</h2>
                <p>Generado: ${generatedAt}</p>
                ${tableHtml}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    });
})();
</script>

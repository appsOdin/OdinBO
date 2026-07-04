<?php
/** @var array<int, array<string, mixed>> $traces */
/** @var int $page */
/** @var int $reg */
/** @var int $totalRecords */
/** @var int $apiHttpCode */
/** @var string $apiMessage */
/** @var string $csrfToken */
$traces       = $traces       ?? [];
$page         = $page         ?? 1;
$reg          = $reg          ?? 10;
$totalRecords = $totalRecords ?? 0;
$apiHttpCode  = $apiHttpCode  ?? 200;
$apiMessage   = $apiMessage   ?? '';
?>

<?php if ($apiHttpCode === 403): ?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;gap:16px;text-align:center;padding:32px;">
    <div style="width:72px;height:72px;border-radius:50%;background:#fff3cd;display:flex;align-items:center;justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#e6a817" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
        </svg>
    </div>
    <div>
        <p style="margin:0 0 6px;font-size:1.25rem;font-weight:600;color:#212529;">Acceso denegado</p>
        <p style="margin:0 0 16px;font-size:0.9rem;color:#6c757d;">No tiene permisos para ver esta seccion.</p>
        <a href="<?= htmlspecialchars(base_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>"
           style="display:inline-block;padding:8px 24px;background:#212529;color:#fff;border-radius:6px;text-decoration:none;font-size:0.9rem;font-weight:500;">
            Volver al dashboard
        </a>
    </div>
</div>
<?php return; endif; ?>

<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Trace Logs</h2>
        <p class="text-muted m-0">Registro de errores y trazas del sistema.</p>
    </div>
</section>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <?php if ($apiHttpCode !== 200): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($apiMessage !== '' ? $apiMessage : 'No fue posible cargar los registros.', ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle" id="tracesTable">
                <thead>
                <tr>
                    <th style="width:175px;">Fecha</th>
                    <th style="width:220px;">Tipo</th>
                    <th>Mensaje</th>
                    <th style="width:100px;"></th>
                </tr>
                </thead>
                <tbody id="tracesTableBody">
                <?php foreach ($traces as $trace): ?>
                    <?php
                    $exRaw   = (string) ($trace['ex'] ?? '');
                    $exData  = json_decode($exRaw, true);
                    $exType  = is_array($exData) ? (string) ($exData['Type']    ?? '-') : '-';
                    $exMsg   = is_array($exData) ? (string) ($exData['Message'] ?? '-') : $exRaw;
                    ?>
                    <tr>
                        <td class="text-nowrap small">
                            <?= htmlspecialchars((string) ($trace['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <span class="badge text-bg-danger text-wrap text-start fw-normal lh-sm"
                                  style="max-width:210px;white-space:normal;word-break:break-all;">
                                <?= htmlspecialchars($exType, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="small">
                            <?= htmlspecialchars($exMsg, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-trace-detail"
                                    data-trace-created="<?= htmlspecialchars((string) ($trace['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-trace-ex="<?= htmlspecialchars($exRaw, ENT_QUOTES, 'UTF-8') ?>"
                                    data-trace-request="<?= htmlspecialchars((string) ($trace['request'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-trace-response="<?= htmlspecialchars((string) ($trace['response'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                Ver mas
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($traces === []): ?>
                    <tr id="tracesEmptyRow">
                        <td colspan="4" class="text-center text-muted py-4">No hay registros para mostrar.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="tracesPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="tracesPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="tracesPerPage" class="form-select form-select-sm" style="width:auto;">
                    <option value="10"  <?= $reg === 10  ? 'selected' : '' ?>>10</option>
                    <option value="25"  <?= $reg === 25  ? 'selected' : '' ?>>25</option>
                    <option value="50"  <?= $reg === 50  ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $reg === 100 ? 'selected' : '' ?>>100</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="tracesPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="tracesPageIndicator">Pagina <?= $page ?></small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="tracesNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
        </div>

    </div>
</div>

<!-- Detail modal -->
<div class="modal fade" id="traceDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Trace</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase">Fecha</label>
                    <p id="traceDetailCreated" class="mb-0 small font-monospace"></p>
                </div>

                <hr class="my-2">
                <p class="fw-semibold small text-muted text-uppercase mb-2">Excepcion</p>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Tipo</label>
                        <p id="traceDetailExType" class="mb-0 small font-monospace text-danger"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Mensaje</label>
                        <p id="traceDetailExMessage" class="mb-0 small"></p>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Stack Trace</label>
                    <pre id="traceDetailExStack" class="bg-light rounded p-2 small mb-0"
                         style="white-space:pre-wrap;word-break:break-all;max-height:220px;overflow-y:auto;"></pre>
                </div>
                <div id="traceDetailExInnerWrap" class="mb-2 d-none">
                    <label class="form-label small text-muted mb-1">Inner Exception</label>
                    <pre id="traceDetailExInner" class="bg-light rounded p-2 small mb-0"
                         style="white-space:pre-wrap;word-break:break-all;max-height:120px;overflow-y:auto;"></pre>
                </div>

                <hr class="my-2">
                <div class="mb-2">
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Request</label>
                    <pre id="traceDetailRequest" class="bg-light rounded p-2 small mb-0"
                         style="white-space:pre-wrap;word-break:break-all;max-height:220px;overflow-y:auto;"></pre>
                </div>

                <hr class="my-2">
                <div>
                    <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Response</label>
                    <pre id="traceDetailResponse" class="bg-light rounded p-2 small mb-0"
                         style="white-space:pre-wrap;word-break:break-all;max-height:180px;overflow-y:auto;"></pre>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const csrfToken = window.APP?.csrfToken   || '';
    const listUrl   = window.APP?.tracesListUrl || '';

    let currentPage = <?= (int) $page ?>;
    let currentReg  = <?= (int) $reg ?>;
    let hasMore     = <?= count($traces) >= $reg ? 'true' : 'false' ?>;
    let loading     = false;

    const tableBody = document.getElementById('tracesTableBody');
    const perPage   = document.getElementById('tracesPerPage');
    const prevBtn   = document.getElementById('tracesPrevPage');
    const nextBtn   = document.getElementById('tracesNextPage');
    const indicator = document.getElementById('tracesPageIndicator');
    const infoEl    = document.getElementById('tracesPaginationInfo');

    const detailModalEl        = document.getElementById('traceDetailModal');
    const detailCreated        = document.getElementById('traceDetailCreated');
    const detailExType         = document.getElementById('traceDetailExType');
    const detailExMessage      = document.getElementById('traceDetailExMessage');
    const detailExStack        = document.getElementById('traceDetailExStack');
    const detailExInnerWrap    = document.getElementById('traceDetailExInnerWrap');
    const detailExInner        = document.getElementById('traceDetailExInner');
    const detailRequest        = document.getElementById('traceDetailRequest');
    const detailResponse       = document.getElementById('traceDetailResponse');

    const escapeHtml = (value) => {
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(String(value ?? '')));
        return p.innerHTML;
    };

    const tryPrettyJson = (value) => {
        if (!value || value === 'null' || value === '') return '—';
        try { return JSON.stringify(JSON.parse(value), null, 2); } catch (e) { return value; }
    };

    const parseEx = (raw) => {
        try { return JSON.parse(raw || ''); } catch (e) { return null; }
    };

    const updateControls = () => {
        if (indicator) indicator.textContent = `Pagina ${currentPage}`;
        if (prevBtn)   prevBtn.disabled = currentPage <= 1;
        if (nextBtn)   nextBtn.disabled = !hasMore;
    };

    const buildRow = (trace) => {
        const created = String(trace.created ?? '');
        const exRaw   = String(trace.ex ?? '');
        const reqRaw  = String(trace.request  ?? '');
        const resRaw  = String(trace.response ?? '');
        const ex      = parseEx(exRaw);
        const exType  = ex ? String(ex.Type    ?? '-') : '-';
        const exMsg   = ex ? String(ex.Message ?? '-') : (exRaw || '-');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-nowrap small">${escapeHtml(created)}</td>
            <td><span class="badge text-bg-danger text-wrap text-start fw-normal lh-sm"
                      style="max-width:210px;white-space:normal;word-break:break-all;">${escapeHtml(exType)}</span></td>
            <td class="small">${escapeHtml(exMsg)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-primary btn-trace-detail"
                    data-trace-created="${escapeHtml(created)}"
                    data-trace-ex="${escapeHtml(exRaw)}"
                    data-trace-request="${escapeHtml(reqRaw)}"
                    data-trace-response="${escapeHtml(resRaw)}">
                    Ver mas
                </button>
            </td>`;
        return tr;
    };

    const renderRows = (rows) => {
        if (!tableBody) return;
        tableBody.innerHTML = '';
        if (!Array.isArray(rows) || rows.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="4" class="text-center text-muted py-4">No hay registros para mostrar.</td>';
            tableBody.appendChild(tr);
            if (infoEl) infoEl.textContent = '';
            return;
        }
        rows.forEach(row => tableBody.appendChild(buildRow(row)));
        if (infoEl) infoEl.textContent = `Mostrando ${rows.length} registros en pagina ${currentPage}`;
    };

    const loadPage = async (page, reg) => {
        if (loading || !listUrl) return;
        loading = true;
        try {
            const response = await fetch(listUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ page, reg, _csrf_token: csrfToken })
            });
            const ct = response.headers.get('content-type') || '';
            const payload = ct.toLowerCase().includes('application/json')
                ? await response.json()
                : { code: String(response.status), message: 'Respuesta invalida', data: null };

            if (String(payload.code) !== '200') {
                if (tableBody) tableBody.innerHTML =
                    `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(payload.message || 'Error al cargar datos.')}</td></tr>`;
                return;
            }
            const rows = Array.isArray(payload.data) ? payload.data : [];
            hasMore     = rows.length >= reg;
            currentPage = page;
            currentReg  = reg;
            renderRows(rows);
            updateControls();
        } catch (err) {
            if (tableBody) tableBody.innerHTML =
                '<tr><td colspan="4" class="text-center text-danger py-4">Error de comunicacion.</td></tr>';
        } finally {
            loading = false;
        }
    };

    // Initial state
    updateControls();

    perPage?.addEventListener('change', () => loadPage(1, parseInt(perPage.value, 10)));
    prevBtn?.addEventListener('click',  () => { if (currentPage > 1) loadPage(currentPage - 1, currentReg); });
    nextBtn?.addEventListener('click',  () => { if (hasMore) loadPage(currentPage + 1, currentReg); });

    // Open detail modal
    document.addEventListener('click', (event) => {
        const btn = event.target instanceof Element ? event.target.closest('.btn-trace-detail') : null;
        if (!(btn instanceof HTMLElement)) return;

        const ex = parseEx(btn.dataset.traceEx || '');

        if (detailCreated)   detailCreated.textContent   = btn.dataset.traceCreated || '—';
        if (detailExType)    detailExType.textContent     = ex ? (ex.Type    || '-') : '-';
        if (detailExMessage) detailExMessage.textContent  = ex ? (ex.Message || '-') : (btn.dataset.traceEx || '-');
        if (detailExStack)   detailExStack.textContent    = ex ? (ex.StackTrace || '—') : '—';

        const inner = ex?.InnerException;
        if (detailExInnerWrap && detailExInner) {
            if (inner) {
                detailExInner.textContent = typeof inner === 'string'
                    ? inner
                    : JSON.stringify(inner, null, 2);
                detailExInnerWrap.classList.remove('d-none');
            } else {
                detailExInnerWrap.classList.add('d-none');
            }
        }

        if (detailRequest)  detailRequest.textContent  = tryPrettyJson(btn.dataset.traceRequest  || '');
        if (detailResponse) detailResponse.textContent = tryPrettyJson(btn.dataset.traceResponse || '');

        if (detailModalEl && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
        }
    });
})();
</script>

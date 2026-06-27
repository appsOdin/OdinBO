<?php
/** @var array<string, mixed> $detail */
$detail = $detail ?? [];

$id = (int) ($detail['id'] ?? 0);
$stateKey = strtoupper((string) ($detail['stateKey'] ?? ''));
$stateName = (string) ($detail['stateName'] ?? 'Desconocido');
$stateBadge = match ($stateKey) {
    'PENDING' => '<span class="badge text-bg-warning">Pendiente</span>',
    'TOSIGNED' => '<span class="badge text-bg-info">Por Firmar</span>',
    'APPROVED' => '<span class="badge text-bg-success">Aprobada</span>',
    'SIGN' => '<span class="badge text-bg-success">Firmada</span>',
    'REJECTED' => '<span class="badge text-bg-danger">Rechazada</span>',
    'CANCELLED' => '<span class="badge text-bg-secondary">Cancelada</span>',
    default => '<span class="badge text-bg-secondary">' . htmlspecialchars($stateName, ENT_QUOTES, 'UTF-8') . '</span>',
};

$solicitanteSigner = is_array($detail['solicitanteSigner'] ?? null) ? $detail['solicitanteSigner'] : [];
$signers = is_array($detail['signers'] ?? null) ? $detail['signers'] : [];
$files = is_array($detail['files'] ?? null) ? $detail['files'] : [];
$totalSigners = (int) ($detail['totalSigners'] ?? count($signers));
$signedCount = (int) ($detail['signedCount'] ?? 0);
$pendingCount = (int) ($detail['pendingCount'] ?? max(0, $totalSigners - $signedCount));
$allSigned = (bool) ($detail['allSigned'] ?? false);
$canCurrentUserSign = (bool) ($detail['canCurrentUserSign'] ?? false);
$isOwner = (bool) ($detail['isOwner'] ?? false);
$canOwnerSign = $isOwner && $stateKey === 'APPROVED';
$progress = $totalSigners > 0 ? (int) round(($signedCount / $totalSigners) * 100) : 0;

$buildSignatureSrc = static function (string $signatureRaw): string {
    $signatureRaw = trim($signatureRaw);
    if ($signatureRaw === '') {
        return '';
    }

    if (str_starts_with($signatureRaw, 'data:image/')) {
        return $signatureRaw;
    }

    return 'data:image/png;base64,' . $signatureRaw;
};

$ownerUserId = (int) ($detail['userId'] ?? 0);
$ownerDisplayName = (string) ($solicitanteSigner['userName'] ?? ($detail['userName'] ?? 'Solicitante'));
$ownerSignatureRaw = (string) (
    $solicitanteSigner['signatureImageBase64']
    ?? $solicitanteSigner['signatureImage']
    ?? $solicitanteSigner['signature']
    ?? ''
);

if ($ownerSignatureRaw === '') {
    $ownerSignatureRaw = (string) (
    $detail['ownerSignatureImageBase64']
    ?? $detail['ownerSignatureBase64']
    ?? $detail['ownerSignatureImage']
    ?? $detail['ownerSignature']
    ?? $detail['signatureImageBase64']
    ?? $detail['signatureImage']
    ?? ''
    );
}

if ($ownerSignatureRaw === '' && $signers !== []) {
    foreach ($signers as $signer) {
        $isOwnerSigner = (bool) ($signer['isOwner'] ?? false);
        $sameUser = $ownerUserId > 0 && (int) ($signer['userId'] ?? 0) === $ownerUserId;
        if (!$isOwnerSigner && !$sameUser) {
            continue;
        }

        $ownerSignatureRaw = (string) (
            $signer['signatureImageBase64']
            ?? $signer['signatureImage']
            ?? $signer['signature']
            ?? ''
        );

        if ($ownerSignatureRaw !== '') {
            break;
        }
    }
}

$ownerSignatureSrc = $buildSignatureSrc($ownerSignatureRaw);
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Detalle de Solicitud #<?= $id ?></h2>
        <p class="text-muted m-0">Vista completa de estado, firmantes, firmas y archivos.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('rrhh/solicitud-vacaciones') ?>" class="btn btn-outline-secondary">Volver a Mis Solicitudes</a>
        <?php if ($canOwnerSign): ?>
            <button type="button" class="btn btn-primary" id="openSignFromDetail">Firmar Solicitud</button>
        <?php endif; ?>
        <?php
        $authRolename = strtoupper((string) ($authUser['rolename'] ?? ''));
        $canReject = ($isOwner || $authRolename === 'ADMIN') && in_array($authRolename, ['ADMIN', 'USER'], true) && !in_array($stateKey, ['REJECTED', 'CANCELLED', 'SIGN'], true);
        ?>
        <?php if ($canReject): ?>
            <button type="button" class="btn btn-danger btn-reject-vacation" data-request-id="<?= $id ?>">Rechazar</button>
        <?php endif; ?>
    </div>
</section>

<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm vacation-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <h5 class="mb-0">Informacion de la Solicitud</h5>
                    <?= $stateBadge ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <small class="text-muted d-block">Solicitante</small>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($detail['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <small class="text-muted d-block">Correo</small>
                        <div><?= htmlspecialchars((string) ($detail['userEmail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <small class="text-muted d-block">Rol del solicitante</small>
                        <div><?= htmlspecialchars((string) ($detail['userRole'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block">Inicio</small>
                        <div><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($detail['startDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block">Fin</small>
                        <div><?= htmlspecialchars(date('d/m/Y', strtotime((string) ($detail['endDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <small class="text-muted d-block"><?= (int) ($detail['requestType'] ?? 0) === 1 ? 'Horas' : 'Dias' ?></small>
                        <div><?= (int) ($detail['quantity'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <small class="text-muted d-block">Fecha de solicitud</small>
                        <div><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($detail['requestDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <small class="text-muted d-block">Tipo</small>
                        <div><?= (int) ($detail['requestType'] ?? 0) === 1 ? 'Permiso' : 'Vacaciones' ?></div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Descripcion</small>
                        <div><?= htmlspecialchars((string) ($detail['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php if ($stateKey === 'REJECTED' && ($detail['rejectedDescription'] ?? '') !== ''): ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Motivo de Rechazo</small>
                        <div class="text-danger"><?= htmlspecialchars((string) $detail['rejectedDescription'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3 small text-muted">
                    <?= $isOwner ? 'Eres el propietario de esta solicitud.' : 'No eres el propietario de esta solicitud.' ?>
                </div>
                <?php if ($ownerSignatureSrc !== ''): ?>
                    <div class="mt-3">
                        <small class="text-muted d-block mb-1">Firma del solicitante (<?= htmlspecialchars($ownerDisplayName, ENT_QUOTES, 'UTF-8') ?>):</small>
                        <img
                            src="<?= htmlspecialchars($ownerSignatureSrc, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Firma del solicitante <?= htmlspecialchars($ownerDisplayName, ENT_QUOTES, 'UTF-8') ?>"
                            class="img-fluid border rounded bg-white p-1"
                            style="max-height: 140px;"
                        >
                    </div>
                <?php endif; ?>
                <?php if ($canOwnerSign): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        Todos los firmantes (<?= $signedCount ?>/<?= $totalSigners ?>) han completado su firma. Ahora puedes firmar tu para finalizar la solicitud.
                    </div>
                <?php elseif ($stateKey === 'SIGN'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        Solicitud completamente firmada.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm vacation-card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Firmantes</h5>
                    <small class="text-muted"><?= $signedCount ?>/<?= $totalSigners ?> firmados</small>
                </div>

                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <p class="small mb-3">
                    <span class="badge text-bg-success">Firmados: <?= $signedCount ?></span>
                    <span class="badge text-bg-warning text-dark">Pendientes: <?= $pendingCount ?></span>
                    <?php if ($allSigned): ?>
                        <span class="badge text-bg-primary">Todos firmaron</span>
                    <?php endif; ?>
                </p>

                <?php if ($signers === []): ?>
                    <div class="text-muted">No hay firmantes asignados.</div>
                <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($signers as $signer): ?>
                            <?php
                            $signatureRaw = (string) (
                                $signer['signatureImageBase64']
                                ?? $signer['signatureImage']
                                ?? $signer['signature']
                                ?? ''
                            );
                            $signatureSrc = $buildSignatureSrc($signatureRaw);
                            $hasSigned = (bool) ($signer['hasSigned'] ?? ($signatureSrc !== ''));
                            ?>
                            <div class="p-3 rounded border <?= $hasSigned ? 'border-success bg-success-subtle' : 'border-warning bg-warning-subtle' ?>">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($signer['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($signer['userEmail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                    <div>
                                        <?php if ($hasSigned): ?>
                                            <span class="badge text-bg-success">Firmado</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($hasSigned): ?>
                                    <small class="text-muted d-block mb-2">Fecha de firma: <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($signer['signDate'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>

                                <?php if ($signatureSrc !== ''): ?>
                                    <div>
                                        <small class="text-muted d-block mb-1">Firma:</small>
                                        <img
                                            src="<?= htmlspecialchars($signatureSrc, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="Firma de <?= htmlspecialchars((string) ($signer['userName'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            class="img-fluid border rounded bg-white p-1"
                                            style="max-height: 140px;"
                                        >
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm vacation-card h-100">
            <div class="card-body">
                <h5 class="mb-3">Archivos Adjuntos</h5>
                <?php if ($files === []): ?>
                    <div class="text-muted">No hay archivos adjuntos.</div>
                <?php else: ?>
                    <div class="vstack gap-2">
                        <?php foreach ($files as $file): ?>
                            <?php
                            $fileId = (int) ($file['id'] ?? 0);
                            $downloadUrl = $fileId > 0
                                ? base_url('rrhh/vacaciones/descargar?fileId=' . $fileId)
                                : '#';
                            ?>
                            <div class="p-2 border rounded d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold small"><?= htmlspecialchars((string) ($file['name'] ?? ($file['fileName'] ?? 'archivo.pdf')), ENT_QUOTES, 'UTF-8') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string) ($file['size'] ?? ($file['sizeFormatted'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <?php if ($fileId > 0): ?>
                                    <a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Descargar</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canOwnerSign): ?>
<div class="modal fade" id="detailSignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Firmar Solicitud #<?= $id ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Dibuja tu firma y confirma para registrar tu aprobacion.</p>
                <div class="signature-pad-wrapper mb-3">
                    <canvas id="detailSignCanvas" class="signature-canvas" aria-label="Area de firma"></canvas>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="detailSignClear">Limpiar</button>
                    <button type="button" class="btn btn-primary" id="detailSignSave">Firmar Documento</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/signature-pad.js') ?>"></script>
<script>
(() => {
    const modalEl = document.getElementById('detailSignModal');
    const openBtn = document.getElementById('openSignFromDetail');
    const clearBtn = document.getElementById('detailSignClear');
    const saveBtn = document.getElementById('detailSignSave');
    const csrfToken = window.APP?.csrfToken || '';
    const requestId = <?= $id ?>;
    const notify = async (message, icon = 'warning') => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            await window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
            return;
        }

        alert(message);
    };

    const getModal = () => (modalEl && window.bootstrap?.Modal)
        ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
        : null;

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

    openBtn?.addEventListener('click', () => {
        window.VacationSignaturePad?.init?.('detailSignCanvas');
        getModal()?.show();
    });

    clearBtn?.addEventListener('click', () => {
        window.VacationSignaturePad?.clear?.();
    });

    saveBtn?.addEventListener('click', async () => {
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
        saveBtn.textContent = 'Guardando...';

        try {
            const result = await fetchJson(window.APP.vacationSaveSignatureUrl, {
                requestId,
                signature,
                _csrf_token: csrfToken
            });

            if (String(result.code) !== '200') {
                await notify(result.message || 'No fue posible registrar la firma.', 'error');
                return;
            }

            getModal()?.hide();
            await notify('Firma registrada exitosamente.', 'success');
            window.location.reload();
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Firmar Documento';
        }
    });
})();
</script>
<?php endif; ?>

<?php if ($canReject): ?>
<div class="modal fade" id="rejectVacationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title">Rechazar Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectRequestId" value="<?= $id ?>">
                <div class="mb-3">
                    <label for="rejectReason" class="form-label fw-semibold">Motivo del Rechazo <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectReason" rows="4" maxlength="200" required placeholder="Describa el motivo del rechazo..."></textarea>
                    <small class="text-muted d-block mt-1"><span id="rejectReasonCounter">0</span>/200 caracteres</small>
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
    const initDetailRejectModule = () => {
        const modalEl = document.getElementById('rejectVacationModal');
        const reasonInput = document.getElementById('rejectReason');
        const counter = document.getElementById('rejectReasonCounter');
        const confirmBtn = document.getElementById('confirmRejectBtn');
        const requestId = <?= $id ?>;
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
            if (reasonInput) reasonInput.value = '';
            if (counter) counter.textContent = '0';
            getModal()?.show();
        });

        reasonInput?.addEventListener('input', () => {
            if (counter) counter.textContent = String(reasonInput.value.length);
        });

        confirmBtn?.addEventListener('click', async () => {
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
                    window.location.href = window.APP.dashboardUrl || '/';
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
        document.addEventListener('DOMContentLoaded', initDetailRejectModule);
    } else {
        initDetailRejectModule();
    }
})();
</script>
<?php endif; ?>

<?php
$minDate = date('Y-m-d');
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1" id="pageTitle">Crear Solicitud de Vacaciones</h2>
        <p class="text-muted m-0" id="pageSubtitle">Registra un nuevo periodo de vacaciones.</p>
    </div>
</section>

<div class="card border-0 shadow-sm vacation-card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('rrhh/solicitud-vacaciones/store') ?>" id="vacationCreateForm" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="request_type" id="requestTypeHidden" value="0">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Tipo de Solicitud</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="request_type_radio" id="typeVacaciones" value="0" checked>
                            <label class="form-check-label" for="typeVacaciones">Vacaciones</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="request_type_radio" id="typePermiso" value="1">
                            <label class="form-check-label" for="typePermiso">Permiso</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="startDate" class="form-label">Fecha de Inicio</label>
                    <input
                        type="date"
                        class="form-control"
                        id="startDate"
                        name="start_date"
                        required
                        min="<?= htmlspecialchars($minDate, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
                <div class="col-md-6">
                    <label for="endDate" class="form-label">Fecha de Fin</label>
                    <input
                        type="date"
                        class="form-control"
                        id="endDate"
                        name="end_date"
                        required
                        min="<?= htmlspecialchars($minDate, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
                <div class="col-md-4">
                    <label for="quantity" class="form-label" id="quantityLabel">Cantidad de Dias</label>
                    <input
                        type="number"
                        class="form-control"
                        id="quantity"
                        name="quantity"
                        required
                        min="1"
                        max="255"
                        readonly
                    >
                    <small class="text-muted" id="quantityHint">Calculado automaticamente.</small>
                </div>
                <div class="col-md-8">
                    <label for="description" class="form-label">Descripcion</label>
                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="100"
                        required
                        placeholder="Ejemplo: Vacaciones de verano"
                    ></textarea>
                    <small class="text-muted">Maximo 100 caracteres.</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="<?= base_url('rrhh/solicitud-vacaciones') ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Solicitud</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('vacationCreateForm');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const quantityInput = document.getElementById('quantity');
    const quantityLabel = document.getElementById('quantityLabel');
    const quantityHint = document.getElementById('quantityHint');
    const requestTypeHidden = document.getElementById('requestTypeHidden');
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');
    const radioVacaciones = document.getElementById('typeVacaciones');
    const radioPermiso = document.getElementById('typePermiso');

    const notify = (message, icon = 'warning') => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({ icon, text: message, confirmButtonText: 'Aceptar' });
            return;
        }
        alert(message);
    };

    if (!form || !startDateInput || !endDateInput || !quantityInput) {
        return;
    }

    const toDate = (value) => {
        if (!value) return null;
        const parsed = new Date(`${value}T00:00:00`);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    const getRequestType = () => (radioPermiso && radioPermiso.checked ? 1 : 0);

    const countWorkDays = (start, end) => {
        let count = 0;
        const cur = new Date(start);
        while (cur <= end) {
            if (cur.getDay() !== 0) { // 0 = domingo
                count++;
            }
            cur.setDate(cur.getDate() + 1);
        }
        return count;
    };

    const recalcDays = () => {
        const start = toDate(startDateInput.value);
        const end = toDate(endDateInput.value);
        if (!start || !end || end < start) {
            quantityInput.value = '';
            return;
        }
        const days = countWorkDays(start, end);
        quantityInput.value = days > 0 ? String(days) : '';
    };

    const applyTypeMode = (type) => {
        requestTypeHidden.value = String(type);

        if (type === 1) {
            pageTitle.textContent = 'Crear Solicitud de Permiso';
            pageSubtitle.textContent = 'Registra un permiso para un dia especifico.';
            quantityLabel.textContent = 'Cantidad de Horas';
            quantityHint.textContent = 'Ingresa la cantidad de horas del permiso.';
            quantityInput.readOnly = false;
            quantityInput.min = '1';
            quantityInput.max = '999';
            quantityInput.value = '';
            endDateInput.readOnly = true;
            if (startDateInput.value) {
                endDateInput.value = startDateInput.value;
                endDateInput.min = startDateInput.value;
                endDateInput.max = startDateInput.value;
            } else {
                endDateInput.value = '';
                endDateInput.removeAttribute('max');
            }
        } else {
            pageTitle.textContent = 'Crear Solicitud de Vacaciones';
            pageSubtitle.textContent = 'Registra un nuevo periodo de vacaciones.';
            quantityLabel.textContent = 'Cantidad de Dias';
            quantityHint.textContent = 'Calculado automaticamente.';
            quantityInput.readOnly = true;
            quantityInput.min = '1';
            quantityInput.max = '255';
            endDateInput.readOnly = false;
            endDateInput.removeAttribute('max');
            endDateInput.min = startDateInput.value || '<?= $minDate ?>';
            recalcDays();
        }
    };

    [radioVacaciones, radioPermiso].forEach((radio) => {
        if (!radio) return;
        radio.addEventListener('change', () => applyTypeMode(getRequestType()));
    });

    startDateInput.addEventListener('change', () => {
        const type = getRequestType();
        if (type === 1) {
            endDateInput.value = startDateInput.value;
            endDateInput.min = startDateInput.value;
            endDateInput.max = startDateInput.value;
        } else {
            endDateInput.min = startDateInput.value || '<?= $minDate ?>';
            if (endDateInput.value && endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
            recalcDays();
        }
    });

    endDateInput.addEventListener('change', () => {
        if (getRequestType() === 0) recalcDays();
    });

    form.addEventListener('submit', (event) => {
        const type = getRequestType();
        const start = toDate(startDateInput.value);
        const end = toDate(endDateInput.value);

        if (!start) { event.preventDefault(); notify('Debe seleccionar una fecha de inicio.'); return; }
        if (!end) { event.preventDefault(); notify('Debe seleccionar una fecha de fin.'); return; }

        if (type === 1) {
            if (startDateInput.value !== endDateInput.value) {
                event.preventDefault();
                notify('Para permisos, la fecha de inicio y fin deben ser el mismo dia.');
                return;
            }
            const hours = Number(quantityInput.value || 0);
            if (!hours || hours < 1 || hours > 999) {
                event.preventDefault();
                notify('La cantidad de horas debe estar entre 1 y 999.');
                return;
            }
        } else {
            if (end < start) {
                event.preventDefault();
                notify('La fecha de fin no puede ser anterior a la fecha de inicio.');
                return;
            }
            const days = countWorkDays(start, end);
            if (days < 1 || days > 255) {
                event.preventDefault();
                notify('La cantidad de dias debe estar entre 1 y 255.');
                return;
            }
            quantityInput.value = String(days);
        }
    });

    applyTypeMode(0);
})();
</script>

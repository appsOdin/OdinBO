<?php
$minDate = date('Y-m-d');
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Crear Solicitud de Vacaciones</h2>
        <p class="text-muted m-0">Registra un nuevo periodo de vacaciones.</p>
    </div>
</section>

<div class="card border-0 shadow-sm vacation-card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('rrhh/solicitud-vacaciones/store') ?>" id="vacationCreateForm" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">
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
                    <label for="quantity" class="form-label">Cantidad de Dias</label>
                    <input
                        type="number"
                        class="form-control"
                        id="quantity"
                        name="quantity"
                        required
                        min="1"
                        max="255"
                    >
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
        if (!value) {
            return null;
        }
        const parsed = new Date(`${value}T00:00:00`);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    const calculateDays = () => {
        const start = toDate(startDateInput.value);
        const end = toDate(endDateInput.value);

        if (!start || !end) {
            return;
        }

        const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
        if (days > 0) {
            quantityInput.value = String(days);
        }
    };

    startDateInput.addEventListener('change', () => {
        endDateInput.min = startDateInput.value || endDateInput.min;
        calculateDays();
    });

    endDateInput.addEventListener('change', calculateDays);

    form.addEventListener('submit', (event) => {
        const start = toDate(startDateInput.value);
        const end = toDate(endDateInput.value);

        if (!start || !end || end <= start) {
            event.preventDefault();
            notify('La fecha de fin debe ser mayor que la fecha de inicio.');
            return;
        }

        const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
        if (days <= 0 || days > 255) {
            event.preventDefault();
            notify('La cantidad de dias debe estar entre 1 y 255.');
            return;
        }

        if (Number(quantityInput.value || 0) !== days) {
            quantityInput.value = String(days);
        }
    });
})();
</script>

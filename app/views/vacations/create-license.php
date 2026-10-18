<?php
$collaborators = $collaborators ?? [];
$licenseTypes = $licenseTypes ?? [];
$catalogError = $catalogError ?? false;
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="fw-semibold mb-1">Crear Solicitud de Licencia</h2>
</section>

<div class="card border-0 shadow-sm vacation-card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('rrhh/solicitud-licencia/store') ?>" id="licenseCreateForm" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) ($csrfToken ?? get_csrf_token()), ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="licenseCollaborator">Colaboradores</label>
                    <select class="form-select" id="licenseCollaborator" name="assigned_user" required>
                        <option value="">Seleccione un colaborador</option>
                        <?php foreach ($collaborators as $collaborator): ?>
                            <option value="<?= htmlspecialchars((string) ($collaborator['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($collaborator['fullname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="licenseType">Tipo de licencia</label>
                    <select class="form-select" id="licenseType" name="license_type" required>
                        <option value="">Seleccione un tipo de licencia</option>
                        <?php foreach ($licenseTypes as $licenseType): ?>
                            <option value="<?= htmlspecialchars((string) ($licenseType['key_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($licenseType['lincense_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="licenseStartDate">Fecha de Inicio</label>
                    <input type="date" class="form-control" id="licenseStartDate" name="start_date" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="licenseEndDate">Fecha de Fin</label>
                    <input type="date" class="form-control" id="licenseEndDate" name="end_date" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="licenseQuantity">Cantidad de dias</label>
                    <input type="number" class="form-control" id="licenseQuantity" name="quantity" min="1" readonly required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="licenseDescription">Detalle</label>
                    <textarea class="form-control" id="licenseDescription" name="description" rows="3" maxlength="100" required></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="licenseFile">Adjunto</label>
                    <input type="file" class="form-control" id="licenseFile" name="files[]" accept="application/pdf,image/png,image/jpeg,.pdf,.png,.jpg,.jpeg" required>
                    <div class="invalid-feedback" id="licenseFileError"></div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <a href="<?= base_url('rrhh/solicitudes-vacaciones') ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" <?= $catalogError || $collaborators === [] || $licenseTypes === [] ? 'disabled' : '' ?>>Crear Solicitud</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const holidays = <?= json_encode(array_values(HOLIDAYS)) ?>;
    const form = document.getElementById('licenseCreateForm');
    const startInput = document.getElementById('licenseStartDate');
    const endInput = document.getElementById('licenseEndDate');
    const quantityInput = document.getElementById('licenseQuantity');
    const fileInput = document.getElementById('licenseFile');
    const fileError = document.getElementById('licenseFileError');
    const descriptionInput = document.getElementById('licenseDescription');
    if (!form || !startInput || !endInput || !quantityInput || !fileInput || !descriptionInput) return;

    const recalculate = () => {
        endInput.setCustomValidity('');
        endInput.min = startInput.value;
        quantityInput.value = '';
        if (!startInput.value || !endInput.value) return;
        if (endInput.value < startInput.value) {
            endInput.setCustomValidity('La fecha de fin no puede ser anterior a la fecha de inicio.');
            return;
        }
        const currentDate = new Date(`${startInput.value}T00:00:00`);
        const endDate = new Date(`${endInput.value}T00:00:00`);
        let quantity = 0;
        while (currentDate <= endDate) {
            const monthDay = `${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(currentDate.getDate()).padStart(2, '0')}`;
            if (currentDate.getDay() !== 0 && !holidays.includes(monthDay)) quantity++;
            currentDate.setDate(currentDate.getDate() + 1);
        }
        quantityInput.value = String(quantity);
        if (quantity < 1) endInput.setCustomValidity('El periodo debe incluir al menos un dia que no sea domingo ni feriado.');
    };

    const validateFile = () => {
        const file = fileInput.files?.[0];
        let message = '';
        if (file && (!/\.(pdf|png|jpe?g)$/i.test(file.name) || !['application/pdf', 'image/png', 'image/jpeg'].includes(file.type))) {
            message = 'Solo se permiten archivos PDF, PNG o JPG.';
        } else if (file && (file.size === 0 || file.size > 10 * 1024 * 1024)) {
            message = 'El archivo debe pesar entre 1 byte y 10 MB.';
        }
        fileInput.setCustomValidity(message);
        fileInput.classList.toggle('is-invalid', message !== '');
        if (fileError) fileError.textContent = message;
    };

    startInput.addEventListener('change', recalculate);
    endInput.addEventListener('change', recalculate);
    fileInput.addEventListener('change', validateFile);
    descriptionInput.addEventListener('input', () => descriptionInput.setCustomValidity(''));
    form.addEventListener('submit', (event) => {
        recalculate();
        validateFile();
        descriptionInput.setCustomValidity(descriptionInput.value.trim() ? '' : 'Ingrese el detalle de la licencia.');
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });
})();
</script>
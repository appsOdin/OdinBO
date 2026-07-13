<?php /** @var string $csrfToken */ ?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Cambiar contraseña</h2>
        <p class="text-muted m-0">Actualiza tu contraseña de acceso.</p>
    </div>
</section>

<div class="card border-0 shadow-sm">
    <div class="card-body" style="max-width: 620px;">
        <form id="changePasswordForm" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label for="oldPassword" class="form-label">Contraseña actual</label>
                <input type="password" class="form-control" id="oldPassword" name="oldPassword" minlength="8" required>
            </div>

            <div class="mb-3">
                <label for="newPassword" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="newPassword" name="newPassword" minlength="8" required>
            </div>

            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirmar nueva contraseña </label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" minlength="8" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
            </div>
        </form>
    </div>
</div>

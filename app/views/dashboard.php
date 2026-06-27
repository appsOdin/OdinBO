<section class="mb-4">
    <h2 class="fw-semibold mb-1">Dashboard</h2>
    <p class="text-muted m-0">Resumen general del sistema.</p>
</section>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Usuario autenticado</p>
                <h3 class="m-0"><?= htmlspecialchars((string) (($authUser['username'] ?? '') ?: 'N/D'), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Fecha actual</p>
                <h3 class="m-0"><?= htmlspecialchars((string) ($today ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
        </div>
    </div>
</div>

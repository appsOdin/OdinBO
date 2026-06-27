<?php
/** @var array<int, \App\Models\Article> $articles */
$articles = $articles ?? [];
?>
<section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-semibold mb-1">Articulos</h2>
        <p class="text-muted m-0">Consulta y detalle de articulos.</p>
    </div>
</section>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 mb-3" id="articleSearchForm" autocomplete="off">
            <div class="col-md-6 col-lg-4">
                <input type="text" id="searchArticles" class="form-control" placeholder="Buscar por ID o descripcion">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary" id="clearArticleSearch">Limpiar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle" id="articlesTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Descripcion</th>
                    <th>Precio</th>
                    <th>Accion</th>
                </tr>
                </thead>
                <tbody id="articlesTableBody">
                <?php foreach ($articles as $article): ?>
                    <tr
                        data-id="<?= htmlspecialchars($article->ID, ENT_QUOTES, 'UTF-8') ?>"
                        data-description="<?= htmlspecialchars($article->DESCRIPTION, ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= htmlspecialchars((string) $article->PRICE, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <td><?= htmlspecialchars($article->ID, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($article->DESCRIPTION, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(number_format($article->PRICE, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-article-detail" type="button">Detalle</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted" id="articlesPaginationInfo"></small>
            <div class="d-flex align-items-center gap-2">
                <label for="articlesPerPage" class="small text-muted m-0">Registros por pagina</label>
                <select id="articlesPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" id="articlesPrevPage" aria-label="Pagina anterior">&larr;</button>
                <small class="text-muted" id="articlesPageIndicator">Pagina 1 de 1</small>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="articlesNextPage" aria-label="Pagina siguiente">&rarr;</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="articleDetailModal" tabindex="-1" aria-labelledby="articleDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title" id="articleDetailModalLabel">Detalle del articulo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-lg-5">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold m-0">Imagen</h6>
                                    <small class="text-muted" id="articleDetailSelected"></small>
                                </div>
                                <div class="article-image-box rounded border bg-white p-2 d-flex justify-content-center align-items-center mb-2" id="articleMainImageBox">
                                    <img src="" alt="Imagen articulo" class="img-fluid d-none" id="articleMainImage">
                                    <small class="text-muted" id="articleMainImageEmpty">Sin imagen disponible.</small>
                                </div>
                                <div class="d-flex justify-content-end mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="expandArticleImage" disabled>Expandir imagen</button>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="articleImageThumbs"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-3">Datos del articulo</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted mb-1">ID</label>
                                        <div class="fw-semibold" id="articleDetailId">-</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label text-muted mb-1">Descripcion</label>
                                        <div class="fw-semibold" id="articleDetailDescription">-</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted mb-1">Precio</label>
                                        <div class="fw-semibold" id="articleDetailPrice">-</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted mb-1">Notas</label>
                                        <div class="fw-semibold" id="articleDetailNotes">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Detalle de sucursales</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle m-0" id="articleStocksTable">
                                <thead>
                                <tr>
                                    <th>Nombre de sucursal</th>
                                    <th>Disponible</th>
                                </tr>
                                </thead>
                                <tbody id="articleStocksTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="articleImageModal" tabindex="-1" aria-labelledby="articleImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header">
                <h5 class="modal-title" id="articleImageModalLabel">Imagen del articulo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Imagen ampliada" class="img-fluid rounded border" id="articleExpandedImage">
            </div>
        </div>
    </div>
</div>

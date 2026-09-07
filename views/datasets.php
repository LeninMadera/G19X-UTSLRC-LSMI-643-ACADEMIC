<?php
$pageTitle = 'Catalogo de datos';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Catalogo de datasets</h2>
        <?php if (in_array($current['rol'], ['admin', 'steward', 'owner'], true)): ?>
        <button id="btn-nuevo" class="btn btn-primary">Registrar dataset</button>
        <?php endif; ?>
    </div>

    <div class="toolbar">
        <input type="search" id="buscar" placeholder="Buscar por nombre o descripcion...">
        <select id="filtro-categoria">
            <option value="">Todas las categorias</option>
        </select>
    </div>

    <div id="msg" class="alert hidden"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Origen</th>
                    <th>Data Owner</th>
                    <th>Estado</th>
                    <th>Score</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="datasets-tbody">
                <tr><td colspan="7" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal de formulario -->
<div id="modal-backdrop" class="modal-backdrop hidden">
    <div class="modal">
        <h3 id="modal-title">Registrar dataset</h3>
        <form id="dataset-form" autocomplete="off">
            <input type="hidden" id="dataset-id" name="id" value="0">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="120" required>
            </div>
            <div class="field">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="5000"></textarea>
            </div>
            <div class="field">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required></select>
            </div>
            <div class="field">
                <label for="origen">Origen de datos</label>
                <select id="origen" name="origen" required></select>
            </div>
            <div class="field" id="owner-field">
                <label for="owner_id">Data Owner</label>
                <select id="owner_id" name="owner_id" required></select>
            </div>
            <div class="checkbox-field">
                <input type="checkbox" id="activo" name="activo" value="1" checked>
                <label for="activo">Dataset activo</label>
            </div>
            <div id="form-error" class="alert alert-error hidden"></div>
            <div class="actions">
                <button type="button" id="btn-cancelar" class="btn btn-ghost">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScript = 'datasets.js';
require_once __DIR__ . '/includes/footer.php'; ?>
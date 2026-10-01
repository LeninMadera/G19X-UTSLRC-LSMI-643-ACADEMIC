<?php
$pageTitle = 'Politicas';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Politicas de gobierno de datos</h2>
        <?php if (in_array($current['rol'], ['admin', 'steward'], true)): ?>
        <button id="btn-nueva" class="btn btn-primary">Nueva politica</button>
        <?php endif; ?>
    </div>

    <div id="msg" class="alert hidden" role="status"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Datasets</th>
                    <th>Creado por</th>
                    <th class="col-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="policies-tbody">
                <tr><td colspan="6" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal formulario politica -->
<div id="modal-policy" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="policy-title">
        <h3 id="policy-title">Nueva politica</h3>
        <form id="policy-form" autocomplete="off">
            <input type="hidden" id="policy-id" name="id" value="0">
            <div class="field">
                <label for="titulo">Titulo</label>
                <input type="text" id="titulo" name="titulo" maxlength="120" required>
            </div>
            <div class="field">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            <div class="field">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required></select>
            </div>
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="activa">activa</option>
                    <option value="inactiva">inactiva</option>
                </select>
            </div>
            <div id="policy-form-error" class="alert alert-error hidden" role="alert"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-policy">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal asignacion de datasets -->
<div id="modal-assign" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="assign-titulo">
        <h3 id="assign-titulo">Asignar datasets a la politica: <span id="assign-title"></span></h3>
        <div id="assign-datasets" class="assign-list">
            <p class="muted">Cargando...</p>
        </div>
        <div id="assign-error" class="alert alert-error hidden" role="alert"></div>
        <div class="actions">
            <button type="button" class="btn btn-ghost" data-close="modal-assign">Cerrar</button>
            <button id="btn-guardar-asignacion" class="btn btn-primary">Guardar asignacion</button>
        </div>
    </div>
</div>

<?php
$pageScript = 'policies.js';
require_once __DIR__ . '/includes/footer.php'; ?>
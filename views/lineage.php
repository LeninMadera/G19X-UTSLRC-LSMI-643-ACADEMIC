<?php
$pageTitle = 'Linaje de datos';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h2>Linaje de datos</h2>
            <p class="muted small">De que dataset proviene cada dataset y con cuales esta relacionado.</p>
        </div>
        <select id="sel-dataset" aria-label="Filtrar relaciones por dataset">
            <option value="">Todos los datasets</option>
        </select>
        <?php if (in_array($current['rol'], ['admin', 'steward', 'owner'], true)): ?>
        <button id="btn-nueva" class="btn btn-primary">Nueva relacion</button>
        <?php endif; ?>
    </div>

    <div id="msg" class="alert hidden" role="status"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Origen</th>
                    <th></th>
                    <th>Destino</th>
                    <th>Tipo</th>
                    <th>Descripcion</th>
                    <th>Registrado por</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="lineage-tbody">
                <tr><td colspan="8" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal formulario relacion -->
<div id="modal-lineage" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="lineage-title">
        <h3 id="lineage-title">Nueva relacion de linaje</h3>
        <form id="lineage-form" autocomplete="off">
            <input type="hidden" id="lineage-id" name="id" value="0">
            <div class="field">
                <label for="origen_id">Dataset origen</label>
                <select id="origen_id" name="origen_id" required></select>
            </div>
            <div class="field">
                <label for="destino_id">Dataset destino</label>
                <select id="destino_id" name="destino_id" required></select>
            </div>
            <div class="field">
                <label for="tipo">Tipo de relacion</label>
                <select id="tipo" name="tipo" required></select>
            </div>
            <div class="field">
                <label for="descripcion">Descripcion (opcional)</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"></textarea>
            </div>
            <div id="lineage-form-error" class="alert alert-error hidden" role="alert"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-lineage">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScript = 'lineage.js';
require_once __DIR__ . '/includes/footer.php'; ?>

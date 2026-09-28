<?php
$pageTitle = 'Detalle de dataset';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <a href="<?= e(APP_URL) ?>/views/datasets.php" class="btn btn-ghost">← Volver al catalogo</a>
        <button id="btn-editar" class="btn btn-primary hidden">Editar</button>
    </div>
    <div id="msg" class="alert hidden"></div>

    <div id="cargando" class="muted">Cargando detalle...</div>

    <div id="detalle" class="hidden">
        <div class="cards">
            <div class="card">
                <h3>Nombre</h3>
                <p class="card-text" id="d-nombre">--</p>
                <div class="tags" id="d-etiquetas"></div>
            </div>
            <div class="card">
                <h3>Score de calidad</h3>
                <p class="card-num" id="d-score">--</p>
                <p class="muted" id="d-score-fecha"></p>
            </div>
            <div class="card">
                <h3>Estado</h3>
                <p class="card-text" id="d-estado">--</p>
                <p class="muted">Categoria IA: <span id="d-categoria-ia">--</span></p>
                <button id="btn-confirmar-clasif" class="btn btn-sm hidden">Confirmar clasificacion IA</button>
            </div>
        </div>

        <div class="panel">
            <h2>Informacion general</h2>
            <table class="table detail-table">
                <tr><th>Categoria</th><td id="d-categoria">--</td></tr>
                <tr><th>Origen de datos</th><td id="d-origen">--</td></tr>
                <tr><th>Clasificacion</th><td id="d-clasificacion">--</td></tr>
                <tr><th>Data Owner</th><td id="d-owner">--</td></tr>
                <tr><th>Descripcion</th><td id="d-descripcion">--</td></tr>
                <tr><th>Creado</th><td id="d-creado">--</td></tr>
                <tr><th>Ultima actualizacion</th><td id="d-actualizado">--</td></tr>
            </table>
        </div>

        <div class="panel">
            <h2>Ultima medicion de calidad</h2>
            <div class="cards" id="d-dimensiones"></div>
        </div>

        <div class="panel">
            <h2>Politicas vinculadas</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Titulo</th><th>Tipo</th><th>Estado</th></tr></thead>
                    <tbody id="d-politicas"><tr><td colspan="3" class="muted center">Sin politicas asignadas.</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Cumplimiento normativo</h2>
            <p class="muted small">Registro declarativo interno del equipo de gobierno de datos; no constituye un dictamen legal.</p>
            <div class="cards">
                <div class="card">
                    <h3>Aplicables</h3>
                    <p class="card-num" id="dc-aplicables">--</p>
                </div>
                <div class="card">
                    <h3>Evaluados</h3>
                    <p class="card-num" id="dc-evaluados">--</p>
                </div>
                <div class="card">
                    <h3>Pendientes</h3>
                    <p class="card-num" id="dc-pendientes">--</p>
                </div>
                <div class="card">
                    <h3>No aplica</h3>
                    <p class="card-num" id="dc-noaplica">--</p>
                </div>
                <div class="card">
                    <h3>Cumplimiento</h3>
                    <p class="card-num" id="dc-cumplimiento">--</p>
                </div>
            </div>
            <button id="btn-agregar-eval" class="btn btn-primary hidden">Agregar evaluacion</button>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Control</th><th>Referencia</th><th>Severidad</th><th>Estado</th><th>Observaciones</th><th>Evidencia</th><th>Evaluador</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody id="d-compliance"><tr><td colspan="9" class="muted center">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Linaje de datos</h2>
            <p class="muted small">Recibe informacion de</p>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Dataset origen</th><th>Tipo</th><th>Descripcion</th></tr></thead>
                    <tbody id="d-linaje-entrada"><tr><td colspan="3" class="muted center">Sin relaciones de entrada.</td></tr></tbody>
                </table>
            </div>
            <p class="muted small">Envia informacion a</p>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Dataset destino</th><th>Tipo</th><th>Descripcion</th></tr></thead>
                    <tbody id="d-linaje-salida"><tr><td colspan="3" class="muted center">Sin relaciones de salida.</td></tr></tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <h2>Analisis de IA reciente</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tipo</th><th>Severidad</th><th>Hallazgo</th><th>Recomendacion</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody id="d-detections"><tr><td colspan="7" class="muted center">Sin analisis de IA.</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal evaluacion de control en este dataset -->
<div id="modal-eval" class="modal-backdrop hidden">
    <div class="modal">
        <h3 id="eval-title">Evaluar control</h3>
        <form id="eval-form" autocomplete="off">
            <input type="hidden" id="eval-control" name="control_id" value="0">
            <div class="field">
                <label for="eval-cselect">Control</label>
                <select id="eval-cselect" required></select>
            </div>
            <div class="field">
                <label for="eval-estado">Estado</label>
                <select id="eval-estado" name="estado">
                    <option value="cumple">Cumple</option>
                    <option value="no_cumple">No cumple</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="no_aplica">No aplica</option>
                </select>
            </div>
            <div class="field">
                <label for="eval-observaciones">Observaciones</label>
                <textarea id="eval-observaciones" name="observaciones" rows="3" maxlength="5000"></textarea>
            </div>
            <div class="field">
                <label for="eval-evidencia">Evidencia (URL / identificador)</label>
                <input type="text" id="eval-evidencia" name="evidencia" maxlength="255" placeholder="Ej: SOP-RET-001">
            </div>
            <div id="eval-form-error" class="alert alert-error hidden"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-eval">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScript = 'dataset_detail.js';
require_once __DIR__ . '/includes/footer.php'; ?>
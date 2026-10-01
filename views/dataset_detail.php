<?php
$pageTitle = 'Detalle de dataset';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head">
        <div class="page-head-id">
            <a href="<?= e(APP_URL) ?>/views/datasets.php" class="btn btn-ghost">
                <svg class="ico" aria-hidden="true"><use href="#i-back"></use></svg>
                Volver al catalogo
            </a>
            <h2 id="d-titulo">Cargando...</h2>
        </div>
        <div class="page-head-actions">
            <button id="btn-confirmar-clasif" class="btn hidden">Confirmar clasificacion IA</button>
            <button id="btn-editar" class="btn btn-primary hidden">Editar dataset</button>
        </div>
</div>

<div id="msg" class="alert hidden" role="status"></div>

<div id="cargando" class="panel">
    <p class="muted center">Cargando detalle del dataset...</p>
</div>

<div id="detalle" class="stack hidden">
    <section class="panel">
        <div class="cards cards-kpis">
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-db"></use></svg></span>
                    <span class="kpi-label">Dataset</span>
                </div>
                <p class="card-text" id="d-nombre">--</p>
                <div class="tags" id="d-etiquetas"></div>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-ok"><svg class="ico" aria-hidden="true"><use href="#i-gauge"></use></svg></span>
                    <span class="kpi-label">Score de calidad</span>
                </div>
                <p class="kpi-num" id="d-score">--</p>
                <p class="kpi-hint" id="d-score-fecha"></p>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-neutral"><svg class="ico" aria-hidden="true"><use href="#i-shield"></use></svg></span>
                    <span class="kpi-label">Estado</span>
                </div>
                <p class="card-text" id="d-estado">--</p>
                <p class="kpi-hint">Categoria IA: <span id="d-categoria-ia">--</span></p>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Informacion general</h2>
        <div class="table-wrap">
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
    </section>

    <section class="panel">
        <h2>Ultima medicion de calidad</h2>
        <div class="cards cards-kpis" id="d-dimensiones"></div>
    </section>

    <section class="panel">
        <h2>Politicas vinculadas</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Titulo</th><th>Tipo</th><th>Estado</th></tr></thead>
                <tbody id="d-politicas"><tr><td colspan="3" class="muted center">Sin politicas asignadas.</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Cumplimiento normativo</h2>
        <p class="muted small">Registro declarativo interno del equipo de gobierno de datos; no constituye un dictamen legal.</p>
        <div class="cards cards-kpis">
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-file"></use></svg></span>
                    <span class="kpi-label">Aplicables</span>
                </div>
                <p class="kpi-num" id="dc-aplicables">--</p>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-ok"><svg class="ico" aria-hidden="true"><use href="#i-check"></use></svg></span>
                    <span class="kpi-label">Evaluados</span>
                </div>
                <p class="kpi-num" id="dc-evaluados">--</p>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-warn"><svg class="ico" aria-hidden="true"><use href="#i-clock"></use></svg></span>
                    <span class="kpi-label">Pendientes</span>
                </div>
                <p class="kpi-num" id="dc-pendientes">--</p>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-neutral"><svg class="ico" aria-hidden="true"><use href="#i-list"></use></svg></span>
                    <span class="kpi-label">No aplica</span>
                </div>
                <p class="kpi-num" id="dc-noaplica">--</p>
            </div>
            <div class="card kpi">
                <div class="kpi-head">
                    <span class="kpi-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-chart"></use></svg></span>
                    <span class="kpi-label">Cumplimiento</span>
                </div>
                <p class="kpi-num" id="dc-cumplimiento">--</p>
            </div>
        </div>
        <div class="section-head">
            <span class="section-label">Evaluaciones</span>
            <button id="btn-agregar-eval" class="btn btn-primary btn-sm hidden">Agregar evaluacion</button>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Control</th><th>Referencia</th><th>Severidad</th><th>Estado</th><th>Observaciones</th><th>Evidencia</th><th>Evaluador</th><th>Fecha</th><th class="col-acciones">Acciones</th></tr></thead>
                <tbody id="d-compliance"><tr><td colspan="9" class="muted center">Cargando...</td></tr></tbody>
            </table>
        </div>
    </section>

    <details class="disclosure">
        <summary>Linaje de datos</summary>
        <div class="disclosure-body">
            <span class="block-label">Recibe informacion de</span>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Dataset origen</th><th>Tipo</th><th>Descripcion</th></tr></thead>
                    <tbody id="d-linaje-entrada"><tr><td colspan="3" class="muted center">Sin relaciones de entrada.</td></tr></tbody>
                </table>
            </div>
            <span class="block-label">Envia informacion a</span>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Dataset destino</th><th>Tipo</th><th>Descripcion</th></tr></thead>
                    <tbody id="d-linaje-salida"><tr><td colspan="3" class="muted center">Sin relaciones de salida.</td></tr></tbody>
                </table>
            </div>
        </div>
    </details>

    <details class="disclosure">
        <summary>Analisis de IA reciente</summary>
        <div class="disclosure-body">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tipo</th><th>Severidad</th><th>Hallazgo</th><th>Recomendacion</th><th>Estado</th><th>Fecha</th><th class="col-acciones">Acciones</th></tr></thead>
                    <tbody id="d-detections"><tr><td colspan="7" class="muted center">Sin analisis de IA.</td></tr></tbody>
                </table>
            </div>
        </div>
    </details>
</div>

<!-- Modal evaluacion de control en este dataset -->
<div id="modal-eval" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="eval-title">
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
            <div id="eval-form-error" class="alert alert-error hidden" role="alert"></div>
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
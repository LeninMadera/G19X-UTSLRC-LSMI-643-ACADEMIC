<?php
$pageTitle = 'Cumplimiento';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Cumplimiento normativo</h2>
        <?php if (in_array($current['rol'], ['admin', 'steward'], true)): ?>
        <button id="btn-nuevo" class="btn btn-primary">Nuevo control</button>
        <?php endif; ?>
    </div>

    <div class="cards" id="kpis">
        <div class="card">
            <h3>Controles</h3>
            <p class="card-num" id="kpi-controles">--</p>
        </div>
        <div class="card">
            <h3>Activos</h3>
            <p class="card-num" id="kpi-activos">--</p>
        </div>
        <div class="card">
            <h3>Criticos / Alta</h3>
            <p class="card-num" id="kpi-criticas">--</p>
        </div>
        <div class="card">
            <h3>Evaluaciones</h3>
            <p class="card-num" id="kpi-evaluaciones">--</p>
        </div>
        <div class="card">
            <h3>Cumplimiento global</h3>
            <p class="card-num" id="kpi-cumplimiento">--</p>
        </div>
    </div>

    <div class="toolbar">
        <select id="filtro-severidad">
            <option value="">Todas las severidades</option>
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="critica">Critica</option>
        </select>
        <select id="filtro-estado">
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>
    </div>

    <div id="msg" class="alert hidden"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Control</th>
                    <th>Referencia</th>
                    <th>Severidad</th>
                    <th>Estado</th>
                    <th>Politica</th>
                    <th>Evaluaciones</th>
                    <th>Creado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="controls-tbody">
                <tr><td colspan="8" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <p class="muted small">Los controles son un registro declarativo interno del equipo de gobierno de datos y no constituyen un dictamen legal.</p>
</section>

<!-- Modal formulario control -->
<div id="modal-control" class="modal-backdrop hidden">
    <div class="modal">
        <h3 id="control-title">Nuevo control</h3>
        <form id="control-form" autocomplete="off">
            <input type="hidden" id="control-id" name="id" value="0">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="150" required>
            </div>
            <div class="field">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="5000"></textarea>
            </div>
            <div class="field">
                <label for="referencia">Norma / marco / categoria de referencia</label>
                <input type="text" id="referencia" name="referencia" maxlength="150" placeholder="Ej: LFPDPPP art. 9">
            </div>
            <div class="field">
                <label for="severidad">Severidad</label>
                <select id="severidad" name="severidad">
                    <option value="baja">Baja</option>
                    <option value="media" selected>Media</option>
                    <option value="alta">Alta</option>
                    <option value="critica">Critica</option>
                </select>
            </div>
            <div class="field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="activo">activo</option>
                    <option value="inactivo">inactivo</option>
                </select>
            </div>
            <div class="field">
                <label for="policy_id">Politica vinculada (opcional)</label>
                <select id="policy_id" name="policy_id"></select>
            </div>
            <div id="control-form-error" class="alert alert-error hidden"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-control">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal evaluacion -->
<div id="modal-eval" class="modal-backdrop hidden">
    <div class="modal">
        <h3>Evaluar control: <span id="eval-title"></span></h3>
        <form id="eval-form" autocomplete="off">
            <input type="hidden" id="eval-control" name="control_id" value="0">
            <div class="field">
                <label for="eval-dataset">Dataset</label>
                <select id="eval-dataset" name="dataset_id" required></select>
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
$pageScript = 'compliance.js';
require_once __DIR__ . '/includes/footer.php'; ?>
<?php
$pageTitle = 'Monitoreo';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Monitoreo continuo de calidad</h2>
        <?php if (in_array($current['rol'], ['admin', 'steward', 'owner'], true)): ?>
        <button id="btn-nueva" class="btn btn-primary">Nueva configuracion</button>
        <?php endif; ?>
    </div>

    <div class="cards-kpis" id="kpis">
        <div class="card kpi">
            <div class="kpi-head">
                <span class="kpi-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-activity"></use></svg></span>
                <span class="kpi-label">Configuraciones activas</span>
            </div>
            <p class="kpi-num" id="kpi-activas">--</p>
        </div>
        <div class="card kpi">
            <div class="kpi-head">
                <span class="kpi-ico is-neutral"><svg class="ico" aria-hidden="true"><use href="#i-list"></use></svg></span>
                <span class="kpi-label">Total de configuraciones</span>
            </div>
            <p class="kpi-num" id="kpi-configs">--</p>
        </div>
        <div class="card kpi">
            <div class="kpi-head">
                <span class="kpi-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-clock"></use></svg></span>
                <span class="kpi-label">Ejecuciones</span>
            </div>
            <p class="kpi-num" id="kpi-ejecuciones">--</p>
        </div>
        <div class="card kpi">
            <div class="kpi-head">
                <span class="kpi-ico is-error"><svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg></span>
                <span class="kpi-label">Fallos</span>
            </div>
            <p class="kpi-num" id="kpi-fallos">--</p>
        </div>
        <div class="card kpi">
            <div class="kpi-head">
                <span class="kpi-ico is-ok"><svg class="ico" aria-hidden="true"><use href="#i-check"></use></svg></span>
                <span class="kpi-label">Tasa de exito</span>
            </div>
            <p class="kpi-num" id="kpi-tasa">--</p>
        </div>
    </div>

    <div id="msg" class="alert hidden" role="status"></div>

    <div class="section-head">
        <span class="section-label">Configuraciones</span>
        <span class="pill-summary" id="mon-resumen"></span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Dataset</th>
                    <th>Frecuencia</th>
                    <th>Estado</th>
                    <th>Proxima ejecucion</th>
                    <th>Ultimo estado</th>
                    <th class="col-num">Ultimo score</th>
                    <th class="col-num">Anomalias</th>
                    <th class="col-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="configs-tbody">
                <tr><td colspan="8" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <p class="table-note" id="mon-tabla-info"></p>
</section>

<details class="disclosure">
    <summary>Como funciona el monitoreo continuo</summary>
    <div class="disclosure-body">
        <p class="muted small">Cada ejecucion revalida la <strong>ultima medicion de calidad</strong> almacenada del dataset y ejecuta la deteccion de anomalias de IA. Las ejecuciones automaticas por vencimiento las dispara el programador <code>cli/monitor.bat</code>, que puede agendar en el Programador de tareas de Windows.</p>
    </div>
</details>

<!-- Modal formulario configuracion -->
<div id="modal-config" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="config-title">
        <h3 id="config-title">Nueva configuracion</h3>
        <form id="config-form" autocomplete="off">
            <input type="hidden" id="config-id" name="id" value="0">
            <div class="field">
                <label for="dataset_id">Dataset</label>
                <select id="dataset_id" name="dataset_id" required></select>
            </div>
            <div class="field">
                <label for="frecuencia">Frecuencia (minutos)</label>
                <input type="number" id="frecuencia" name="frecuencia" min="1" max="43200" value="1440" required list="frecuencias-sugeridas">
                <datalist id="frecuencias-sugeridas"></datalist>
                <span class="muted small">Sugeridas: 5, 15, 30, 60, 180, 360, 720 y 1440 minutos (1 dia).</span>
            </div>
            <div class="field">
                <label for="activo">Estado</label>
                <select id="activo" name="activo">
                    <option value="1">activo</option>
                    <option value="0">inactivo</option>
                </select>
            </div>
            <div id="config-form-error" class="alert alert-error hidden" role="alert"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-config">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal historial de ejecuciones -->
<div id="modal-runs" class="modal-backdrop hidden">
    <div class="modal modal-xl" role="dialog" aria-modal="true" aria-labelledby="runs-titulo">
        <div class="toolbar">
            <h3 id="runs-titulo">Historial: <span id="runs-title"></span></h3>
            <button type="button" class="btn btn-ghost btn-sm" data-close="modal-runs">Cerrar</button>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Causa</th>
                    <th>Estado</th>
                    <th class="col-num">Score</th>
                    <th class="col-num">Anomalias nuevas</th>
                    <th>Detalle</th>
                </tr>
                </thead>
                <tbody id="runs-tbody">
                    <tr><td colspan="6" class="muted center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$pageScript = 'monitoring.js';
require_once __DIR__ . '/includes/footer.php'; ?>
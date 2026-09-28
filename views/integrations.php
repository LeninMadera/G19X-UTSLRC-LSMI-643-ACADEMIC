<?php
$pageTitle = 'Fuentes externas';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Fuentes externas / Integraciones</h2>
        <?php if (in_array($current['rol'], ['admin', 'steward'], true)): ?>
        <button id="btn-nueva" class="btn btn-primary">Nueva fuente</button>
        <?php endif; ?>
    </div>

    <div class="cards" id="kpis">
        <div class="card">
            <h3>Fuentes activas</h3>
            <p class="card-num" id="kpi-activas">--</p>
        </div>
        <div class="card">
            <h3>Total de fuentes</h3>
            <p class="card-num" id="kpi-total">--</p>
        </div>
        <div class="card">
            <h3>Ultimas sincronizaciones ok</h3>
            <p class="card-num" id="kpi-ok">--</p>
        </div>
        <div class="card">
            <h3>Registros leidos</h3>
            <p class="card-num" id="kpi-registros">--</p>
        </div>
    </div>

    <div id="msg" class="alert hidden"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Dataset vinculado</th>
                    <th>Estado</th>
                    <th>Ultimo estado</th>
                    <th>Ultima ejecucion</th>
                    <th>Corridas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="sources-tbody">
                <tr><td colspan="9" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <p class="muted small">Cada fuente es una <strong>API REST que se consume por GET (JSON)</strong>. El modulo valida la URL (proteccion SSRF con resolucion DNS), limita el tamano de la respuesta y <strong>no copia los datos externos a MySQL</strong>: solo registra metadatos de cada corrida (total de registros y campos detectados). Probando conexion se verifica conectividad y formato; sincronizando se actualiza el estado y el historial de la fuente. Los hosts locales o privados solo se permiten con <code>INTEGRACION_PERMITIR_LOCALHOST=true</code> (pruebas).</p>
</section>

<!-- Modal formulario de fuente -->
<div id="modal-fuente" class="modal-backdrop hidden">
    <div class="modal modal-xl">
        <h3 id="fuente-title">Nueva fuente</h3>
        <form id="fuente-form" autocomplete="off">
            <input type="hidden" id="fuente-id" name="id" value="0">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="150" required>
            </div>
            <div class="field">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" rows="3" maxlength="5000"></textarea>
            </div>
            <div class="field">
                <label for="url">URL (GET, JSON)</label>
                <input type="url" id="url" name="url" maxlength="500" placeholder="https://ejemplo.com/api/datos" required>
                <span class="muted small">Solo http/https. Se rechazan hosts locales/privados salvo permiso expreso de pruebas.</span>
            </div>
            <div class="field">
                <label for="dataset_id">Dataset vinculado (opcional)</label>
                <select id="dataset_id" name="dataset_id">
                    <option value="">Sin vincular</option>
                </select>
            </div>
            <div class="field">
                <label for="timeout">Timeout (segundos)</label>
                <input type="number" id="timeout" name="timeout" min="1" max="60" value="15" required>
            </div>
            <div class="field">
                <label for="activo">Estado</label>
                <select id="activo" name="activo">
                    <option value="1">activo</option>
                    <option value="0">inactivo</option>
                </select>
            </div>
            <div id="fuente-form-error" class="alert alert-error hidden"></div>
            <div class="actions">
                <button type="button" class="btn btn-ghost" data-close="modal-fuente">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal historial de ejecuciones -->
<div id="modal-hist" class="modal-backdrop hidden">
    <div class="modal modal-xl">
        <div class="toolbar">
            <h3>Historial: <span id="hist-title"></span></h3>
            <button type="button" class="btn btn-ghost btn-sm" data-close="modal-hist">Cerrar</button>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Registros</th>
                        <th>Duracion</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="hist-tbody">
                    <tr><td colspan="6" class="muted center">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$pageScript = 'integrations.js';
require_once __DIR__ . '/includes/footer.php'; ?>
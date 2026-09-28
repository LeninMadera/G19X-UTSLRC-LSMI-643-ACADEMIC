<?php
$pageTitle = 'Reportes';
require_once __DIR__ . '/includes/header.php';
$rol = currentRole();
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h2>Reportes</h2>
            <p class="muted small">Estado actual de la calidad de datos y bitacora del sistema.</p>
        </div>
        <div class="toolbar-actions">
            <a href="<?= e(APP_URL); ?>/api/reports.php?action=export" class="btn btn-ghost">Exportar CSV</a>
            <button class="btn" id="rp-refresh">Actualizar</button>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Estado de calidad por dataset</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Dataset</th>
                    <th>Categoria</th>
                    <th class="col-num">Ultimo score</th>
                    <th>Ultima medicion</th>
                    <th>Nivel</th>
                </tr>
            </thead>
            <tbody id="rp-tabla"></tbody>
        </table>
    </div>
    <p class="table-note" id="rp-tabla-info"></p>
</section>

<?php if ($rol === 'admin'): ?>
<section class="panel">
    <h2>Bitacora de actividad (admin)</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Accion</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody id="rp-log"></tbody>
        </table>
    </div>
    <p class="table-note" id="rp-log-info"></p>
</section>
<?php endif; ?>

<section class="panel">
    <h2>Alertas y recomendaciones de IA</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dataset</th>
                    <th>Tipo</th>
                    <th>Severidad</th>
                    <th class="col-wide">Descripcion</th>
                    <th class="col-wide">Recomendacion</th>
                </tr>
            </thead>
            <tbody id="rp-alertas"></tbody>
        </table>
    </div>
    <p class="table-note" id="rp-alertas-info"></p>
</section>

<?php
$pageScript = 'reports.js';
require_once __DIR__ . '/includes/footer.php'; ?>

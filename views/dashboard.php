<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h2>Panel de control</h2>
            <p class="muted small" id="dl-fecha">Cargando indicadores...</p>
        </div>
        <button class="btn" id="dl-refresh">Actualizar</button>
    </div>
</section>

<section class="cards-kpis" id="dl-kpis">
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-datasets">-</div>
        <div class="kpi-label">Datasets registrados</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-calidad">-</div>
        <div class="kpi-label">Calidad promedio</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-alertas">-</div>
        <div class="kpi-label">Alertas IA pendientes</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-politicas">-</div>
        <div class="kpi-label">Politicas activas</div>
    </div>
</section>

<section class="cards-kpis" id="dl-kpis-monitoreo">
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-mon-activas">-</div>
        <div class="kpi-label">Monitoreos activos</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-mon-ejecuciones">-</div>
        <div class="kpi-label">Ejecuciones de monitoreo</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-mon-fallos">-</div>
        <div class="kpi-label">Ejecuciones con fallo</div>
    </div>
    <div class="card kpi">
        <div class="kpi-num" id="dl-kpi-mon-tasa">-</div>
        <div class="kpi-label">Tasa de exito (monitoreo)</div>
    </div>
</section>

<div class="grid-2">
    <section class="panel">
        <h2>Score de calidad por dataset</h2>
        <div class="chart-box"><canvas id="dl-chart-scores" height="260"></canvas></div>
        <div class="legend-row small">
            <span class="dot" style="background:#16a34a"></span> 85+ Excelente
            <span class="dot" style="background:#d97706"></span> 70-84 Aceptable
            <span class="dot" style="background:#dc2626"></span> &lt; 70 Por mejorar
        </div>
    </section>

    <section class="panel">
        <h2>Categorias de datos</h2>
        <div id="dl-categorias" class="bar-col"></div>
    </section>
</div>

<div class="grid-2">
    <section class="panel">
        <h2>Alertas recientes (IA)</h2>
        <ul id="dl-alertas" class="list-notif"></ul>
    </section>

    <section class="panel">
        <h2>Actividad reciente</h2>
        <ul id="dl-actividad" class="list-notif"></ul>
    </section>
</div>

<?php
$pageScript = 'dashboard.js';
require_once __DIR__ . '/includes/footer.php'; ?>
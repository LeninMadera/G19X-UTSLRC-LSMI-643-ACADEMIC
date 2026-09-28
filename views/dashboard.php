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
        <div class="toolbar-actions">
            <span class="pill-summary" id="dl-pendientes"></span>
            <button class="btn" id="dl-refresh">Actualizar</button>
        </div>
    </div>
</section>

<section class="cards-kpis" id="dl-kpis">
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-db"></use></svg></span>
            <span class="kpi-label">Datasets registrados</span>
        </div>
        <div class="kpi-num" id="dl-kpi-datasets">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-gauge"></use></svg></span>
            <span class="kpi-label">Calidad promedio</span>
        </div>
        <div class="kpi-num" id="dl-kpi-calidad">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-bell"></use></svg></span>
            <span class="kpi-label">Alertas IA pendientes</span>
        </div>
        <div class="kpi-num" id="dl-kpi-alertas">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-file"></use></svg></span>
            <span class="kpi-label">Politicas activas</span>
        </div>
        <div class="kpi-num" id="dl-kpi-politicas">-</div>
    </div>
</section>

<div class="section-head">
    <span class="section-label">Monitoreo continuo</span>
    <span class="muted small" id="dl-mon-fecha">-</span>
</div>

<section class="cards-kpis" id="dl-kpis-monitoreo">
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-shield"></use></svg></span>
            <span class="kpi-label">Monitoreos activos</span>
        </div>
        <div class="kpi-num" id="dl-kpi-mon-activas">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-chart"></use></svg></span>
            <span class="kpi-label">Ejecuciones de monitoreo</span>
        </div>
        <div class="kpi-num" id="dl-kpi-mon-ejecuciones">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg></span>
            <span class="kpi-label">Ejecuciones con fallo</span>
        </div>
        <div class="kpi-num" id="dl-kpi-mon-fallos">-</div>
    </div>
    <div class="card kpi">
        <div class="kpi-head">
            <span class="kpi-ico"><svg class="ico" aria-hidden="true"><use href="#i-clock"></use></svg></span>
            <span class="kpi-label">Tasa de exito (monitoreo)</span>
        </div>
        <div class="kpi-num" id="dl-kpi-mon-tasa">-</div>
    </div>
</section>

<div class="grid-2">
    <section class="panel">
        <h2>Score de calidad por dataset</h2>
        <div class="chart-box"><canvas id="dl-chart-scores" height="260"></canvas></div>
        <div class="legend-row small">
            <span class="legend-item"><span class="dot dot-ok"></span>85+ Excelente</span>
            <span class="legend-item"><span class="dot dot-warn"></span>70-84 Aceptable</span>
            <span class="legend-item"><span class="dot dot-error"></span>&lt; 70 Por mejorar</span>
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

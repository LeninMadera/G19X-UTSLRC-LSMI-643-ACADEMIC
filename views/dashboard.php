<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h2>Bienvenido/a, <?= e($current['name']) ?></h2>
    <p class="muted">Este es el panel principal del proyecto. A partir de la Etapa 7
       mostrara aqui las metricas del gobierno de datos y las alertas de IA.</p>
</section>

<div class="cards">
    <div class="card">
        <h3>Catalogo</h3>
        <p class="card-num">0</p>
        <p class="muted">datasets registrados</p>
    </div>
    <div class="card">
        <h3>Calidad promedio</h3>
        <p class="card-num">--</p>
        <p class="muted">score global</p>
    </div>
    <div class="card">
        <h3>Alertas IA</h3>
        <p class="card-num">0</p>
        <p class="muted">anomalias pendientes</p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
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

<?php
$pageScript = 'dataset_detail.js';
require_once __DIR__ . '/includes/footer.php'; ?>
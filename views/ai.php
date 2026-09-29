<?php
$pageTitle = 'Asistente IA';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h2>Asistente de Inteligencia Artificial</h2>
            <p class="muted small" id="ai-mode">Verificando disponibilidad del servicio de IA...</p>
        </div>
        <select id="sel-dataset" aria-label="Seleccionar dataset a analizar">
            <option value="">Selecciona un dataset</option>
        </select>
    </div>
    <div id="msg" class="alert hidden" role="status"></div>
</section>

<section class="panel" id="panel-acciones" style="display:none">
    <h2>Acciones disponibles</h2>
    <div class="cards">
        <div class="card">
            <h3>Clasificar dataset</h3>
            <p class="muted">La IA sugiere categoria y etiquetas segun el nombre y la descripcion.</p>
            <button class="btn btn-primary" data-accion="classify">Clasificar</button>
        </div>
        <div class="card">
            <h3>Detectar anomalias</h3>
            <p class="muted">Analiza el historial de calidad y busca caidas o valores bajo umbral.</p>
            <button class="btn btn-primary" data-accion="anomalies">Detectar</button>
        </div>
        <div class="card">
            <h3>Resumen del estado</h3>
            <p class="muted">Genera una descripcion en lenguaje natural del dataset.</p>
            <button class="btn btn-primary" data-accion="summary">Resumir</button>
        </div>
        <div class="card">
            <h3>Sugerencias de mejora</h3>
            <p class="muted">Propone acciones concretas segun las dimensiones debiles.</p>
            <button class="btn btn-primary" data-accion="suggest">Sugerir</button>
        </div>
    </div>
</section>

<section class="panel" id="panel-resultados" style="display:none">
    <h2>Resultado del analisis</h2>
    <div id="resultado" class="muted">--</div>
</section>

<?php
$pageScript = 'ai.js';
require_once __DIR__ . '/includes/footer.php'; ?>
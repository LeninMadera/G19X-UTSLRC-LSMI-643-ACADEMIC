<?php
$pageTitle = 'Asistente IA';
require_once __DIR__ . '/includes/header.php';

/* El mismo filtro de rol que ya aplica el script: solo estos roles pueden
   lanzar acciones. Solo condiciona que texto de ayuda se muestra, no la
   logica ni los permisos. */
$rolPuedeUsarIA = in_array($current['rol'], ['admin', 'steward', 'owner'], true);
?>
<section class="panel ai-hero">
    <div class="ai-hero-top">
        <div class="ai-hero-id">
            <span class="ai-hero-mark">
                <svg class="ico" aria-hidden="true"><use href="#i-sparkles"></use></svg>
            </span>
            <div class="ai-hero-id-txt">
                <h2>Asistente de Inteligencia Artificial</h2>
                <p class="muted small">Clasifica, revisa y resume los datasets del catalogo con asistencia de IA.</p>
            </div>
        </div>

        <div class="ai-status-wrap">
            <span class="pill-summary ai-status" id="ai-mode">
                <span class="dot"></span>
                <span>Verificando disponibilidad del servicio...</span>
            </span>
            <span class="ai-engine" id="ai-engine"></span>
        </div>
    </div>

    <?php if ($rolPuedeUsarIA): ?>
    <div class="ai-scope">
        <div class="ai-scope-txt">
            <span class="block-label">Dataset seleccionado</span>
            <span class="ai-scope-hint">Las cuatro acciones se aplican a este dataset.</span>
        </div>
        <select id="sel-dataset" aria-label="Seleccionar dataset a analizar">
            <option value="">Selecciona un dataset</option>
        </select>
    </div>

    <p class="ai-hint" id="ai-hint">
        <svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg>
        <span>Elige un dataset arriba para habilitar las acciones de IA.</span>
    </p>
    <?php endif; ?>

    <div id="msg" class="alert hidden" role="status"></div>
</section>

<section class="panel" id="panel-acciones" style="display:none">
    <div class="section-head">
        <span class="section-label">Acciones de IA</span>
    </div>

    <div class="ai-actions">
        <div class="ai-action">
            <span class="ai-action-ico is-info"><svg class="ico" aria-hidden="true"><use href="#i-grid"></use></svg></span>
            <h3>Clasificar dataset</h3>
            <p>La IA sugiere categoria y etiquetas segun el nombre y la descripcion.</p>
            <button class="btn btn-primary btn-block" data-accion="classify">Clasificar</button>
        </div>

        <div class="ai-action">
            <span class="ai-action-ico is-error"><svg class="ico" aria-hidden="true"><use href="#i-alert"></use></svg></span>
            <h3>Detectar anomalias</h3>
            <p>Analiza el historial de calidad y busca caidas o valores bajo umbral.</p>
            <button class="btn btn-primary btn-block" data-accion="anomalies">Detectar</button>
        </div>

        <div class="ai-action">
            <span class="ai-action-ico is-ok"><svg class="ico" aria-hidden="true"><use href="#i-file"></use></svg></span>
            <h3>Resumen del estado</h3>
            <p>Genera una descripcion en lenguaje natural del dataset.</p>
            <button class="btn btn-primary btn-block" data-accion="summary">Resumir</button>
        </div>

        <div class="ai-action">
            <span class="ai-action-ico is-warn"><svg class="ico" aria-hidden="true"><use href="#i-sparkles"></use></svg></span>
            <h3>Sugerencias de mejora</h3>
            <p>Propone acciones concretas segun las dimensiones debiles.</p>
            <button class="btn btn-primary btn-block" data-accion="suggest">Sugerir</button>
        </div>
    </div>
</section>

<section class="panel" id="panel-resultados" style="display:none">
    <div class="section-head">
        <span class="section-label">Resultado del analisis</span>
        <span class="pill-summary" id="ai-result-accion"></span>
    </div>
    <div id="resultado"></div>
</section>

<?php
$pageScript = 'ai.js';
require_once __DIR__ . '/includes/footer.php'; ?>

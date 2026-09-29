<?php
$pageTitle = 'Calidad de datos';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <div>
            <h2>Monitoreo de calidad</h2>
            <p class="muted small">Score = promedio ponderado: completitud 20% · exactitud 25% · consistencia 20% · vigencia 15% · unicidad 20%</p>
        </div>
        <select id="sel-dataset" aria-label="Seleccionar dataset a medir">
            <option value="">Selecciona un dataset</option>
        </select>
    </div>

    <div id="msg" class="alert hidden" role="status"></div>
</section>

<section class="panel" id="panel-historico" style="display:none">
    <h2>Historial de mediciones</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Completitud</th>
                    <th>Exactitud</th>
                    <th>Consistencia</th>
                    <th>Vigencia</th>
                    <th>Unicidad</th>
                    <th>Score</th>
                    <th>Registrado por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="history-tbody">
                <tr><td colspan="9" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php if (in_array($current['rol'], ['admin', 'steward', 'owner'], true)): ?>
<section class="panel" id="panel-form" style="display:none">
    <h2>Registrar nueva medicion</h2>
    <form id="quality-form">
        <div class="cards">
            <div class="field card"><label for="completitud">Completitud (%)</label>
                <input type="number" id="completitud" name="completitud" min="0" max="100" step="0.01" inputmode="decimal" required>
            </div>
            <div class="field card"><label for="exactitud">Exactitud (%)</label>
                <input type="number" id="exactitud" name="exactitud" min="0" max="100" step="0.01" inputmode="decimal" required>
            </div>
            <div class="field card"><label for="consistencia">Consistencia (%)</label>
                <input type="number" id="consistencia" name="consistencia" min="0" max="100" step="0.01" inputmode="decimal" required>
            </div>
            <div class="field card"><label for="vigencia">Vigencia (%)</label>
                <input type="number" id="vigencia" name="vigencia" min="0" max="100" step="0.01" inputmode="decimal" required>
            </div>
            <div class="field card"><label for="unicidad">Unicidad (%)</label>
                <input type="number" id="unicidad" name="unicidad" min="0" max="100" step="0.01" inputmode="decimal" required>
            </div>
        </div>
        <div id="form-error" class="alert alert-error hidden" role="alert"></div>
        <button type="submit" class="btn btn-primary">Registrar medicion</button>
    </form>
</section>
<?php endif; ?>

<?php
$pageScript = 'quality.js';
require_once __DIR__ . '/includes/footer.php'; ?>
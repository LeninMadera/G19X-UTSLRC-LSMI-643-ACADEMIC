<?php
/**
 * API de calidad de datos.
 *
 *   GET  api/quality.php?action=history&dataset_id=N   (todos los roles)
 *   POST api/quality.php?action=register                (admin, steward; owner en su dataset)
 *   POST api/quality.php?action=delete                  (admin, steward)
 *
 * El score global es el promedio ponderado de 5 dimensiones:
 *   completitud 20% | exactitud 25% | consistencia 20% | vigencia 15% | unicidad 20%
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

const PESOS = [
    'completitud'  => 0.20,
    'exactitud'    => 0.25,
    'consistencia' => 0.20,
    'vigencia'     => 0.15,
    'unicidad'     => 0.20,
];

const UMBRAL_ALERTA = 80.0;

/** Valida una dimension (0 a 100, hasta 2 decimales). */
function validarDimension($valor, string $nombre, array &$errores): void
{
    if (!is_numeric($valor)) {
        $errores[] = ucfirst($nombre) . ' debe ser un numero.';
        return;
    }
    $v = (float)$valor;
    if ($v < 0 || $v > 100) {
        $errores[] = ucfirst($nombre) . ' debe estar entre 0 y 100.';
    }
}

/** Calcula el score con los pesos definidos. */
function calcularScore(array $dim): float
{
    $score = 0.0;
    foreach (PESOS as $dimension => $peso) {
        $score += (float)$dim[$dimension] * $peso;
    }
    return round($score, 2);
}

switch ($action) {

    case 'history':
        $datasetId = (int) query('dataset_id', 0);
        if ($datasetId <= 0) {
            respondJson(false, null, 'Dataset invalido.');
        }

        $stmt = db()->prepare(
            'SELECT qm.id, qm.completitud, qm.exactitud, qm.consistencia, qm.vigencia,
                    qm.unicidad, qm.score, qm.fecha_medicion, u.nombre AS registrado_por
             FROM quality_metrics qm
             LEFT JOIN users u ON u.id = qm.user_id
             WHERE qm.dataset_id = :dataset_id
             ORDER BY qm.fecha_medicion DESC, qm.id DESC'
        );
        $stmt->execute(['dataset_id' => $datasetId]);
        $metrics = $stmt->fetchAll();

        // Marcar las mediciones bajo el umbral para mostrarlas como alerta
        foreach ($metrics as &$m) {
            $m['bajo_umbral'] = (float)$m['score'] < UMBRAL_ALERTA;
        }

        respondJson(true, [
            'metrics'      => $metrics,
            'umbral'       => UMBRAL_ALERTA,
            'pesos'        => PESOS,
        ]);
        break;

    case 'register':
        requireRole(['admin', 'steward', 'owner']);

        $data = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);

        if ($datasetId <= 0) {
            respondJson(false, null, 'Debes seleccionar un dataset.');
        }

        // Verificar que el owner solo registre en sus datasets
        if (currentRole() === 'owner') {
            $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
            $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
            if ($stmt->fetch() === false) {
                respondJson(false, null, 'Solo puedes registrar calidad en tus datasets.');
            }
        }

        $dim = [];
        foreach (PESOS as $dimension => $peso) {
            $dim[$dimension] = $data[$dimension] ?? null;
        }

        $errores = [];
        foreach ($dim as $dimension => $valor) {
            validarDimension($valor, str_replace('_', ' ', $dimension), $errores);
        }
        if (!empty($errores)) {
            respondJson(false, null, implode(' ', $errores));
        }

        $score = calcularScore($dim);

        try {
            $stmt = db()->prepare(
                'INSERT INTO quality_metrics
                    (dataset_id, completitud, exactitud, consistencia, vigencia, unicidad, score, user_id)
                 VALUES
                    (:dataset_id, :completitud, :exactitud, :consistencia, :vigencia, :unicidad, :score, :user_id)'
            );
            $stmt->execute([
                'dataset_id'   => $datasetId,
                'completitud'  => $dim['completitud'],
                'exactitud'    => $dim['exactitud'],
                'consistencia' => $dim['consistencia'],
                'vigencia'     => $dim['vigencia'],
                'unicidad'     => $dim['unicidad'],
                'score'        => $score,
                'user_id'      => currentUser()['id'],
            ]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo registrar la medicion.');
        }

        logActivity('calidad:register', ['dataset_id' => $datasetId, 'score' => $score]);
        respondJson(true, ['score' => $score], 'Medicion registrada. Score: ' . $score);
        break;

    case 'delete':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de medicion invalido.');
        }

        $stmt = db()->prepare('DELETE FROM quality_metrics WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Medicion no encontrada.', 404);
        }

        logActivity('calidad:delete', ['metric_id' => $id]);
        respondJson(true, null, 'Medicion eliminada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
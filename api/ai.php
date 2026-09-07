<?php
/**
 * API del modulo de Inteligencia Artificial.
 *
 *   GET  api/ai.php?action=status      (todos los roles)
 *   POST api/ai.php?action=classify    (admin, steward, owner)
 *   POST api/ai.php?action=anomalies   (admin, steward, owner)
 *   POST api/ai.php?action=summary     (admin, steward, owner)
 *   POST api/ai.php?action=suggest     (admin, steward, owner)
 */
require_once __DIR__ . '/../includes/ai_service.php';

requireAuth();

$action = query('action', '');

/** True si el rol actual puede editar (o es dueno de) ese dataset. */
function puedeEditarDataset(int $datasetId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
    $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
    return $stmt->fetch() !== false;
}

/** Contador de anomalias pendientes para el resumen. */
function anomaliasPendientes(int $datasetId): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM ai_detections
         WHERE dataset_id = :id AND tipo = 'anomalia' AND estado = 'pendiente'"
    );
    $stmt->execute(['id' => $datasetId]);
    return (int)$stmt->fetchColumn();
}

switch ($action) {

    case 'status':
        respondJson(true, [
            'disponible' => ai_disponible(),
            'simulado'   => ai_simulado(),
            'modelo'     => AI_MODEL,
            'proveedor'  => parse_url(AI_BASE_URL, PHP_URL_HOST) ?: 'desconocido',
        ]);
        break;

    case 'classify':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);
        if ($datasetId <= 0 || !puedeEditarDataset($datasetId)) {
            respondJson(false, null, 'Dataset invalido o sin permisos.');
        }

        $stmt = db()->prepare('SELECT nombre, descripcion FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datasetId]);
        $dataset = $stmt->fetch();
        if (!$dataset) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        try {
            $res = ia_clasificar($dataset['nombre'], (string)$dataset['descripcion']);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        $jsonEtiquetas = json_encode($res['etiquetas'], JSON_UNESCAPED_UNICODE);
        db()->prepare(
            'UPDATE datasets SET categoria_ia = :cat, etiquetas_ia = :tags WHERE id = :id'
        )->execute(['cat' => $res['categoria'], 'tags' => $jsonEtiquetas, 'id' => $datasetId]);

        db()->prepare(
            'INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado)
             VALUES (:id, :tipo, :sev, :desc, :rec, :est)'
        )->execute([
            'id'    => $datasetId,
            'tipo'  => 'clasificacion',
            'sev'   => 'baja',
            'desc'  => 'Categoria sugerida: ' . $res['categoria'] . '. Etiquetas: ' . implode(', ', $res['etiquetas']),
            'rec'   => 'Revisa y confirma la clasificacion sugerida por la IA.',
            'est'   => 'pendiente',
        ]);

        logActivity('ai:classify', ['dataset_id' => $datasetId, 'simulado' => ai_simulado()]);
        respondJson(true, ['categoria' => $res['categoria'], 'etiquetas' => $res['etiquetas']],
            ai_simulado() ? 'Clasificacion (simulada) aplicada.' : 'Clasificacion aplicada.');
        break;

    case 'anomalies':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);
        if ($datasetId <= 0 || !puedeEditarDataset($datasetId)) {
            respondJson(false, null, 'Dataset invalido o sin permisos.');
        }

        $stmt = db()->prepare(
            'SELECT fecha_medicion, completitud, exactitud, consistencia, vigencia, unicidad, score
             FROM quality_metrics WHERE dataset_id = :id ORDER BY fecha_medicion ASC'
        );
        $stmt->execute(['id' => $datasetId]);
        $metrics = $stmt->fetchAll();

        try {
            $anomalias = ia_detectar_anomalias($metrics);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        $insertadas = 0;
        foreach ($anomalias as $a) {
            db()->prepare(
                'INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado)
                 VALUES (:id, :tipo, :sev, :desc, :rec, :est)'
            )->execute([
                'id'    => $datasetId,
                'tipo'  => 'anomalia',
                'sev'   => $a['severidad'],
                'desc'  => mb_substr($a['descripcion'], 0, 500),
                'rec'   => null,
                'est'   => 'pendiente',
            ]);
            $insertadas++;
        }

        logActivity('ai:anomalies', ['dataset_id' => $datasetId, 'insertadas' => $insertadas, 'simulado' => ai_simulado()]);
        respondJson(true, ['anomalias' => $anomalias], 'Analisis completado. ' . $insertadas . ' anomalias detectadas.');
        break;

    case 'summary':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);
        if ($datasetId <= 0 || !puedeEditarDataset($datasetId)) {
            respondJson(false, null, 'Dataset invalido o sin permisos.');
        }

        $stmt = db()->prepare(
            'SELECT d.nombre, d.categoria, d.categoria_ia,
                    (SELECT qm.score FROM quality_metrics qm
                     WHERE qm.dataset_id = d.id
                     ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultimo_score,
                    (SELECT COUNT(*) FROM policy_datasets pd WHERE pd.dataset_id = d.id) AS politicas
             FROM datasets d WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $datasetId]);
        $row = $stmt->fetch();
        if (!$row) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        $ctx = $row;
        $ctx['anomalias_pendientes'] = anomaliasPendientes($datasetId);

        try {
            $texto = ia_resumen($ctx);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        logActivity('ai:summary', ['dataset_id' => $datasetId, 'simulado' => ai_simulado()]);
        respondJson(true, ['resumen' => $texto], '');
        break;

    case 'suggest':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);
        if ($datasetId <= 0 || !puedeEditarDataset($datasetId)) {
            respondJson(false, null, 'Dataset invalido o sin permisos.');
        }

        $stmt = db()->prepare(
            'SELECT completitud, exactitud, consistencia, vigencia, unicidad, score
             FROM quality_metrics WHERE dataset_id = :id
             ORDER BY fecha_medicion DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $datasetId]);
        $ultima = $stmt->fetch();

        try {
            $sugerencias = ia_sugerencias($ultima);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        foreach ($sugerencias as $s) {
            db()->prepare(
                'INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado)
                 VALUES (:id, :tipo, :sev, :desc, :rec, :est)'
            )->execute([
                'id'    => $datasetId,
                'tipo'  => 'sugerencia',
                'sev'   => 'media',
                'desc'  => mb_substr($s['recomendacion'], 0, 500),
                'rec'   => null,
                'est'   => 'pendiente',
            ]);
        }

        logActivity('ai:suggest', ['dataset_id' => $datasetId, 'sugerencias' => count($sugerencias), 'simulado' => ai_simulado()]);
        respondJson(true, ['sugerencias' => $sugerencias], 'Sugerencias generadas: ' . count($sugerencias));
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
<?php
/**
 * API del modulo de Inteligencia Artificial.
 *
 *   GET  api/ai.php?action=status      (todos los roles)
 *   POST api/ai.php?action=classify    (admin, steward, owner)
 *   POST api/ai.php?action=anomalies   (admin, steward, owner)
 *   POST api/ai.php?action=summary     (admin, steward, owner)
 *   POST api/ai.php?action=suggest     (admin, steward, owner)
 *   POST api/ai.php?action=resolve     (admin, steward, owner)  body: { detection_id, estado }
 *   POST api/ai.php?action=confirm_classify (admin, steward, owner) body: { detection_id }
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
    // Indica que una deteccion fue atendida (estado pendiente -> revisada/aplicada/descartada).
    // POST api/ai.php?action=resolve  body: { detection_id, estado }
    case 'resolve':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $detectionId = (int)($data['detection_id'] ?? 0);
        $estado = trim((string)($data['estado'] ?? ''));
        if ($detectionId <= 0) {
            respondJson(false, null, 'Deteccion invalida.');
        }
        if (!in_array($estado, ['revisada', 'aplicada', 'descartada'], true)) {
            respondJson(false, null, 'Estado invalido.');
        }

        $stmt = db()->prepare('SELECT dataset_id FROM ai_detections WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $detectionId]);
        $det = $stmt->fetch();
        if (!$det) {
            respondJson(false, null, 'Deteccion no encontrada.', 404);
        }
        if (!puedeEditarDataset((int)$det['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        db()->prepare('UPDATE ai_detections SET estado = :estado WHERE id = :id')
            ->execute(['estado' => $estado, 'id' => $detectionId]);
        logActivity('ai:resolve', ['detection_id' => $detectionId, 'estado' => $estado]);
        respondJson(true, null, 'Estado actualizado a ' . $estado . '.');
        break;

    // Aplica la clasificacion sugerida por la IA al dataset y cierra la deteccion.
    // POST api/ai.php?action=confirm_classify  body: { detection_id }
    case 'confirm_classify':
        requireRole(['admin', 'steward', 'owner']);
        $data = jsonInput();
        $detectionId = (int)($data['detection_id'] ?? 0);
        if ($detectionId <= 0) {
            respondJson(false, null, 'Deteccion invalida.');
        }

        $stmt = db()->prepare(
            'SELECT dataset_id, tipo, estado, descripcion_ia FROM ai_detections WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $detectionId]);
        $det = $stmt->fetch();
        if (!$det) {
            respondJson(false, null, 'Deteccion no encontrada.', 404);
        }
        if ($det['tipo'] !== 'clasificacion') {
            respondJson(false, null, 'La deteccion no es una clasificacion.');
        }
        if ($det['estado'] === 'aplicada') {
            respondJson(false, null, 'La clasificacion ya fue aplicada.');
        }
        if (!puedeEditarDataset((int)$det['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        $stmt = db()->prepare(
            'SELECT categoria_ia, etiquetas_ia FROM datasets WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $det['dataset_id']]);
        $ds = $stmt->fetch();
        if (!$ds || $ds['categoria_ia'] === null || $ds['categoria_ia'] === '') {
            respondJson(false, null, 'No hay categoria de IA que confirmar.');
        }

        db()->prepare('UPDATE datasets SET categoria = :cat WHERE id = :id')
            ->execute(['cat' => $ds['categoria_ia'], 'id' => $det['dataset_id']]);
        db()->prepare("UPDATE ai_detections SET estado = 'aplicada' WHERE id = :id")
            ->execute(['id' => $detectionId]);

        logActivity('ai:confirm_classify', ['dataset_id' => $det['dataset_id'], 'categoria' => $ds['categoria_ia']]);
        respondJson(true, ['categoria' => $ds['categoria_ia']], 'Clasificacion confirmada y aplicada al dataset.');
        break;

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

        try {
            $res = ai_ejecutar_anomalias($datasetId);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        logActivity('ai:anomalies', ['dataset_id' => $datasetId, 'insertadas' => $res['insertadas'], 'simulado' => ai_simulado()]);
        respondJson(true, ['anomalias' => $res['detectadas']], 'Analisis completado. ' . $res['insertadas'] . ' anomalias detectadas.');
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

        db()->prepare(
            "INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, estado)
             VALUES (:id, :tipo, :sev, :desc, 'aplicada')"
        )->execute([
            'id'    => $datasetId,
            'tipo'  => 'resumen',
            'sev'   => 'baja',
            'desc'  => mb_substr($texto, 0, 500),
        ]);

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
        $ultima = $stmt->fetch() ?: null;

        try {
            $sugerencias = ia_sugerencias($ultima);
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        foreach ($sugerencias as $s) {
            $desc = mb_substr($s['descripcion'], 0, 500);
            $prev = db()->prepare(
                "SELECT id FROM ai_detections
                 WHERE dataset_id = :id AND tipo = 'sugerencia' AND estado = 'pendiente' AND descripcion_ia = :desc
                 LIMIT 1"
            );
            $prev->execute(['id' => $datasetId, 'desc' => $desc]);
            if ($prev->fetch() !== false) {
                continue;
            }
            db()->prepare(
                'INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado)
                 VALUES (:id, :tipo, :sev, :desc, :rec, :est)'
            )->execute([
                'id'    => $datasetId,
                'tipo'  => 'sugerencia',
                'sev'   => 'media',
                'desc'  => $desc,
                'rec'   => mb_substr($s['recomendacion'], 0, 500),
                'est'   => 'pendiente',
            ]);
        }

        logActivity('ai:suggest', ['dataset_id' => $datasetId, 'sugerencias' => count($sugerencias), 'simulado' => ai_simulado()]);
        respondJson(true, ['sugerencias' => $sugerencias], 'Sugerencias generadas: ' . count($sugerencias));
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
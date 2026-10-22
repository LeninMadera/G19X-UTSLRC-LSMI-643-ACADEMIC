<?php
/**
 * API del modulo de Fuentes Externas / Integraciones.
 *
 *   GET  api/integrations.php?action=list                     (todos los roles)
 *   GET  api/integrations.php?action=get&id=N                 (todos los roles)
 *   GET  api/integrations.php?action=historial&source_id=N    (todos los roles)
 *   POST api/integrations.php?action=create                   (admin, steward)
 *   POST api/integrations.php?action=update                   (admin, steward)
 *   POST api/integrations.php?action=delete                   (admin, steward)
 *   POST api/integrations.php?action=probar                   (admin, steward; owner en las propias)
 *   POST api/integrations.php?action=sincronizar              (admin, steward; owner en las propias)
 *   POST api/integrations.php?action=toggle                   (admin, steward; owner en las propias)
 *
 * El modulo consume una API REST externa por GET (HTTP), valida la
 * URL contra riesgos SSRF (resolucion DNS incluida), limita el tamano
 * de la respuesta y NO copia los datos externos a MySQL: solo registra
 * metadatos de cada corrida (total de registros y campos del primer
 * objeto) en external_sync_runs.
 *
 * Seguridad: las acciones POST de este modulo exigen el token CSRF
 * de la sesion (ver requireCsrfIfPost() en includes/functions.php).
 */
require_once __DIR__ . '/../includes/integrations_service.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

switch ($action) {

    case 'list':
        $sources = db()->query(
            "SELECT s.*, d.nombre AS dataset, u.nombre AS creado_por,
                    (SELECT COUNT(*) FROM external_sync_runs r
                     WHERE r.source_id = s.id) AS corridas
             FROM external_sources s
             LEFT JOIN datasets d ON d.id = s.dataset_id
             LEFT JOIN users u ON u.id = s.creado_por
             ORDER BY s.id DESC"
        )->fetchAll();

        foreach ($sources as &$source) {
            $source['puede_gestionar'] = integracion_puede_gestionar((int)$source['id']);
        }
        unset($source);

        $datasets = db()->query(
            'SELECT id, nombre, owner_id FROM datasets ORDER BY nombre'
        )->fetchAll();

        $kpiTotal      = (int)db()->query('SELECT COUNT(*) FROM external_sources')->fetchColumn();
        $kpiActivas    = (int)db()->query('SELECT COUNT(*) FROM external_sources WHERE activo = 1')->fetchColumn();
        $kpiOk         = (int)db()->query("SELECT COUNT(*) FROM external_sources WHERE ultimo_estado = 'ok'")->fetchColumn();
        $kpiRegistros  = (int)db()->query(
            "SELECT COALESCE(SUM(registros_leidos), 0) FROM external_sync_runs
             WHERE tipo_ejecucion = 'sync' AND estado = 'ok'"
        )->fetchColumn();

        respondJson(true, [
            'sources'           => $sources,
            'datasets'          => $datasets,
            'limite_bytes'      => integracion_limite_bytes(),
            'permitir_localhost' => integracion_permitir_localhost(),
            'resumen'           => [
                'total'            => $kpiTotal,
                'activas'          => $kpiActivas,
                'ultimas_ok'       => $kpiOk,
                'registros_leidos' => $kpiRegistros,
            ],
        ]);
        break;

    case 'get':
        $id = (int) query('id', 0);
        $fuente = integracion_fuente($id);
        if ($fuente === null) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }
        $fuente['puede_gestionar'] = integracion_puede_gestionar($id);
        respondJson(true, $fuente);
        break;

    case 'historial':
        $sourceId = (int) query('source_id', 0);
        if ($sourceId <= 0) {
            respondJson(false, null, 'Fuente invalida.');
        }
        $stmt = db()->prepare('SELECT id FROM external_sources WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $sourceId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }
        $stmt = db()->prepare(
            'SELECT r.*, u.nombre AS ejecutado_por
             FROM external_sync_runs r
             LEFT JOIN users u ON u.id = r.ejecutado_por
             WHERE r.source_id = :source_id
             ORDER BY r.fecha DESC, r.id DESC LIMIT 100'
        );
        $stmt->execute(['source_id' => $sourceId]);
        $runs = $stmt->fetchAll();
        foreach ($runs as &$run) {
            $decodificado = json_decode((string)($run['metadatos'] ?? ''), true);
            $run['metadatos'] = is_array($decodificado) ? $decodificado : null;
        }
        unset($run);
        respondJson(true, ['runs' => $runs]);
        break;

    case 'create':
        requireRole(['admin', 'steward']);

        $data         = jsonInput();
        $nombre       = trim((string)($data['nombre'] ?? ''));
        $descripcion  = trim((string)($data['descripcion'] ?? ''));
        $url          = trim((string)($data['url'] ?? ''));
        $datasetId    = isset($data['dataset_id']) && $data['dataset_id'] !== ''
            ? (int)$data['dataset_id'] : 0;
        $timeout      = integracion_timeout_valido($data['timeout'] ?? 15);
        $activo       = isset($data['activo']) ? (int)$data['activo'] : 1;

        if ($nombre === '' || mb_strlen($nombre) > 150) {
            respondJson(false, null, 'Nombre obligatorio (max 150 caracteres).');
        }
        if (mb_strlen($descripcion) > 5000) {
            respondJson(false, null, 'La descripcion es demasiado larga.');
        }
        if ($url === '' || mb_strlen($url) > 500) {
            respondJson(false, null, 'URL obligatoria (max 500 caracteres).');
        }
        $errorUrl = integracion_validar_url($url);
        if ($errorUrl !== '') {
            respondJson(false, null, $errorUrl);
        }
        if ($timeout === null) {
            respondJson(false, null, 'Timeout invalido (1-60 segundos).');
        }
        if (!in_array($activo, [0, 1], true)) {
            respondJson(false, null, 'Estado activo invalido.');
        }
        $datasetId = integracion_dataset_existente($datasetId);

        try {
            $stmt = db()->prepare(
                'INSERT INTO external_sources
                    (nombre, descripcion, url, tipo, dataset_id, timeout_seg, activo, creado_por)
                 VALUES (:nombre, :descripcion, :url, :tipo, :dataset_id, :timeout, :activo, :creado_por)'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'url'         => $url,
                'tipo'        => 'api',
                'dataset_id'  => $datasetId,
                'timeout'     => $timeout,
                'activo'      => $activo,
                'creado_por'  => currentUser()['id'],
            ]);
            $sourceId = (int)db()->lastInsertId();
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo crear la fuente externa.');
        }

        logActivity('integracion:crear', ['source_id' => $sourceId]);
        respondJson(true, ['id' => $sourceId], 'Fuente externa creada.');
        break;

    case 'update':
        requireRole(['admin', 'steward']);

        $data         = jsonInput();
        $id           = (int)($data['id'] ?? 0);
        $nombre       = trim((string)($data['nombre'] ?? ''));
        $descripcion  = trim((string)($data['descripcion'] ?? ''));
        $url          = trim((string)($data['url'] ?? ''));
        $datasetId    = array_key_exists('dataset_id', $data) && $data['dataset_id'] !== ''
            ? (int)$data['dataset_id'] : 0;
        $timeout      = integracion_timeout_valido($data['timeout'] ?? 15);
        $activo       = isset($data['activo']) ? (int)$data['activo'] : null;

        if ($id <= 0) {
            respondJson(false, null, 'ID de fuente invalido.');
        }
        if ($nombre === '' || mb_strlen($nombre) > 150) {
            respondJson(false, null, 'Nombre obligatorio (max 150 caracteres).');
        }
        if (mb_strlen($descripcion) > 5000) {
            respondJson(false, null, 'La descripcion es demasiado larga.');
        }
        if ($url === '' || mb_strlen($url) > 500) {
            respondJson(false, null, 'URL obligatoria (max 500 caracteres).');
        }
        $errorUrl = integracion_validar_url($url);
        if ($errorUrl !== '') {
            respondJson(false, null, $errorUrl);
        }
        if ($timeout === null) {
            respondJson(false, null, 'Timeout invalido (1-60 segundos).');
        }
        if ($activo === null || !in_array($activo, [0, 1], true)) {
            respondJson(false, null, 'Estado activo invalido.');
        }
        $stmt = db()->prepare('SELECT id FROM external_sources WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }
        $datasetId = integracion_dataset_existente($datasetId);

        try {
            db()->prepare(
                'UPDATE external_sources
                 SET nombre = :nombre, descripcion = :descripcion, url = :url,
                     dataset_id = :dataset_id, timeout_seg = :timeout, activo = :activo
                 WHERE id = :id'
            )->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'url'         => $url,
                'dataset_id'  => $datasetId,
                'timeout'     => $timeout,
                'activo'      => $activo,
                'id'          => $id,
            ]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo actualizar la fuente externa.');
        }

        logActivity('integracion:editar', ['source_id' => $id]);
        respondJson(true, null, 'Fuente externa actualizada.');
        break;

    case 'delete':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de fuente invalido.');
        }
        $stmt = db()->prepare('DELETE FROM external_sources WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }

        logActivity('integracion:eliminar', ['source_id' => $id]);
        respondJson(true, null, 'Fuente externa eliminada.');
        break;

    case 'probar':
    case 'sincronizar':
        requireRole(['admin', 'steward', 'owner']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de fuente invalido.');
        }
        $fuente = integracion_fuente($id);
        if ($fuente === null) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }
        if (!integracion_puede_gestionar($id)) {
            respondJson(false, null, 'No tienes permisos sobre esta fuente.', 403);
        }

        try {
            $res = integracion_ejecutar($id, $action === 'probar' ? 'prueba' : 'sync');
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }
        respondJson(true, $res, $res['mensaje']);
        break;

    case 'toggle':
        requireRole(['admin', 'steward', 'owner']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de fuente invalido.');
        }
        $fuente = integracion_fuente($id);
        if ($fuente === null) {
            respondJson(false, null, 'Fuente externa no encontrada.', 404);
        }
        if (!integracion_puede_gestionar($id)) {
            respondJson(false, null, 'No tienes permisos sobre esta fuente.', 403);
        }

        $nuevo = (int)$fuente['activo'] === 1 ? 0 : 1;
        db()->prepare('UPDATE external_sources SET activo = :activo WHERE id = :id')
            ->execute(['activo' => $nuevo, 'id' => $id]);

        if ($nuevo === 1) {
            logActivity('integracion:activar', ['source_id' => $id]);
            respondJson(true, ['id' => $id, 'activo' => $nuevo], 'Fuente externa activada.');
        }
        logActivity('integracion:desactivar', ['source_id' => $id]);
        respondJson(true, ['id' => $id, 'activo' => $nuevo], 'Fuente externa desactivada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}

/**
 * Valida que el dataset vinculado exista. Devuelve el id o 0/null.
 */
function integracion_dataset_existente(int $datasetId): ?int
{
    if ($datasetId <= 0) {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $datasetId]);
    if ($stmt->fetch() === false) {
        respondJson(false, null, 'Dataset vinculado no encontrado.');
    }
    return $datasetId;
}
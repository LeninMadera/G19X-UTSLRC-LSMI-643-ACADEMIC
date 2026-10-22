<?php
/**
 * API del modulo de Monitoreo Continuo de Calidad.
 *
 *   GET  api/monitoring.php?action=list               (todos los roles)
 *   GET  api/monitoring.php?action=runs&config_id=N   (todos los roles)
 *   POST api/monitoring.php?action=create   (admin, steward; owner en sus datasets)
 *   POST api/monitoring.php?action=update   (admin, steward; owner en sus datasets)
 *   POST api/monitoring.php?action=toggle   (admin, steward; owner en sus datasets)
 *   POST api/monitoring.php?action=run      (admin, steward; owner en sus datasets)
 *   POST api/monitoring.php?action=delete   (admin, steward; owner en sus datasets)
 *
 * La ejecucion automatica por vencimiento la hace el programador
 * del CLI (cli/monitor.php). Este endpoint cubre la ejecucion
 * manual ("Monitorear ahora") y la administracion de las
 * configuraciones.
 */
require_once __DIR__ . '/../includes/monitoring_service.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

switch ($action) {

    case 'list':
        $configs = db()->query(
            "SELECT mc.*, d.nombre AS dataset, u.nombre AS creado_por,
                    (SELECT COUNT(*) FROM monitoring_runs mr
                     WHERE mr.config_id = mc.id) AS ejecuciones
             FROM monitoring_configs mc
             INNER JOIN datasets d ON d.id = mc.dataset_id
             LEFT JOIN users u ON u.id = mc.creado_por
             ORDER BY mc.id DESC"
        )->fetchAll();

        $datasets = db()->query(
            'SELECT id, nombre, owner_id FROM datasets ORDER BY nombre'
        )->fetchAll();

        $kpiConfigs    = (int)db()->query('SELECT COUNT(*) FROM monitoring_configs')->fetchColumn();
        $kpiActivas    = (int)db()->query('SELECT COUNT(*) FROM monitoring_configs WHERE activo = 1')->fetchColumn();
        $kpiEjecuciones = (int)db()->query('SELECT COUNT(*) FROM monitoring_runs')->fetchColumn();
        $kpiOk         = (int)db()->query("SELECT COUNT(*) FROM monitoring_runs WHERE estado = 'ok'")->fetchColumn();
        $kpiFallos     = (int)db()->query("SELECT COUNT(*) FROM monitoring_runs WHERE estado = 'fallo'")->fetchColumn();
        $ultima        = db()->query('SELECT MAX(fecha) FROM monitoring_runs')->fetchColumn();

        respondJson(true, [
            'configs'     => $configs,
            'datasets'    => $datasets,
            'frecuencias' => MONITOR_FRECUENCIAS,
            'rango_frecuencia' => [MONITOR_FRECUENCIA_MIN, MONITOR_FRECUENCIA_MAX],
            'resumen'     => [
                'configs'         => $kpiConfigs,
                'activas'         => $kpiActivas,
                'ejecuciones'     => $kpiEjecuciones,
                'ok'              => $kpiOk,
                'fallos'          => $kpiFallos,
                'tasa_exito'      => $kpiOk + $kpiFallos > 0
                    ? round($kpiOk * 100 / ($kpiOk + $kpiFallos), 1) : null,
                'ultima_ejecucion' => $ultima ?: null,
            ],
        ]);
        break;

    case 'runs':
        $configId = (int) query('config_id', 0);
        $params = [];
        $where = '';
        if ($configId > 0) {
            $stmt = db()->prepare('SELECT id FROM monitoring_configs WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $configId]);
            if ($stmt->fetch() === false) {
                respondJson(false, null, 'Configuracion de monitoreo no encontrada.', 404);
            }
            $where = 'WHERE mr.config_id = :config_id';
            $params['config_id'] = $configId;
        }
        $stmt = db()->prepare(
            "SELECT mr.id, mr.config_id, mr.dataset_id, mr.causa, mr.estado, mr.score,
                    mr.anomalias_nuevas, mr.resumen, mr.fecha, d.nombre AS dataset,
                    mc.frecuencia
             FROM monitoring_runs mr
             INNER JOIN datasets d ON d.id = mr.dataset_id
             INNER JOIN monitoring_configs mc ON mc.id = mr.config_id
             {$where}
             ORDER BY mr.fecha DESC, mr.id DESC LIMIT 100"
        );
        $stmt->execute($params);
        respondJson(true, ['runs' => $stmt->fetchAll()]);
        break;

    case 'create':
        requireRole(['admin', 'steward', 'owner']);

        $data      = jsonInput();
        $datasetId = (int)($data['dataset_id'] ?? 0);
        $frecuencia = monitor_frecuencia_valida($data['frecuencia'] ?? null);

        if ($datasetId <= 0) {
            respondJson(false, null, 'Dataset invalido.');
        }
        if ($frecuencia === null) {
            respondJson(false, null, 'La frecuencia debe ser un entero entre 1 y 43200 minutos.');
        }
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datasetId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }
        if (!monitor_puede_gestionar($datasetId)) {
            respondJson(false, null, 'No tienes permisos para configurar el monitoreo de este dataset.', 403);
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO monitoring_configs (dataset_id, frecuencia, activo, proxima_ejecucion, creado_por)
                 VALUES (:dataset_id, :frecuencia, 1, NOW() + INTERVAL :f MINUTE, :creado_por)'
            );
            $stmt->execute([
                'dataset_id' => $datasetId,
                'frecuencia' => $frecuencia,
                'f'          => $frecuencia,
                'creado_por' => currentUser()['id'],
            ]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                respondJson(false, null, 'El dataset ya tiene una configuracion de monitoreo.');
            }
            respondJson(false, null, 'No se pudo crear la configuracion de monitoreo.');
        }
        $configId = (int)db()->lastInsertId();

        logActivity('monitoreo:configurar', [
            'config_id'  => $configId,
            'dataset_id' => $datasetId,
            'frecuencia' => $frecuencia,
        ]);
        $cfg = monitor_config($configId);
        respondJson(true, ['id' => $configId, 'proxima_ejecucion' => $cfg ? $cfg['proxima_ejecucion'] : null], 'Configuracion de monitoreo creada.');
        break;

    case 'update':
        requireRole(['admin', 'steward', 'owner']);

        $data       = jsonInput();
        $configId   = (int)($data['id'] ?? 0);
        $frecuencia = monitor_frecuencia_valida($data['frecuencia'] ?? null);
        $activo     = isset($data['activo']) ? (int)$data['activo'] : null;

        if ($configId <= 0) {
            respondJson(false, null, 'ID de configuracion invalido.');
        }
        if ($frecuencia === null) {
            respondJson(false, null, 'La frecuencia debe ser un entero entre 1 y 43200 minutos.');
        }
        if ($activo === null || !in_array($activo, [0, 1], true)) {
            respondJson(false, null, 'Estado activo invalido.');
        }
        $config = monitor_config($configId);
        if ($config === null) {
            respondJson(false, null, 'Configuracion de monitoreo no encontrada.', 404);
        }
        if (!monitor_puede_gestionar((int)$config['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        $antes = (int)$config['activo'];
        try {
            db()->prepare(
                'UPDATE monitoring_configs SET frecuencia = :frecuencia, activo = :activo WHERE id = :id'
            )->execute(['frecuencia' => $frecuencia, 'activo' => $activo, 'id' => $configId]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo actualizar la configuracion de monitoreo.');
        }
        monitor_programar($configId, $frecuencia, $activo === 1);

        if ($antes === 0 && $activo === 1) {
            logActivity('monitoreo:activar', ['config_id' => $configId, 'dataset_id' => $config['dataset_id']]);
            respondJson(true, null, 'Configuracion de monitoreo activada.');
        } elseif ($antes === 1 && $activo === 0) {
            logActivity('monitoreo:desactivar', ['config_id' => $configId, 'dataset_id' => $config['dataset_id']]);
            respondJson(true, null, 'Configuracion de monitoreo desactivada.');
        }
        logActivity('monitoreo:configurar', ['config_id' => $configId, 'dataset_id' => $config['dataset_id'], 'frecuencia' => $frecuencia]);
        respondJson(true, null, 'Configuracion de monitoreo actualizada.');
        break;

    case 'toggle':
        requireRole(['admin', 'steward', 'owner']);

        $data     = jsonInput();
        $configId = (int)($data['id'] ?? 0);
        if ($configId <= 0) {
            respondJson(false, null, 'ID de configuracion invalido.');
        }
        $config = monitor_config($configId);
        if ($config === null) {
            respondJson(false, null, 'Configuracion de monitoreo no encontrada.', 404);
        }
        if (!monitor_puede_gestionar((int)$config['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        $nuevo = (int)$config['activo'] === 1 ? 0 : 1;
        db()->prepare('UPDATE monitoring_configs SET activo = :activo WHERE id = :id')
            ->execute(['activo' => $nuevo, 'id' => $configId]);
        monitor_programar($configId, (int)$config['frecuencia'], $nuevo === 1);

        if ($nuevo === 1) {
            logActivity('monitoreo:activar', ['config_id' => $configId, 'dataset_id' => $config['dataset_id']]);
            respondJson(true, ['activo' => $nuevo], 'Configuracion de monitoreo activada.');
        }
        logActivity('monitoreo:desactivar', ['config_id' => $configId, 'dataset_id' => $config['dataset_id']]);
        respondJson(true, ['activo' => $nuevo], 'Configuracion de monitoreo desactivada.');
        break;

    case 'run':
        requireRole(['admin', 'steward', 'owner']);

        $data     = jsonInput();
        $configId = (int)($data['id'] ?? 0);
        if ($configId <= 0) {
            respondJson(false, null, 'ID de configuracion invalido.');
        }
        $config = monitor_config($configId);
        if ($config === null) {
            respondJson(false, null, 'Configuracion de monitoreo no encontrada.', 404);
        }
        if (!monitor_puede_gestionar((int)$config['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        try {
            $res = monitor_ejecutar($configId, 'manual');
        } catch (RuntimeException $e) {
            respondJson(false, null, $e->getMessage());
        }

        if ($res['omitido']) {
            respondJson(true, $res, 'Omitida: la configuracion ya estaba en ejecucion.');
        }
        respondJson(true, $res, 'Monitoreo ejecutado.');
        break;

    case 'delete':
        requireRole(['admin', 'steward', 'owner']);

        $data     = jsonInput();
        $configId = (int)($data['id'] ?? 0);
        if ($configId <= 0) {
            respondJson(false, null, 'ID de configuracion invalido.');
        }
        $config = monitor_config($configId);
        if ($config === null) {
            respondJson(false, null, 'Configuracion de monitoreo no encontrada.', 404);
        }
        if (!monitor_puede_gestionar((int)$config['dataset_id'])) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        db()->prepare('DELETE FROM monitoring_configs WHERE id = :id')->execute(['id' => $configId]);

        logActivity('monitoreo:configurar', [
            'config_id'  => $configId,
            'dataset_id' => $config['dataset_id'],
            'accion'     => 'eliminar',
        ]);
        respondJson(true, null, 'Configuracion de monitoreo eliminada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
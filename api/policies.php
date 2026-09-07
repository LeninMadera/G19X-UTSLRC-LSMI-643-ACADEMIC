<?php
/**
 * API de politicas de gobierno de datos.
 *
 *   GET  api/policies.php?action=list      (todos los roles)
 *   GET  api/policies.php?action=get&id=N  (todos los roles)
 *   POST api/policies.php?action=create    (admin, steward)
 *   POST api/policies.php?action=update    (admin, steward)
 *   POST api/policies.php?action=delete    (admin, steward)
 *   POST api/policies.php?action=assign    (admin, steward)
 *   POST api/policies.php?action=unassign  (admin, steward)
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

const TIPOS = ['naming', 'retencion', 'acceso', 'clasificacion'];

switch ($action) {

    case 'list':
        $policies = db()->query(
            "SELECT p.id, p.titulo, p.descripcion, p.tipo, p.estado, p.fecha_creacion,
                    u.nombre AS creado_por,
                    (SELECT COUNT(*) FROM policy_datasets pd WHERE pd.policy_id = p.id) AS datasets_asignados,
                    COALESCE((SELECT JSON_ARRAYAGG(pd.dataset_id) FROM policy_datasets pd
                              WHERE pd.policy_id = p.id), JSON_ARRAY()) AS dataset_ids
             FROM policies p
             LEFT JOIN users u ON u.id = p.creado_por
             ORDER BY p.fecha_creacion DESC"
        )->fetchAll();

        // Convertir el JSON de ids en array PHP
        foreach ($policies as &$p) {
            $p['dataset_ids'] = json_decode($p['dataset_ids'], true);
        }

        $datasets = db()->query(
            'SELECT id, nombre FROM datasets ORDER BY nombre'
        )->fetchAll();

        respondJson(true, ['policies' => $policies, 'datasets' => $datasets, 'tipos' => TIPOS]);
        break;

    case 'get':
        $id  = (int) query('id', 0);
        $stmt = db()->prepare(
            'SELECT p.*, u.nombre AS creado_por
             FROM policies p
             LEFT JOIN users u ON u.id = p.creado_por
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $policy = $stmt->fetch();
        if (!$policy) {
            respondJson(false, null, 'Politica no encontrada.', 404);
        }
        respondJson(true, $policy);
        break;

    case 'create':
        requireRole(['admin', 'steward']);

        $data        = jsonInput();
        $titulo      = trim((string)($data['titulo'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $tipo        = trim((string)($data['tipo'] ?? ''));
        $estado      = trim((string)($data['estado'] ?? 'activa'));

        if ($titulo === '' || mb_strlen($titulo) > 120) {
            respondJson(false, null, 'Titulo obligatorio (max 120 caracteres).');
        }
        if (!in_array($tipo, TIPOS, true)) {
            respondJson(false, null, 'Tipo de politica invalido.');
        }
        if (!in_array($estado, ['activa', 'inactiva'], true)) {
            respondJson(false, null, 'Estado invalido.');
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO policies (titulo, descripcion, tipo, estado, creado_por)
                 VALUES (:titulo, :descripcion, :tipo, :estado, :creado_por)'
            );
            $stmt->execute([
                'titulo'      => $titulo,
                'descripcion' => $descripcion,
                'tipo'        => $tipo,
                'estado'      => $estado,
                'creado_por'  => currentUser()['id'],
            ]);
            $policyId = (int) db()->lastInsertId();
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo crear la politica.');
        }

        logActivity('politicas:create', ['policy_id' => $policyId]);
        respondJson(true, ['id' => $policyId], 'Politica creada.');
        break;

    case 'update':
        requireRole(['admin', 'steward']);

        $data        = jsonInput();
        $id          = (int)($data['id'] ?? 0);
        $titulo      = trim((string)($data['titulo'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $tipo        = trim((string)($data['tipo'] ?? ''));
        $estado      = trim((string)($data['estado'] ?? 'activa'));

        if ($id <= 0) {
            respondJson(false, null, 'ID de politica invalido.');
        }
        if ($titulo === '' || mb_strlen($titulo) > 120) {
            respondJson(false, null, 'Titulo obligatorio (max 120 caracteres).');
        }
        if (!in_array($tipo, TIPOS, true)) {
            respondJson(false, null, 'Tipo de politica invalido.');
        }
        if (!in_array($estado, ['activa', 'inactiva'], true)) {
            respondJson(false, null, 'Estado invalido.');
        }

        $stmt = db()->prepare('SELECT id FROM policies WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Politica no encontrada.', 404);
        }

        try {
            $stmt = db()->prepare(
                'UPDATE policies
                 SET titulo = :titulo, descripcion = :descripcion, tipo = :tipo, estado = :estado
                 WHERE id = :id'
            );
            $stmt->execute([
                'titulo'      => $titulo,
                'descripcion' => $descripcion,
                'tipo'        => $tipo,
                'estado'      => $estado,
                'id'          => $id,
            ]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo actualizar la politica.');
        }

        logActivity('politicas:update', ['policy_id' => $id]);
        respondJson(true, null, 'Politica actualizada.');
        break;

    case 'delete':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de politica invalido.');
        }

        try {
            $stmt = db()->prepare('DELETE FROM policies WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo eliminar la politica.');
        }
        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Politica no encontrada.', 404);
        }

        logActivity('politicas:delete', ['policy_id' => $id]);
        respondJson(true, null, 'Politica eliminada.');
        break;

    case 'assign':
    case 'unassign':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $policyId = (int)($data['policy_id'] ?? 0);
        $datasetId = (int)($data['dataset_id'] ?? 0);

        if ($policyId <= 0 || $datasetId <= 0) {
            respondJson(false, null, 'Debes indicar politica y dataset.');
        }

        // Verificar que ambos existan antes de tocar la tabla puente
        $stmt = db()->prepare('SELECT id FROM policies WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $policyId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Politica no encontrada.', 404);
        }
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datasetId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        try {
            if ($action === 'assign') {
                $stmt = db()->prepare('SELECT id FROM policy_datasets WHERE policy_id = :p AND dataset_id = :d LIMIT 1');
                $stmt->execute(['p' => $policyId, 'd' => $datasetId]);
                if ($stmt->fetch() === false) {
                    $stmt = db()->prepare('INSERT INTO policy_datasets (policy_id, dataset_id) VALUES (:p, :d)');
                    $stmt->execute(['p' => $policyId, 'd' => $datasetId]);
                }
            } else {
                $stmt = db()->prepare('DELETE FROM policy_datasets WHERE policy_id = :p AND dataset_id = :d');
                $stmt->execute(['p' => $policyId, 'd' => $datasetId]);
            }
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo modificar la asignacion.');
        }

        logActivity('politicas:' . $action, ['policy_id' => $policyId, 'dataset_id' => $datasetId]);
        respondJson(true, null, $action === 'assign' ? 'Politica asignada al dataset.' : 'Politica desasignada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
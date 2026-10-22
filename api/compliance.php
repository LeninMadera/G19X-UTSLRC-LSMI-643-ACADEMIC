<?php
/**
 * API del modulo de Cumplimiento Normativo.
 *
 *   GET  api/compliance.php?action=list                 (todos los roles)
 *   GET  api/compliance.php?action=get&id=N             (todos los roles)
 *   GET  api/compliance.php?action=dataset_status&dataset_id=N  (todos los roles)
 *   POST api/compliance.php?action=create               (admin, steward)
 *   POST api/compliance.php?action=update               (admin, steward)
 *   POST api/compliance.php?action=delete               (admin, steward)
 *   POST api/compliance.php?action=evaluate             (admin, steward; owner en sus datasets)
 *
 * Los controles son un registro declarativo interno del equipo;
 * el modulo NO emite dictamenes legales.
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

const SEVERIDADES = ['baja', 'media', 'alta', 'critica'];
const ESTADOS_CONTROL = ['activo', 'inactivo'];
const ESTADOS_EVALUACION = ['cumple', 'no_cumple', 'pendiente', 'no_aplica'];

/**
 * Indica si el dataset pertenece al usuario en sesion.
 * Misma regla de propiedad que aplican los modulos de datasets y linaje.
 */
function esPropietario(int $datasetId): bool
{
    $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
    $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
    return $stmt->fetch() !== false;
}

/**
 * Quien puede registrar evaluaciones: admin y steward en todos los
 * datasets; owner unicamente en los suyos; analyst no.
 */
function puedeEvaluar(int $datasetId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    if ($rol === 'owner') {
        return esPropietario($datasetId);
    }
    return false;
}

/** Porcentaje de cumplimiento sobre el total de evaluaciones decididas (cumple + no_cumple). */
function calcCumplimiento(array $porEstado): ?float
{
    $cumple  = (int)($porEstado['cumple'] ?? 0);
    $decide  = $cumple + (int)($porEstado['no_cumple'] ?? 0);
    if ($decide === 0) {
        return null;
    }
    return round($cumple * 100 / $decide, 1);
}

switch ($action) {

    case 'list':
        $controles = db()->query(
            "SELECT c.id, c.nombre, c.descripcion, c.referencia, c.severidad, c.estado,
                    c.policy_id, p.titulo AS policy_titulo, u.nombre AS creado_por,
                    c.fecha_creacion,
                    (SELECT COUNT(*) FROM compliance_evaluations ce
                     WHERE ce.control_id = c.id) AS evaluaciones
             FROM compliance_controls c
             LEFT JOIN policies p ON p.id = c.policy_id
             LEFT JOIN users u ON u.id = c.creado_por
             ORDER BY c.fecha_creacion DESC, c.id DESC"
        )->fetchAll();

        $datasets = db()->query(
            'SELECT id, nombre FROM datasets ORDER BY nombre'
        )->fetchAll();

        $policies = db()->query(
            "SELECT id, titulo FROM policies WHERE estado = 'activa' ORDER BY titulo"
        )->fetchAll();

        $kpiControles = (int) db()->query('SELECT COUNT(*) FROM compliance_controls')->fetchColumn();
        $kpiActivos   = (int) db()->query("SELECT COUNT(*) FROM compliance_controls WHERE estado = 'activo'")->fetchColumn();
        $kpiCriticas  = (int) db()->query(
            "SELECT COUNT(*) FROM compliance_controls WHERE severidad IN ('critica', 'alta')"
        )->fetchColumn();
        $kpiEvaluaciones = (int) db()->query('SELECT COUNT(*) FROM compliance_evaluations')->fetchColumn();

        $porEstado = [];
        foreach (db()->query(
            'SELECT estado, COUNT(*) AS total FROM compliance_evaluations GROUP BY estado'
        )->fetchAll() as $row) {
            $porEstado[$row['estado']] = (int) $row['total'];
        }

        respondJson(true, [
            'controles'  => $controles,
            'datasets'   => $datasets,
            'policies'   => $policies,
            'resumen'    => [
                'total_controles'   => $kpiControles,
                'activos'           => $kpiActivos,
                'criticas'          => $kpiCriticas,
                'evaluaciones'      => $kpiEvaluaciones,
                'cumplimiento_pct'  => calcCumplimiento($porEstado),
                'por_estado'        => $porEstado,
            ],
        ]);
        break;

    case 'get':
        $id = (int) query('id', 0);
        $stmt = db()->prepare(
            'SELECT c.*, u.nombre AS creado_por, p.titulo AS policy_titulo
             FROM compliance_controls c
             LEFT JOIN users u ON u.id = c.creado_por
             LEFT JOIN policies p ON p.id = c.policy_id
             WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $control = $stmt->fetch();
        if (!$control) {
            respondJson(false, null, 'Control no encontrado.', 404);
        }
        respondJson(true, $control);
        break;

    case 'dataset_status':
        $id = (int) query('dataset_id', 0);
        if ($id <= 0) {
            respondJson(false, null, 'Dataset invalido.');
        }
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        $stmt = db()->prepare(
            "SELECT ce.control_id, ce.estado, ce.observaciones, ce.evidencia, ce.fecha,
                    u.nombre AS evaluador, c.nombre AS control, c.referencia, c.severidad,
                    c.policy_id, p.titulo AS policy_titulo
             FROM compliance_evaluations ce
             INNER JOIN compliance_controls c ON c.id = ce.control_id
             LEFT JOIN users u ON u.id = ce.evaluador_id
             LEFT JOIN policies p ON p.id = c.policy_id
             WHERE ce.dataset_id = :id
             ORDER BY c.nombre ASC"
        );
        $stmt->execute(['id' => $id]);
        $evaluaciones = $stmt->fetchAll();

        $stmt = db()->prepare(
            "SELECT c.id, c.nombre, c.referencia, c.severidad
             FROM compliance_controls c
             WHERE c.estado = 'activo'
               AND NOT EXISTS (
                 SELECT 1 FROM compliance_evaluations ce
                 WHERE ce.control_id = c.id AND ce.dataset_id = :id
               )
             ORDER BY c.nombre ASC"
        );
        $stmt->execute(['id' => $id]);
        $disponibles = $stmt->fetchAll();

        $porEstado = ['cumple' => 0, 'no_cumple' => 0, 'pendiente' => 0, 'no_aplica' => 0];
        foreach ($evaluaciones as $e) {
            $porEstado[$e['estado']]++;
        }
        $aplicables = $porEstado['cumple'] + $porEstado['no_cumple'] + $porEstado['pendiente'];

        respondJson(true, [
            'evaluaciones' => $evaluaciones,
            'controles_disponibles' => $disponibles,
            'puede_evaluar' => puedeEvaluar($id),
            'indicadores' => [
                'aplicables'      => $aplicables,
                'evaluados'       => $porEstado['cumple'] + $porEstado['no_cumple'],
                'pendientes'      => $porEstado['pendiente'],
                'no_aplica'       => $porEstado['no_aplica'],
                'cumplimiento_pct' => calcCumplimiento($porEstado),
            ],
        ]);
        break;

    case 'create':
    case 'update':
        requireRole(['admin', 'steward']);

        $data         = jsonInput();
        $id           = $action === 'update' ? (int)($data['id'] ?? 0) : 0;
        $nombre       = trim((string)($data['nombre'] ?? ''));
        $descripcion  = trim((string)($data['descripcion'] ?? ''));
        $referencia   = trim((string)($data['referencia'] ?? ''));
        $severidad    = trim((string)($data['severidad'] ?? 'media'));
        $estado       = trim((string)($data['estado'] ?? 'activo'));
        $policyId     = isset($data['policy_id']) && $data['policy_id'] !== ''
            ? (int) $data['policy_id'] : 0;

        if ($nombre === '' || mb_strlen($nombre) > 150) {
            respondJson(false, null, 'Nombre obligatorio (max 150 caracteres).');
        }
        if (mb_strlen($descripcion) > 5000) {
            respondJson(false, null, 'La descripcion es demasiado larga.');
        }
        if (mb_strlen($referencia) > 150) {
            respondJson(false, null, 'La referencia es demasiado larga (max 150 caracteres).');
        }
        if (!in_array($severidad, SEVERIDADES, true)) {
            respondJson(false, null, 'Severidad invalida.');
        }
        if (!in_array($estado, ESTADOS_CONTROL, true)) {
            respondJson(false, null, 'Estado invalido.');
        }
        if ($policyId > 0) {
            $stmt = db()->prepare('SELECT id FROM policies WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $policyId]);
            if ($stmt->fetch() === false) {
                respondJson(false, null, 'Politica vinculada no encontrada.');
            }
        } elseif ($action === 'update') {
            $policyId = null;
        }

        if ($action === 'update') {
            if ($id <= 0) {
                respondJson(false, null, 'ID de control invalido.');
            }
            $stmt = db()->prepare('SELECT id FROM compliance_controls WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            if ($stmt->fetch() === false) {
                respondJson(false, null, 'Control no encontrado.', 404);
            }
            try {
                $stmt = db()->prepare(
                    'UPDATE compliance_controls
                     SET nombre = :nombre, descripcion = :descripcion,
                         referencia = :referencia, severidad = :severidad,
                         estado = :estado, policy_id = :policy_id
                     WHERE id = :id'
                );
                $stmt->execute([
                    'nombre'      => $nombre,
                    'descripcion' => $descripcion,
                    'referencia'  => $referencia !== '' ? $referencia : null,
                    'severidad'   => $severidad,
                    'estado'      => $estado,
                    'policy_id'   => $policyId > 0 ? $policyId : null,
                    'id'          => $id,
                ]);
            } catch (PDOException $e) {
                respondJson(false, null, 'No se pudo actualizar el control.');
            }
            logActivity('cumplimiento:control_editar', ['control_id' => $id]);
            respondJson(true, null, 'Control actualizado.');
        } else {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO compliance_controls
                        (nombre, descripcion, referencia, severidad, estado, policy_id, creado_por)
                     VALUES (:nombre, :descripcion, :referencia, :severidad, :estado, :policy_id, :creado_por)'
                );
                $stmt->execute([
                    'nombre'      => $nombre,
                    'descripcion' => $descripcion,
                    'referencia'  => $referencia !== '' ? $referencia : null,
                    'severidad'   => $severidad,
                    'estado'      => $estado,
                    'policy_id'   => $policyId > 0 ? $policyId : null,
                    'creado_por'  => currentUser()['id'],
                ]);
                $controlId = (int) db()->lastInsertId();
            } catch (PDOException $e) {
                respondJson(false, null, 'No se pudo crear el control.');
            }
            logActivity('cumplimiento:control_crear', ['control_id' => $controlId]);
            respondJson(true, ['id' => $controlId], 'Control creado.');
        }
        break;

    case 'delete':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de control invalido.');
        }

        try {
            $stmt = db()->prepare('DELETE FROM compliance_controls WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo eliminar el control.');
        }
        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Control no encontrado.', 404);
        }

        logActivity('cumplimiento:control_eliminar', ['control_id' => $id]);
        respondJson(true, null, 'Control eliminado.');
        break;

    case 'evaluate':
        requireAuth();

        $data         = jsonInput();
        $controlId    = (int)($data['control_id'] ?? 0);
        $datasetId    = (int)($data['dataset_id'] ?? 0);
        $estado       = trim((string)($data['estado'] ?? ''));
        $observaciones = trim((string)($data['observaciones'] ?? ''));
        $evidencia    = trim((string)($data['evidencia'] ?? ''));

        if ($controlId <= 0 || $datasetId <= 0) {
            respondJson(false, null, 'Debes indicar control y dataset.');
        }
        if (!in_array($estado, ESTADOS_EVALUACION, true)) {
            respondJson(false, null, 'Estado de evaluacion invalido.');
        }
        if (mb_strlen($observaciones) > 5000) {
            respondJson(false, null, 'Las observaciones son demasiado largas.');
        }
        if (mb_strlen($evidencia) > 255) {
            respondJson(false, null, 'La evidencia es demasiado larga (max 255 caracteres).');
        }
        if (!puedeEvaluar($datasetId)) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }

        $stmt = db()->prepare(
            "SELECT id FROM compliance_controls WHERE id = :id AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute(['id' => $controlId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Control no encontrado o inactivo.', 404);
        }
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datasetId]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO compliance_evaluations
                    (control_id, dataset_id, estado, observaciones, evidencia, evaluador_id)
                 VALUES (:control_id, :dataset_id, :estado, :observaciones, :evidencia, :evaluador_id)
                 ON DUPLICATE KEY UPDATE
                     estado = VALUES(estado),
                     observaciones = VALUES(observaciones),
                     evidencia = VALUES(evidencia),
                     evaluador_id = VALUES(evaluador_id)'
            );
            $stmt->execute([
                'control_id'   => $controlId,
                'dataset_id'   => $datasetId,
                'estado'       => $estado,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
                'evidencia'    => $evidencia !== '' ? $evidencia : null,
                'evaluador_id' => currentUser()['id'],
            ]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo registrar la evaluacion.');
        }

        logActivity('cumplimiento:evaluar', [
            'control_id' => $controlId,
            'dataset_id' => $datasetId,
            'estado'     => $estado,
        ]);
        respondJson(true, null, 'Evaluacion registrada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
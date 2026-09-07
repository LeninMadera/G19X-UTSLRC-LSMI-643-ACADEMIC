<?php
/**
 * API del catalogo de datasets.
 *
 *   GET  api/datasets.php?action=list&search=...&categoria=...   (todos los roles)
 *   GET  api/datasets.php?action=detail&id=N                      (todos los roles)
 *   POST api/datasets.php?action=create                           (admin, steward, owner)
 *   POST api/datasets.php?action=update                           (admin, steward, owner en su dataset)
 *   POST api/datasets.php?action=delete                           (admin, steward)
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

// Catalogos cerrados: evita valores inventados en categoria/origen
const CATEGORIAS = ['Finanzas', 'Clientes', 'Operaciones', 'Ventas', 'Recursos Humanos', 'Marketing', 'Legal', 'Otros'];
const ORIGENES  = ['Base de datos', 'Archivo', 'API', 'CRM', 'SQL Server', 'CSV', 'Otro'];

/** Usuarios validos como Data Owner (rol 'owner' o admin). */
function obtenerOwners(): array
{
    return db()->query(
        "SELECT u.id, u.nombre, r.nombre AS rol
         FROM users u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE r.nombre IN ('owner', 'admin')
         ORDER BY u.nombre"
    )->fetchAll();
}

/** Devuelve el id del dataset si el usuario puede editarlo, o 0. */
function puedeEditar(int $datasetId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    // 'owner' solo sus propios datasets
    $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
    $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
    return $stmt->fetch() !== false;
}

switch ($action) {

    case 'list':
        $search    = trim((string) query('search', ''));
        $categoria = trim((string) query('categoria', ''));

        $sql = 'SELECT d.id, d.nombre, d.descripcion, d.categoria, d.origen, d.activo,
                       d.categoria_ia, d.etiquetas_ia, u.nombre AS owner_name,
                       (SELECT qm.score FROM quality_metrics qm
                         WHERE qm.dataset_id = d.id
                         ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultimo_score
                FROM datasets d
                INNER JOIN users u ON u.id = d.owner_id
                WHERE 1 = 1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (d.nombre LIKE :search_a OR d.descripcion LIKE :search_b)';
            $params['search_a'] = '%' . $search . '%';
            $params['search_b'] = '%' . $search . '%';
        }
        if ($categoria !== '') {
            $sql .= ' AND d.categoria = :categoria';
            $params['categoria'] = $categoria;
        }

        $sql .= ' ORDER BY d.fecha_actualizacion DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $datasets = $stmt->fetchAll();

        respondJson(true, [
            'datasets'   => $datasets,
            'categorias' => CATEGORIAS,
            'owners'     => obtenerOwners(),
        ]);
        break;

    case 'detail':
        $id = (int) query('id', 0);

        $stmt = db()->prepare(
            'SELECT d.*, u.nombre AS owner_name, u.email AS owner_email
             FROM datasets d
             INNER JOIN users u ON u.id = d.owner_id
             WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $dataset = $stmt->fetch();
        if (!$dataset) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        // Ultima medicion de calidad
        $stmt = db()->prepare(
            'SELECT completitud, exactitud, consistencia, vigencia, unicidad, score, fecha_medicion
             FROM quality_metrics WHERE dataset_id = :id
             ORDER BY fecha_medicion DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $dataset['ultima_calidad'] = $stmt->fetch();

        // Historico corto de scores
        $stmt = db()->prepare(
            'SELECT score, fecha_medicion FROM quality_metrics
             WHERE dataset_id = :id ORDER BY fecha_medicion ASC LIMIT 30'
        );
        $stmt->execute(['id' => $id]);
        $dataset['historial_score'] = $stmt->fetchAll();

        // Politicas vinculadas
        $stmt = db()->prepare(
            'SELECT p.id, p.titulo, p.tipo, p.estado
             FROM policies p
             INNER JOIN policy_datasets pd ON pd.policy_id = p.id
             WHERE pd.dataset_id = :id'
        );
        $stmt->execute(['id' => $id]);
        $dataset['politicas'] = $stmt->fetchAll();

        // Detecciones de IA recientes
        $stmt = db()->prepare(
            'SELECT id, tipo, severidad, descripcion_ia, recomendacion_ia, estado, fecha
             FROM ai_detections WHERE dataset_id = :id
             ORDER BY fecha DESC LIMIT 5'
        );
        $stmt->execute(['id' => $id]);
        $dataset['detections'] = $stmt->fetchAll();

        respondJson(true, $dataset);
        break;

    case 'create':
        requireRole(['admin', 'steward', 'owner']);

        $data        = jsonInput();
        $nombre      = trim((string)($data['nombre'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $categoria   = trim((string)($data['categoria'] ?? ''));
        $origen      = trim((string)($data['origen'] ?? ''));
        $ownerId     = (int)($data['owner_id'] ?? 0);
        $activo      = isset($data['activo']) ? (int)$data['activo'] : 1;

        $rol = currentRole();
        if ($rol === 'owner') {
            // Un Data Owner solo puede crear datasets de si mismo
            $ownerId = (int) currentUser()['id'];
        }

        if ($nombre === '' || mb_strlen($nombre) > 120) {
            respondJson(false, null, 'Nombre obligatorio (max 120 caracteres).');
        }
        if (mb_strlen($descripcion) > 5000) {
            respondJson(false, null, 'La descripcion es demasiado larga.');
        }
        if (!in_array($categoria, CATEGORIAS, true)) {
            respondJson(false, null, 'Categoria invalida.');
        }
        if ($origen === '' || !in_array($origen, ORIGENES, true)) {
            respondJson(false, null, 'Origen invalido.');
        }
        if ($ownerId <= 0) {
            respondJson(false, null, 'Debes seleccionar un Data Owner.');
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO datasets (nombre, descripcion, categoria, origen, owner_id, activo)
                 VALUES (:nombre, :descripcion, :categoria, :origen, :owner_id, :activo)'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'origen'      => $origen,
                'owner_id'    => $ownerId,
                'activo'      => $activo,
            ]);
            $datasetId = (int) db()->lastInsertId();
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo crear el dataset.');
        }

        logActivity('datasets:create', ['dataset_id' => $datasetId]);
        respondJson(true, ['id' => $datasetId], 'Dataset creado.');
        break;

    case 'update':
        requireRole(['admin', 'steward', 'owner']);

        $data        = jsonInput();
        $id          = (int)($data['id'] ?? 0);
        $nombre      = trim((string)($data['nombre'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $categoria   = trim((string)($data['categoria'] ?? ''));
        $origen      = trim((string)($data['origen'] ?? ''));
        // El owner_id solo se cambia por admin/steward
        $ownerId     = (int)($data['owner_id'] ?? 0);
        $activo      = isset($data['activo']) ? (int)$data['activo'] : 1;

        if ($id <= 0) {
            respondJson(false, null, 'ID de dataset invalido.');
        }
        if (!puedeEditar($id)) {
            respondJson(false, null, 'No tienes permisos sobre este dataset.', 403);
        }
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            respondJson(false, null, 'Nombre obligatorio (max 120 caracteres).');
        }
        if (!in_array($categoria, CATEGORIAS, true)) {
            respondJson(false, null, 'Categoria invalida.');
        }
        if ($origen === '' || !in_array($origen, ORIGENES, true)) {
            respondJson(false, null, 'Origen invalido.');
        }
        if ($ownerId <= 0) {
            respondJson(false, null, 'Debes seleccionar un Data Owner.');
        }

        try {
            $stmt = db()->prepare(
                'UPDATE datasets
                 SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria,
                     origen = :origen, owner_id = :owner_id, activo = :activo
                 WHERE id = :id'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'origen'      => $origen,
                'owner_id'    => $ownerId,
                'activo'      => $activo,
                'id'          => $id,
            ]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo actualizar el dataset.');
        }

        logActivity('datasets:update', ['dataset_id' => $id]);
        respondJson(true, null, 'Dataset actualizado.');
        break;

    case 'delete':
        requireRole(['admin', 'steward']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de dataset invalido.');
        }

        try {
            $stmt = db()->prepare('DELETE FROM datasets WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo eliminar el dataset.');
        }
        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Dataset no encontrado.', 404);
        }

        logActivity('datasets:delete', ['dataset_id' => $id]);
        respondJson(true, null, 'Dataset eliminado.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
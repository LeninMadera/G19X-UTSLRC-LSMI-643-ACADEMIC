<?php
/**
 * API de linaje de datos.
 *
 * Registra y consulta de que dataset proviene cada dataset y con
 * que otros datasets esta relacionado.
 *
 *   GET  api/lineage.php?action=list                 (todos los roles)
 *   GET  api/lineage.php?action=list&dataset_id=N    (solo relaciones de un dataset)
 *   GET  api/lineage.php?action=get&id=N             (todos los roles)
 *   POST api/lineage.php?action=create               (admin, steward; owner si ambos datasets son suyos)
 *   POST api/lineage.php?action=update               (admin, steward; owner si ambos datasets son suyos)
 *   POST api/lineage.php?action=delete               (admin, steward; owner si ambos datasets son suyos)
 *
 * La fila l significa: "el dataset origen aporta informacion al
 * dataset destino segun el tipo indicado".
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

// Catalogo cerrado de tipos de relacion (mismo que chk_dl_tipo en SQL)
const TIPOS_LINEAJE = ['derivado_de', 'alimenta', 'integra', 'transforma', 'copia', 'version_de'];

const MAX_DESCRIPCION = 500;

/**
 * Indica si el dataset pertenece al usuario en sesion.
 * Reutiliza la misma regla de propiedad que aplica el catalogo.
 */
function esPropietario(int $datasetId): bool
{
    $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
    $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
    return $stmt->fetch() !== false;
}

/**
 * Un Data Owner solo gestiona relaciones entre datasets propios.
 * admin y steward gestionan todas; analyst no gestiona ninguna.
 */
function puedeEditarLineaje(int $origenId, int $destinoId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    if ($rol === 'owner') {
        return esPropietario($origenId) && esPropietario($destinoId);
    }
    return false;
}

/**
 * Valida el cuerpo del POST para create y update.
 * En update el id de la relacion llega como 'id', no como
 * 'origen_id', asi que los campos se leen por separado.
 * Devuelve [datos normalizados, errores].
 */
function validarRelacion(array $data, bool $esUpdate): array
{
    $errores = [];

    $origenId  = (int)($data['origen_id'] ?? 0);
    $destinoId = (int)($data['destino_id'] ?? 0);
    $tipo      = trim((string)($data['tipo'] ?? ''));
    $descripcion = trim((string)($data['descripcion'] ?? ''));

    // Al actualizar no se espera origen ni destino: se conservan
    if ($esUpdate) {
        $origenId  = 0;
        $destinoId = 0;
    } else {
        if ($origenId <= 0 || $destinoId <= 0) {
            $errores[] = 'Debes seleccionar el dataset origen y el dataset destino.';
        } elseif ($origenId === $destinoId) {
            $errores[] = 'El dataset origen y el destino deben ser distintos.';
        }
    }

    if (!in_array($tipo, TIPOS_LINEAJE, true)) {
        $errores[] = 'Tipo de relacion invalido.';
    }
    if (mb_strlen($descripcion) > MAX_DESCRIPCION) {
        $errores[] = 'La descripcion supera los ' . MAX_DESCRIPCION . ' caracteres.';
    }

    return [
        [
            'origen_id'   => $origenId,
            'destino_id'  => $destinoId,
            'tipo'        => $tipo,
            'descripcion' => $descripcion,
        ],
        $errores,
    ];
}

/** Devuelve un SELECT con origen y destino resueltos a nombres. */
function consultaRelaciones(string $where = '', array $params = []): array
{
    $sql = 'SELECT l.id, l.origen_id, l.destino_id, l.tipo, l.descripcion,
                   l.fecha_creacion, l.creado_por,
                   o.nombre AS origen_nombre, d.nombre AS destino_nombre,
                   u.nombre AS creado_por_nombre
            FROM data_lineage l
            INNER JOIN datasets o ON o.id = l.origen_id
            INNER JOIN datasets d ON d.id = l.destino_id
            LEFT JOIN users u ON u.id = l.creado_por';

    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $sql .= ' ORDER BY l.fecha_creacion DESC, l.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

switch ($action) {

    case 'list':
        $datasetId = (int) query('dataset_id', 0);

        // Sin filtro devuelve todas; con filtro, las relaciones en las
        // que el dataset participa como origen o como destino.
        // Se usan dos placeholders distintos con el mismo valor porque
        // la conexion no emula sentencias preparadas.
        if ($datasetId > 0) {
            $relaciones = consultaRelaciones(
                '(l.origen_id = :d_origen OR l.destino_id = :d_destino)',
                ['d_origen' => $datasetId, 'd_destino' => $datasetId]
            );
        } else {
            $relaciones = consultaRelaciones();
        }

        // El catalogo alimenta los select del formulario: solo se
        // pueden elegir datasets que ya existen
        $datasets = db()->query('SELECT id, nombre FROM datasets ORDER BY nombre')->fetchAll();

        respondJson(true, [
            'relaciones' => $relaciones,
            'datasets'   => $datasets,
            'tipos'      => TIPOS_LINEAJE,
        ]);
        break;

    case 'get':
        $id = (int) query('id', 0);

        $stmt = db()->prepare('SELECT * FROM data_lineage WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $relacion = $stmt->fetch();
        if (!$relacion) {
            respondJson(false, null, 'Relacion no encontrada.', 404);
        }
        respondJson(true, $relacion);
        break;

    case 'create':
        requireRole(['admin', 'steward', 'owner']);

        [$datos, $errores] = validarRelacion(jsonInput(), false);
        if (!empty($errores)) {
            respondJson(false, null, implode(' ', $errores));
        }

        // Solo datasets reales del catalogo
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datos['origen_id']]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'El dataset origen no existe.', 404);
        }
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $datos['destino_id']]);
        if ($stmt->fetch() === false) {
            respondJson(false, null, 'El dataset destino no existe.', 404);
        }

        if (!puedeEditarLineaje($datos['origen_id'], $datos['destino_id'])) {
            respondJson(false, null, 'No tienes permisos sobre estos datasets.', 403);
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO data_lineage (origen_id, destino_id, tipo, descripcion, creado_por)
                 VALUES (:origen_id, :destino_id, :tipo, :descripcion, :creado_por)'
            );
            $stmt->execute([
                'origen_id'   => $datos['origen_id'],
                'destino_id'  => $datos['destino_id'],
                'tipo'        => $datos['tipo'],
                'descripcion' => $datos['descripcion'],
                'creado_por'  => currentUser()['id'],
            ]);
            $lineageId = (int) db()->lastInsertId();
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                respondJson(false, null, 'Ya existe esa misma relacion con ese tipo.');
            }
            respondJson(false, null, 'No se pudo crear la relacion.');
        }

        logActivity('linaje:create', [
            'relacion_id' => $lineageId,
            'origen_id'   => $datos['origen_id'],
            'destino_id'  => $datos['destino_id'],
            'tipo'        => $datos['tipo'],
        ]);
        respondJson(true, ['id' => $lineageId], 'Relacion registrada.');
        break;

    case 'update':
        requireRole(['admin', 'steward', 'owner']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de relacion invalido.');
        }

        [$datos, $errores] = validarRelacion($data, true);
        if (!empty($errores)) {
            respondJson(false, null, implode(' ', $errores));
        }

        // La relacion guarda el origen y el destino: se recuperan para
        // validar permisos y detectar duplicados al cambiar el tipo
        $stmt = db()->prepare('SELECT origen_id, destino_id FROM data_lineage WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $actual = $stmt->fetch();
        if ($actual === false) {
            respondJson(false, null, 'Relacion no encontrada.', 404);
        }

        if (!puedeEditarLineaje((int)$actual['origen_id'], (int)$actual['destino_id'])) {
            respondJson(false, null, 'No tienes permisos sobre estos datasets.', 403);
        }

        try {
            $stmt = db()->prepare(
                'UPDATE data_lineage SET tipo = :tipo, descripcion = :descripcion WHERE id = :id'
            );
            $stmt->execute([
                'tipo'        => $datos['tipo'],
                'descripcion' => $datos['descripcion'],
                'id'          => $id,
            ]);
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                respondJson(false, null, 'Ya existe esa misma relacion con ese tipo.');
            }
            respondJson(false, null, 'No se pudo actualizar la relacion.');
        }

        logActivity('linaje:update', [
            'relacion_id' => $id,
            'origen_id'   => (int)$actual['origen_id'],
            'destino_id'  => (int)$actual['destino_id'],
            'tipo'        => $datos['tipo'],
        ]);
        respondJson(true, null, 'Relacion actualizada.');
        break;

    case 'delete':
        requireRole(['admin', 'steward', 'owner']);

        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respondJson(false, null, 'ID de relacion invalido.');
        }

        $stmt = db()->prepare('SELECT origen_id, destino_id FROM data_lineage WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $relacion = $stmt->fetch();
        if ($relacion === false) {
            respondJson(false, null, 'Relacion no encontrada.', 404);
        }

        if (!puedeEditarLineaje((int)$relacion['origen_id'], (int)$relacion['destino_id'])) {
            respondJson(false, null, 'No tienes permisos sobre estos datasets.', 403);
        }

        $stmt = db()->prepare('DELETE FROM data_lineage WHERE id = :id');
        $stmt->execute(['id' => $id]);

        logActivity('linaje:delete', [
            'relacion_id' => $id,
            'origen_id'   => (int)$relacion['origen_id'],
            'destino_id'  => (int)$relacion['destino_id'],
        ]);
        respondJson(true, null, 'Relacion eliminada.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}

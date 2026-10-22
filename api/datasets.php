<?php
/**
 * API del catalogo de datasets.
 *
 *   GET  api/datasets.php?action=list&search=...&categoria=...   (todos los roles)
 *   GET  api/datasets.php?action=search&q=...&categoria=...      (todos los roles, busqueda semantica con IA)
 *   GET  api/datasets.php?action=detail&id=N                      (todos los roles)
 *   GET  api/datasets.php?action=export&search=...&categoria=...  (todos los roles, CSV)
 *   POST api/datasets.php?action=create                           (admin, steward, owner)
 *   POST api/datasets.php?action=update                           (admin, steward, owner en su dataset)
 *   POST api/datasets.php?action=delete                           (admin, steward)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/semantic_search.php';

requireAuth();

$action = query('action', '');

requireCsrfIfPost();

// Catalogos cerrados: evita valores inventados en categoria/origen
const CATEGORIAS = ['Finanzas', 'Clientes', 'Operaciones', 'Ventas', 'Recursos Humanos', 'Marketing', 'Legal', 'Otros'];
const ORIGENES  = ['Base de datos', 'Archivo', 'API', 'CRM', 'SQL Server', 'CSV', 'Otro'];
const CLASIFICACIONES = ['publico', 'interno', 'confidencial', 'restringido'];

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

        $sql = 'SELECT d.id, d.nombre, d.descripcion, d.categoria, d.origen, d.activo, d.clasificacion,
                       d.categoria_ia, d.etiquetas_ia, d.owner_id, u.nombre AS owner_name,
                       (SELECT qm.score FROM quality_metrics qm
                         WHERE qm.dataset_id = d.id
                         ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultimo_score
                FROM datasets d
                INNER JOIN users u ON u.id = d.owner_id
                WHERE 1 = 1';
        $params = [];

        if ($search !== '') {
            // Campos de texto buscables del dataset. Un placeholder distinto
            // por columna: con emulacion de prepares desactivada reutilizar el
            // mismo placeholder varias veces provoke SQLSTATE[HY093].
            $likes = [];
            foreach (CAMPOS_BUSQUEDA as $col => $campo) {
                $ph = 'search_' . $col;
                $likes[] = "d.{$campo} LIKE :{$ph}";
                $params[$ph] = '%' . $search . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $likes) . ')';
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

    case 'search':
        // Busqueda semantica asistida por IA: la IA expande la consulta en
        // terminos relacionados, SQL trae las candidatas y PHP las ordena por
        // relevancia. Mismos permisos que 'list' (solo requireAuth).
        $search    = mb_substr(trim((string) query('q', '')), 0, MAX_QUERY);
        $categoria = trim((string) query('categoria', ''));

        if ($search === '') {
            respondJson(true, [
                'datasets'      => [],
                'categorias'    => CATEGORIAS,
                'terminos'      => [],
                'simulado'      => ai_simulado(),
                'fallback'      => false,
            ], 'Escribe una consulta para usar la busqueda semantica.');
        }

        // 1) Expansion de la consulta, con cache FIFO en la sesion
        $claveCache = sem_normalizar($search);
        $fallback   = false;
        $cache      = sem_cache_leer($claveCache);

        if ($cache !== null) {
            $expansion = $cache;
        } else {
            try {
                $expansion = sem_expandir_consulta($search, CATEGORIAS);
                sem_cache_guardar($claveCache, $expansion);
            } catch (Throwable $e) {
                // La IA fallo o no devolvio nada utilizable: se degrada a la
                // busqueda literal con el termino original, sin expansion.
                $expansion = sem_simular_expansion($search);
                $expansion['otros']    = [];
                $expansion['simulado'] = true;
                $fallback = true;
            }
        }

        // 2) Lista de terminos: el original pesa mas que los expandidos.
        //    En fallback solo se busca el termino original, tal cual.
        if ($fallback) {
            $terminosBusqueda = [
                ['texto' => $expansion['original'], 'etiqueta' => $expansion['original'], 'original' => true],
            ];
        } else {
            $terminosBusqueda = sem_lista_terminos($expansion);
        }

        // 3) Candidatas: cualquier termino en cualquier campo de texto
        $resultados = [];

        // Una consulta sin texto utilizable (solo signos o palabras vacias)
        // no genera terminos: se responde vacio en lugar de consultar.
        if ($terminosBusqueda !== []) {
            $sql = 'SELECT d.id, d.nombre, d.descripcion, d.categoria, d.origen, d.activo, d.clasificacion,
                           d.categoria_ia, d.etiquetas_ia, d.owner_id, d.fecha_actualizacion,
                           u.nombre AS owner_name,
                           (SELECT qm.score FROM quality_metrics qm
                             WHERE qm.dataset_id = d.id
                             ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultimo_score
                    FROM datasets d
                    INNER JOIN users u ON u.id = d.owner_id
                    WHERE 1 = 1';
            $params = [];

            // Un placeholder distinto por cada par termino/campo: con emulacion
            // de prepares desactivada reutilizarlo Provocaria SQLSTATE[HY093].
            $likes = [];
            $i = 0;
            foreach ($terminosBusqueda as $t) {
                foreach (CAMPOS_BUSQUEDA as $campo) {
                    $ph = 'sem_' . $i++;
                    $likes[] = "d.{$campo} LIKE :{$ph}";
                    $params[$ph] = '%' . $t['texto'] . '%';
                }
            }
            $sql .= ' AND (' . implode(' OR ', $likes) . ')';

            if ($categoria !== '') {
                $sql .= ' AND d.categoria = :categoria';
                $params['categoria'] = $categoria;
            }

            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            // 4) Orden por relevancia ponderada
            $puntuacion = [
                'original'        => $expansion['original'],
                'original_factor' => FACTOR_TERMINO_ORIGINAL,
                'todos'           => $terminosBusqueda,
            ];
            $resultados = sem_puntuar($stmt->fetchAll(), $puntuacion);

            $maximo = sem_maximo_puntaje($puntuacion);
            foreach ($resultados as &$r) {
                $r['puntos']    = (int) $r['puntos'];
                $r['relevancia'] = $maximo > 0
                    ? (int) min(100, round($r['puntos'] * 100 / $maximo))
                    : 0;
            }
            unset($r);
        }

        // Solo datos operacionales: nunca la consulta ni los terminos.
        logActivity('datasets:buscar', [
            'modo'       => 'semantico',
            'simulado'   => (bool) $expansion['simulado'],
            'fallback'   => $fallback,
            'resultados' => count($resultados),
        ]);

        respondJson(true, [
            'datasets'       => $resultados,
            'categorias'     => CATEGORIAS,
            'terminos'       => array_column($terminosBusqueda, 'etiqueta'),
            'interpretacion' => $expansion['interpretacion'],
            'simulado'       => (bool) $expansion['simulado'],
            'fallback'       => $fallback,
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

        // Linaje de datos: de que datasets recibe y hacia que datasets envia
        $stmt = db()->prepare(
            'SELECT l.id, l.origen_id, d.nombre AS origen_nombre, l.tipo, l.descripcion, l.fecha_creacion
             FROM data_lineage l
             INNER JOIN datasets d ON d.id = l.origen_id
             WHERE l.destino_id = :id
             ORDER BY l.fecha_creacion DESC, l.id DESC'
        );
        $stmt->execute(['id' => $id]);
        $dataset['linaje_entrada'] = $stmt->fetchAll();

        $stmt = db()->prepare(
            'SELECT l.id, l.destino_id, d.nombre AS destino_nombre, l.tipo, l.descripcion, l.fecha_creacion
             FROM data_lineage l
             INNER JOIN datasets d ON d.id = l.destino_id
             WHERE l.origen_id = :id
             ORDER BY l.fecha_creacion DESC, l.id DESC'
        );
        $stmt->execute(['id' => $id]);
        $dataset['linaje_salida'] = $stmt->fetchAll();

        respondJson(true, $dataset);
        break;

    case 'create':
        requireRole(['admin', 'steward', 'owner']);

        $data        = jsonInput();
        $nombre      = trim((string)($data['nombre'] ?? ''));
        $descripcion = trim((string)($data['descripcion'] ?? ''));
        $categoria   = trim((string)($data['categoria'] ?? ''));
        $origen      = trim((string)($data['origen'] ?? ''));
        $clasificacion = trim((string)($data['clasificacion'] ?? 'publico'));
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
        if (!in_array($clasificacion, CLASIFICACIONES, true)) {
            respondJson(false, null, 'Clasificacion invalida.');
        }
        if ($ownerId <= 0) {
            respondJson(false, null, 'Debes seleccionar un Data Owner.');
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO datasets (nombre, descripcion, categoria, origen, owner_id, clasificacion, activo)
                 VALUES (:nombre, :descripcion, :categoria, :origen, :owner_id, :clasificacion, :activo)'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'origen'      => $origen,
                'owner_id'    => $ownerId,
                'clasificacion' => $clasificacion,
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
        $clasificacion = trim((string)($data['clasificacion'] ?? 'publico'));
        $ownerId     = (int)($data['owner_id'] ?? 0);
        $activo      = isset($data['activo']) ? (int)$data['activo'] : 1;

        $rol = currentRole();
        if ($rol === 'owner') {
            // Un Data Owner solo puede gestionar sus propios datasets
            $ownerId = (int) currentUser()['id'];
        }

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
        if (!in_array($clasificacion, CLASIFICACIONES, true)) {
            respondJson(false, null, 'Clasificacion invalida.');
        }
        if ($ownerId <= 0) {
            respondJson(false, null, 'Debes seleccionar un Data Owner.');
        }

        try {
            $stmt = db()->prepare(
                'UPDATE datasets
                 SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria,
                     origen = :origen, owner_id = :owner_id, clasificacion = :clasificacion, activo = :activo
                 WHERE id = :id'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'origen'      => $origen,
                'owner_id'    => $ownerId,
                'clasificacion' => $clasificacion,
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

    case 'export':
        $search    = trim((string) query('search', ''));
        $categoria = trim((string) query('categoria', ''));

        $sql = 'SELECT d.id, d.nombre, d.descripcion, d.categoria, d.origen, d.clasificacion, d.activo,
                       u.nombre AS owner_name, u.email AS owner_email,
                       (SELECT qm.score FROM quality_metrics qm
                         WHERE qm.dataset_id = d.id
                         ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultimo_score
                FROM datasets d
                INNER JOIN users u ON u.id = d.owner_id
                WHERE 1 = 1';
        $params = [];

        if ($search !== '') {
            // Mismo criterio que en 'list': un placeholder por columna.
            $likes = [];
            foreach (CAMPOS_BUSQUEDA as $col => $campo) {
                $ph = 'search_' . $col;
                $likes[] = "d.{$campo} LIKE :{$ph}";
                $params[$ph] = '%' . $search . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $likes) . ')';
        }
        if ($categoria !== '') {
            $sql .= ' AND d.categoria = :categoria';
            $params['categoria'] = $categoria;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll();

        $rows = array_map(function ($r) {
            $nivel = $r['ultimo_score'] === null ? 'Sin mediciones'
                : ($r['ultimo_score'] >= 85 ? 'Excelente' : ($r['ultimo_score'] >= 70 ? 'Aceptable' : 'Por mejorar'));
            return [
                $r['id'],
                $r['nombre'],
                $r['categoria'],
                $r['origen'],
                $r['clasificacion'] ?? 'publico',
                $r['owner_name'] . ' <' . $r['owner_email'] . '>',
                $r['activo'] == 1 ? 'Si' : 'No',
                $r['ultimo_score'] ?? '',
                $nivel,
            ];
        }, $datos);

        descargarCsv('catalogo_datasets_' . date('Ymd_His') . '.csv', ['ID', 'Nombre', 'Categoria', 'Origen', 'Clasificacion', 'Propietario', 'Activo', 'Ultimo score', 'Nivel'], $rows);
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}

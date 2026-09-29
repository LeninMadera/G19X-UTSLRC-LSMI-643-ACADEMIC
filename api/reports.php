<?php
/**
 * API de reportes y metricas del dashboard.
 *
 *   GET api/reports.php?action=overview   (todos los roles)
 *   GET api/reports.php?action=alerts      (todos los roles)
 *   GET api/reports.php?action=logs        (solo admin)
 */
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$action = query('action', '');

/** Ultimo score de calidad de cada dataset (medicion mas reciente por fecha e id). */
function ultimosScores(): array
{
    return db()->query(
        "SELECT d.id, d.nombre, d.categoria,
                (SELECT qm.score FROM quality_metrics qm
                 WHERE qm.dataset_id = d.id
                 ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS score,
                (SELECT qm.fecha_medicion FROM quality_metrics qm
                 WHERE qm.dataset_id = d.id
                 ORDER BY qm.fecha_medicion DESC, qm.id DESC LIMIT 1) AS ultima_medicion
         FROM datasets d ORDER BY d.nombre ASC"
    )->fetchAll();
}

/**
 * Feed de "Actividad reciente" del dashboard, ya filtrado en el servidor.
 *
 * No se borra ni se modifica ningun registro: la bitacora sigue completa y
 * el historial sin filtrar de action=logs (solo admin) no se toca. Aqui
 * unicamente se decide que filas puede recibir el usuario actual.
 *
 * Politica (minimo privilegio):
 *   admin   feed completo, sin cambios.
 *   steward todo menos autenticacion y gestion de cuentas: su alcance ya es
 *           global sobre datasets, calidad, politicas, cumplimiento y linaje.
 *   owner   solo eventos propios o asociados a un dataset suyo.
 *   analyst solo eventos propios.
 * login, logout y usuarios:* quedan fuera para cualquier rol que no sea
 * admin, y un evento que no se puede asociar con certeza a algo que el rol
 * controla se descarta en vez de mostrarse.
 *
 * La asociacion se resuelve por los datos que el propio evento trae en su
 * JSON: dataset_id directo; source_id y metric_id se Translate a su dataset
 * siguiendo la llave; origen_id y destino_id ya son ids de dataset, porque en
 * data_lineage son claves foraneas a datasets(id).
 */
function actividadVisible(int $uid, string $rol, int $limit = 8): array
{
    $base = "SELECT al.accion, al.detalle, al.fecha, u.nombre AS usuario
             FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id";

    // El feed completo del admin es exactamente el que habia antes.
    if ($rol === 'admin') {
        return db()->query($base . " ORDER BY al.fecha DESC, al.id DESC LIMIT $limit")->fetchAll();
    }

    // Datasets a los que apunta un evento, sea directo o a traves de la entidad
    // que el evento nombra. Se resuelve con subconsultas escalares correlacionadas
    // (una tabla derivada correlacionada no es portable en MySQL) y con
    // JSON_UNQUOTE + CAST porque el detalle es JSON y d.id es entero.
    $datasetsDelEvento = "(
        CAST(JSON_UNQUOTE(JSON_EXTRACT(al.detalle, '$.dataset_id')) AS UNSIGNED),
        (SELECT es.dataset_id FROM external_sources es
          WHERE es.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(al.detalle, '$.source_id')) AS UNSIGNED)),
        (SELECT qm.dataset_id FROM quality_metrics qm
          WHERE qm.id = CAST(JSON_UNQUOTE(JSON_EXTRACT(al.detalle, '$.metric_id')) AS UNSIGNED)),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(al.detalle, '$.origen_id')) AS UNSIGNED),
        CAST(JSON_UNQUOTE(JSON_EXTRACT(al.detalle, '$.destino_id')) AS UNSIGNED)
    )";

    $where = "al.accion NOT IN ('login', 'logout') AND al.accion NOT LIKE 'usuarios:%'";

    switch ($rol) {
        case 'steward':
            // Su alcance ya es global sobre el catalogo: no hace falta acotar
            // por dataset, pero si excluir autenticacion y gestion de cuentas.
            $sql = "$base WHERE $where ORDER BY al.fecha DESC, al.id DESC LIMIT :lim";
            $params = [];
            break;

        case 'owner':
            // Lo suyo o lo de sus datasets. :uid y :uid2 son el mismo usuario:
            // PDO con EMULATE_PREPARES apagado no admite un nombre repetido.
            $sql = "$base
                    WHERE $where
                      AND (al.user_id = :uid
                           OR EXISTS (SELECT 1 FROM datasets d
                                       WHERE d.owner_id = :uid2
                                         AND d.id IN $datasetsDelEvento))
                    ORDER BY al.fecha DESC, al.id DESC LIMIT :lim";
            $params = [':uid' => $uid, ':uid2' => $uid];
            break;

        default: // analyst y cualquier rol no contemplado: solo lo propio
            $sql = "$base
                    WHERE $where AND al.user_id = :uid
                    ORDER BY al.fecha DESC, al.id DESC LIMIT :lim";
            $params = [':uid' => $uid];
            break;
    }

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Defensa en profundidad: fuera del admin, el JSON del evento se queda
    // sin los campos que describen una cuenta (rol, correo, id de usuario).
    foreach ($rows as &$r) {
        if ($r['detalle'] === null) {
            continue;
        }
        $detalle = json_decode($r['detalle'], true);
        if (!is_array($detalle)) {
            $r['detalle'] = null;
            continue;
        }
        foreach (['rol', 'rol_id', 'email', 'user_id'] as $campo) {
            unset($detalle[$campo]);
        }
        $r['detalle'] = $detalle === []
            ? null
            : json_encode($detalle, JSON_UNESCAPED_UNICODE);
    }
    unset($r);

    return $rows;
}

switch ($action) {

    case 'overview':
        $totalDatasets   = (int)db()->query('SELECT COUNT(*) FROM datasets')->fetchColumn();
        $politicasActivas = (int)db()->query("SELECT COUNT(*) FROM policies WHERE estado = 'activa'")->fetchColumn();
        $alertasPendientes = (int)db()->query(
            "SELECT COUNT(*) FROM ai_detections WHERE tipo = 'anomalia' AND estado = 'pendiente'"
        )->fetchColumn();
        $sugerenciasPendientes = (int)db()->query(
            "SELECT COUNT(*) FROM ai_detections WHERE tipo = 'sugerencia' AND estado = 'pendiente'"
        )->fetchColumn();

        $scores = ultimosScores();
        $conScore = array_filter($scores, fn($s) => $s['score'] !== null);
        $promedio = count($conScore) > 0
            ? round(array_sum(array_column($conScore, 'score')) / count($conScore), 2)
            : null;

        $porCategoria = db()->query(
            "SELECT categoria, COUNT(*) AS total FROM datasets GROUP BY categoria ORDER BY total DESC, categoria ASC"
        )->fetchAll();

        $alertas = db()->query(
            "SELECT a.id, a.tipo, a.severidad, a.descripcion_ia, a.estado, a.fecha, d.nombre AS dataset
             FROM ai_detections a JOIN datasets d ON d.id = a.dataset_id
             WHERE a.tipo = 'anomalia'
             ORDER BY a.fecha DESC, a.id DESC LIMIT 6"
        )->fetchAll();

        $actividad = actividadVisible((int)currentUser()['id'], (string)currentRole());

        // Indicadores del monitoreo continuo. Si las tablas aun no
        // existen (modulo no migrado), se degrada a null sin romper.
        $monitoreo = null;
        try {
            $mOk   = (int)db()->query("SELECT COUNT(*) FROM monitoring_runs WHERE estado = 'ok'")->fetchColumn();
            $mFal  = (int)db()->query("SELECT COUNT(*) FROM monitoring_runs WHERE estado = 'fallo'")->fetchColumn();
            $mExito = ($mOk + $mFal) > 0 ? round($mOk * 100 / ($mOk + $mFal), 1) : null;
            $monitoreo = [
                'configs_activas'  => (int)db()->query('SELECT COUNT(*) FROM monitoring_configs WHERE activo = 1')->fetchColumn(),
                'ejecuciones'      => $mOk + $mFal,
                'ok'               => $mOk,
                'fallos'           => $mFal,
                'tasa_exito'       => $mExito,
                'ultima_ejecucion' => db()->query('SELECT MAX(fecha) FROM monitoring_runs')->fetchColumn() ?: null,
            ];
        } catch (PDOException $e) {
            $monitoreo = null;
        }

        respondJson(true, [
            'total_datasets'   => $totalDatasets,
            'politicas_activas' => $politicasActivas,
            'alertas_pendientes' => $alertasPendientes,
            'sugerencias_pendientes' => $sugerenciasPendientes,
            'promedio_calidad' => $promedio,
            'scores'           => $scores,
            'por_categoria'    => $porCategoria,
            'alertas'          => $alertas,
            'actividad'        => $actividad,
            'monitoreo'        => $monitoreo,
        ]);
        break;

    case 'alerts':
        $limit = min(max((int)query('limit', 20), 1), 50);
        $stmt = db()->prepare(
            "SELECT a.id, a.tipo, a.severidad, a.descripcion_ia, a.recomendacion_ia, a.estado, a.fecha, d.nombre AS dataset
             FROM ai_detections a JOIN datasets d ON d.id = a.dataset_id
             ORDER BY a.fecha DESC, a.id DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        respondJson(true, ['alerts' => $stmt->fetchAll()]);
        break;

    case 'logs':
        requireRole(['admin']);
        $limit = min(max((int)query('limit', 100), 1), 200);
        $stmt = db()->prepare(
            "SELECT al.id, al.accion, al.detalle, al.ip, al.fecha, u.nombre AS usuario, u.email
             FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.fecha DESC, al.id DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        respondJson(true, ['logs' => $stmt->fetchAll()]);
        break;

    case 'export':
        $rows = [];
        foreach (ultimosScores() as $s) {
            $nivel = $s['score'] === null ? 'Sin mediciones'
                : ($s['score'] >= 85 ? 'Excelente' : ($s['score'] >= 70 ? 'Aceptable' : 'Por mejorar'));
            $rows[] = [
                $s['nombre'],
                $s['categoria'],
                $s['score'] ?? '',
                $s['ultima_medicion'] ?? '',
                $nivel,
            ];
        }
        descargarCsv('reporte_calidad_' . date('Ymd_His') . '.csv', ['Dataset', 'Categoria', 'Ultimo score', 'Ultima medicion', 'Nivel'], $rows);
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}
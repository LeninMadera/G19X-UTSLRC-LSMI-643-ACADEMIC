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

        $actividad = db()->query(
            "SELECT al.accion, al.detalle, al.fecha, u.nombre AS usuario
             FROM activity_logs al LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.fecha DESC, al.id DESC LIMIT 8"
        )->fetchAll();

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
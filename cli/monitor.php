#!/usr/bin/env php
<?php
/**
 * Programador de monitoreo continuo de calidad (consola).
 *
 * Uso:
 *   php cli/monitor.php                ejecuta las configuraciones activas y
 *                                      VENCIDAS (proxima_ejecucion <= ahora)
 *   php cli/monitor.php --run-id=ID    fuerza una ejecucion manual de una
 *                                      configuracion especifica
 *   php cli/monitor.php --list         muestra las configuraciones con su
 *                                      proximo vencimiento
 *
 * Para automatizarlo en Windows: agrega una tarea en el Programador de
 * tareas que ejecute cli\monitor.bat con la frecuencia que necesites.
 */
require_once __DIR__ . '/../includes/monitoring_service.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo se ejecuta desde la consola.\n");
    exit(1);
}

/** Imprime el resultado de una corrida en texto plano. */
function imprimir_resultado(array $res, int $configId): void
{
    if ($res['omitido']) {
        echo "[omitido] config #{$configId}: ya estaba en ejecucion.\n";
        return;
    }
    $etiqueta = $res['estado'] === 'ok' ? 'OK' : 'FALLO';
    $score    = $res['score'] === null ? 'NULL' : $res['score'];
    printf("[%s] config #%d -> run #%d estado=%s score=%s anomalias=%d\n",
        $etiqueta, $configId, $res['run_id'], $res['estado'], $score, $res['anomalias']);
    if ($res['resumen'] !== null && $res['resumen'] !== '') {
        echo "        " . $res['resumen'] . "\n";
    }
}

$args = array_slice($argv, 1);

try {
    $listado = in_array('--list', $args, true);
    $forzado = null;
    foreach ($args as $arg) {
        if (preg_match('/^--run-id=(\d+)$/', $arg, $m)) {
            $forzado = (int)$m[1];
        }
    }

    if ($listado) {
        $rows = db()->query(
            "SELECT mc.id, mc.dataset_id, mc.frecuencia, mc.activo, mc.corriendo,
                    mc.proxima_ejecucion, mc.estado_ultima, mc.score_ultimo, d.nombre AS dataset
             FROM monitoring_configs mc
             INNER JOIN datasets d ON d.id = mc.dataset_id
             ORDER BY mc.activo DESC, mc.proxima_ejecucion ASC, mc.id ASC"
        )->fetchAll();
        printf("%-4s %-26s %-8s %-7s %-7s %-19s %-7s\n",
            'id', 'dataset', 'frec', 'activo', 'corr', 'proxima', 'estado');
        foreach ($rows as $r) {
            printf("%-4d %-26s %-8s %-7s %-7s %-19s %-7s\n",
                (int)$r['id'],
                mb_substr($r['dataset'], 0, 26),
                (int)$r['frecuencia'] . 'min',
                (int)$r['activo'] === 1 ? 'si' : 'no',
                (int)$r['corriendo'] === 1 ? 'si' : 'no',
                $r['proxima_ejecucion'] ?? 'nunca',
                $r['estado_ultima'] ?? '-');
        }
        exit(0);
    }

    if ($forzado !== null) {
        $res = monitor_ejecutar($forzado, 'manual');
        imprimir_resultado($res, $forzado);
        exit(0);
    }

    // Ejecucion automatica: solo activas (inactivas jamas) y vencidas.
    $dueIds = db()->query(
        "SELECT id FROM monitoring_configs
         WHERE activo = 1 AND corriendo = 0
           AND proxima_ejecucion IS NOT NULL
           AND proxima_ejecucion <= NOW()
         ORDER BY proxima_ejecucion ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (count($dueIds) === 0) {
        echo "Sin configuraciones vencidas.\n";
        exit(0);
    }

    foreach ($dueIds as $id) {
        $res = monitor_ejecutar((int)$id, 'automatico');
        imprimir_resultado($res, (int)$id);
    }
    exit(0);
} catch (\Throwable $e) {
    $sugerencia = $e instanceof PDOException
        ? ' No se encontraron las tablas de monitoreo: en bases existentes ejecuta sql\migration_monitoring.sql.'
        : '';
    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . $sugerencia . PHP_EOL);
    exit(1);
}
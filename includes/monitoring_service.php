<?php
/**
 * Servicio de monitoreo continuo de calidad de datos.
 *
 * Reutiliza la deteccion de anomalias del modulo de IA mediante
 * ai_ejecutar_anomalias() y las mediciones de calidad registradas:
 * cada ejecucion REVALIDA la ultima medicion almacenada, nunca crea
 * metricas propias ni inventa valores.
 *
 * Este motor es usado por api/monitoring.php (boton "Monitorear
 * ahora") y por cli/monitor.php (ejecuciones automaticas).
 */
require_once __DIR__ . '/ai_service.php';

/** Frecuencias sugeridas en la interfaz (minutos). */
const MONITOR_FRECUENCIAS = [5, 15, 30, 60, 180, 360, 720, 1440];

/** Rango permitido de frecuencia en minutos (validado en API/CLI). */
const MONITOR_FRECUENCIA_MIN = 1;
const MONITOR_FRECUENCIA_MAX = 43200;

/** Lee una configuracion de monitoreo o null si no existe. */
function monitor_config(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM monitoring_configs WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $c = $stmt->fetch();
    return $c === false ? null : $c;
}

/** Valida la frecuencia (entero en 1..43200). Devuelve int o null. */
function monitor_frecuencia_valida($valor): ?int
{
    if (!is_numeric($valor)) {
        return null;
    }
    $f = filter_var($valor, FILTER_VALIDATE_INT);
    if ($f === false || $f < MONITOR_FRECUENCIA_MIN || $f > MONITOR_FRECUENCIA_MAX) {
        return null;
    }
    return $f;
}

/**
 * Quien puede administrar el monitoreo de un dataset: admin y
 * steward en todos; el owner solo en los suyos; analyst no.
 * Misma regla de propiedad que cumplimiento y linaje.
 */
function monitor_puede_gestionar(int $datasetId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    if ($rol === 'owner') {
        $stmt = db()->prepare('SELECT id FROM datasets WHERE id = :id AND owner_id = :owner LIMIT 1');
        $stmt->execute(['id' => $datasetId, 'owner' => currentUser()['id']]);
        return $stmt->fetch() !== false;
    }
    return false;
}

/**
 * Recalcula la proxima ejecucion: NOW()+frecuencia si el dataset
 * esta activo; NULL si esta inactivo (no es elegible para el
 * programador del CLI hasta reactivarse).
 */
function monitor_programar(int $configId, int $frecuencia, bool $activo): void
{
    if ($activo) {
        db()->prepare(
            'UPDATE monitoring_configs SET proxima_ejecucion = NOW() + INTERVAL :f MINUTE WHERE id = :id'
        )->execute(['f' => $frecuencia, 'id' => $configId]);
    } else {
        db()->prepare(
            'UPDATE monitoring_configs SET proxima_ejecucion = NULL WHERE id = :id'
        )->execute(['id' => $configId]);
    }
}

/**
 * Ejecuta el monitoreo de una configuracion.
 *
 * Pasos:
 *  1. Lock atomico (corriendo): una sola corrida a la vez por config.
 *  2. Revalida la ultima medicion de calidad almacenada del dataset.
 *  3. Si hay mediciones, ejecuta la deteccion de anomalias (IA).
 *  4. Registra la corrida en monitoring_runs, actualiza el espejo de
 *     la configuracion, libera el lock y programa la siguiente.
 *
 * Causa: 'manual' (boton "Monitorear ahora" / CLI --run-id) o
 * 'automatico' (programador del CLI). Se ignora proxima_ejecucion:
 * el llamador decide cuando invocarla.
 *
 * Regla de negocio (ajuste aprobado):
 *   - Dataset sin mediciones de calidad -> corrida FALLO con resumen
 *     "No existen mediciones de calidad registradas para este dataset",
 *     score NULL, sin llamar a la IA de anomalias, con lock liberado y
 *     proxima ejecucion programada normalmente.
 *   - Error de la IA de anomalias -> corrida FALLO con el mensaje.
 *
 * Devuelve: ['omitido'=>bool, 'run_id'=>?int, 'estado'=>?string,
 *            'score'=>?float, 'anomalias'=>?int, 'resumen'=>?string].
 * Si omitido=true es porque otra corrida estaba en curso y esta se
 * descarto (no se registra nada, no se toca el lock ajeno).
 */
function monitor_ejecutar(int $configId, string $causa): array
{
    if (!in_array($causa, ['manual', 'automatico'], true)) {
        throw new InvalidArgumentException('Causa de ejecucion invalida.');
    }
    $config = monitor_config($configId);
    if ($config === null) {
        throw new RuntimeException('Configuracion de monitoreo no encontrada.');
    }
    if ((int)$config['activo'] !== 1) {
        throw new RuntimeException('La configuracion de monitoreo esta inactiva.');
    }

    $stmt = db()->prepare(
        'UPDATE monitoring_configs SET corriendo = 1 WHERE id = :id AND corriendo = 0'
    );
    $stmt->execute(['id' => $configId]);
    if ($stmt->rowCount() === 0) {
        return [
            'omitido' => true, 'run_id' => null, 'estado' => null,
            'score' => null, 'anomalias' => null, 'resumen' => null,
        ];
    }

    $datasetId  = (int)$config['dataset_id'];
    $frecuencia = (int)$config['frecuencia'];

    try {
        $stmt = db()->prepare(
            'SELECT score FROM quality_metrics
             WHERE dataset_id = :id ORDER BY fecha_medicion DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $datasetId]);
        $ultima = $stmt->fetch();

        if ($ultima === false) {
            $estado    = 'fallo';
            $score     = null;
            $anomalias = 0;
            $resumen   = 'No existen mediciones de calidad registradas para este dataset';
        } else {
            $score = (float)$ultima['score'];
            try {
                $res       = ai_ejecutar_anomalias($datasetId);
                $estado    = 'ok';
                $anomalias = $res['insertadas'];
                $resumen   = 'Revalidacion de calidad completada; ultimo score ' . $score
                           . '; ' . $anomalias . ' anomalias nuevas.';
            } catch (RuntimeException $e) {
                $estado    = 'fallo';
                $anomalias = 0;
                $resumen   = mb_substr('Fallo en la deteccion de anomalias: ' . $e->getMessage(), 0, 1000);
            }
        }

        db()->prepare(
            'INSERT INTO monitoring_runs
                (config_id, dataset_id, causa, estado, score, anomalias_nuevas, resumen)
             VALUES (:config_id, :dataset_id, :causa, :estado, :score, :anomalias, :resumen)'
        )->execute([
            'config_id'  => $configId,
            'dataset_id' => $datasetId,
            'causa'      => $causa,
            'estado'     => $estado,
            'score'      => $score !== null ? $score : null,
            'anomalias'  => $anomalias,
            'resumen'    => $resumen,
        ]);
        $runId = (int)db()->lastInsertId();

        db()->prepare(
            'UPDATE monitoring_configs
             SET estado_ultima = :estado, score_ultimo = :score, anomalias_ultimas = :anomalias,
                 ultima_ejecucion = NOW(), corriendo = 0
             WHERE id = :id'
        )->execute([
            'estado'    => $estado,
            'score'     => $score !== null ? $score : null,
            'anomalias' => $anomalias,
            'id'        => $configId,
        ]);

        monitor_programar($configId, $frecuencia, true);
    } catch (\Throwable $e) {
        // Ante cualquier fallo se libera el lock antes de propagar.
        db()->prepare('UPDATE monitoring_configs SET corriendo = 0 WHERE id = :id')
            ->execute(['id' => $configId]);
        throw $e;
    }

    logActivity('monitoreo:ejecutar', [
        'config_id'  => $configId,
        'dataset_id' => $datasetId,
        'causa'      => $causa,
        'estado'     => $estado,
        'score'      => $score,
    ]);

    return [
        'omitido'   => false,
        'run_id'    => $runId,
        'estado'    => $estado,
        'score'     => $score,
        'anomalias' => $anomalias,
        'resumen'   => $resumen,
    ];
}
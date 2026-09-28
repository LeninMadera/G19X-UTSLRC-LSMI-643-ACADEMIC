<?php
/**
 * Servicio de integracion con fuentes externas (consumo REST/JSON).
 *
 * Seguridad aplicada antes de cada peticion:
 *   1. Esquema http/https, host presente y sin credenciales en la URL.
 *   2. Rechazo de hosts locales ('localhost', '.local') y de IPs
 *      privadas/loopback/link-local literales.
 *   3. Resolucion DNS previa: si el host resuelve a una IP no publica
 *      se rechaza (SSRF). La unica excepcion es INTEGRACION_PERMITIR_LOCALHOST=true,
 *      que se usa SOLO en el ambiente de pruebas con un mock local.
 *   4. Sin redirecciones (FOLLOWLOCATION=false) y limite del cuerpo
 *      recibido con un callback de escritura que aborta la descarga
 *      al superar INTEGRACION_MAX_BYTES (no se usa MAXFILEDOWNLOAD,
 *      que no aplica a cuerpos HTTP).
 *
 * El modulo NO copia los datos externos a MySQL: solo registra
 * metadatos de la sincronizacion (total de registros y campos del
 * primer objeto JSON).
 */
require_once __DIR__ . '/functions.php';

/** Rango permitido del timeout por fuente (segundos). */
const INTEGRACION_TIMEOUT_MIN = 1;
const INTEGRACION_TIMEOUT_MAX = 60;

/** Lee una fuente externa o null si no existe. */
function integracion_fuente(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM external_sources WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $f = $stmt->fetch();
    return $f === false ? null : $f;
}

/** True si el ambiente de pruebas permite hosts locales (mock E2E). */
function integracion_permitir_localhost(): bool
{
    return defined('INTEGRACION_PERMITIR_LOCALHOST') && INTEGRACION_PERMITIR_LOCALHOST === true;
}

/** Tamano maximo del cuerpo de la respuesta externa (bytes). */
function integracion_limite_bytes(): int
{
    return defined('INTEGRACION_MAX_BYTES') ? (int)INTEGRACION_MAX_BYTES : 1048576;
}

/** Valida el timeout por fuente (entero 1..60). Devuelve int o null. */
function integracion_timeout_valido($valor): ?int
{
    if (!is_numeric($valor)) {
        return null;
    }
    $t = filter_var($valor, FILTER_VALIDATE_INT);
    if ($t === false || $t < INTEGRACION_TIMEOUT_MIN || $t > INTEGRACION_TIMEOUT_MAX) {
        return null;
    }
    return $t;
}

/**
 * Resuelve un hostname a su lista de IPs (A y IPv6/AAAA).
 * Devuelve [] si falla la resolucion (o no hay registros).
 */
function integracion_resolver(string $host): array
{
    $ips = [];
    $registros = @dns_get_record($host, DNS_A | DNS_AAAA);
    if (!is_array($registros)) {
        return [];
    }
    foreach ($registros as $r) {
        if (($r['type'] ?? '') === 'A' && !empty($r['ip'])) {
            $ips[] = $r['ip'];
        }
        if (($r['type'] ?? '') === 'AAAA' && !empty($r['ipv6'])) {
            $ips[] = $r['ipv6'];
        }
    }
    return array_values(array_unique($ips));
}

/**
 * Valida la URL de una fuente externa.
 * Devuelve '' si es valida o un mensaje de error legible.
 */
function integracion_validar_url(string $url): string
{
    $partes = parse_url($url);
    if ($partes === false || empty($partes['scheme']) || empty($partes['host'])) {
        return 'URL invalida: debe ser una URL http/https completa.';
    }
    if (!in_array(strtolower($partes['scheme']), ['http', 'https'], true)) {
        return 'URL invalida: solo se permiten esquemas http y https.';
    }
    if (!empty($partes['user'])) {
        return 'URL invalida: no se permiten credenciales en la URL.';
    }
    if (integracion_permitir_localhost()) {
        return '';
    }

    $host = strtolower((string)$partes['host']);
    if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
        return 'URL rechazada por seguridad: no se permiten hosts locales.';
    }

    $hostLimpio = trim($host, '[]');
    $ip = filter_var($hostLimpio, FILTER_VALIDATE_IP);
    if ($ip !== false) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'URL rechazada por seguridad: direccion IP no permitida (' . $ip . ').';
        }
        return '';
    }

    $ips = integracion_resolver($hostLimpio);
    if ($ips === []) {
        return 'URL rechazada por seguridad: no se pudo resolver el host de la fuente.';
    }
    foreach ($ips as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'URL rechazada por seguridad: el host resuelve a una direccion no permitida (' . $ip . ').';
        }
    }
    return '';
}

/** True si el array es una lista (indices 0..n-1 consecutivos). */
function integracion_es_lista(array $a): bool
{
    return array_keys($a) === range(0, count($a) - 1);
}

/**
 * Extrae los metadatos resumidos de una respuesta JSON.
 * Devuelve ['registros' => int, 'campos' => string[]] (nunca los datos).
 */
function integracion_metadatos(array $decodificado): array
{
    $lista = integracion_es_lista($decodificado)
        ? $decodificado
        : (isset($decodificado['data']) && is_array($decodificado['data']) && integracion_es_lista($decodificado['data'])
            ? $decodificado['data']
            : [$decodificado]);

    $primero = $lista[0] ?? null;
    $campos = is_array($primero)
        ? array_slice(array_keys($primero), 0, 20)
        : ['registro'];

    return [
        'registros' => count($lista),
        'campos'    => array_values(array_map('strval', $campos)),
    ];
}

/**
 * Ejecuta el GET hacia la fuente externa con todas las validaciones.
 *
 * Devuelve:
 *   ['ok'=>bool, 'error'=>string, 'http_code'=>int, 'cuerpo'=>?array,
 *    'duracion_ms'=>int]
 */
function integracion_fetch(array $fuente): array
{
    $url         = (string)$fuente['url'];
    $limite      = integracion_limite_bytes();
    $timeout     = integracion_timeout_valido($fuente['timeout_seg'] ?? null) ?? 15;
    $inicio      = microtime(true);

    $errorUrl = integracion_validar_url($url);
    if ($errorUrl !== '') {
        return ['ok' => false, 'error' => $errorUrl, 'http_code' => 0, 'cuerpo' => null, 'duracion_ms' => 0];
    }

    $ch = curl_init($url);
    $cuerpo  = '';
    $abortado = false;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_HTTPGET        => true,
        CURLOPT_CONNECTTIMEOUT => defined('INTEGRACION_CONNECT_TIMEOUT') ? (int)INTEGRACION_CONNECT_TIMEOUT : 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS      => 0,
        CURLOPT_USERAGENT      => 'GobiernoDatosIA/1.0 (+integraciones)',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    // Limite de tamano real para cuerpos HTTP: callback de escritura
    // que aborta la descarga si el cuerpo supera el limite.
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$cuerpo, &$abortado, $limite) {
        $nuevoTam = strlen($cuerpo) + strlen($chunk);
        if ($nuevoTam > $limite) {
            $abortado = true;
            return 0;
        }
        $cuerpo .= $chunk;
        return strlen($chunk);
    });

    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $duracion = (int)round((microtime(true) - $inicio) * 1000);

    if ($abortado) {
        return [
            'ok' => false,
            'error' => 'La respuesta externa supero el limite de ' . $limite . ' bytes.',
            'http_code' => $code,
            'cuerpo' => null,
            'duracion_ms' => $duracion,
        ];
    }
    if ($resp === false) {
        return [
            'ok' => false,
            'error' => 'Error de conexion con la fuente externa: ' . $err,
            'http_code' => $code,
            'cuerpo' => null,
            'duracion_ms' => $duracion,
        ];
    }
    if ($code < 200 || $code >= 300) {
        return [
            'ok' => false,
            'error' => 'La fuente externa respondio con el codigo HTTP ' . $code . '.',
            'http_code' => $code,
            'cuerpo' => null,
            'duracion_ms' => $duracion,
        ];
    }

    $decodificado = json_decode($cuerpo, true);
    if (!is_array($decodificado)) {
        return [
            'ok' => false,
            'error' => 'La fuente externa no devolvio un JSON valido.',
            'http_code' => $code,
            'cuerpo' => null,
            'duracion_ms' => $duracion,
        ];
    }
    return [
        'ok' => true,
        'error' => '',
        'http_code' => $code,
        'cuerpo' => $decodificado,
        'duracion_ms' => $duracion,
    ];
}

/**
 * Quien puede gestionar una fuente: admin y steward en todas; el
 * owner solo en las vinculadas a un dataset propio; analyst no.
 * Misma regla de propiedad que los modulos de monitoreo y cumplimiento.
 */
function integracion_puede_gestionar(int $sourceId): bool
{
    $rol = currentRole();
    if (in_array($rol, ['admin', 'steward'], true)) {
        return true;
    }
    if ($rol === 'owner') {
        $stmt = db()->prepare(
            'SELECT s.id FROM external_sources s
             INNER JOIN datasets d ON d.id = s.dataset_id
             WHERE s.id = :id AND d.owner_id = :owner LIMIT 1'
        );
        $stmt->execute(['id' => $sourceId, 'owner' => currentUser()['id']]);
        return $stmt->fetch() !== false;
    }
    return false;
}

/**
 * Ejecuta 'prueba' (Probar conexion) o 'sync' (Sincronizar ahora)
 * sobre una fuente y registra la corrida en external_sync_runs.
 *
 * 'sync' ademas actualiza el espejo de la fuente (ultimo_estado,
 * ultima_ejecucion, ultimo_resumen y total_ejecuciones); 'prueba'
 * NO toca ese espejo. Ambas funcionan aunque la fuente este inactiva.
 *
 * La respuesta del feed NUNCA se guarda (ni en BD ni en bitacora);
 * solo el total de registros, los campos del primer objeto y un
 * mensaje corto.
 *
 * Devuelve: ['run_id'=>int, 'ok'=>bool, 'estado'=>string,
 *            'registros'=>int, 'metadatos'=>?array, 'mensaje'=>string,
 *            'duracion_ms'=>int]
 */
function integracion_ejecutar(int $sourceId, string $tipo): array
{
    if (!in_array($tipo, ['prueba', 'sync'], true)) {
        throw new InvalidArgumentException('Tipo de ejecucion invalido.');
    }
    $fuente = integracion_fuente($sourceId);
    if ($fuente === null) {
        throw new RuntimeException('Fuente externa no encontrada.');
    }

    $res = integracion_fetch($fuente);

    $registros = 0;
    $metadatos = null;
    if ($res['ok']) {
        $metadatos = integracion_metadatos($res['cuerpo']);
        $registros = (int)($metadatos['registros'] ?? 0);
    }

    $mensaje = $res['ok']
        ? ($tipo === 'prueba'
            ? 'Conexion exitosa' . ($registros > 0 ? ': ' . $registros . ' registros leidos.' : '.')
            : 'Sincronizacion exitosa: ' . $registros . ' registros leidos.')
        : $res['error'];

    db()->prepare(
        'INSERT INTO external_sync_runs
            (source_id, tipo_ejecucion, estado, registros_leidos, metadatos, mensaje, duracion_ms, ejecutado_por)
         VALUES (:source_id, :tipo, :estado, :registros, :metadatos, :mensaje, :duracion, :usuario)'
    )->execute([
        'source_id'  => $sourceId,
        'tipo'       => $tipo,
        'estado'     => $res['ok'] ? 'ok' : 'error',
        'registros'  => $registros,
        'metadatos'  => $metadatos !== null ? json_encode($metadatos, JSON_UNESCAPED_UNICODE) : null,
        'mensaje'    => mb_substr($mensaje, 0, 500),
        'duracion'   => $res['duracion_ms'],
        'usuario'    => currentUser()['id'],
    ]);
    $runId = (int)db()->lastInsertId();

    if ($tipo === 'sync') {
        db()->prepare(
            'UPDATE external_sources
             SET ultimo_estado = :estado, ultima_ejecucion = NOW(),
                 ultimo_resumen = :resumen, total_ejecuciones = total_ejecuciones + 1
             WHERE id = :id'
        )->execute([
            'estado'  => $res['ok'] ? 'ok' : 'error',
            'resumen' => mb_substr($mensaje, 0, 500),
            'id'      => $sourceId,
        ]);
    }

    logActivity('integracion:' . ($tipo === 'prueba' ? 'probar' : 'sincronizar'), [
        'source_id' => $sourceId,
        'estado'    => $res['ok'] ? 'ok' : 'error',
        'registros' => $registros,
    ]);

    return [
        'run_id'      => $runId,
        'ok'          => $res['ok'],
        'estado'      => $res['ok'] ? 'ok' : 'error',
        'registros'   => $registros,
        'metadatos'   => $metadatos,
        'mensaje'     => $mensaje,
        'duracion_ms' => $res['duracion_ms'],
    ];
}
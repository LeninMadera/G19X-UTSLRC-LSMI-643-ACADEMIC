<?php
/**
 * Servicio de Inteligencia Artificial.
 *
 * Dos modos:
 *   1) REAL  - si AI_API_KEY esta definida en .env, se llama a la API
 *              del proveedor configurado (OpenAI compatible: OpenAI, Groq, ...)
 *              via curl, usando AI_BASE_URL y AI_MODEL.
 *   2) SIMULADO - si no hay clave, se usan heuristicas deterministas
 *              para que el proyecto funcione y se pueda demostrar.
 *
 * La clave NUNCA sale del servidor: el frontend solo llama a /api/ai.php.
 */
require_once __DIR__ . '/functions.php';

const AI_CATEGORIAS = ['Finanzas', 'Clientes', 'Operaciones', 'Ventas', 'Recursos Humanos', 'Marketing', 'Legal', 'Otros'];

/** True si hay clave de API configurada. */
function ai_disponible(): bool
{
    return AI_API_KEY !== '';
}

/** Marca si una accion se esta ejecutando en modo simulado. */
function ai_simulado(): bool
{
    return !ai_disponible();
}

/**
 * Llama al modelo de IA (cualquier proveedor OpenAI-compatible: OpenAI, Groq, ...)
 * y devuelve el texto de la respuesta. Reintenta hasta 3 veces ante errores
 * transitorios (429/5xx), respuestas vacias o JSON mode no soportado.
 * Lanza RuntimeException ante cualquier error.
 */
function ai_llamar(string $systemPrompt, string $userPrompt, bool $json = true): string
{
    if (!ai_disponible()) {
        throw new RuntimeException('IA no configurada: define AI_API_KEY en el archivo .env.');
    }

    $url = AI_BASE_URL . '/chat/completions';
    $payloadBase = [
        'model'       => AI_MODEL,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'max_tokens'  => 1024,
        'temperature' => 0,
    ];
    if ($json) {
        $payloadBase['response_format'] = ['type' => 'json_object'];
    }

    $payload = $payloadBase;
    $maxIntentos = 3;
    for ($intento = 1; $intento <= $maxIntentos; $intento++) {
        [$code, $body] = ai_peticion($url, $payload);

        // Tolerancia: si el proveedor/modelo no soporta JSON mode, se reintenta sin el
        if ($code === 400 && $json && isset($payload['response_format'])) {
            $payload = $payloadBase;
            unset($payload['response_format']);
            continue;
        }

        $data = json_decode($body, true);
        $content = trim((string)($data['choices'][0]['message']['content'] ?? ''));

        if ($code === 200 && $content !== '') {
            return $content;
        }

        // Errores transitorios o respuestas vacias: reintentar (no sobre cargas de 4xx)
        $recuperable = in_array($code, [429, 500, 502, 503, 504], true) || ($code === 200 && $content === '');
        if ($recuperable && $intento < $maxIntentos) {
            usleep(500000 * $intento);
            continue;
        }

        if ($code !== 200) {
            $msg = $data['error']['message'] ?? 'respuesta desconocida';
            throw new RuntimeException('La API de IA respondio con el codigo ' . $code . ': ' . $msg);
        }
        throw new RuntimeException('La API de IA devolvio repetidamente una respuesta vacia.');
    }

    throw new RuntimeException('La API de IA no pudo completar la peticion.');
}

/** Ejecuta una peticion POST JSON a la API y devuelve [codigo HTTP, cuerpo]. */
function ai_peticion(string $url, array $payload): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException('Error de conexion con la API de IA: ' . $curlError);
    }
    return [$code, (string)$body];
}

/** Pide a la IA una respuesta JSON y la decodifica. */
function ai_pedir_json(string $systemPrompt, string $userPrompt): array
{
    $texto = ai_llamar($systemPrompt, $userPrompt, true);
    $json  = json_decode($texto, true);
    if (!is_array($json)) {
        throw new RuntimeException('La IA no devolvio un JSON valido.');
    }
    return $json;
}

// ============================================================
//  1) CLASIFICACION AUTOMATICA DE DATASETS
// ============================================================

function ia_clasificar(string $nombre, string $descripcion): array
{
    if (ai_simulado()) {
        return simular_clasificar($nombre, $descripcion);
    }

    $categorias = implode(', ', AI_CATEGORIAS);
    $system = 'Eres un asistente de gobierno de datos. Clasifica dataset en UNA categoria '
        . 'de la lista y propone entre 2 y 4 etiquetas cortas en espanol. '
        . 'Responde SOLO con JSON: {"categoria":"...","etiquetas":["...","..."]}';
    $user = "Categorias validas: {$categorias}.\n"
        . "Nombre del dataset: {$nombre}\nDescripcion: " . mb_substr($descripcion, 0, 500);

    $res = ai_pedir_json($system, $user);
    $categoria = (string)($res['categoria'] ?? '');
    if (!in_array($categoria, AI_CATEGORIAS, true)) {
        $categoria = 'Otros';
    }
    $etiquetas = array_values(array_filter(
        array_map('trim', (array)($res['etiquetas'] ?? [])),
        fn($t) => $t !== ''
    ));
    if (count($etiquetas) < 1) {
        $etiquetas = ['datos'];
    }

    return ['categoria' => $categoria, 'etiquetas' => array_slice($etiquetas, 0, 6)];
}

function simular_clasificar(string $nombre, string $descripcion): array
{
    $texto = mb_strtolower($nombre . ' ' . $descripcion);
    $reglas = [
        'Finanzas'            => ['finan', 'contab', 'pago', 'factura', 'gasto'],
        'Clientes'            => ['client', 'crm', 'satisfaccion', 'encuesta', 'segmento'],
        'Operaciones'         => ['inventario', 'almacen', 'stock', 'logistica', 'proveedor'],
        'Ventas'              => ['venta', 'pedido', 'comercio', 'sucursal', 'facturacion'],
        'Recursos Humanos'    => ['empleado', 'nomina', 'recurso humano', 'asistencia'],
        'Marketing'           => ['marketing', 'campaña', 'campaña', 'publicidad'],
        'Legal'               => ['legal', 'contrato', 'abogado', 'cumplimiento'],
    ];
    $categoria = 'Otros';
    $etiquetas = ['datos'];
    foreach ($reglas as $cat => $palabras) {
        foreach ($palabras as $p) {
            if (mb_strpos($texto, $p) !== false) {
                $categoria = $cat;
                $etiquetas = array_slice([$palabras[0], 'general', 'organizacional'], 0, 3);
                break 2;
            }
        }
    }
    return ['categoria' => $categoria, 'etiquetas' => array_values(array_unique($etiquetas))];
}

// ============================================================
//  2) DETECCION DE ANOMALIAS EN EL HISTORICO DE CALIDAD
// ============================================================

function ia_detectar_anomalias(array $metrics): array
{
    if (ai_simulado()) {
        return simular_anomalias($metrics);
    }
    if (count($metrics) < 1) {
        return [];
    }

    $resumen = array_map(fn($m) => [
        'fecha'    => $m['fecha_medicion'],
        'score'    => (float)$m['score'],
        'completitud'    => (float)$m['completitud'],
        'exactitud'      => (float)$m['exactitud'],
        'consistencia'   => (float)$m['consistencia'],
        'vigencia'       => (float)$m['vigencia'],
        'unicidad'       => (float)$m['unicidad'],
    ], array_slice($metrics, -15));

    $system = 'Eres un analista de calidad de datos. Dado el historial de mediciones '
        . '(porcentajes 0-100 de cinco dimensiones y el score global), detecta anomalias '
        . 'como caidas bruscas, valores por debajo del umbral o tendencias peligrosas. '
        . 'Responde SOLO con JSON: {"anomalias":[{"descripcion":"...","severidad":"baja|media|alta|critica","recomendacion":"..."}]} '
        . 'Puede devolver un array vacio si no hay anomalias.';
    $user = json_encode($resumen, JSON_UNESCAPED_UNICODE);

    $res = ai_pedir_json($system, $user);
    $anomalias = (array)($res['anomalias'] ?? []);
    $validas = ['baja', 'media', 'alta', 'critica'];
    $resultado = [];
    foreach ($anomalias as $a) {
        $sev = (string)($a['severidad'] ?? 'media');
        if (!in_array($sev, $validas, true)) {
            $sev = 'media';
        }
        $resultado[] = [
            'descripcion'    => trim((string)($a['descripcion'] ?? '')),
            'severidad'      => $sev,
            'recomendacion'  => trim((string)($a['recomendacion'] ?? '')),
        ];
    }
    return array_filter($resultado, fn($a) => $a['descripcion'] !== '');
}

function simular_anomalias(array $metrics): array
{
    $resultado = [];
    $n = count($metrics);
    if ($n === 0) {
        return $resultado;
    }

    $ultima = $metrics[$n - 1];

    // Score global bajo umbral en la ultima medicion (aplica con 1+ mediciones)
    if ((float)$ultima['score'] < 80) {
        $resultado[] = [
            'descripcion'    => 'El score global de calidad (' . $ultima['score'] . ') esta por debajo del umbral minimo de 80.',
            'severidad'      => (float)$ultima['score'] < 60 ? 'critica' : 'alta',
            'recomendacion'  => 'Investigar las causas de la baja en el score y corregir las dimensiones mas afectadas.',
        ];
    }

    if ($n < 2) {
        return $resultado;
    }

    $anterior = $metrics[$n - 2];

    $dims = ['completitud', 'exactitud', 'consistencia', 'vigencia', 'unicidad'];
    $nombres = [
        'completitud'  => 'Completitud',
        'exactitud'    => 'Exactitud',
        'consistencia' => 'Consistencia',
        'vigencia'     => 'Vigencia',
        'unicidad'     => 'Unicidad',
    ];

    // Caidas bruscas entre la ultima y la anterior medicion
    foreach ($dims as $dim) {
        $delta = (float)$ultima[$dim] - (float)$anterior[$dim];
        if ($delta <= -10) {
            $resultado[] = [
                'descripcion'    => $nombres[$dim] . ' bajo ' . abs($delta) . ' puntos entre la ultima y la medicion anterior.',
                'severidad'      => $delta <= -25 ? 'critica' : 'alta',
                'recomendacion'  => 'Revisar la causa de la caida en ' . $nombres[$dim] . ' y restablecer los procesos de carga.',
            ];
        } elseif ($delta <= -5) {
            $resultado[] = [
                'descripcion'    => $nombres[$dim] . ' presento un descenso leve de ' . abs($delta) . ' puntos.',
                'severidad'      => 'media',
                'recomendacion'  => 'Monitorear ' . $nombres[$dim] . ' en las proximas mediciones para confirmar la tendencia.',
            ];
        }
    }

    return array_slice($resultado, 0, 4);
}

/**
 * Ejecuta la deteccion de anomalias de un dataset y registra en
 * ai_detections unicamente las nuevas (deduplicando por descripcion
 * y estado pendiente). Se reutiliza desde api/ai.php y desde el
 * motor de monitoreo continuo para que ambos usen la misma logica.
 *
 * Devuelve ['detectadas' => lista de anomalias, 'insertadas' => int].
 */
function ai_ejecutar_anomalias(int $datasetId): array
{
    $stmt = db()->prepare(
        'SELECT fecha_medicion, completitud, exactitud, consistencia, vigencia, unicidad, score
         FROM quality_metrics WHERE dataset_id = :id ORDER BY fecha_medicion ASC'
    );
    $stmt->execute(['id' => $datasetId]);
    $metrics = $stmt->fetchAll();

    if (count($metrics) === 0) {
        return ['detectadas' => [], 'insertadas' => 0];
    }

    $anomalias = ia_detectar_anomalias($metrics);

    $insertadas = 0;
    foreach ($anomalias as $a) {
        $desc = mb_substr($a['descripcion'], 0, 500);
        $prev = db()->prepare(
            "SELECT id FROM ai_detections
             WHERE dataset_id = :id AND tipo = 'anomalia' AND estado = 'pendiente' AND descripcion_ia = :desc
             LIMIT 1"
        );
        $prev->execute(['id' => $datasetId, 'desc' => $desc]);
        if ($prev->fetch() !== false) {
            continue;
        }
        db()->prepare(
            'INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado)
             VALUES (:id, :tipo, :sev, :desc, :rec, :est)'
        )->execute([
            'id'    => $datasetId,
            'tipo'  => 'anomalia',
            'sev'   => $a['severidad'],
            'desc'  => $desc,
            'rec'   => mb_substr($a['recomendacion'] ?? '', 0, 500) ?: null,
            'est'   => 'pendiente',
        ]);
        $insertadas++;
    }

    return ['detectadas' => $anomalias, 'insertadas' => $insertadas];
}

// ============================================================
//  3) RESUMEN INTELIGENTE DEL ESTADO DE UN DATASET
// ============================================================

function ia_resumen(array $ctx): string
{
    if (ai_simulado()) {
        return simular_resumen($ctx);
    }

    $system = 'Eres un asistente de gobierno de datos. Responde en espanol con un parrafo '
        . 'corto (max 300 caracteres) que resuma el estado general del dataset.';
    $user = json_encode($ctx, JSON_UNESCAPED_UNICODE);

    return ai_llamar($system, $user, false);
}

function simular_resumen(array $ctx): string
{
    $score = $ctx['ultimo_score'] ?? null;
    $calidad = $score === null
        ? 'aun no tiene mediciones de calidad'
        : 'tiene un score de calidad de ' . $score;
    $acciones = ($ctx['politicas'] ?? 0);
    $pendientes = ($ctx['anomalias_pendientes'] ?? 0);
    $texto = 'El dataset "' . mb_substr($ctx['nombre'], 0, 120)
        . '" de la categoria ' . ($ctx['categoria'] ?? 'sin categoria')
        . ' ' . $calidad . '. Cumple ' . $acciones . ' politicas de gobierno y tiene '
        . $pendientes . ' anomalias pendientes de revision.';
    return mb_substr($texto, 0, 400);
}

// ============================================================
//  4) SUGERENCIAS DE MEJORA
// ============================================================

function ia_sugerencias(?array $ultima): array
{
    if ($ultima === null || $ultima === []) {
        return [];
    }
    if (ai_simulado()) {
        return simular_sugerencias($ultima);
    }

    $system = 'Eres un consultor de calidad de datos. Dada la ultima medicion (porcentajes 0-100), '
        . 'propone 1 a 3 acciones concretas de mejora priorizando las dimensiones mas bajas. '
        . 'Responde SOLO con JSON: {"sugerencias":[{"descripcion":"...","recomendacion":"..."}]}';
    $user = json_encode($ultima, JSON_UNESCAPED_UNICODE);

    $res = ai_pedir_json($system, $user);
    $sugerencias = (array)($res['sugerencias'] ?? []);
    $resultado = [];
    foreach ($sugerencias as $s) {
        $texto = trim((string)($s['recomendacion'] ?? ''));
        if ($texto !== '') {
            $resultado[] = [
                'descripcion'   => trim((string)($s['descripcion'] ?? 'Sugerencia de mejora')),
                'recomendacion' => $texto,
            ];
        }
    }
    return array_slice($resultado, 0, 3);
}

function simular_sugerencias(array $ultima): array
{
    $sugerencias = [];
    $orden = [
        ['dim' => 'vigencia',     'label' => 'Vigencia',     'accion' => 'Definir un proceso de actualizacion periodica de los datos.'],
        ['dim' => 'completitud',  'label' => 'Completitud',  'accion' => 'Revisar los campos en blanco o nulos y cargar los valores faltantes.'],
        ['dim' => 'unicidad',     'label' => 'Unicidad',     'accion' => 'Eliminar registros duplicados y crear reglas de unicidad.'],
        ['dim' => 'consistencia', 'label' => 'Consistencia', 'accion' => 'Conciliar las fuentes de origen para alinear formatos y criterios.'],
        ['dim' => 'exactitud',    'label' => 'Exactitud',    'accion' => 'Validar contra fuentes confiables y corregir valores erroneos.'],
    ];
    foreach ($orden as $item) {
        if ((float)$ultima[$item['dim']] < 85) {
            $sugerencias[] = [
                'descripcion'   => $item['label'] . ' por debajo del nivel recomendado (' . $ultima[$item['dim']] . '%).',
                'recomendacion' => $item['label'] . ' baja (' . $ultima[$item['dim']] . '%): ' . $item['accion'],
            ];
        }
    }
    return array_slice($sugerencias, 0, 3);
}
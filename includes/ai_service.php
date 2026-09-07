<?php
/**
 * Servicio de Inteligencia Artificial.
 *
 * Dos modos:
 *   1) REAL  - si AI_API_KEY esta definida en .env, se llama a la API
 *              de OpenAI (chat completions) via curl.
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
 * Llama al modelo de OpenAI y devuelve el texto de la respuesta.
 * Lanza RuntimeException ante cualquier error.
 */
function ai_llamar(string $systemPrompt, string $userPrompt, bool $json = true): string
{
    if (!ai_disponible()) {
        throw new RuntimeException('IA no configurada: define AI_API_KEY en el archivo .env.');
    }

    $url = 'https://api.openai.com/v1/chat/completions';
    $payload = [
        'model'       => AI_MODEL,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'max_tokens'  => 300,
        'temperature' => 0,
    ];
    if ($json) {
        $payload['response_format'] = ['type' => 'json_object'];
    }

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
    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new RuntimeException('Error de conexion con la API de IA: ' . $curlError);
    }
    $data = json_decode($resp, true);
    if ($httpCode !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $msg = $data['error']['message'] ?? 'respuesta desconocida';
        throw new RuntimeException('La API de IA respondio con el codigo ' . $httpCode . ': ' . $msg);
    }
    return trim((string)$data['choices'][0]['message']['content']);
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
    if (count($metrics) < 2) {
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
        . 'Responde SOLO con JSON: {"anomalias":[{"descripcion":"...","severidad":"baja|media|alta|critica"}]} '
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
            'descripcion' => trim((string)($a['descripcion'] ?? '')),
            'severidad'   => $sev,
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
            'descripcion' => 'El score global de calidad (' . $ultima['score'] . ') esta por debajo del umbral minimo de 80.',
            'severidad'   => (float)$ultima['score'] < 60 ? 'critica' : 'alta',
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
                'descripcion' => $nombres[$dim] . ' bajo ' . abs($delta) . ' puntos entre la ultima y la medicion anterior.',
                'severidad'   => $delta <= -25 ? 'critica' : 'alta',
            ];
        } elseif ($delta <= -5) {
            $resultado[] = [
                'descripcion' => $nombres[$dim] . ' presento un descenso leve de ' . abs($delta) . ' puntos.',
                'severidad'   => 'media',
            ];
        }
    }

    return array_slice($resultado, 0, 4);
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

function ia_sugerencias(array $ultima): array
{
    if (ai_simulado()) {
        return simular_sugerencias($ultima);
    }
    if ($ultima === null) {
        return [];
    }

    $system = 'Eres un consultor de calidad de datos. Dada la ultima medicion (porcentajes 0-100), '
        . 'propone 1 a 3 acciones concretas de mejora priorizando las dimensiones mas bajas. '
        . 'Responde SOLO con JSON: {"sugerencias":[{"recomendacion":"..."}]}';
    $user = json_encode($ultima, JSON_UNESCAPED_UNICODE);

    $res = ai_pedir_json($system, $user);
    $sugerencias = (array)($res['sugerencias'] ?? []);
    $resultado = [];
    foreach ($sugerencias as $s) {
        $texto = trim((string)($s['recomendacion'] ?? ''));
        if ($texto !== '') {
            $resultado[] = ['recomendacion' => $texto];
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
            $sugerencias[] = ['recomendacion' => $item['label'] . ' baja (' . $ultima[$item['dim']] . '%): ' . $item['accion']];
        }
    }
    return array_slice($sugerencias, 0, 3);
}
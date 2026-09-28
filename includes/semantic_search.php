<?php
/**
 * Busqueda semantica asistida por IA para el catalogo de datasets.
 *
 * Estrategia HIBRIDA: la busqueda literal actual (LIKE en SQL) siempre
 * es la base garantizada. La IA aporta terminos relacionados a partir de
 * la consulta en lenguaje natural y MySQL devuelve las candidatas; el
 * orden final lo calcula PHP con una puntuacion ponderada por campo.
 *
 * Si la IA no esta configurada o falla, el sistema degrada a la busqueda
 * literal ampliada. Nunca devuelve un error al usuario.
 *
 * PRIVACIDAD: al proveedor externo solo se envia el texto de la consulta
 * del usuario (recortado) y la lista de categorias validas, que es una
 * constante de codigo. Nunca se envian nombres, descripciones, etiquetas
 * ni ningun otro metadato del catalogo. La bitacora registra unicamente
 * datos operacionales: modo, simulado, fallback y cantidad de resultados.
 */
require_once __DIR__ . '/ai_service.php';

// ------------------------------------------------------------
//  Configuracion de la busqueda semantica
// ------------------------------------------------------------

/** Peso de cada campo del dataset segun cuanto aporta a la relevancia. */
const PESOS_CAMPO = [
    'nombre'        => 10,
    'etiquetas_ia'  => 8,
    'categoria'     => 6,
    'categoria_ia'  => 6,
    'descripcion'   => 4,
    'origen'        => 3,
];

/** El termino original siempre pesa mas que los expandidos por la IA. */
const FACTOR_TERMINO_ORIGINAL = 1.0;
const FACTOR_TERMINO_EXPANSION = 0.6;

/** Bonus cuando el nombre del dataset contiene la frase original completa. */
const BONUS_FRASE_ORIGINAL = 15;

/** Maximo de terminos expandidos que se aceptan de la IA. */
const MAX_TERMINOS_EXPANDIDOS = 8;

/** Maximo de terminos con los que se busca realmente en el catalogo. */
const MAX_TERMNOS_BUSQUEDA = 12;

/**
 * Palabras vacias que la IA suele incluir en sus frases y que no aportan
 * nada a la busqueda ("de" apareceria en casi todas las descripciones).
 */
const STOPWORDS = [
    'de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas',
    'y', 'o', 'u', 'e', 'en', 'con', 'por', 'para', 'que', 'se', 'su',
    'sus', 'lo', 'al', 'a', 'es', 'son', 'sobre', 'entre', 'the', 'of',
];

/** Maximo de busquedas guardadas en la cache de sesion (FIFO). */
const MAX_CACHE_BUSQUEDAS = 10;

/** Longitud maxima de la consulta antes de enviarla a la IA o al SQL. */
const MAX_QUERY = 120;

/** Campos de la tabla datasets donde se busca cada termino. */
const CAMPOS_BUSQUEDA = ['nombre', 'descripcion', 'etiquetas_ia', 'categoria', 'categoria_ia', 'origen'];

/**
 * Diccionario de sinonimos del dominio, usado por el modo simulado.
 * Clave: palabra detectada en la consulta. Valor: terminos relacionados.
 */
const SINONIMOS_DOMINIO = [
    'cliente'    => ['crm', 'contacto', 'segmento', 'consumidor', 'cartera', 'comprador'],
    'clientes'   => ['crm', 'contacto', 'segmento', 'consumidor', 'cartera', 'comprador'],
    'usuario'    => ['cliente', 'consumidor', 'contacto'],
    'persona'    => ['cliente', 'contacto', 'empleado'],
    'venta'      => ['facturacion', 'ingreso', 'comercial', 'pedido', 'sucursal', 'factura'],
    'ventas'     => ['facturacion', 'ingreso', 'comercial', 'pedido', 'sucursal', 'factura'],
    'cobro'      => ['factura', 'pago', 'facturacion'],
    'ingreso'    => ['venta', 'facturacion', 'utilidad'],
    'inventario' => ['stock', 'almacen', 'producto', 'existencias', 'deposito'],
    'producto'   => ['inventario', 'stock', 'catalogo', 'sku'],
    'stock'      => ['inventario', 'almacen', 'existencias'],
    'almacen'    => ['inventario', 'stock', 'deposito', 'bodega'],
    'satisfaccion' => ['encuesta', 'opinion', 'atencion', 'experiencia', 'nps', 'cliente'],
    'encuesta'   => ['satisfaccion', 'opinion', 'atencion', 'experiencia'],
    'calidad'    => ['score', 'medicion', 'exactitud', 'completitud', 'dato'],
    'personal'   => ['empleado', 'nomina', 'asistencia', 'recurso', 'humano', 'rrhh'],
    'empleado'   => ['nomina', 'personal', 'asistencia', 'recurso'],
    'nomina'     => ['empleado', 'personal', 'salario', 'pago'],
    'marketing'  => ['campana', 'publicidad', 'cliente', 'segmento', 'conversion'],
    'campana'    => ['marketing', 'publicidad', 'conversion'],
    'legal'      => ['contrato', 'abogado', 'cumplimiento', 'normativa', 'litigio'],
    'contrato'   => ['legal', 'proveedor', 'clausula', 'abogado'],
    'proveedor'  => ['contrato', 'inventario', 'abastecimiento', 'compra'],
    'finanzas'   => ['venta', 'factura', 'ingreso', 'pago', 'presupuesto'],
    'presupuesto' => ['finanzas', 'gasto', 'ingreso'],
    'operacion'  => ['inventario', 'almacen', 'logistica', 'pedido', 'inventario'],
    'logistica'  => ['almacen', 'pedido', 'envio', 'inventario', 'transporte'],
    'operaciones' => ['inventario', 'almacen', 'logistica', 'pedido'],
];

// ------------------------------------------------------------
//  Utilidades de texto
// ------------------------------------------------------------

/**
 * Quita acentos y diacriticos. Los caracteres se escriben como escapes
 * \x{} dentro de las expresiones regulares para que el fuente siga
 * siendo ASCII puro y no dependa de la codificacion con la que se lea.
 */
function sem_sin_acentos(string $texto): string
{
    static $grupos = [
        'a' => '\x{e1}\x{e0}\x{e4}\x{e2}\x{c1}\x{c0}\x{c4}\x{c2}',
        'e' => '\x{e9}\x{e8}\x{eb}\x{ea}\x{c9}\x{c8}\x{cb}\x{ca}',
        'i' => '\x{ed}\x{ec}\x{ef}\x{ee}\x{cd}\x{cc}\x{cf}\x{ce}',
        'o' => '\x{f3}\x{f2}\x{f6}\x{f4}\x{d3}\x{d2}\x{d6}\x{d4}',
        'u' => '\x{fa}\x{f9}\x{fc}\x{fb}\x{da}\x{d9}\x{dc}\x{db}',
        'n' => '\x{f1}\x{d1}',
        'c' => '\x{e7}\x{c7}',
        'y' => '\x{fd}\x{ff}',
    ];
    foreach ($grupos as $letra => $clase) {
        $texto = (string)preg_replace('/[' . $clase . ']/u', $letra, $texto);
    }
    return $texto;
}

/**
 * Normaliza texto para comparar y para la cache: minusculas, sin
 * acentos y sin signos de puntuacion. No es un stemmer completo.
 */
function sem_normalizar(string $texto): string
{
    $texto = sem_sin_acentos(mb_strtolower(trim($texto)));
    $texto = preg_replace('/[^a-z0-9\s]+/u', ' ', $texto);
    $texto = preg_replace('/\s+/u', ' ', (string)$texto);
    return trim($texto);
}

/** Normaliza un termino: minusculas, sin acentos y sin espacios sobrantes. */
function sem_termino(string $termino): string
{
    return preg_replace('/\s+/u', ' ', sem_normalizar($termino));
}

// ------------------------------------------------------------
//  1) EXPANSION DE LA CONSULTA CON IA
// ------------------------------------------------------------

/**
 * Interpreta la consulta en lenguaje natural y devuelve los terminos
 * relacionados con los que se buscara en el catalogo.
 *
 * @return array{original:string, otros:string[], categoria:?string, interpretacion:string, simulado:bool}
 * @throws RuntimeException si el proveedor falla o devuelve algo inutil
 */
function sem_expandir_consulta(string $consulta, array $categorias): array
{
    if (ai_simulado()) {
        return sem_simular_expansion($consulta);
    }

    $listaCategorias = implode(', ', $categorias);
    $system = 'Eres un asistente de catalogo de datos. Tu tarea es expandir una consulta de '
        . 'busqueda en lenguaje natural para recuperar datasets relacionados por significado. '
        . 'Devuelve terminos cortos en espanol (1 a 3 palabras), en minusculas y sin acentos, '
        . 'que probablemente aparezcan en los nombres, descripciones o etiquetas de los datasets. '
        . 'Reglas: incluye tambien el termino que el usuario escribio literalmente, '
        . 'no inventes productos ni categorias que no existan, y como maximo 6 terminos. '
        . 'Responde SOLO con JSON: {"terminos":["..."],"categoria":"...","interpretacion":"..."}. '
        . 'El campo categoria debe ser una de la lista de categorias validas, o cadena vacia si '
        . 'ninguna encaja. Interpretacion es una frase breve de porque se expandio la busqueda.';
    $user = "Categorias validas del catalogo: {$listaCategorias}\nConsulta del usuario: {$consulta}";

    // La IA solo recibe el texto de la consulta: ningun dato del catalogo
    $res = ai_pedir_json($system, $user);

    if (!is_array($res)) {
        throw new RuntimeException('La IA no devolvio una expansion valida.');
    }

    // El termino original se antepone y las categorias validas se filtran
    $original = sem_termino($consulta);
    $otros = [];
    foreach ((array)($res['terminos'] ?? []) as $t) {
        $t = sem_termino((string)$t);
        if ($t !== '' && $t !== $original) {
            $otros[] = $t;
        }
    }
    $otros = array_slice(array_values(array_unique($otros)), 0, MAX_TERMINOS_EXPANDIDOS);

    $categoria = trim((string)($res['categoria'] ?? ''));
    if (!in_array($categoria, $categorias, true)) {
        $categoria = null;
    }
    $interpretacion = mb_substr(trim((string)($res['interpretacion'] ?? '')), 0, 200);

    // Si la IA no aporta nada utilizable, se considera fallo
    if (count($otros) === 0 && $categoria === null && $interpretacion === '') {
        throw new RuntimeException('La IA no devolvio terminos de busqueda.');
    }

    return [
        'original'       => $original,
        'otros'          => $otros,
        'categoria'      => $categoria,
        'interpretacion' => $interpretacion,
        'simulado'       => false,
    ];
}

/**
 * Expansion sin IA, con el diccionario de sinonimos del dominio y con
 * las palabras de la consulta. Permite demostrar la funcion sin clave.
 */
function sem_simular_expansion(string $consulta): array
{
    $original = sem_termino($consulta);
    $palabras = preg_split('/\s+/u', $original) ?: [];

    $otros = [];
    foreach ($palabras as $p) {
        if (isset(SINONIMOS_DOMINIO[$p])) {
            foreach (SINONIMOS_DOMINIO[$p] as $s) {
                $otros[] = $s;
            }
        }
        // Forma singular para un termino plural
        $singular = rtrim($p, 's');
        if (isset(SINONIMOS_DOMINIO[$singular])) {
            foreach (SINONIMOS_DOMINIO[$singular] as $s) {
                $otros[] = $s;
            }
        }
    }

    return [
        'original'       => $original,
        'otros'          => array_slice(array_values(array_unique($otros)), 0, MAX_TERMINOS_EXPANDIDOS),
        'categoria'      => null,
        'interpretacion' => 'Expansion local por diccionario de sinonimos (sin IA).',
        'simulado'       => true,
    ];
}

// ------------------------------------------------------------
//  2) LISTA DE TERMINOS DE BUSQUEDA
// ------------------------------------------------------------

/**
 * Construye la lista de terminos con los que se busca en el catalogo.
 *
 * La IA puede devolver frases largas ("segmentacion de clientes") que
 * nunca aparecen tal cual en un dataset, asi que cada frase se parte
 * en palabras individuales. Se conservan:
 *   - la frase original completa (para el bonus de coincidencia exacta),
 *   - sus palabras significativas, que pesan igual que el original,
 *   - las palabras de los terminos expandidos, que pesan menos.
 *
 * Los terminos ya repetidos se descartan y el total se limita a
 * MAX_TERMNOS_BUSQUEDA.
 */
function sem_lista_terminos(array $expansion): array
{
    $lista = [];

    $agregar = function (string $texto, bool $original) use (&$lista): void {
        $texto = sem_termino($texto);
        if ($texto === '' || mb_strlen($texto) < 3) {
            return;
        }
        foreach ($lista as $t) {
            if ($t['texto'] === $texto) {
                return;
            }
        }
        $lista[] = ['texto' => $texto, 'etiqueta' => $texto, 'original' => $original];
    };

    // El original manda: primero la frase completa, luego sus palabras
    $agregar($expansion['original'], true);
    foreach (preg_split('/\s+/u', $expansion['original']) ?: [] as $palabra) {
        if (!in_array(sem_termino($palabra), STOPWORDS, true)) {
            $agregar($palabra, true);
        }
    }

    // Despues las palabras de los terminos expandidos por la IA
    foreach ($expansion['otros'] as $otro) {
        foreach (preg_split('/\s+/u', sem_termino($otro)) ?: [] as $palabra) {
            if (!in_array(sem_termino($palabra), STOPWORDS, true)) {
                $agregar($palabra, false);
            }
        }
    }

    return array_slice($lista, 0, MAX_TERMNOS_BUSQUEDA);
}

// ------------------------------------------------------------
//  3) CACHE DE SESION (FIFO, maximo 10 busquedas)
// ------------------------------------------------------------

/** Devuelve la expansion guardada en sesion, o null si no existe. */
function sem_cache_leer(string $clave): ?array
{
    if (!isset($_SESSION['busqueda_semantica'][$clave])) {
        return null;
    }
    $item = $_SESSION['busqueda_semantica'][$clave];
    return is_array($item) ? $item : null;
}

/**
 * Guarda la expansion en la cache de sesion. Al superar el limite se
 * elimina la entrada mas antigua (FIFO). Nada de esto llega a MySQL ni
 * a la bitacora de actividad: vive solo en la sesion del usuario.
 */
function sem_cache_guardar(string $clave, array $item): void
{
    if (!isset($_SESSION['busqueda_semantica']) || !is_array($_SESSION['busqueda_semantica'])) {
        $_SESSION['busqueda_semantica'] = [];
    }
    // Al borrar y reasignar, la entrada queda como la mas reciente
    unset($_SESSION['busqueda_semantica'][$clave]);
    $_SESSION['busqueda_semantica'][$clave] = $item;

    while (count($_SESSION['busqueda_semantica']) > MAX_CACHE_BUSQUEDAS) {
        array_shift($_SESSION['busqueda_semantica']);
    }
}

// ------------------------------------------------------------
//  4) ORDEN POR RELEVANCIA
// ------------------------------------------------------------

/**
 * Calcula la relevancia de cada dataset a partir de los terminos de la
 * consulta y sus campos de texto. Devuelve los datasets ordenados por
 * puntos descendentes, con los motivos y campos que hicieron match.
 */
function sem_puntuar(array $datasets, array $terminos): array
{
    $original = $terminos['original'];
    $factorOriginal = $terminos['original_factor'];

    // Precalcula el texto buscable de cada dataset
    foreach ($datasets as $i => $d) {
        $campos = [];
        foreach (CAMPOS_BUSQUEDA as $campo) {
            $valor = (string)($d[$campo] ?? '');
            if ($valor !== '') {
                $campos[$campo] = sem_normalizar($valor);
            }
        }
        $datasets[$i]['_campos'] = $campos;
    }

    foreach ($datasets as $i => $d) {
        $puntos = 0;
        $motivos = [];
        $camposMatched = [];

        foreach ($terminos['todos'] as $t) {
            $texto = $t['texto'];
            $factor = $t['original'] ? $factorOriginal : FACTOR_TERMINO_EXPANSION;
            $porCampo = 0;
            $nombreCoincide = false;

            foreach ($d['_campos'] as $campo => $valorNormalizado) {
                if ($valorNormalizado === '') {
                    continue;
                }
                if (strpos($valorNormalizado, $texto) !== false) {
                    $porCampo += PESOS_CAMPO[$campo] ?? 0;
                    $camposMatched[$campo] = true;
                    if ($campo === 'nombre') {
                        $nombreCoincide = true;
                    }
                }
            }

            if ($porCampo > 0) {
                $puntos += (int) round($porCampo * $factor);
                $motivos[] = $t['etiqueta'];

                // Bonus si el nombre del dataset contiene la frase original
                if ($t['original'] && $original !== '' && $nombreCoincide) {
                    $puntos += BONUS_FRASE_ORIGINAL;
                }
            }
        }

        $datasets[$i]['puntos'] = $puntos;
        $datasets[$i]['motivos'] = array_values(array_unique($motivos));
        $datasets[$i]['campos'] = array_keys($camposMatched);
        unset($datasets[$i]['_campos']);
    }

    // Ordena por puntos y, a igualdad, por fecha de actualizacion
    usort($datasets, function ($a, $b) {
        if ($a['puntos'] !== $b['puntos']) {
            return $b['puntos'] <=> $a['puntos'];
        }
        return strcmp((string)$b['fecha_actualizacion'], (string)$a['fecha_actualizacion']);
    });

    return $datasets;
}

/**
 * Normaliza el maximo de puntos posible (para convertir a 0-100) con
 * los terminos de la consulta y el peso maximo de campo.
 */
function sem_maximo_puntaje(array $terminos): int
{
    $pesoMax = max(PESOS_CAMPO);
    $total = 0;
    foreach ($terminos['todos'] as $t) {
        $factor = $t['original'] ? $terminos['original_factor'] : FACTOR_TERMINO_EXPANSION;
        $total += (int) round($pesoMax * $factor);
    }
    return $total + BONUS_FRASE_ORIGINAL;
}

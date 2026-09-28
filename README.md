# Plataforma de Gobierno de Datos con Inteligencia Artificial

Proyecto universitario: plataforma web para **catalogar datos, medir su calidad y aplicar politicas de gobierno**, con asistencia de **Inteligencia Artificial** (clasificacion automatica, deteccion de anomalias, resumen y sugerencias de mejora).

La aplicacion se construyo por etapas (E0 a E8) y cada etapa se probo de forma manual de extremo a extremo (E2E) contra la base de datos real.

## Stack y herramientas

| Tecnologia | Uso |
|---|---|
| PHP 8.x (puro, sin frameworks) | Logica del servidor |
| PDO + consultas preparadas | Acceso a datos (anti SQL Injection) |
| MySQL 8 | Base de datos |
| HTML5, CSS3, JavaScript vanilla | Interfaz |
| API REST + JSON | Comunicacion front/back |
| API de OpenAI | Funciones de IA (`AI_API_KEY`) |
| Git / GitHub | Control de versiones |
| Postman | Pruebas de la API |
| VS Code | Editor |

## Estructura del proyecto

```
gobierno-datos-ia/
├── api/               Endpoints REST (JSON): auth, users, datasets, quality,
│                      policies, compliance, lineage, ai, reports
├── config/            Carga de .env, constantes, conexion PDO
├── includes/          Funciones (auth, rol, log, JSON), servicio de IA
│                      y busqueda semantica (semantic_search.php)
├── views/             Paginas HTML (login, dashboard, usuarios, datasets,
│                      calidad, politicas, cumplimiento, linaje, IA, reportes)
├── assets/            CSS, JavaScript (vanilla, sin librerias)
├── sql/               database.sql (estructura), migration_lineage.sql
│                      y migration_compliance.sql (migraciones por modulo)
│                      y seed.sql
├── postman/           Coleccion y entorno de Postman
├── .env               Credenciales locales (NO se sube a GitHub)
└── index.php          Punto de entrada (redirige al login/dashboard)
```

## Requisitos

- PHP 8.x con extensiones `pdo_mysql`, `curl`, `mbstring`
- MySQL 8 (el esquema usa `caching_sha2_password`; asegurate de usar un cliente compatible, por ejemplo PHP/PDO o MySQL Workbench)
- Navegador moderno y, opcionalmente, Postman

## Instalacion

1. Descarga o clona el proyecto (por ejemplo en `htdocs` de XAMPP o en cualquier carpeta).
2. Crea la base de datos y las tablas con `sql/database.sql` y carga los datos iniciales con `sql/seed.sql`.
   Puedes importarlos desde PHP (compatible con MySQL 8) con un script temporal:

   ```php
   $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', 'TU_PASSWORD');
   $sql = file_get_contents(__DIR__ . '/sql/database.sql');
   $pdo->exec($sql);                    // crea BD y tablas
   $pdo->exec(file_get_contents(__DIR__ . '/sql/seed.sql')); // datos iniciales
   ```

3. Copia `.env.example` a `.env` y completa tus credenciales (MySQL, URL base, APP_KEY, opcionalmente la clave de OpenAI).
4. Ejecuta el servidor de desarrollo:

   ```
   php -S 127.0.0.1:8000 -t .
   ```

   (Tambien funciona bajo Apache: colocalo en `htdocs` y abre `http://localhost/gobierno-datos-ia`.)

5. Abre `http://127.0.0.1:8000` en el navegador e inicia sesion.

## Usuarios de prueba (seed)

| Rol | Email | Contrasena |
|---|---|---|
| admin | admin@example.com | `Admin123!` |
| steward | steward@example.com | `Steward123!` |
| owner | owner@example.com | `Owner123!` |
| analyst | analyst@example.com | `Analyst123!` |

## Roles y permisos

| Modulo | admin | steward | owner | analyst |
|---|---|---|---|---|
| Datasets | CRUD todos | CRUD todos | CRUD solo los suyos | Solo lectura |
| Calidad | Registra/elimina | Registra/elimina | Solo los suyos | Solo lectura |
| Politicas | CRUD + asignacion | CRUD + asignacion | — | Solo lectura |
| Cumplimiento | CRUD controles + evalua | CRUD controles + evalua | Evalua solo sus datasets | Solo lectura |
| Linaje de datos | CRUD | CRUD | Solo entre sus datasets | Solo lectura |
| Usuarios | CRUD | — | — | — |
| IA (4 acciones) | Si | Si | Solo sus datasets | No |
| Reportes / dashboard | Completo (bitacora) | Completo | Completo | Completo |
| Bitacora de actividad | Si (todas) | No | No | No |

## Funciones por modulo

- **Dashboard**: KPIs (datasets, calidad promedio, alertas pendientes, politicas activas), grafico de score por dataset en canvas puro, distribucion por categoria, alertas IA y actividad reciente.
- **Datasets**: catalogo con busqueda literal y **busqueda semantica asistida por IA**, filtro por categoria, ficha de detalle (score, dimensiones, politicas asignadas, detectores de IA, etiquetas IA).
- **Calidad**: registros de mediciones por dataset, calculo de score ponderado (5 dimensiones) y alerta visual cuando el score baja de 80.
- **Politicas**: CRUD de politicas (naming, retencion, acceso, clasificacion) y asignacion/desasignacion a datasets.
- **Cumplimiento**: catalogo de controles normativos (con norma/marco/categoria de referencia, severidad y politica vinculada) y evaluaciones por dataset (`cumple`, `no cumple`, `pendiente`, `no aplica`). Registro declarativo interno, **no es un dictamen legal**.
- **Linaje de datos**: registro de relaciones `dataset origen -> dataset destino` con 6 tipos (`derivado_de`, `alimenta`, `integra`, `transforma`, `copia`, `version_de`). La ficha de cada dataset muestra de que datasets recibe y hacia que datasets envia informacion.
- **IA**: clasificar dataset, detectar anomalias, resumen inteligente y sugerencias de mejora.
- **Usuarios**: administracion de usuarios y roles (solo admin).
- **Reportes**: estado de calidad por dataset, alertas/recomendaciones de IA y bitacora de actividad.

## Modelo de calidad de datos

El score se calcula con una **media ponderada de 5 dimensiones** (0-100):

| Dimension | Peso |
|---|---|
| Completitud | 20% |
| Exactitud | 25% |
| Consistencia | 20% |
| Vigencia | 15% |
| Unicidad | 20% |

`score = completitud·0.20 + exactitud·0.25 + consistencia·0.20 + vigencia·0.15 + unicidad·0.20`

Niveles: **>=85 Excelente**, **70–84 Aceptable**, **<70 Por mejorar**, y un registro se marca **"bajo umbral"** cuando el score es menor a 80.

## Inteligencia Artificial

El servicio vive en `includes/ai_service.php` y se expone por `api/ai.php`.

- **Conexion real**: agrega tu clave de API en `.env` (`AI_API_KEY`). El modelo se configura con `AI_MODEL` y el proveedor con `AI_BASE_URL`. La clave **nunca llega al navegador**; toda la comunicacion ocurre en el servidor.
- **Proveedores**: el sistema acepta cualquier API de chat compatible con el formato de OpenAI (`/v1/chat/completions`). Por defecto usa OpenAI (`https://api.openai.com/v1`), pero puedes usar **Groq** (capa gratuita):
  1. Crea una cuenta en la consola de Groq y genera una API key gratuita.
  2. Configura tu `.env` (usa un modelo que soporte JSON mode; en esta cuenta el recomendado es `openai/gpt-oss-20b`):
     ```
     AI_API_KEY=gsk_tu_clave_de_groq
     AI_MODEL=openai/gpt-oss-20b
     AI_BASE_URL=https://api.groq.com/openai/v1
     ```
     Para ver los modelos disponibles en tu cuenta: `GET https://api.groq.com/openai/v1/models`.
  3. Si el modelo elegido no soporta JSON mode, el servicio reintenta la peticion sin el automaticamente.
- **Conexion simulada**: si `AI_API_KEY` esta vacia, el modulo opera en **modo simulado** usando heuristicas locales deterministas (misma interfaz y mismos efectos), ideal para demostrar el proyecto sin consumir tokens. El panel de IA lo indica con un aviso.

Acciones disponibles (con resultados guardados en la tabla `ai_detections` y/o `categoria_ia`/`etiquetas_ia` del dataset):

| Accion | Que hace |
|---|---|
| `classify` | Sugiere categoria y etiquetas segun nombre y descripcion |
| `anomalies` | Analiza el historial de calidad y detecta caidas o scores bajo umbral |
| `summary` | Genera un resumen en lenguaje natural del estado del dataset |
| `suggest` | Propone acciones de mejora segun las dimensiones debiles |

## Búsqueda semántica asistida por IA

Permite encontrar datasets por significado y no solo por coincidencia literal de palabras. Vive en `includes/semantic_search.php` y se expone con `api/datasets.php?action=search`.

### Cómo funciona

Es una búsqueda **híbrida**, no un reemplazo de la búsqueda normal:

1. **Expansión de la consulta**: la IA recibe el texto escrito por el usuario y devuelve términos relacionados (`{"terminos":[...],"categoria":"...","interpretacion":"..."}`).
2. **Candidatas en SQL**: se busca cada término (el original más los expandidos) en `nombre`, `descripcion`, `etiquetas_ia`, `categoria`, `categoria_ia` y `origen`.
3. **Ranking en PHP**: cada dataset recibe una puntuación ponderada por campo y se ordena por relevancia, con desempate por `fecha_actualizacion`.

### Puntuación

| Campo | Peso |  | Término | Multiplicador |
|---|---|---|---|---|
| `nombre` | 10 | | original (×1.0) | peso completo |
| `etiquetas_ia` | 8 | | expandidos | ×0.6 |
| `categoria` / `categoria_ia` | 6 | | frase original completa en el nombre | +15 |
| `descripcion` | 4 | | | |
| `origen` | 3 | | | |

La API devuelve `puntos` (valor bruto, usado para ordenar), `relevancia` (0-100, se muestra en la tabla) y `motivos` (términos y campos que coincidieron, para que el usuario vea *por qué* aparece cada resultado).

### Uso

En el catálogo, **pulsar Enter o el botón "Buscar con IA"**. Escribir sigue usando la búsqueda literal con su retardo de 300 ms. El botón "Usar búsqueda normal" vuelve al listado literal.

La búsqueda literal de `action=list` y `action=export` también se amplió a los mismos seis campos.

### Modos y respaldo

| Situación | Comportamiento |
|---|---|
| `AI_API_KEY` vacía | **Modo simulado**: diccionario local de sinónimos del dominio, la interfaz lo indica |
| IA devuelve error, timeout o JSON inválido | `fallback`: se degrada a la búsqueda literal con el término original, sin error visible |
| Sin resultados | Mensaje "Sin coincidencias para esta busqueda" |

En ningún caso la búsqueda semántica deja al usuario sin resultados que la búsqueda literal habría encontrado.

### Privacidad

- Al proveedor externo **solo** sale el texto de la consulta (máximo 120 caracteres) y la lista de categorías válida, que es una constante de código. **Nunca** salen nombres, descripciones, etiquetas, propietarios ni otros metadatos del catálogo.
- La **bitácora** registra únicamente datos operacionales: `modo`, `simulado`, `fallback` y `resultados`. No guarda ni la consulta ni los términos expandidos.
- Las expansiones se cachean en `$_SESSION` para no repetir llamadas al proveedor: máximo **10 búsquedas por sesión**, con eliminación **FIFO** de la más antigua. Esa caché no se escribe en MySQL ni en la bitácora.
- No requiere cambios en la base de datos.

### Limitaciones

- Es recuperación semántica **basada en términos expandidos**, no en embeddings: si un dataset no comparte ninguna palabra con esos términos, no aparece.
- La base de datos usa `utf8mb4_unicode_ci`, así que el `LIKE` ya ignora mayúsculas y acentos; los términos se comparan normalizados (minúsculas y sin tildes).
- La latencia de la llamada al proveedor es de 1-3 s, por eso solo se dispara de forma explícita.

## API REST

Convencion: `api/<modulo>.php?action=<accion>`. Respuestas siempre en JSON:

```json
{ "success": true, "data": { }, "message": "..." }
```

Autenticacion por **cookie de sesion** (`GDIA_SESSION`): primero se llama al login y las siguientes peticiones usan la cookie.

| Modulo | Acciones |
|---|---|
| `auth.php` | `login`, `logout`, `session` |
| `users.php` | `list`, `get`, `create`, `update`, `delete` (admin) |
| `datasets.php` | `list` (con `search`, `categoria`), `search` (busqueda semantica con `q`, `categoria`), `detail`, `create`, `update`, `delete` |
| `quality.php` | `history`, `register`, `delete` |
| `policies.php` | `list`, `get`, `create`, `update`, `delete`, `assign`, `unassign` |
| `compliance.php` | `list`, `get`, `create`, `update`, `delete`, `evaluate`, `dataset_status` |
| `lineage.php` | `list` (con `dataset_id`), `get`, `create`, `update`, `delete` |
| `ai.php` | `status`, `classify`, `anomalies`, `summary`, `suggest`, `resolve`, `confirm_classify` |
| `reports.php` | `overview`, `alerts`, `logs` (solo admin) |

Ejemplo con curl (o PowerShell):

```
POST /api/auth.php?action=login      {"email":"admin@example.com","password":"Admin123!"}
GET  /api/datasets.php?action=detail&id=1
POST /api/quality.php?action=register {"dataset_id":1,"completitud":92,"exactitud":88.5,...}
POST /api/ai.php?action=classify      {"dataset_id":1}
POST /api/lineage.php?action=create   {"origen_id":2,"destino_id":1,"tipo":"alimenta"}
POST /api/compliance.php?action=create   {"nombre":"Acceso minimo","referencia":"ISO 27001","severidad":"alta","policy_id":3}
POST /api/compliance.php?action=evaluate {"control_id":1,"dataset_id":2,"estado":"cumple"}
GET  /api/compliance.php?action=dataset_status&dataset_id=2
GET  /api/reports.php?action=overview
```

## Postman

En `postman/` hay una coleccion lista para importar:

1. Abre Postman -> Import -> selecciona `GobiernoDatosIA.postman_collection.json`.
2. Importa tambien el entorno `GobiernoDatosIA.postman_environment.json` y activalo.
3. Ajusta la variable `baseUrl` a tu servidor.
4. Ejecuta primero **Auth > Login** (guarda la cookie de sesion) y despues cualquier otro request.

## Base de datos

11 tablas: `roles`, `users`, `datasets`, `quality_metrics`, `policies`, `policy_datasets`, `data_lineage`, `compliance_controls`, `compliance_evaluations`, `ai_detections` y `activity_logs`, con llaves foraneas, restricciones `CHECK` e indice para busquedas. El detalle completo esta en `sql/database.sql`.

Si tu base de datos ya existe y solo le falta un modulo, ejecuta la migracion correspondiente, que crea unicamente sus tablas con sus constraints, sin datos y sin tocar ninguna tabla previa (idempotente, se puede reejecutar):
- `sql/migration_lineage.sql`: crea `data_lineage`.
- `sql/migration_compliance.sql`: crea `compliance_controls` y `compliance_evaluations`.

Los datos de ejemplo estan en `sql/seed.sql` y solo aplican a bases nuevas.

## Seguridad aplicada

- Contrasenas con `password_hash()` (bcrypt) y sesiones con `session_regenerate_id()`
- Consultas preparadas en todo el acceso a datos (anti SQL Injection)
- Salida escapada en PHP (`e()`) y en JavaScript (funcion `esc()`) antes de insertar en el DOM (anti XSS)
- Control de roles por sesion en cada endpoint (`requireRole`)
- Claves de API solo en el servidor (`.env`), nunca en el navegador
- Cabeceras de seguridad (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) y cookies `HttpOnly` + `SameSite`
- Errores de produccion ocultos (`APP_DEBUG=0`); el JS tolera fallos de red sin romper la interfaz
- Bitacora de actividad de los movimientos del sistema

## Pruebas realizadas

Cada etapa se verifico con pruebas E2E sobre la BD real: autenticacion (Etapa 1), usuarios (2), datasets (3), calidad (4), politicas (5), IA en modo simulado (6), reportes/dashboard (7), cierre de seguridad (8), linaje de datos (9) y cumplimiento normativo (10). La coleccion de Postman permite re-ejecutar la API completa manualmente.

## Nota sobre credenciales

El archivo `.env` con tus claves esta excluido de Git. Nunca lo subas a GitHub. Genera `APP_KEY` con:

```
php -r "echo bin2hex(random_bytes(16));"
```

## Referencias oficiales

- Documentacion de PHP y PDO: <https://www.php.net/docs.php>
- MySQL: <https://dev.mysql.com/doc/>
- JavaScript y DOM (MDN): <https://developer.mozilla.org/>
- Postman: <https://learning.postman.com/>
- API de OpenAI: <https://platform.openai.com/docs/>
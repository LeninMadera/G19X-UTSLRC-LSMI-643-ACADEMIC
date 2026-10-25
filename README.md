# Plataforma de Gobierno de Datos con Inteligencia Artificial

Proyecto universitario: plataforma web para **catalogar datos, medir su calidad y aplicar politicas de gobierno**, con asistencia de **Inteligencia Artificial** (clasificacion automatica, deteccion de anomalias, resumen y sugerencias de mejora).

La aplicación se construyó por etapas hasta la Etapa 12, y cada una se probó de forma manual de extremo a extremo (E2E) contra la base de datos real.

## Inicio rápido

Resumen corto para ejecutar el proyecto:

1. Ten listo **PHP 8.x** con las extensiones `pdo_mysql`, `curl` y `mbstring`, y **MySQL 8**.
2. Ejecuta `sql/database.sql` (crea la base de datos y las tablas) y después `sql/seed.sql` (datos de ejemplo).
3. Copia `.env.example` a `.env`.
4. Edita el `.env` y completa `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` con los datos de tu MySQL.
5. Ejecuta el servidor desde la raíz del proyecto:

   ```
   php -S 127.0.0.1:8000 -t .
   ```

   En Windows también puedes usar `iniciar.bat`, que verifica PHP, levanta el servidor y abre el navegador.
6. Abre `http://127.0.0.1:8000` en el navegador e inicia sesión.
7. Entra con cualquiera de las cuentas de prueba documentadas más abajo en [Usuarios de prueba (seed)](#usuarios-de-prueba-seed).
8. `AI_API_KEY` puede quedar vacía: el sistema funciona igual en **modo simulado**.

Para instrucciones detalladas, consultar la sección Instalación de este README y
`docs/MANUAL-TECNICO-Plataforma-Gobierno-Datos-IA.md`.

## Stack y herramientas

| Tecnologia | Uso |
|---|---|
| PHP 8.x (puro, sin frameworks) | Logica del servidor |
| PDO + consultas preparadas | Acceso a datos (anti SQL Injection) |
| MySQL 8 | Base de datos |
| HTML5, CSS3, JavaScript vanilla | Interfaz |
| API REST + JSON | Comunicacion front/back |
| API de chat compatible con OpenAI (OpenAI, Groq, ...) | Funciones de IA (`AI_BASE_URL`, `AI_MODEL`, `AI_API_KEY`) |
| Git / GitHub | Control de versiones |
| Postman | Pruebas de la API |
| VS Code | Editor |

## Estructura del proyecto

```
gobierno-datos-ia/
├── api/               Endpoints REST (JSON): auth, users, datasets, quality,
│                      policies, compliance, lineage, monitoring,
│                      integrations, ai, reports
├── assets/            CSS, JavaScript (vanilla, sin librerias)
├── cli/               Monitor por linea de comandos: monitor.php (--list,
│                      --run-id) y monitor.bat (Programador de tareas de Windows)
├── config/            Carga de .env, constantes, conexion PDO
├── docs/              Documentación formal del proyecto: PRD, MVP,
│                      requerimientos, propuesta técnica, arquitectura, plan
│                      del proyecto, reporte QA, manual técnico, plan de
│                      mantenimiento, acta de capacitación e informe final
├── includes/          Funciones (auth, rol, log, JSON), servicio de IA
│                      (ai_service.php), busqueda semantica
│                      (semantic_search.php), monitoreo (monitoring_service.php)
│                      e integraciones (integrations_service.php)
├── views/             Paginas HTML (login, dashboard, usuarios, datasets,
│                      detalle de dataset, calidad, politicas, cumplimiento,
│                      linaje, IA, reportes, monitoreo, fuentes externas) e
│                      includes/ con el header y el footer comunes
├── sql/               database.sql (estructura), seed.sql (datos de ejemplo)
│                      y las 4 migraciones por modulo: migration_lineage.sql,
│                      migration_compliance.sql, migration_monitoring.sql y
│                      migration_integrations.sql
├── postman/           Coleccion y entorno de Postman
├── iniciar.bat        Inicio rapido en Windows: verifica PHP, levanta el
│                      servidor y abre el navegador
├── jsconfig.json      Ajustes del editor para JavaScript
├── .env.example       Plantilla de configuracion (SI se entrega, sin secretos)
├── .gitignore         Exclusiones de Git (entre ellas, .env)
├── README.md          Este documento
└── index.php          Punto de entrada (redirige al login/dashboard)
```

> `.env` no aparece en la estructura porque es un archivo **local y personal**:
> no se versiona, no se entrega y no debe compartirse. Ver
> [Configuracion local y secretos](#configuracion-local-y-secretos).

## Requisitos

- PHP 8.x con extensiones `pdo_mysql`, `curl`, `mbstring`
- MySQL 8 (el esquema usa `caching_sha2_password`; asegurate de usar un cliente compatible, por ejemplo PHP/PDO o MySQL Workbench)
- Navegador moderno y, opcionalmente, Postman
- Node.js y npm (opcional, solo para ejecutar las pruebas con Newman)

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

3. Copia `.env.example` a `.env` y completa tus credenciales (MySQL, URL base y, opcionalmente, `APP_KEY` y la clave de tu proveedor de IA):

   ```
   Copy-Item .env.example .env      # Windows PowerShell
   cp .env.example .env             # Linux / macOS
   ```

   Edita **solo el `.env`** resultante con tus valores reales. `.env.example` es la
   plantilla y debe permanecer siempre sin secretos.
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
| Datasets | CRUD todos | CRUD todos | Crea y edita solo los suyos | Solo lectura |
| Calidad | Registra/elimina | Registra/elimina | Registra solo en los suyos (no elimina) | Solo lectura |
| Politicas | CRUD + asignacion | CRUD + asignacion | — | Solo lectura |
| Cumplimiento | CRUD controles + evalua | CRUD controles + evalua | Evalua solo sus datasets | Solo lectura |
| Linaje de datos | CRUD | CRUD | Solo entre sus datasets | Solo lectura |
| Monitoreo | Lectura y gestion completa | Lectura y gestion completa | Configura y ejecuta solo sus datasets | Solo lectura |
| Fuentes externas | CRUD + probar/sincronizar | CRUD + probar/sincronizar | Lee, activa/desactiva, prueba y sincroniza las de sus datasets | Solo lectura |
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
- **Monitoreo**: configuraciones de revalidacion periodica de la calidad por dataset (una por dataset, frecuencia en minutos, activo/inactivo), ejecucion manual ("Monitorear ahora") y programada via CLI, historial de ejecuciones `ok/fallo` con score revalidado y anomalias nuevas, lock de concurrencia y 4 KPIs en el dashboard.
- **Fuentes externas**: catalogo de fuentes REST/JSON consumidas por GET. Validacion de URL anti-SSRF (esquema, hosts locales y resolucion DNS), limite de tamano de la respuesta (1 MB por defecto), sin redirecciones, historial de `prueba`/`sync` con metadatos resumidos (total y campos), vinculo opcional con un dataset del catalogo, activar/desactivar con toggle y KPIs propios.
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

## Monitoreo continuo de calidad

El módulo revalida automáticamente la calidad de cada dataset **sin crear métricas nuevas**: cada ejecución reutiliza la **última medición de calidad almacenada** y corre la **detección de anomalías de IA** (`ai_ejecutar_anomalias`, compartida con el módulo de IA vía `includes/ai_service.php`).

- **Configuraciones**: una por dataset (`monitoring_configs`), con frecuencia en minutos (1 a 43200; sugeridas: 5, 15, 30, 60, 180, 360, 720 y 1440). Un dataset **inactivo** nunca es elegible para ejecución automática y su `proxima_ejecucion` es NULL hasta reactivarse.
- **Ejecuciones**: cada corrida queda en `monitoring_runs` como `ok` o `fallo`, con el score revalidado, anomalías nuevas y un resumen. Un dataset **sin mediciones de calidad** se registra como `fallo` con el resumen *"No existen mediciones de calidad registradas para este dataset"* (sin llamar a la IA, liberando el lock y programando la siguiente ejecución).
- **Ejecución manual**: botón **"Monitorear ahora"** en la vista y `POST api/monitoring.php?action=run`. Ignora `proxima_ejecucion`.
- **Ejecución automática (programada)**: el programador es el CLI `cli\monitor.php`, que ejecuta las configuraciones **activas y vencidas** (`proxima_ejecucion <= ahora`). Para automatizarlo en Windows crea una tarea en el Programador de tareas con `cli\monitor.bat`.
- **Concurrencia**: cada configuración tiene un lock (`corriendo`) que descarta ejecuciones simultáneas (UPDATE atómico), por lo que el CLI puede programarse tan seguido como se quiera.
- **Dashboard**: el panel de control suma 4 KPIs de monitoreo (monitoreos activos, ejecuciones, fallos y tasa de éxito).

Bitácora: `monitoreo:configurar`, `monitoreo:activar`, `monitoreo:desactivar` y `monitoreo:ejecutar` (el CLI registra con usuario NULL).

Uso rápido:

```
php cli\monitor.php --list
php cli\monitor.php --run-id=1
php cli\monitor.php
```

## Fuentes externas / Integraciones

Modulo que consume APIs externas por **GET (REST/JSON)** y las vincula al gobierno de datos. **No copia los datos externos a MySQL**: cada corrida solo registra metadatos resumidos (total de registros y campos del primer objeto JSON) en `external_sync_runs` para demostrar la conexion, el formato y la trazabilidad.

- **Fuentes** (`external_sources`): nombre, descripcion, URL, timeout por fuente (1-60 s), estado activo/inactivo, espejo de la ultima sincronizacion (patron de `monitoring_configs`) y **vinculo opcional** con un dataset del catalogo (`dataset_id`, FK con `ON DELETE SET NULL`).
- **Operaciones**: **Probar conexion** (prueba GET sin tocar el espejo), **Sincronizar ahora** (GET + actualiza `ultimo_estado`, `ultima_ejecucion`, `ultimo_resumen` y `total_ejecuciones`), **Historial**, **Crear/Editar/Eliminar**, y **Activar/Desactivar** con `POST ...action=toggle` que se refleja de inmediato en la interfaz.
- **Seguridad**:
  - URL validada en create/update y en cada peticion: solo `http`/`https`, sin credenciales, sin redirecciones (`FOLLOWLOCATION=false`) y con timeout fijo de conexion.
  - **Proteccion SSRF**: se rechazan hosts `localhost`/`.local` e IPs privadas/loopback/link-local literales; ademas el hostname **se resuelve por DNS antes de conectar** y si cualquiera de sus IPs no es publica se rechaza.
  - Hosts locales/privados **unicamente** cuando `INTEGRACION_PERMITIR_LOCALHOST=true` (ambiente de pruebas con un mock local). Tamano maximo de respuesta controlado por `INTEGRACION_MAX_BYTES` (1 MB por defecto) usando un callback de escritura de cURL que **aborta la descarga** al superarlo.
  - La bitacora registra solo datos operacionales (`source_id`, estado, total), nunca respuestas ni contenido sensible. No se almacenan credenciales de fuentes (solo APIs publicas sin auth).

Bitacora: `integracion:crear`, `integracion:editar`, `integracion:eliminar`, `integracion:activar`, `integracion:desactivar`, `integracion:probar` y `integracion:sincronizar`.

Configuracion en `.env`:

```
INTEGRACION_TIMEOUT=15
INTEGRACION_CONNECT_TIMEOUT=5
INTEGRACION_MAX_BYTES=1048576
INTEGRACION_PERMITIR_LOCALHOST=false
```

`INTEGRACION_PERMITIR_LOCALHOST` tambien puede pasarse como variable de entorno real al iniciar el servidor (el harness de pruebas E2E lo hace asi, sin editar el `.env`).

> **ADVERTENCIA:** `INTEGRACION_PERMITIR_LOCALHOST=true` desactiva por completo la validacion anti-SSRF (esquema, hosts locales, IPs privadas y resolucion DNS). Debe usarse **unicamente en desarrollo o pruebas con un mock local**; nunca en un despliegue real.

Datos de ejemplo del seed (academicos): una API publica de demo inactiva (depende de internet) y una fuente hacia un mock local `http://127.0.0.1:8760/datos` (requiere `INTEGRACION_PERMITIR_LOCALHOST=true`).

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
| `datasets.php` | `list` (con `search`, `categoria`), `search` (busqueda semantica con `q`, `categoria`), `detail`, `create`, `update`, `delete`, `export` |
| `quality.php` | `history`, `register`, `delete` |
| `policies.php` | `list`, `get`, `create`, `update`, `delete`, `assign`, `unassign` |
| `compliance.php` | `list`, `get`, `create`, `update`, `delete`, `evaluate`, `dataset_status` |
| `monitoring.php` | `list`, `runs`, `create`, `update`, `toggle`, `run`, `delete` |
| `integrations.php` | `list`, `get`, `historial`, `create`, `update`, `delete`, `probar`, `sincronizar`, `toggle` |
| `lineage.php` | `list` (con `dataset_id`), `get`, `create`, `update`, `delete` |
| `ai.php` | `status`, `classify`, `anomalies`, `summary`, `suggest`, `resolve`, `confirm_classify` |
| `reports.php` | `overview`, `alerts`, `export` (todos los roles), `logs` (solo admin) |

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
POST /api/monitoring.php?action=create   {"dataset_id":1,"frecuencia":1440}
POST /api/monitoring.php?action=run      {"id":1}
POST /api/integrations.php?action=create   {"nombre":"API indicadores","url":"https://api.restful-api.dev/objects","dataset_id":4,"timeout":15,"activo":1}
POST /api/integrations.php?action=probar   {"id":1}
POST /api/integrations.php?action=sincronizar {"id":1}
POST /api/integrations.php?action=toggle   {"id":1}
GET  /api/reports.php?action=overview
```

## Postman

En `postman/` hay una coleccion lista para importar:

1. Abre Postman -> Import -> selecciona `GobiernoDatosIA.postman_collection.json`.
2. Importa tambien el entorno `GobiernoDatosIA.postman_environment.json` y activalo.
3. Ajusta la variable `baseUrl` a tu servidor.
4. Ejecuta primero **Auth > Login** (guarda la cookie de sesion) y despues cualquier otro request.

La coleccion incluye pruebas de login, sesion, token CSRF (POST sin token y con token invalido) y de cada modulo. Los requests que crean datos usan valores unicos y los eliminan al final; por eso se puede ejecutar completa varias veces seguidas.

## Pruebas automatizadas con Newman

Newman ejecuta la misma coleccion de Postman desde la terminal. No requiere agregar dependencias al proyecto: se instala de forma global con npm.

### Requisitos

- Node.js y npm instalados (`node --version`, `npm --version`).
- MySQL con la base `gobierno_datos_ia` creada y el seed cargado.
- Servidor PHP en marcha (ver [Instalacion](#instalacion)).
- Conexion a internet para los requests de **Integraciones**, que consultan una API publica de demostracion.

### Instalacion de Newman

```bash
npm install -g newman
newman --version
```

### Ejecucion

En una terminal, desde la raiz del proyecto, levanta el servidor:

```bash
php -S 127.0.0.1:8000 -t .
```

En otra terminal, ejecuta la coleccion completa:

```bash
newman run postman/GobiernoDatosIA.postman_collection.json -e postman/GobiernoDatosIA.postman_environment.json
```

El entorno incluido apunta a `http://127.0.0.1:8000` y usa las credenciales de ejemplo de `sql/seed.sql` (`admin@example.com` / `Admin123!`). No contiene secretos reales. Para usar otro servidor o usuario, sobreescribe las variables sin editar los archivos:

```bash
newman run postman/GobiernoDatosIA.postman_collection.json \
  -e postman/GobiernoDatosIA.postman_environment.json \
  --env-var baseUrl=http://127.0.0.1:8000 \
  --env-var adminEmail=admin@example.com \
  --env-var adminPassword=Admin123!
```

En PowerShell, usa una sola linea o el caracter de continuacion `` ` `` en lugar de `\`.

### Resultado esperado

Newman muestra el detalle por request y un resumen con `requests`, `assertions`, `passed` y `failed`. La coleccion es repetible: crea dos datasets, un usuario, una politica, un control, una relacion de linaje, una configuracion de monitoreo y una fuente externa temporales; registra una medicion de calidad y localiza su ID por la API; usa todos esos IDs generados en lugar de IDs fijos y elimina los datos al final. El logout es el ultimo request.

Para evitar llamadas reales al proveedor de IA durante la prueba, deja `AI_API_KEY` vacia en `.env` (la aplicacion usa el modo simulado). Si la API publica de **Integraciones** no responde por falta de internet, esos requests pueden fallar aunque el resto de la coleccion sea correcto.


## Base de datos

15 tablas: `roles`, `users`, `datasets`, `quality_metrics`, `policies`, `policy_datasets`, `data_lineage`, `compliance_controls`, `compliance_evaluations`, `monitoring_configs`, `monitoring_runs`, `external_sources`, `external_sync_runs`, `ai_detections` y `activity_logs`, con llaves foraneas, restricciones `CHECK` e indices para busquedas. El detalle completo esta en `sql/database.sql`.

Si tu base de datos ya existe y solo le falta un modulo, ejecuta la migracion correspondiente, que crea unicamente sus tablas con sus constraints, sin datos y sin tocar ninguna tabla previa (idempotente, se puede reejecutar):
- `sql/migration_lineage.sql`: crea `data_lineage`.
- `sql/migration_compliance.sql`: crea `compliance_controls` y `compliance_evaluations`.
- `sql/migration_monitoring.sql`: crea `monitoring_configs` y `monitoring_runs`.
- `sql/migration_integrations.sql`: crea `external_sources` y `external_sync_runs`.

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
- Proteccion SSRF en el modulo de fuentes externas (esquema, hosts locales, resolucion DNS y limite de tamano del cuerpo)
- Proteccion CSRF con token por sesion en todas las peticiones POST que modifican datos (cabecera `X-CSRF-Token` o campo `csrf_token`); el login queda exento para permitir el inicio de sesion

## Pruebas realizadas

Cada etapa se verifico con pruebas E2E sobre la BD real: autenticacion (Etapa 1), usuarios (2), datasets (3), calidad (4), politicas (5), IA en modo simulado (6), reportes/dashboard (7), cierre de seguridad (8), linaje de datos (9), cumplimiento normativo (10), monitoreo continuo (11) y fuentes externas / integraciones (12). La colección de Postman permite re-ejecutar manualmente los flujos principales de la API.

## Configuracion local y secretos

El archivo `.env` contiene **tu configuracion local**: contrasena de MySQL y clave de
OpenAI/Groq. Por eso:

- **No se sube a GitHub.** Ya esta en `.gitignore` y no forma parte del historial del repositorio.
- **No se incluye en la entrega.** Si compartes el proyecto, compártelo **sin `.env`**: quien lo reciba
  crea el suyo copiando `.env.example`.
- **Nunca se documentan aquí sus valores.** Este README y `.env.example` solo contienen
  nombres de variables y ejemplos.

`APP_KEY` está en `.env` como variable **opcional y reservada**: el proyecto la carga
pero no la usa en ningún proceso, así que puedes dejarla vacía y el sistema funciona
igual. Si más adelante se usa para cifrar sesiones o firmar datos, se genera con:

```
php -r "echo bin2hex(random_bytes(16));"
```

Si una clave llega a publicarse por error, revócala y genera una nueva en el proveedor:
borrarla del archivo no basta si ya quedó expuesta.

### Qué excluir al comprimir el proyecto para entregar

Al generar el ZIP de entrega, deja fuera:

| Qué excluir | Por qué |
|---|---|
| `.env` | Contiene contrasena de BD y clave de IA (y `APP_KEY` si la rellenaste) |
| `.git/` | Historial completo del repositorio |
| `config/local*.php` | Configuracion local si llegara a existir |
| `.vscode/settings.json` | Preferencias locales del editor |
| `*.log`, `*.tmp`, `Thumbs.db`, `.DS_Store` | Temporales y basura del sistema |
| `vendor/` | Dependencias (se regeneran; este proyecto no usa Composer) |
| `*.pem`, `*.key`, `*.p12`, `*.crt`, `*.sqlite`, `*.db` | Claves privadas y bases de datos locales |

Incluye **`.env.example`**: es lo que permite a otra persona configurar y ejecutar el
proyecto sin conocer tus secretos.

## Referencias oficiales

- Documentacion de PHP y PDO: <https://www.php.net/docs.php>
- MySQL: <https://dev.mysql.com/doc/>
- JavaScript y DOM (MDN): <https://developer.mozilla.org/>
- Postman: <https://learning.postman.com/>
- API de OpenAI: <https://platform.openai.com/docs/>
- Groq (API compatible con OpenAI): <https://console.groq.com/docs>


## Uso de herramientas de inteligencia artificial

Durante el desarrollo del proyecto se utilizaron **ChatGPT** y **OpenCode** como herramientas de apoyo para la revisión de código, la resolución de errores, el ajuste de la documentación y la preparación de pruebas reproducibles con Postman y Newman.

Las sugerencias generadas por estas herramientas fueron revisadas antes de incorporar los cambios correspondientes al proyecto.

Este uso de herramientas de apoyo es independiente de la inteligencia artificial integrada en la plataforma, implementada en `api/ai.php` e `includes/ai_service.php`, que proporciona funciones de clasificación, detección de anomalías, generación de resúmenes y sugerencias de mejora.

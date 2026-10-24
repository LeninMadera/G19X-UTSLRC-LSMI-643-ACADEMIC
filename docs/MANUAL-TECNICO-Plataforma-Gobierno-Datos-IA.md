# MANUAL TÉCNICO
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Manual técnico  
**Versión:** 1.0  
**Estado:** Final  
**Stack implementado:** PHP 8.x, MySQL 8, HTML5, CSS3, JavaScript, API REST + JSON y API compatible con OpenAI

---

## 1. Introducción

El presente Manual Técnico describe la instalación, configuración, estructura y operación técnica de la Plataforma de Gobierno de Datos con Inteligencia Artificial.

El documento está dirigido a personal técnico, administradores o desarrolladores responsables de instalar, ejecutar, mantener o revisar el sistema.

La plataforma fue desarrollada como una aplicación web modular basada en PHP y MySQL, con una API REST JSON, frontend en HTML, CSS y JavaScript, y servicios externos opcionales para Inteligencia Artificial e integraciones REST/JSON.

---

## 2. Objetivo

Proporcionar la información necesaria para:

- Preparar el entorno local.
- Configurar la base de datos.
- Configurar variables de entorno.
- Ejecutar la aplicación.
- Comprender la estructura del proyecto.
- Identificar los módulos principales.
- Configurar la Inteligencia Artificial.
- Configurar y ejecutar monitoreo.
- Configurar fuentes externas.
- Utilizar la colección de Postman.
- Conocer las principales medidas de seguridad.
- Realizar tareas básicas de mantenimiento y diagnóstico.

---

## 3. Requisitos técnicos

### 3.1 Software requerido

| Componente | Requisito |
|---|---|
| PHP | 8.x |
| MySQL | 8.x |
| Navegador | Chrome, Edge, Firefox u otro navegador moderno |
| Servidor web | PHP Built-in Server, Apache o equivalente |
| Git | Recomendado para control de versiones |
| Postman | Opcional, para pruebas de API |

### 3.2 Extensiones PHP necesarias

La instalación debe contar con:

- `pdo_mysql`
- `curl`
- `mbstring`

Se recomienda verificar las extensiones disponibles con:

```bash
php -m
```

---

## 4. Estructura principal del proyecto

```text
gobierno-datos-ia/
├── api/
│   ├── ai.php
│   ├── auth.php
│   ├── compliance.php
│   ├── datasets.php
│   ├── integrations.php
│   ├── lineage.php
│   ├── monitoring.php
│   ├── policies.php
│   ├── quality.php
│   ├── reports.php
│   └── users.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── ai.js
│       ├── app.js
│       ├── compliance.js
│       ├── dashboard.js
│       ├── datasets.js
│       ├── dataset_detail.js
│       ├── integrations.js
│       ├── lineage.js
│       ├── monitoring.js
│       ├── policies.js
│       ├── quality.js
│       ├── reports.js
│       └── users.js
│
├── cli/
│   ├── monitor.php
│   └── monitor.bat
│
├── config/
│   ├── app.php
│   └── database.php
│
├── includes/
│   ├── ai_service.php
│   ├── functions.php
│   ├── integrations_service.php
│   ├── monitoring_service.php
│   └── semantic_search.php
│
├── postman/
│   ├── GobiernoDatosIA.postman_collection.json
│   └── GobiernoDatosIA.postman_environment.json
│
├── sql/
│   ├── database.sql
│   ├── migration_compliance.sql
│   ├── migration_integrations.sql
│   ├── migration_lineage.sql
│   ├── migration_monitoring.sql
│   └── seed.sql
│
├── views/
│   ├── includes/
│   ├── ai.php
│   ├── compliance.php
│   ├── dashboard.php
│   ├── datasets.php
│   ├── dataset_detail.php
│   ├── integrations.php
│   ├── lineage.php
│   ├── login.php
│   ├── monitoring.php
│   ├── policies.php
│   ├── quality.php
│   ├── reports.php
│   └── users.php
│
├── .env.example
├── .gitignore
├── index.php
├── iniciar.bat
├── jsconfig.json
└── README.md
```

---

## 5. Preparación del entorno

### 5.1 Copiar el proyecto

Descomprimir o copiar la carpeta del proyecto en una ubicación local.

Ejemplo:

```text
C:\proyectos\gobierno-datos-ia
```

### 5.2 Preparar el archivo `.env`

La entrega debe incluir:

```text
.env.example
```

Crear una copia con el nombre:

```text
.env
```

El archivo `.env` contiene configuración local y no debe incluirse en Git ni en el ZIP público de entrega.

---

## 6. Configuración de variables de entorno

Las variables principales utilizadas por la aplicación son:

| Variable | Función |
|---|---|
| `DB_HOST` | Host del servidor MySQL |
| `DB_PORT` | Puerto de MySQL |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` | Usuario de MySQL |
| `DB_PASS` | Contraseña de MySQL |
| `APP_URL` | URL base de la aplicación |
| `APP_DEBUG` | Activa o desactiva información de depuración |
| `AI_API_KEY` | Clave del proveedor de IA |
| `AI_MODEL` | Modelo configurado |
| `AI_BASE_URL` | URL base compatible con OpenAI |
| `INTEGRACION_TIMEOUT` | Tiempo máximo de solicitud externa |
| `INTEGRACION_CONNECT_TIMEOUT` | Tiempo máximo para establecer conexión |
| `INTEGRACION_MAX_BYTES` | Límite máximo de respuesta externa |
| `INTEGRACION_PERMITIR_LOCALHOST` | Permite localhost/red privada solo para desarrollo |

### Ejemplo de configuración

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=gobierno_datos_ia
DB_USER=root
DB_PASS=

APP_URL=http://127.0.0.1:8000
APP_DEBUG=0

AI_API_KEY=
AI_MODEL=
AI_BASE_URL=

INTEGRACION_PERMITIR_LOCALHOST=false
```

Los valores reales dependen del entorno utilizado.

---

## 7. Instalación de la base de datos

### 7.1 Instalación nueva

El archivo principal se encuentra en:

```text
sql/database.sql
```

Este archivo crea la estructura principal de la base de datos.

**Advertencia:** el script contiene una instrucción para eliminar y recrear la base de datos. No debe ejecutarse sobre una instalación que contenga información que se desee conservar.

### 7.2 Datos de prueba

El archivo:

```text
sql/seed.sql
```

contiene información inicial utilizada para pruebas y demostración.

### 7.3 Migraciones

El proyecto incluye migraciones para módulos incorporados posteriormente:

```text
sql/migration_lineage.sql
sql/migration_compliance.sql
sql/migration_monitoring.sql
sql/migration_integrations.sql
```

Las migraciones se utilizan cuando la base principal ya existe y se requiere agregar únicamente las estructuras nuevas.

---

## 8. Base de datos

La solución utiliza 15 tablas:

1. `roles`
2. `users`
3. `datasets`
4. `quality_metrics`
5. `policies`
6. `policy_datasets`
7. `ai_detections`
8. `data_lineage`
9. `activity_logs`
10. `compliance_controls`
11. `compliance_evaluations`
12. `monitoring_configs`
13. `monitoring_runs`
14. `external_sources`
15. `external_sync_runs`

La conexión se administra mediante:

```text
config/database.php
```

El acceso se realiza con PDO y consultas preparadas.

---

## 9. Ejecución de la aplicación

### 9.1 Servidor integrado de PHP

Desde la carpeta raíz del proyecto:

```bash
php -S 127.0.0.1:8000 -t .
```

Después abrir en el navegador:

```text
http://127.0.0.1:8000
```

### 9.2 Ejecución mediante archivo BAT

El proyecto también contiene:

```text
iniciar.bat
```

Este archivo puede utilizarse en Windows cuando el entorno PHP se encuentra correctamente configurado.

### 9.3 Apache/XAMPP

También puede colocarse el proyecto dentro de la carpeta pública del servidor web, por ejemplo:

```text
htdocs/
```

y acceder mediante la URL correspondiente.

---

## 10. Acceso inicial

El archivo `seed.sql` incluye usuarios de prueba para los cuatro roles del sistema.

Roles disponibles:

- Admin.
- Steward.
- Owner.
- Analyst.

Las credenciales concretas deben consultarse en la configuración de prueba o README del proyecto y no deben reemplazarse por contraseñas reales dentro de este manual.

---

## 11. Módulos principales

### 11.1 Usuarios

Archivos principales:

```text
views/users.php
assets/js/users.js
api/users.php
```

Funciones:

- Listar usuarios.
- Crear usuarios.
- Editar usuarios.
- Activar/desactivar usuarios.
- Eliminar cuando la regla lo permite.
- Asignar roles.

Reglas importantes:

- Solo Admin puede administrar usuarios.
- Un Admin no puede desactivar su propia cuenta.
- Debe quedar al menos un administrador activo.

---

### 11.2 Datasets

Archivos:

```text
views/datasets.php
views/dataset_detail.php
assets/js/datasets.js
assets/js/dataset_detail.js
api/datasets.php
```

Funciones principales:

- Registro.
- Consulta.
- Edición.
- Búsqueda.
- Filtros.
- Detalle.
- Información relacionada.

---

### 11.3 Calidad

Archivos:

```text
views/quality.php
assets/js/quality.js
api/quality.php
```

Dimensiones utilizadas:

- Completitud.
- Exactitud.
- Consistencia.
- Vigencia.
- Unicidad.

Fórmula:

```text
score =
completitud × 0.20 +
exactitud × 0.25 +
consistencia × 0.20 +
vigencia × 0.15 +
unicidad × 0.20
```

Clasificación:

- Excelente: `>= 85`
- Aceptable: `70 a 84.99`
- Por mejorar: `< 70`
- Alerta: `< 80`

---

### 11.4 Políticas

Archivos:

```text
views/policies.php
assets/js/policies.js
api/policies.php
```

Funciones:

- Crear política.
- Editar.
- Consultar.
- Eliminar cuando corresponde.
- Asignar a datasets.
- Desasignar.

---

### 11.5 Cumplimiento

Archivos:

```text
views/compliance.php
assets/js/compliance.js
api/compliance.php
```

Estados:

- Cumple.
- No cumple.
- Pendiente.
- No aplica.

El módulo funciona como registro interno de seguimiento y no como dictamen legal automático.

---

### 11.6 Linaje

Archivos:

```text
views/lineage.php
assets/js/lineage.js
api/lineage.php
```

Tipos de relación disponibles:

- Derivado de.
- Alimenta.
- Integra.
- Transforma.
- Copia.
- Versión de.

No se permite que el mismo dataset sea simultáneamente origen y destino.

---

### 11.7 Inteligencia Artificial

Archivos:

```text
views/ai.php
assets/js/ai.js
api/ai.php
includes/ai_service.php
```

Acciones:

- `classify`
- `anomalies`
- `summary`
- `suggest`

El sistema puede trabajar con:

- Proveedor real compatible con OpenAI.
- Modo simulado local.

---

## 12. Configuración de Inteligencia Artificial

La integración utiliza:

```text
AI_API_KEY
AI_MODEL
AI_BASE_URL
```

Ejemplo conceptual:

```env
AI_API_KEY=CLAVE_DEL_PROVEEDOR
AI_MODEL=MODELO_CONFIGURADO
AI_BASE_URL=URL_COMPATIBLE
```

Nunca se debe colocar una clave real directamente en archivos PHP o JavaScript.

### 12.1 Modo simulado

Si no existe una clave configurada, el sistema puede utilizar el modo simulado.

Este modo permite:

- Demostrar la interfaz.
- Ejecutar las acciones principales.
- Evitar depender de una conexión externa durante una demostración.

---

## 13. Búsqueda semántica

Servicio:

```text
includes/semantic_search.php
```

La implementación utiliza una búsqueda híbrida:

1. Recibe el texto del usuario.
2. Puede solicitar a la IA términos relacionados.
3. Busca sobre campos del catálogo.
4. Ordena resultados por relevancia.
5. Si la IA no está disponible, utiliza búsqueda normal.

La solución no requiere embeddings ni una base vectorial.

---

## 14. Monitoreo

Archivos:

```text
views/monitoring.php
assets/js/monitoring.js
api/monitoring.php
includes/monitoring_service.php
cli/monitor.php
cli/monitor.bat
```

Funciones:

- Crear configuración.
- Definir frecuencia.
- Activar/desactivar.
- Ejecutar manualmente.
- Consultar historial.
- Programar ejecución desde el sistema operativo.

El monitoreo utiliza la última medición real de calidad disponible.

Si un dataset no tiene mediciones, el sistema registra el estado correspondiente y no crea información ficticia.

---

## 15. Ejecución del monitoreo por CLI

Desde la raíz del proyecto puede ejecutarse el monitor mediante:

```bash
php cli/monitor.php
```

En Windows también se incluye:

```text
cli/monitor.bat
```

Para automatizarlo puede utilizarse el Programador de tareas de Windows u otro mecanismo equivalente.

El programador externo es responsable de ejecutar periódicamente el CLI.

---

## 16. Integraciones externas

Archivos:

```text
views/integrations.php
assets/js/integrations.js
api/integrations.php
includes/integrations_service.php
```

La implementación acepta fuentes:

```text
REST / JSON / GET
```

Funciones:

- Alta.
- Edición.
- Activación/desactivación.
- Prueba de conexión.
- Sincronización de metadatos.
- Historial.

La aplicación no copia masivamente los datos externos a MySQL.

---

## 17. Seguridad de integraciones

Las solicitudes externas incluyen controles para reducir riesgo de SSRF:

- Solo HTTP/HTTPS.
- Validación del host.
- Resolución DNS.
- Bloqueo de loopback.
- Bloqueo de redes privadas.
- Bloqueo de link-local.
- Redirecciones desactivadas.
- Timeout.
- Límite máximo de bytes.

La variable:

```text
INTEGRACION_PERMITIR_LOCALHOST=true
```

solo debe utilizarse durante desarrollo o pruebas.

En una operación normal debe mantenerse:

```text
false
```

---

## 18. API REST

Convención:

```text
api/<modulo>.php?action=<accion>
```

Módulos:

| API | Función |
|---|---|
| `auth.php` | Sesión, login y logout |
| `users.php` | Usuarios y roles |
| `datasets.php` | Catálogo |
| `quality.php` | Calidad |
| `policies.php` | Políticas |
| `compliance.php` | Cumplimiento |
| `lineage.php` | Linaje |
| `monitoring.php` | Monitoreo |
| `integrations.php` | Fuentes externas |
| `ai.php` | Inteligencia Artificial |
| `reports.php` | Dashboard, alertas, reportes y logs |

Respuesta general:

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

Las acciones protegidas requieren una sesión válida.

---

## 19. Postman

La colección se encuentra en:

```text
postman/GobiernoDatosIA.postman_collection.json
```

El entorno se encuentra en:

```text
postman/GobiernoDatosIA.postman_environment.json
```

Uso básico:

1. Abrir Postman.
2. Importar la colección.
3. Importar el environment.
4. Configurar la URL local.
5. Ejecutar login.
6. Mantener la cookie de sesión.
7. Ejecutar las solicitudes principales.

La colección permite reproducir los flujos principales de la API, pero no representa una cobertura exhaustiva de todas las combinaciones posibles.

---

## 20. Seguridad general

El sistema implementa:

- `password_hash()` para contraseñas.
- Verificación de contraseñas en servidor.
- Sesiones PHP.
- Regeneración de ID de sesión.
- Cookie HttpOnly.
- SameSite=Lax.
- Control de rol en backend.
- Validación de propiedad.
- PDO.
- Consultas preparadas.
- Escape de salida.
- `.env` fuera de Git.
- Cabeceras de seguridad.
- Bitácora.
- Protección contra SSRF.
- Restricciones para conservar al menos un Admin activo.

### Limitación conocida

La plataforma implementa protección CSRF para las peticiones que modifican estado. El token se almacena en la sesión del usuario, se genera con `bin2hex(random_bytes(32))` mediante `csrfToken()` y se envía desde el frontend en la cabecera `X-CSRF-Token` (gestionado por `assets/js/app.js` mediante `window.CSRF_TOKEN`). Los endpoints validan el token con `requireCsrf()` o `requireCsrfIfPost()` (aplicado en todos los endpoints API que aceptan POST, con excepción deliberada de `POST api/auth.php?action=login`). En caso de token inválido o ausente se devuelve HTTP 403. Las solicitudes GET no requieren validación CSRF.

Antes de un despliegue productivo debe considerarse:

- HTTPS.
- Protección CSRF para peticiones POST mutantes (token en sesión, envío vía `X-CSRF-Token` desde el frontend, validación en backend; excepción `login`).
- Gestión segura de secretos.
- Políticas de respaldo.
- Registro centralizado.
- Endurecimiento adicional de servidor.

---

## 21. Logs y auditoría

La auditoría funcional se guarda en:

```text
activity_logs
```

Registra información como:

- Usuario.
- Acción.
- Detalle.
- IP.
- Fecha.

La bitácora completa se encuentra disponible solamente para Admin.

El dashboard muestra actividad filtrada según el rol.

---

## 22. Solución de problemas

### 22.1 No conecta a MySQL

Revisar:

- MySQL iniciado.
- `DB_HOST`.
- `DB_PORT`.
- `DB_NAME`.
- `DB_USER`.
- `DB_PASS`.

Verificar también que la extensión:

```text
pdo_mysql
```

esté habilitada.

---

### 22.2 Pantalla en blanco o error PHP

Verificar:

```env
APP_DEBUG=1
```

solo temporalmente durante desarrollo.

No debe mantenerse activo en un entorno público.

También se puede ejecutar:

```bash
php -l archivo.php
```

para validar sintaxis.

---

### 22.3 La IA no responde

Revisar:

- `AI_API_KEY`.
- `AI_MODEL`.
- `AI_BASE_URL`.
- Acceso a Internet.
- Límites del proveedor.

Si no existe clave válida, utilizar el modo simulado.

---

### 22.4 Integración externa bloqueada

Revisar si la URL apunta a:

- `localhost`
- `127.0.0.1`
- Red privada
- Host no permitido

Para pruebas locales puede utilizarse temporalmente:

```env
INTEGRACION_PERMITIR_LOCALHOST=true
```

No dejar habilitado en operación normal.

---

### 22.5 Monitoreo sin resultados

Comprobar que el dataset tenga al menos una medición de calidad registrada.

El monitor no crea automáticamente una medición si no existe.

---

### 22.6 Error de permisos

Verificar:

- Usuario autenticado.
- Rol.
- Propiedad del dataset.
- Acción ejecutada.

Los permisos se validan en backend aunque la interfaz oculte determinados botones.

---

## 23. Respaldo

Para conservar la información del sistema se recomienda respaldar:

- Base de datos MySQL.
- Código fuente.
- Archivo `.env` en una ubicación privada.
- Documentación técnica.

No se recomienda almacenar `.env` en repositorios públicos o paquetes de entrega.

---

## 24. Actualización del sistema

Antes de modificar el sistema:

1. Respaldar la base de datos.
2. Confirmar que el repositorio esté limpio.
3. Crear una rama o punto de restauración si aplica.
4. Realizar el cambio.
5. Validar sintaxis.
6. Ejecutar pruebas funcionales.
7. Ejecutar pruebas de regresión.
8. Aplicar migraciones cuando sean necesarias.
9. Actualizar documentación si cambia el comportamiento.

---

## 25. Revisión del proyecto antes de entrega

Antes de generar el ZIP final verificar:

- No incluir `.env`.
- No incluir `.git/`.
- No incluir `.vscode/`.
- No incluir logs.
- No incluir archivos temporales.
- No incluir claves reales.
- Sí incluir `.env.example`.
- Sí incluir SQL.
- Sí incluir migraciones.
- Sí incluir Postman.
- Sí incluir README.
- Sí incluir documentación final.

---

## 26. Consideración sobre `database.sql`

El archivo:

```text
sql/database.sql
```

está pensado para una instalación nueva.

Debido a que recrea la base de datos, no debe ejecutarse sobre información que se desee conservar.

Para actualizar una instalación existente deben utilizarse las migraciones correspondientes y realizar un respaldo previamente.

---

## 27. Validación técnica final

La versión final fue revisada mediante:

- Sintaxis PHP.
- Sintaxis JavaScript.
- Matriz de permisos.
- Pruebas de API.
- Pruebas E2E.
- Pruebas de CRUD.
- Integridad de base de datos.
- Pruebas de IA real y simulada.
- Pruebas de monitoreo.
- Pruebas de integraciones.
- Validación SSRF.
- Pruebas responsive.
- Revisión de secretos.

Los hallazgos principales de la auditoría funcional fueron corregidos antes del cierre del sistema.

---

## 28. Archivos relacionados

La documentación técnica del proyecto se complementa con:

- `README.md`
- PRD.
- MVP.
- Documento de Requerimientos.
- Propuesta Técnica.
- Documento de Arquitectura.
- Plan del Proyecto.
- Reporte QA.
- Plan de Mantenimiento.
- Acta de Capacitación.
- Informe Final.

---

## 29. Conclusión

La Plataforma de Gobierno de Datos con Inteligencia Artificial dispone de una arquitectura modular y de un entorno técnico definido para su instalación, configuración y ejecución.

Este manual proporciona las instrucciones principales para preparar MySQL, configurar el archivo `.env`, ejecutar la aplicación, utilizar la API, configurar Inteligencia Artificial, operar el monitoreo y administrar fuentes externas.

La versión actual está orientada a una entrega académica y entorno local. Antes de utilizarse en producción deberán incorporarse controles adicionales de seguridad, despliegue, respaldo y operación.

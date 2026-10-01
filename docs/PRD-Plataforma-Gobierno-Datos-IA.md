# PRD — Product Requirements Document
## Plataforma de Gobierno de Datos con Inteligencia Artificial

| Campo | Detalle |
|---|---|
| **Producto** | Plataforma de Gobierno de Datos con IA |
| **Versión del documento** | 1.1 — Actualizado al alcance final implementado |
| **Tipo** | Proyecto universitario (entrega académica) |
| **Stack implementado** | PHP 8.x, MySQL 8, JavaScript vanilla, HTML5, CSS3, API REST + JSON, API OpenAI-compatible (Groq/OpenAI) |
| **Estado** | Sistema funcional validado |
| **Autor** | [Nombre del estudiante] |

---

## 1. Resumen ejecutivo

La Plataforma de Gobierno de Datos con Inteligencia Artificial es una aplicación web orientada a centralizar la administración de activos de datos empresariales. Permite catalogar datasets y sus metadatos, medir y dar seguimiento a la calidad, administrar políticas, registrar evaluaciones de cumplimiento, visualizar relaciones de linaje, configurar monitoreo, consultar fuentes externas y utilizar Inteligencia Artificial para apoyar tareas de clasificación, detección de anomalías, generación de resúmenes y sugerencias de mejora.

La solución trabaja con cuatro roles de usuario: **admin, steward, owner y analyst**. Los permisos se aplican tanto en la interfaz como en la API. Además, las acciones relevantes quedan registradas en una bitácora para facilitar la trazabilidad y la auditoría del sistema.

La plataforma administra principalmente **metadatos y elementos de gobierno**. No copia ni procesa de forma masiva el contenido completo de los datasets externos.

---

## 2. Problema

Las organizaciones pueden acumular información en distintas fuentes sin contar con una visión centralizada de sus activos de datos. Esta situación puede provocar dificultades como:

- Desconocimiento de qué datasets existen, quién es responsable de ellos y qué políticas les aplican.
- Falta de seguimiento sistemático de dimensiones de calidad como completitud, exactitud, consistencia, vigencia y unicidad.
- Poca trazabilidad sobre el origen, destino y relación entre conjuntos de datos.
- Evaluaciones de cumplimiento dispersas o sin un registro centralizado.
- Análisis manual de tendencias, anomalías y oportunidades de mejora.
- Dificultad para monitorear de forma periódica el estado de los datasets.
- Falta de visibilidad sobre las fuentes externas asociadas y sus metadatos de sincronización.
- Ausencia de una bitácora central que permita identificar qué usuario realizó una acción y cuándo.

---

## 3. Objetivos

### 3.1 Objetivo general

Centralizar el gobierno de datos empresariales en una plataforma web que permita administrar metadatos, calidad, políticas, cumplimiento, linaje, monitoreo e integraciones, incorporando Inteligencia Artificial como apoyo para el análisis y la clasificación de datasets.

### 3.2 Objetivos específicos

1. Catalogar datasets con nombre, descripción, categoría, origen, responsable y etiquetas.
2. Permitir búsquedas tradicionales y búsqueda semántica asistida por IA sobre el catálogo.
3. Medir la calidad mediante cinco dimensiones y calcular automáticamente un score ponderado.
4. Administrar políticas de gobierno y asignarlas a datasets.
5. Registrar controles y evaluaciones internas de cumplimiento.
6. Mantener relaciones de linaje entre datasets.
7. Aplicar IA para clasificar, detectar anomalías, resumir y sugerir mejoras.
8. Configurar monitoreo periódico sobre datasets y conservar su historial de ejecuciones.
9. Registrar fuentes externas REST/JSON y sincronizar únicamente sus metadatos.
10. Proveer dashboard, reportes, alertas y bitácora de actividad.
11. Aplicar autenticación, control por roles, validaciones y medidas básicas de seguridad.
12. Mantener una API REST JSON para las operaciones principales de la plataforma.

---

## 4. Alcance

### 4.1 Dentro del alcance (In)

- Autenticación y sesiones.
- Gestión de usuarios con cuatro roles.
- Catálogo de datasets y ficha de detalle.
- Búsqueda literal, filtros y búsqueda semántica asistida por IA.
- Registro e historial de mediciones de calidad.
- Cálculo automático del score de calidad.
- Gestión y asignación de políticas.
- Controles y evaluaciones internas de cumplimiento.
- Linaje de datos entre datasets.
- Módulo de IA con acciones de clasificación, anomalías, resumen y sugerencias.
- Modo de IA real mediante proveedor OpenAI-compatible.
- Modo simulado local para demostración y contingencia.
- Monitoreo de datasets con configuraciones, ejecución manual, historial y soporte por CLI para programación externa.
- Gestión de fuentes externas REST/JSON mediante solicitudes GET.
- Prueba de conexión y sincronización de metadatos de fuentes externas.
- Dashboard con KPIs y visualizaciones.
- Reportes, alertas y bitácora.
- Exportaciones disponibles en los módulos que las implementan.
- API REST JSON.
- Colección de Postman para reproducir los flujos principales.

### 4.2 Fuera del alcance (Out)

- Ingesta automática o copia masiva del contenido completo de los datasets.
- Procesos ETL completos o sincronización bidireccional de datos.
- Conectores privados con OAuth, API keys de terceros o autenticación avanzada hacia fuentes externas.
- Medición automática directa de la calidad sobre el contenido bruto de una fuente externa.
- Notificaciones por correo, Slack u otros servicios.
- Despliegue productivo con dominio, HTTPS, alta disponibilidad o balanceo de carga.
- Motor vectorial de embeddings; la búsqueda semántica implementada es asistida por IA mediante expansión de consulta y relevancia ponderada.
- Dictamen legal automático de cumplimiento; el módulo de cumplimiento es un registro declarativo interno.
- Servicio interno permanente de planificación; la ejecución programada de monitoreo se apoya en CLI y en el programador de tareas del sistema operativo.

---

## 5. Usuarios y roles

| Rol | Descripción | Permisos principales |
|---|---|---|
| **Admin** | Administrador del sistema | Acceso total a los módulos, gestión de usuarios, datasets, calidad, políticas, cumplimiento, linaje, monitoreo, integraciones, IA, reportes y bitácora completa |
| **Steward** | Responsable de gobierno de datos | Gestión de datasets, calidad, políticas, cumplimiento, linaje, monitoreo, integraciones e IA según los permisos definidos; sin administración de usuarios |
| **Owner** | Responsable de uno o más datasets | Puede crear y editar sus propios datasets, registrar calidad en los suyos y gestionar recursos asociados a sus datasets según el módulo; no puede eliminar datasets |
| **Analyst** | Usuario de consulta | Acceso principalmente de lectura; las acciones de escritura se encuentran restringidas |

### 5.1 Visibilidad de actividad reciente

- **Admin:** puede consultar la actividad global.
- **Steward:** no recibe eventos de login/logout ni administración de usuarios.
- **Owner:** recibe actividad propia o relacionada de forma segura con datasets de su responsabilidad.
- **Analyst:** recibe únicamente actividad propia en el feed de actividad reciente.
- La bitácora completa de `reports?action=logs` permanece restringida al rol Admin.

---

## 6. Requerimientos funcionales

| ID | Requerimiento | Prioridad |
|---|---|---|
| **RF-01** | El sistema debe permitir iniciar y cerrar sesión con correo y contraseña | Alta |
| **RF-02** | El sistema debe gestionar usuarios y asignar uno de los cuatro roles disponibles | Alta |
| **RF-03** | El sistema debe permitir registrar datasets con nombre, descripción, categoría, origen y responsable | Alta |
| **RF-04** | El sistema debe permitir buscar y filtrar datasets mediante criterios tradicionales | Alta |
| **RF-05** | El sistema debe ofrecer búsqueda semántica asistida por IA sobre el catálogo, con fallback a la búsqueda normal | Media |
| **RF-06** | El sistema debe mostrar una ficha de detalle del dataset con su información de gobierno relacionada | Alta |
| **RF-07** | El sistema debe permitir registrar mediciones de calidad en cinco dimensiones con valores de 0 a 100 | Alta |
| **RF-08** | El sistema debe calcular automáticamente el score ponderado de calidad | Alta |
| **RF-09** | El sistema debe mostrar una alerta cuando el score sea menor a 80 | Alta |
| **RF-10** | El sistema debe clasificar el score en Excelente, Aceptable o Por mejorar según los umbrales definidos | Media |
| **RF-11** | El sistema debe permitir gestionar políticas de gobierno | Alta |
| **RF-12** | El sistema debe permitir asignar y desasignar políticas a datasets | Alta |
| **RF-13** | El sistema debe permitir gestionar controles de cumplimiento | Alta |
| **RF-14** | El sistema debe registrar evaluaciones de cumplimiento por dataset con estados cumple, no cumple, pendiente y no aplica | Alta |
| **RF-15** | El sistema debe permitir registrar relaciones de linaje entre datasets | Alta |
| **RF-16** | El sistema debe impedir relaciones de linaje donde el dataset de origen y destino sean el mismo | Alta |
| **RF-17** | El sistema debe clasificar datasets con IA y persistir categoría y etiquetas sugeridas | Alta |
| **RF-18** | El sistema debe detectar anomalías a partir del historial de calidad mediante IA | Alta |
| **RF-19** | El sistema debe generar un resumen en lenguaje natural del estado de un dataset | Media |
| **RF-20** | El sistema debe sugerir acciones de mejora según las dimensiones débiles de calidad | Media |
| **RF-21** | El sistema debe registrar las detecciones de IA que requieran persistencia, incluyendo severidad y estado | Alta |
| **RF-22** | El sistema debe operar el módulo de IA en modo simulado cuando no exista una clave de proveedor disponible | Media |
| **RF-23** | El sistema debe mostrar un dashboard con KPIs, alertas, actividad y visualizaciones del estado de la plataforma | Alta |
| **RF-24** | El sistema debe generar reportes de calidad, alertas y actividad | Media |
| **RF-25** | El sistema debe registrar en bitácora las acciones relevantes con usuario, detalle, fecha e información disponible de auditoría | Alta |
| **RF-26** | El sistema debe filtrar la actividad reciente según el rol y el alcance del usuario | Alta |
| **RF-27** | El sistema debe permitir crear y administrar configuraciones de monitoreo asociadas a datasets | Alta |
| **RF-28** | El sistema debe permitir ejecutar el monitoreo manualmente y registrar su historial | Alta |
| **RF-29** | El monitoreo debe utilizar la última medición real de calidad disponible y no inventar métricas inexistentes | Alta |
| **RF-30** | El sistema debe permitir registrar fuentes externas REST/JSON con vínculo opcional a un dataset | Alta |
| **RF-31** | El sistema debe permitir probar una fuente externa y sincronizar sus metadatos sin copiar el contenido completo a MySQL | Alta |
| **RF-32** | El sistema debe conservar el historial de sincronizaciones de las fuentes externas | Media |
| **RF-33** | El sistema debe exponer sus operaciones principales mediante una API REST con respuestas JSON | Alta |

---

## 7. Requerimientos no funcionales

| ID | Requerimiento | Detalle |
|---|---|---|
| **RNF-01** | Seguridad de autenticación | Contraseñas protegidas con `password_hash()`; sesión regenerada al autenticarse y cookie de sesión protegida |
| **RNF-02** | Anti SQL Injection | Acceso a datos mediante PDO y consultas preparadas |
| **RNF-03** | Anti XSS | Escapado de salida en PHP y JavaScript antes de insertar contenido no confiable en el DOM |
| **RNF-04** | Control de acceso | Verificación de sesión, rol y propiedad del recurso en los endpoints que lo requieren |
| **RNF-05** | Protección de secretos | API keys y contraseñas en `.env`, excluido del repositorio; `.env.example` como plantilla pública |
| **RNF-06** | Manejo de errores | La API debe responder JSON controlado y evitar exponer errores internos al usuario final |
| **RNF-07** | Integridad de datos | Llaves foráneas, restricciones, índices y `utf8mb4` en MySQL |
| **RNF-08** | Resiliencia de IA | Reintentos frente a errores transitorios del proveedor y fallback cuando el modelo no acepta el formato solicitado |
| **RNF-09** | Compatibilidad | PHP 8.x, MySQL 8 y navegador moderno |
| **RNF-10** | Disponibilidad de demostración | La IA debe poder operar en modo simulado sin una conexión externa |
| **RNF-11** | Protección SSRF | Las integraciones deben validar esquema, host y resolución de red, bloqueando destinos locales o privados en operación normal |
| **RNF-12** | Límites de integraciones | Sin redirecciones automáticas, timeout configurable y límite máximo de respuesta |
| **RNF-13** | Usabilidad responsive | La interfaz debe conservar navegación, tablas, formularios y modales utilizables en escritorio y móvil |
| **RNF-14** | Trazabilidad | Las acciones relevantes deben quedar registradas en bitácora para auditoría |

### 7.1 Limitación de seguridad conocida

La plataforma no implementa actualmente tokens anti-CSRF en los endpoints mutantes. La sesión y el control por rol no sustituyen una protección CSRF dedicada, por lo que este punto se considera una mejora futura si el sistema se despliega en un entorno productivo.

---

## 8. Modelo de calidad de datos

El score global utiliza una escala de 0 a 100 y se calcula como media ponderada de cinco dimensiones:

| Dimensión | Peso |
|---|---:|
| Completitud | 20% |
| Exactitud | 25% |
| Consistencia | 20% |
| Vigencia | 15% |
| Unicidad | 20% |

**Fórmula:**

`score = completitud·0.20 + exactitud·0.25 + consistencia·0.20 + vigencia·0.15 + unicidad·0.20`

**Niveles:**

- **Excelente:** score ≥ 85
- **Aceptable:** score entre 70 y 84.99
- **Por mejorar:** score < 70
- **Alerta:** score < 80

---

## 9. Módulo de Inteligencia Artificial

### 9.1 Configuración

- **Proveedor:** API compatible con OpenAI (`/v1/chat/completions`).
- **Proveedores compatibles utilizados:** OpenAI o Groq.
- **Variables:** `AI_API_KEY`, `AI_MODEL`, `AI_BASE_URL`.
- **Modo simulado:** heurísticas locales cuando no existe una clave configurada o cuando se requiere una demostración sin consumo externo.

### 9.2 Acciones principales

| Acción | Entrada | Salida | Persistencia |
|---|---|---|---|
| **classify** | Nombre y descripción del dataset | Categoría y etiquetas sugeridas | Categoría/etiquetas en dataset y registro relacionado de IA |
| **anomalies** | Historial reciente de calidad | Lista de anomalías y severidad | Detecciones de IA |
| **summary** | Metadatos, calidad, políticas y anomalías | Resumen en lenguaje natural | Respuesta en vivo |
| **suggest** | Última medición de calidad | Acciones sugeridas de mejora | Respuesta en vivo |

La plataforma puede reutilizar lógica de IA para funciones auxiliares, como la expansión de términos en la búsqueda semántica y el análisis utilizado por monitoreo.

---

## 10. Búsqueda semántica

La búsqueda semántica implementada es **asistida por IA** y no depende de un motor vectorial.

El flujo es:

1. Se recibe la consulta del usuario.
2. La IA puede generar términos relacionados o equivalentes.
3. La búsqueda se ejecuta sobre campos del catálogo, incluyendo nombre, descripción, etiquetas, categoría y origen.
4. Los resultados se ordenan mediante una relevancia ponderada.
5. Si la IA no está disponible, el sistema conserva la búsqueda tradicional como fallback.
6. La consulta puede almacenarse temporalmente en caché de sesión para evitar llamadas repetidas.

Solo se envía al proveedor la información necesaria para expandir la consulta; no se envía el contenido completo de los datasets.

---

## 11. Cumplimiento y linaje

### 11.1 Cumplimiento

El módulo permite administrar controles internos y evaluar datasets mediante los estados:

- `cumple`
- `no_cumple`
- `pendiente`
- `no_aplica`

Las evaluaciones pueden conservar observaciones, evidencia, evaluador y fecha. El resultado constituye un **registro declarativo interno** y no un dictamen legal.

### 11.2 Linaje

El sistema permite establecer relaciones entre datasets para representar dependencias u origen de información. Entre los tipos utilizados se encuentran:

- derivado de
- alimenta
- integra
- transforma
- copia
- versión de

No se permite una relación en la que el mismo dataset sea origen y destino.

---

## 12. Monitoreo continuo

El módulo de monitoreo permite:

- Crear una configuración por dataset.
- Definir frecuencia de ejecución.
- Activar o desactivar la configuración.
- Ejecutar el monitoreo manualmente.
- Consultar el historial de ejecuciones.
- Evitar ejecuciones concurrentes sobre una misma configuración mediante estado de bloqueo.
- Programar ejecuciones externas utilizando el CLI y herramientas del sistema operativo.

El monitoreo **no inventa mediciones de calidad**. Utiliza la última medición registrada del dataset y ejecuta análisis de anomalías cuando corresponde.

Si un dataset no cuenta con mediciones de calidad, la ejecución se registra con el estado correspondiente y el resto de configuraciones puede continuar.

---

## 13. Fuentes externas e integraciones

El sistema permite registrar fuentes externas basadas en **REST/JSON mediante GET**.

Funciones principales:

- Registrar y editar la fuente.
- Activar o desactivar.
- Probar conexión.
- Sincronizar metadatos.
- Consultar historial de sincronización.
- Vincular opcionalmente una fuente con un dataset.

La plataforma almacena metadatos como campos detectados, cantidad de registros e información de sincronización. No copia de forma masiva el contenido externo a MySQL.

### 13.1 Seguridad de integraciones

- Solo esquemas HTTP/HTTPS.
- Bloqueo de credenciales embebidas en URL.
- Resolución DNS y bloqueo de destinos privados, locales, loopback o link-local en operación normal.
- Redirecciones HTTP deshabilitadas.
- Timeout configurable dentro de los límites definidos por la aplicación.
- Tamaño máximo de respuesta controlado.
- `INTEGRACION_PERMITIR_LOCALHOST=true` debe utilizarse únicamente para desarrollo o pruebas, ya que permite conexiones a localhost/redes locales y desactiva esa parte de la protección SSRF.

---

## 14. API REST

Convención principal:

`api/<modulo>.php?action=<accion>`

Respuesta estándar:

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

La autenticación se realiza mediante la cookie de sesión del sistema.

| Módulo | Operaciones principales |
|---|---|
| `auth.php` | login, logout, session |
| `users.php` | listado, roles, creación, actualización y eliminación según permisos |
| `datasets.php` | list/search, detail, create, update, delete, export |
| `quality.php` | history, register, delete |
| `policies.php` | list, detail, create, update, delete, assign, unassign |
| `ai.php` | status, classify, anomalies, summary, suggest y acciones auxiliares de confirmación/resolución cuando aplican |
| `reports.php` | overview, alerts, export y logs restringidos a Admin |
| `compliance.php` | consulta y gestión de controles/evaluaciones de cumplimiento |
| `lineage.php` | consulta y gestión de relaciones de linaje |
| `monitoring.php` | consulta, configuración, activación, ejecución e historial |
| `integrations.php` | gestión de fuentes, prueba de conexión, sincronización e historial |

La colección de Postman incluida permite reproducir manualmente los **flujos principales** de la API; no se considera una cobertura exhaustiva de todas las combinaciones posibles.

---

## 15. Criterios de aceptación

1. Un usuario puede autenticarse y solo ejecutar las acciones permitidas por su rol.
2. El sistema diferencia correctamente los cuatro roles definidos.
3. El score calculado por el backend coincide con la fórmula ponderada.
4. Un dataset con score menor a 80 genera una alerta visual.
5. Las políticas pueden asignarse y desasignarse sin producir duplicados.
6. La búsqueda semántica puede utilizar IA y mantiene un fallback cuando el proveedor no está disponible.
7. Las evaluaciones de cumplimiento conservan sus estados y relación con el dataset.
8. El linaje impide relaciones de origen y destino iguales.
9. Con clave configurada, las acciones de IA pueden utilizar un proveedor real; sin clave, el modo simulado continúa disponible.
10. El monitoreo registra ejecuciones y no crea métricas inexistentes.
11. Las fuentes externas pueden probarse y sincronizar metadatos sin copiar el contenido completo.
12. La protección SSRF bloquea destinos privados/locales cuando el modo de desarrollo se encuentra desactivado.
13. La API responde JSON controlado y respeta sesión, rol y propiedad del recurso.
14. El dashboard y la actividad reciente respetan el alcance de cada rol.
15. La bitácora administrativa completa permanece restringida al Admin.
16. Las acciones principales del sistema pueden ejecutarse de extremo a extremo sin respuestas 500 ni errores JavaScript no controlados.

---

## 16. Métricas de éxito

| Métrica | Meta |
|---|---|
| Roles funcionando | 4/4 con permisos correctos |
| Acciones principales de IA | 4/4 operativas en modo real y simulado |
| API | Respuestas JSON controladas y validación de sesión/rol |
| Integridad de base de datos | Sin registros huérfanos ni duplicados donde existen restricciones de unicidad |
| Flujos principales | Ejecutables de extremo a extremo |
| Seguridad básica | Sin secretos reales incluidos en Git |
| Interfaz | Vistas principales utilizables en escritorio y móvil |
| Alcance funcional | Módulos requeridos por el proyecto implementados y disponibles |

### 16.1 Evidencia de validación disponible

La revisión funcional final del proyecto incluyó pruebas de sintaxis PHP/JavaScript, permisos por rol, API, navegador, integridad de datos, IA real/simulada y controles de seguridad. El reporte QA formal documentará los resultados completos y sus evidencias.

---

## 17. Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Consumo o límite de tokens de IA | Modo simulado y configuración intercambiable del proveedor |
| Caídas o límites del proveedor | Reintentos ante errores transitorios y fallback cuando corresponde |
| Fuga de credenciales | `.env` excluido de Git y plantilla `.env.example` sin secretos |
| SSRF en fuentes externas | Validación de esquema/host/DNS y bloqueo de redes privadas en operación normal |
| Respuestas externas demasiado grandes | Límite máximo de respuesta y timeout |
| Datos de calidad inexistentes | El monitoreo informa el caso y no inventa mediciones |
| Acceso indebido por rol | Validación de sesión, rol y propiedad en el servidor |
| Exposición de actividad administrativa | Feed filtrado por rol y bitácora completa restringida a Admin |
| Despliegue productivo sin controles adicionales | Mantener el uso académico/local y considerar CSRF, HTTPS y endurecimiento adicional antes de producción |

---

## 18. Estado de implementación

La plataforma evolucionó desde un núcleo inicial de autenticación, usuarios, catálogo, calidad, políticas, IA, dashboard y reportes hacia el alcance final requerido por el proyecto.

Los principales bloques implementados son:

1. Estructura base, conexión PDO, esquema SQL y seed.
2. Autenticación, sesiones y plantilla de navegación.
3. Gestión de usuarios y roles.
4. Catálogo y detalle de datasets.
5. Calidad de datos y score ponderado.
6. Políticas y asignación a datasets.
7. Módulo de IA real/simulado.
8. Dashboard, reportes y bitácora.
9. Endurecimiento de seguridad, Postman y proveedor de IA configurable.
10. Búsqueda semántica asistida por IA y linaje.
11. Cumplimiento normativo interno.
12. Monitoreo continuo/automático.
13. Fuentes externas e integraciones REST/JSON.
14. Validación funcional, revisión de permisos, responsive y cierre técnico.

El README técnico del repositorio conserva el detalle operativo de instalación, configuración, módulos, API y seguridad.

---

## 19. Definition of Done

El producto se considera terminado cuando:

- [x] La autenticación y los cuatro roles funcionan.
- [x] El catálogo y detalle de datasets funcionan.
- [x] La calidad y el cálculo ponderado funcionan.
- [x] Las políticas y asignaciones funcionan.
- [x] La búsqueda semántica asistida por IA funciona.
- [x] El cumplimiento interno funciona.
- [x] El linaje funciona.
- [x] El monitoreo funciona.
- [x] Las fuentes externas REST/JSON funcionan.
- [x] Las cuatro acciones principales de IA funcionan en modo real y simulado.
- [x] Dashboard, reportes y bitácora funcionan.
- [x] La API REST aplica autenticación y permisos.
- [x] El README contiene instalación, configuración y seguridad.
- [x] Existe seed con usuarios de prueba por rol.
- [x] La aplicación fue revisada funcionalmente de extremo a extremo.
- [ ] La documentación formal de entrega se encuentra completa.
- [ ] Se genera el ZIP limpio definitivo para entrega.

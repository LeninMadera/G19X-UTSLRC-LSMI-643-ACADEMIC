# REPORTE QA
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Reporte de aseguramiento de calidad (QA)  
**Versión:** 1.0  
**Estado:** Final  
**Resultado general:** Aprobado para cierre académico, con hallazgos críticos corregidos

---

## 1. Introducción

El presente reporte documenta las pruebas de calidad realizadas sobre la Plataforma de Gobierno de Datos con Inteligencia Artificial. El objetivo de la validación fue comprobar el funcionamiento general del sistema, el control de acceso por roles, la integridad de la base de datos, la respuesta de la API, el comportamiento de los módulos de Inteligencia Artificial, monitoreo e integraciones, así como la estabilidad de la interfaz.

Las pruebas se realizaron sobre una copia de auditoría separada del proyecto y de la base de datos principal. De esta forma, las validaciones destructivas o de modificación no alteraron la información real utilizada por el proyecto.

---

## 2. Objetivo de las pruebas

Validar que la plataforma:

- Funcione de extremo a extremo.
- Respete los permisos definidos para los cuatro roles.
- Mantenga integridad de datos.
- Responda sin errores críticos del servidor.
- Entregue respuestas JSON controladas desde la API.
- Mantenga operativos los módulos principales.
- No exponga secretos dentro del repositorio.
- Aplique controles básicos de seguridad.
- Funcione de forma usable en escritorio y dispositivos móviles.

---

## 3. Alcance de QA

La validación cubrió los siguientes componentes:

| Área | Elementos revisados |
|---|---|
| Autenticación | Login, logout, sesión, redirecciones |
| Usuarios | CRUD, roles, activación/desactivación |
| Datasets | Catálogo, detalle, búsqueda, edición |
| Calidad | Registro, historial, score ponderado |
| Políticas | CRUD, asignación y desasignación |
| IA | Modo real y simulado |
| Búsqueda semántica | Expansión de consulta y fallback |
| Cumplimiento | Controles y evaluaciones |
| Linaje | Relaciones entre datasets |
| Monitoreo | Configuración, ejecución e historial |
| Integraciones | REST/JSON, prueba, sincronización y SSRF |
| Dashboard | KPIs, alertas y actividad |
| Reportes | Calidad, alertas y bitácora |
| API | Sesión, permisos y respuestas JSON |
| Base de datos | Tablas, relaciones e integridad |
| Interfaz | Vistas, responsive y errores JavaScript |
| Seguridad | Sesión, permisos, SQLi, XSS, secretos y SSRF |

---

## 4. Entorno de pruebas

Las pruebas principales se realizaron en un entorno sandbox separado.

**Características del entorno:**

- Copia aislada del proyecto.
- Base de datos independiente de auditoría.
- Servidor PHP local.
- Fuente JSON simulada para integraciones.
- Proveedor de IA real y modo simulado.
- Navegador automatizado para pruebas E2E.
- Git utilizado únicamente para revisar el estado y cambios del proyecto.

La base de datos real del proyecto no fue modificada durante la auditoría.

---

## 5. Validación estática

### 5.1 PHP

Se revisaron los archivos PHP principales del proyecto mediante comprobación de sintaxis.

**Resultado:**

- 35 archivos PHP revisados.
- 0 errores de sintaxis detectados.

### 5.2 JavaScript

Se revisaron los archivos JavaScript mediante validación de sintaxis.

**Resultado:**

- 13 archivos JavaScript revisados.
- 0 errores de sintaxis detectados.

### 5.3 JSON

Se verificaron los archivos JSON utilizados por configuración y Postman.

**Resultado:**

- Archivos JSON válidos.
- Sin errores estructurales detectados.

---

## 6. Validación de base de datos

La base de datos utilizada por el sistema contiene 15 tablas funcionales:

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

También se verificó la presencia de las migraciones correspondientes a:

- Linaje.
- Cumplimiento.
- Monitoreo.
- Integraciones.

### Resultado

- Integridad general correcta.
- Sin registros huérfanos detectados durante la auditoría.
- Las relaciones principales se comportaron de forma consistente con el diseño.

---

## 7. Pruebas de autenticación

Se validaron los siguientes escenarios:

| Caso | Resultado |
|---|---|
| Login con credenciales válidas | Aprobado |
| Login con credenciales inválidas | Aprobado |
| Regeneración de sesión | Aprobado |
| Acceso sin sesión | Aprobado |
| Cierre de sesión | Aprobado |
| Redirección de vistas protegidas | Aprobado |

La autenticación funcionó correctamente con los cuatro roles definidos.

---

## 8. Pruebas de permisos por rol

Se evaluaron las acciones de la API con cinco estados de sesión:

- Sin sesión.
- Admin.
- Steward.
- Owner.
- Analyst.

La matriz evaluó:

**61 acciones de API × 5 estados de sesión = 305 validaciones.**

### Resultado final

**305/305 validaciones de permisos correctas.**

La distribución de permisos se mantuvo alineada con la implementación esperada.

Ejemplo del módulo de usuarios:

- Sin sesión: `401`.
- Steward: `403`.
- Owner: `403`.
- Analyst: `403`.
- Admin: acceso permitido de acuerdo con la acción.

---

## 9. Pruebas E2E por rol

Se ejecutaron recorridos de navegador sobre las vistas principales con los cuatro roles.

**Configuración:**

- 4 roles.
- 12 vistas principales.
- Validaciones de navegación y contenido.

### Resultado

**72/72 pruebas E2E aprobadas.**

No se detectaron errores no controlados que impidieran utilizar las vistas principales.

---

## 10. Pruebas funcionales de API

Se ejecutaron flujos funcionales sobre los módulos principales.

### Resultado de la validación final

- 102 flujos funcionales ejecutados.
- 0 fallos por error de red.
- 0 respuestas 5xx detectadas en la batería final.

El cambio esperado después de la corrección de administradores fue:

- Intentar desactivar la propia cuenta de administrador pasó de una respuesta permitida a un rechazo controlado `400`.

---

## 11. Pruebas de CRUD por interfaz

Se probaron operaciones de creación, consulta, actualización y eliminación o acciones equivalentes desde la interfaz.

### Resultado

**22/23 pruebas aprobadas.**

El único caso que no finalizó como una operación normal corresponde al monitoreo de un dataset sin métricas de calidad.

Este comportamiento se considera correcto, ya que el sistema debe informar que no existen mediciones disponibles en lugar de inventar valores de calidad.

---

## 12. Pruebas del módulo de calidad

Se validó:

- Registro de las cinco dimensiones.
- Cálculo del score.
- Historial.
- Umbral de alerta.
- Restricciones por rol.

La fórmula utilizada por el sistema es:

`score = completitud·0.20 + exactitud·0.25 + consistencia·0.20 + vigencia·0.15 + unicidad·0.20`

### Resultado

- Cálculo correcto.
- Alertas visuales funcionales.
- Historial disponible.
- Restricciones por rol respetadas.

---

## 13. Pruebas de Inteligencia Artificial

Se probaron las cuatro acciones principales:

| Acción | Modo real | Modo simulado |
|---|---|---|
| Clasificación | Aprobado | Aprobado |
| Anomalías | Aprobado | Aprobado |
| Resumen | Aprobado | Aprobado |
| Sugerencias | Aprobado | Aprobado |

También se verificó:

- Proveedor configurable.
- Fallback al modo simulado.
- Persistencia de resultados cuando corresponde.
- Manejo de respuestas del proveedor.
- Uso de IA dentro de los permisos establecidos.

---

## 14. Pruebas de búsqueda semántica

Se verificó que:

- La consulta puede expandirse con términos relacionados mediante IA.
- La búsqueda utiliza campos del catálogo.
- Existe fallback a la búsqueda tradicional.
- El sistema no requiere embeddings ni base vectorial.

### Resultado

Funcionalidad aprobada y consistente con el diseño de búsqueda semántica asistida por IA.

---

## 15. Pruebas de cumplimiento

Se validaron:

- Registro de controles.
- Evaluaciones por dataset.
- Estados disponibles.
- Persistencia.
- Permisos.

Estados probados:

- `cumple`
- `no_cumple`
- `pendiente`
- `no_aplica`

### Resultado

Módulo funcional y sin errores críticos detectados.

---

## 16. Pruebas de linaje

Se verificó:

- Registro de relaciones.
- Consulta de relaciones.
- Diferentes tipos de linaje.
- Restricción de origen y destino iguales.
- Permisos por rol.

### Resultado

Linaje funcional y consistente con las reglas de negocio.

---

## 17. Pruebas de monitoreo

Se validó:

- Creación de configuración.
- Activación y desactivación.
- Ejecución manual.
- Historial.
- Uso de última métrica real.
- Manejo de dataset sin mediciones.
- Prevención de ejecuciones concurrentes.
- Ejecución mediante CLI.

### Resultado

El módulo funcionó correctamente y no generó métricas de calidad inexistentes.

---

## 18. Pruebas de integraciones externas

Se probaron fuentes REST/JSON utilizando solicitudes GET.

Se validó:

- Alta de fuente.
- Prueba de conexión.
- Sincronización.
- Historial.
- Asociación con dataset.
- Límites de respuesta.
- Timeout.
- Bloqueo de destinos privados/locales en operación normal.

### Resultado

Módulo funcional y controles SSRF verificados.

---

## 19. Pruebas de seguridad

### 19.1 SQL Injection

Se revisó el acceso a datos basado en PDO y consultas preparadas.

**Resultado:** no se detectó explotación por SQL Injection durante las pruebas realizadas.

### 19.2 XSS

Se revisó el escapado de contenido en frontend y backend.

**Resultado:** no se detectaron casos de XSS explotables durante la revisión manual realizada.

### 19.3 Secretos

Se revisó el historial de Git y los archivos trackeados.

**Resultado:**

- `.env` no se encuentra trackeado.
- No se detectaron claves reales dentro del historial del repositorio revisado.
- `.env.example` se mantiene como plantilla pública.

### 19.4 SSRF

Las integraciones externas fueron probadas con controles de:

- Esquema.
- Host.
- DNS.
- Redes privadas.
- Loopback.
- Link-local.
- Redirecciones.
- Timeout.
- Tamaño máximo de respuesta.

**Resultado:** controles operativos en el alcance implementado.

---

## 20. Pruebas con entradas inválidas

Se probaron 27 entradas malformadas o inválidas.

### Resultado

- No se detectaron errores 500.
- No se detectaron respuestas HTML inesperadas desde la API.
- Los errores se devolvieron de forma controlada.

---

## 21. Pruebas responsive

Se revisaron las vistas principales en escritorio y móvil.

Durante la etapa visual se ejecutaron validaciones de layout y responsive, incluyendo correcciones previas de desbordamiento.

### Resultado final

- 96/96 verificaciones responsive aprobadas en la batería específica.
- Sin desbordamientos críticos detectados en las vistas finales.
- Formularios, tablas, navegación y modales utilizables en resoluciones móviles.

---

## 22. Auditoría funcional general

La auditoría funcional completa ejecutó:

**578 comprobaciones.**

La revisión incluyó:

- Sintaxis.
- Permisos.
- API.
- E2E.
- Base de datos.
- IA.
- Integraciones.
- Seguridad.
- Manejo de errores.
- Interfaz.

### Resultado general

No se detectaron:

- Errores 500 generalizados.
- Respuestas HTML inesperadas de API.
- Errores JavaScript no controlados que impidieran usar el sistema.
- Corrupción de datos durante las pruebas.

La auditoría sí identificó hallazgos específicos que fueron clasificados por severidad.

---

## 23. Hallazgos de la auditoría

| ID | Severidad | Hallazgo | Estado |
|---|---|---|---|
| H-01 | Alta | Un Admin podía desactivar su propia cuenta | Corregido |
| H-02 | Media | `views/users.php` podía entregar HTML parcial a usuarios no autorizados | Corregido |
| H-03 | Baja | Endpoints no validan estrictamente el método HTTP | Aceptado como mejora futura |
| H-04 | Baja | Conversión permisiva de algunos IDs | Aceptado como mejora futura |
| H-05 | Baja | Texto simulado “1 anomalias” | No afecta funcionalidad |
| H-06 | Informativa | Frontend obtiene el rol desde dos fuentes | Sin impacto de seguridad |
| H-07 | Informativa | `database.sql` recrea la base al instalar | Documentado |

---

## 24. Correcciones finales aplicadas

Después de la auditoría se implementaron las dos correcciones consideradas necesarias.

### 24.1 Protección contra bloqueo administrativo

Se modificó:

`api/users.php`

Reglas agregadas:

1. Un administrador no puede desactivar su propia cuenta.
2. No se puede desactivar a un administrador cuando esto dejaría el sistema sin ningún administrador activo.

### 24.2 Autorización temprana en vista de usuarios

Se modificó:

`views/users.php`

La validación de sesión y rol ahora se realiza antes de cargar el encabezado HTML.

Comportamiento final:

- Sin sesión → redirección a login.
- No Admin → redirección a dashboard.
- Admin → vista completa.

### 24.3 Documentación de localhost en integraciones

Se actualizó el README para indicar que:

`INTEGRACION_PERMITIR_LOCALHOST=true`

debe utilizarse únicamente para desarrollo o pruebas.

---

## 25. Validación posterior a las correcciones

Se ejecutó una batería específica para confirmar que las correcciones no introdujeran regresiones.

| Suite | Resultado |
|---|---|
| PHP lint en archivos modificados | 0 errores |
| `git diff --check` | Limpio |
| Batería específica de la corrección | 22/22 |
| Matriz de permisos | 305/305 |
| Flujos funcionales API | 102, sin 5xx/red |
| E2E navegador | 72/72 |
| CRUD por UI | 22/23, comportamiento esperado en monitoreo sin métricas |

La base de datos real permaneció sin modificaciones durante esta batería.

---

## 26. Estado de los hallazgos

### Cerrados

- Auto-desactivación de Admin.
- Posibilidad de dejar el sistema sin administradores activos.
- HTML parcial en vista de usuarios.
- Advertencia de seguridad sobre localhost en integraciones.

### Mejoras futuras no bloqueantes

- Validación estricta de `REQUEST_METHOD`.
- Validación más estricta del formato de IDs.
- Corrección gramatical del texto simulado.
- Unificación de la fuente de rol utilizada por frontend.
- Protección CSRF para peticiones POST mutantes (token en sesión, envío vía `X-CSRF-Token`, validación en backend; excepción `login`, sin validación en GET).
- Endurecimiento adicional para un despliegue productivo.

Estas mejoras no impiden la entrega académica del sistema.

---

## 27. Criterios de aceptación QA

| Criterio | Estado |
|---|---|
| Autenticación funcional | Aprobado |
| 4 roles operativos | Aprobado |
| Permisos backend | Aprobado |
| Catálogo | Aprobado |
| Calidad | Aprobado |
| Políticas | Aprobado |
| IA | Aprobado |
| Búsqueda semántica | Aprobado |
| Cumplimiento | Aprobado |
| Linaje | Aprobado |
| Monitoreo | Aprobado |
| Integraciones | Aprobado |
| Dashboard | Aprobado |
| Reportes | Aprobado |
| Bitácora | Aprobado |
| API JSON | Aprobado |
| Integridad BD | Aprobado |
| Seguridad básica | Aprobado |
| Responsive | Aprobado |
| Hallazgos críticos | Corregidos |

---

## 28. Resultado final

La Plataforma de Gobierno de Datos con Inteligencia Artificial cumple satisfactoriamente con las pruebas funcionales realizadas para el alcance académico definido.

Los módulos principales fueron probados de extremo a extremo y los permisos fueron validados con los cuatro roles. La auditoría identificó dos hallazgos relevantes que fueron corregidos y posteriormente sometidos a pruebas de regresión.

No quedaron hallazgos de severidad alta o media abiertos después de la corrección final.

---

## 29. Conclusión

Con base en los resultados obtenidos, el sistema se considera **aprobado desde el punto de vista funcional para la entrega académica**.

La plataforma mantiene autenticación, control por roles, integridad de datos, módulos de Gobierno de Datos, Inteligencia Artificial, monitoreo, integraciones, reportes y auditoría funcionando dentro del alcance establecido.

Las observaciones restantes corresponden a mejoras de endurecimiento o evolución futura y no representan bloqueos para el cierre del proyecto.

# DOCUMENTO DE REQUERIMIENTOS
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Documento de requerimientos  
**Versión:** 1.0  
**Estado:** Final  
**Tecnologías implementadas:** PHP 8.x, MySQL 8, HTML5, CSS3, JavaScript, API REST + JSON y API compatible con OpenAI (Groq/OpenAI)

---

## 1. Introducción

El presente documento define los requerimientos funcionales y no funcionales de la Plataforma de Gobierno de Datos con Inteligencia Artificial. La solución fue desarrollada para centralizar la administración de activos de datos, su calidad, políticas, cumplimiento, linaje, monitoreo, fuentes externas, reportes y funciones asistidas por Inteligencia Artificial.

La plataforma administra principalmente metadatos y elementos relacionados con el gobierno de datos. No realiza la ingestión masiva del contenido completo de los datasets ni sustituye herramientas empresariales especializadas de integración o cumplimiento legal.

---

## 2. Propósito

El propósito del sistema es proporcionar un entorno centralizado para registrar, consultar y administrar información relacionada con el gobierno de datos empresariales, facilitando la trazabilidad, la evaluación de calidad, la aplicación de políticas, el seguimiento de cumplimiento y el análisis mediante Inteligencia Artificial.

La plataforma debe permitir que distintos perfiles de usuario trabajen sobre la misma información de acuerdo con los permisos asignados a su rol.

---

## 3. Alcance

La plataforma incluye los siguientes módulos:

- Autenticación y sesiones.
- Gestión de usuarios y roles.
- Catálogo y detalle de datasets.
- Búsqueda tradicional y búsqueda semántica asistida por IA.
- Calidad de datos.
- Políticas de gobierno.
- Cumplimiento normativo interno.
- Linaje de datos.
- Monitoreo continuo.
- Fuentes externas REST/JSON.
- Asistente de Inteligencia Artificial.
- Dashboard.
- Reportes, alertas y bitácora de actividad.
- API REST JSON.

### 3.1 Fuera del alcance

No forman parte del alcance actual:

- Ingesta o copia masiva del contenido completo de datasets externos.
- Procesos ETL completos.
- Sincronización bidireccional con sistemas externos.
- Notificaciones por correo o mensajería.
- Despliegue productivo con dominio, HTTPS o alta disponibilidad.
- Dictamen legal automático de cumplimiento.
- Motor vectorial de embeddings.
- Medición automática de calidad directamente sobre el contenido bruto de fuentes externas.

---

## 4. Usuarios y roles

La plataforma utiliza cuatro roles principales:

| Rol | Descripción | Alcance general |
|---|---|---|
| **Admin** | Administrador del sistema | Acceso total, incluida la administración de usuarios y la bitácora completa |
| **Steward** | Responsable de gobierno de datos | Gestión de datasets, calidad, políticas, cumplimiento, linaje, monitoreo, integraciones e IA según permisos |
| **Owner** | Responsable de datasets | Gestión limitada a datasets y recursos propios |
| **Analyst** | Usuario de consulta | Acceso principalmente de lectura |

### 4.1 Reglas generales de acceso

- Solo el rol Admin puede administrar usuarios.
- El Owner no puede modificar recursos ajenos.
- El Analyst no puede ejecutar acciones de escritura restringidas.
- La bitácora administrativa completa está disponible solo para Admin.
- La actividad reciente se filtra de acuerdo con el rol y el alcance del usuario.
- La autorización debe aplicarse en el servidor, no únicamente en la interfaz.

---

## 5. Requerimientos funcionales

### 5.1 Autenticación y usuarios

**RF-01.** El sistema debe permitir iniciar sesión mediante correo electrónico y contraseña.  
**RF-02.** El sistema debe permitir cerrar sesión e invalidar la sesión activa.  
**RF-03.** El sistema debe impedir el acceso a vistas protegidas cuando no exista una sesión válida.  
**RF-04.** El sistema debe permitir al Admin consultar, crear, editar, activar, desactivar y eliminar usuarios según las restricciones implementadas.  
**RF-05.** El sistema no debe permitir que un administrador desactive su propia cuenta.  
**RF-06.** El sistema no debe permitir que se desactive al último administrador activo.  
**RF-07.** El sistema debe permitir asignar uno de los cuatro roles disponibles a cada usuario.

### 5.2 Catálogo de datasets

**RF-08.** El sistema debe permitir registrar datasets con nombre, descripción, categoría, origen y responsable.  
**RF-09.** El sistema debe permitir consultar el catálogo de datasets.  
**RF-10.** El sistema debe permitir buscar datasets mediante texto y filtros.  
**RF-11.** El sistema debe mostrar una ficha de detalle de cada dataset.  
**RF-12.** El sistema debe permitir editar datasets de acuerdo con los permisos del rol.  
**RF-13.** El Owner debe poder crear y editar únicamente datasets dentro de su alcance.  
**RF-14.** La eliminación de datasets debe respetar las restricciones de rol definidas en la API.  
**RF-15.** El sistema debe permitir exportar información del catálogo cuando la acción se encuentre disponible.

### 5.3 Búsqueda semántica

**RF-16.** El sistema debe ofrecer búsqueda semántica asistida por IA.  
**RF-17.** La búsqueda semántica debe utilizar la consulta del usuario para generar términos relacionados cuando el proveedor de IA esté disponible.  
**RF-18.** La búsqueda debe utilizar campos como nombre, descripción, etiquetas, categoría y origen.  
**RF-19.** Si la IA no está disponible, el sistema debe conservar la búsqueda tradicional como alternativa.  
**RF-20.** El sistema no debe enviar al proveedor externo el contenido completo de los datasets para realizar la búsqueda.

### 5.4 Calidad de datos

**RF-21.** El sistema debe permitir registrar mediciones de calidad en cinco dimensiones: completitud, exactitud, consistencia, vigencia y unicidad.  
**RF-22.** Cada dimensión debe aceptar valores de 0 a 100.  
**RF-23.** El sistema debe calcular automáticamente el score global de calidad.  
**RF-24.** El score debe calcularse con los pesos: completitud 20 %, exactitud 25 %, consistencia 20 %, vigencia 15 % y unicidad 20 %.  
**RF-25.** El sistema debe mostrar una alerta cuando el score sea menor a 80.  
**RF-26.** El sistema debe clasificar el resultado como Excelente, Aceptable o Por mejorar.  
**RF-27.** El Owner debe poder registrar calidad únicamente en datasets propios.  
**RF-28.** La eliminación de mediciones debe respetar las restricciones de rol.

### 5.5 Políticas

**RF-29.** El sistema debe permitir registrar políticas de gobierno.  
**RF-30.** El sistema debe permitir editar, activar, desactivar y eliminar políticas según los permisos definidos.  
**RF-31.** El sistema debe permitir asignar y desasignar políticas a datasets.  
**RF-32.** Una misma política no debe duplicarse sobre el mismo dataset.

### 5.6 Cumplimiento

**RF-33.** El sistema debe permitir registrar controles de cumplimiento.  
**RF-34.** Los controles deben poder relacionarse con políticas cuando corresponda.  
**RF-35.** El sistema debe permitir evaluar un dataset mediante los estados: cumple, no cumple, pendiente y no aplica.  
**RF-36.** Las evaluaciones deben conservar observaciones, evidencia, evaluador y fecha cuando existan.  
**RF-37.** El módulo debe considerarse un registro declarativo interno y no un dictamen legal.  
**RF-38.** El Owner debe poder evaluar únicamente datasets de su propiedad.

### 5.7 Linaje de datos

**RF-39.** El sistema debe permitir registrar relaciones de linaje entre datasets.  
**RF-40.** El sistema debe admitir relaciones como derivado de, alimenta, integra, transforma, copia y versión de.  
**RF-41.** El sistema no debe permitir que un dataset sea origen y destino de la misma relación.  
**RF-42.** El sistema debe evitar duplicados de una misma relación de linaje.  
**RF-43.** El Owner solo debe gestionar relaciones cuando los datasets involucrados se encuentren dentro de su alcance.

### 5.8 Monitoreo

**RF-44.** El sistema debe permitir crear una configuración de monitoreo asociada a un dataset.  
**RF-45.** El sistema debe permitir definir la frecuencia de monitoreo dentro de los límites configurados.  
**RF-46.** El sistema debe permitir activar y desactivar configuraciones.  
**RF-47.** El sistema debe permitir ejecutar el monitoreo manualmente.  
**RF-48.** El sistema debe registrar el historial de ejecuciones.  
**RF-49.** El sistema debe evitar ejecuciones concurrentes sobre una misma configuración.  
**RF-50.** El monitoreo debe utilizar la última medición real de calidad disponible.  
**RF-51.** El sistema no debe inventar métricas cuando un dataset no tenga mediciones registradas.  
**RF-52.** El sistema debe permitir la programación externa del monitoreo mediante el CLI incluido.

### 5.9 Fuentes externas

**RF-53.** El sistema debe permitir registrar fuentes externas REST/JSON.  
**RF-54.** Las fuentes externas deben utilizar solicitudes GET.  
**RF-55.** El sistema debe permitir vincular opcionalmente una fuente con un dataset.  
**RF-56.** El sistema debe permitir probar la conexión con una fuente.  
**RF-57.** El sistema debe permitir sincronizar metadatos de la fuente.  
**RF-58.** El sistema debe conservar un historial de sincronizaciones.  
**RF-59.** La sincronización no debe copiar de forma masiva el contenido externo a MySQL.  
**RF-60.** El sistema debe aplicar controles contra SSRF, redirecciones no autorizadas, respuestas excesivamente grandes y tiempos de espera.

### 5.10 Inteligencia Artificial

**RF-61.** El sistema debe permitir configurar un proveedor compatible con la API de OpenAI.  
**RF-62.** El sistema debe permitir utilizar proveedores como Groq u OpenAI mediante configuración.  
**RF-63.** El sistema debe permitir clasificar datasets y sugerir categoría y etiquetas.  
**RF-64.** El sistema debe detectar anomalías con base en el historial de calidad.  
**RF-65.** El sistema debe generar un resumen del estado de un dataset.  
**RF-66.** El sistema debe generar sugerencias de mejora a partir de las dimensiones de calidad.  
**RF-67.** El sistema debe disponer de un modo simulado cuando no exista una clave de IA configurada.  
**RF-68.** Las detecciones que requieran persistencia deben registrar la información correspondiente en la base de datos.  
**RF-69.** Las acciones de IA deben respetar los permisos establecidos para cada rol.

### 5.11 Dashboard, reportes y bitácora

**RF-70.** El sistema debe mostrar un dashboard con indicadores generales de la plataforma.  
**RF-71.** El dashboard debe mostrar información obtenida de datos reales registrados en la plataforma.  
**RF-72.** El sistema debe mostrar alertas y actividad reciente.  
**RF-73.** La actividad reciente debe filtrarse en el servidor según el rol y el alcance del usuario.  
**RF-74.** El sistema debe generar reportes de calidad, alertas y actividad.  
**RF-75.** El acceso a la bitácora completa debe estar restringido al Admin.  
**RF-76.** El sistema debe registrar acciones relevantes con usuario, detalle, fecha e información disponible de auditoría.

### 5.12 API REST

**RF-77.** El sistema debe exponer las operaciones principales mediante una API REST con respuestas JSON.  
**RF-78.** Los endpoints protegidos deben requerir una sesión válida.  
**RF-79.** Las acciones restringidas deben validar el rol y la propiedad del recurso cuando corresponda.  
**RF-80.** Los errores controlados de la API deben responder en formato JSON.

---

## 6. Requerimientos no funcionales

**RNF-01. Seguridad de credenciales.** Las contraseñas deben almacenarse mediante `password_hash()` y nunca en texto plano.  
**RNF-02. Sesiones.** La sesión debe regenerarse al autenticar al usuario y utilizar las protecciones disponibles en la aplicación.  
**RNF-03. SQL Injection.** El acceso a MySQL debe realizarse mediante PDO y consultas preparadas.  
**RNF-04. XSS.** El sistema debe escapar contenido no confiable antes de insertarlo en HTML.  
**RNF-05. Secretos.** Las claves y contraseñas de configuración deben almacenarse en `.env`, excluido del repositorio.  
**RNF-06. Integridad.** La base de datos debe utilizar llaves foráneas, restricciones e índices para preservar relaciones válidas.  
**RNF-07. Manejo de errores.** Los errores de API deben controlarse sin exponer información interna innecesaria.  
**RNF-08. Compatibilidad.** El sistema debe funcionar con PHP 8.x, MySQL 8 y navegadores modernos.  
**RNF-09. Responsive.** Las vistas principales deben conservar su funcionalidad en escritorio y dispositivos móviles.  
**RNF-10. Resiliencia de IA.** La aplicación debe manejar errores transitorios del proveedor y conservar el modo simulado como alternativa.  
**RNF-11. Seguridad de integraciones.** Las fuentes externas deben validar esquema, host y destino de red en operación normal.  
**RNF-12. Rendimiento de integraciones.** Las solicitudes externas deben utilizar timeout y límite máximo de respuesta.  
**RNF-13. Auditoría.** Las acciones relevantes deben quedar registradas en bitácora.  
**RNF-14. Accesibilidad básica.** Los formularios y modales deben permitir interacción mediante teclado y estados de foco visibles.  
**RNF-15. Mantenibilidad.** El proyecto debe mantener separación entre vistas, JavaScript, API, configuración, servicios y SQL.

---

## 7. Reglas de negocio

**RN-01.** Debe existir al menos un administrador activo.  
**RN-02.** Un administrador no puede desactivar su propia cuenta.  
**RN-03.** Un Owner solo puede modificar recursos propios cuando el módulo aplique propiedad.  
**RN-04.** Un Analyst no puede ejecutar acciones de escritura restringidas.  
**RN-05.** No puede existir una relación de linaje con el mismo dataset como origen y destino.  
**RN-06.** La calidad se calcula siempre con los cinco pesos definidos por el sistema.  
**RN-07.** Un score menor a 80 se considera una condición de alerta.  
**RN-08.** El monitoreo no crea mediciones de calidad inexistentes.  
**RN-09.** Una fuente externa sincroniza metadatos, no el contenido completo de sus registros.  
**RN-10.** La bitácora completa solo puede consultarse por Admin.  
**RN-11.** `INTEGRACION_PERMITIR_LOCALHOST=true` debe utilizarse únicamente para desarrollo o pruebas.  
**RN-12.** Los resultados del módulo de cumplimiento no constituyen un dictamen legal.

---

## 8. Entidades principales

La plataforma utiliza, entre otras, las siguientes entidades funcionales:

- Usuarios.
- Roles.
- Datasets.
- Mediciones de calidad.
- Políticas.
- Asignaciones de políticas.
- Detecciones de IA.
- Bitácora de actividad.
- Linaje.
- Controles de cumplimiento.
- Evaluaciones de cumplimiento.
- Configuraciones de monitoreo.
- Ejecuciones de monitoreo.
- Fuentes externas.
- Ejecuciones de sincronización.

El esquema físico se encuentra definido en `sql/database.sql` y en las migraciones incluidas en el repositorio.

---

## 9. Criterios generales de aceptación

1. Los cuatro roles pueden autenticarse y reciben los permisos establecidos.
2. Los usuarios sin sesión no pueden acceder a contenido protegido.
3. El Admin conserva acceso a la administración de usuarios.
4. No es posible dejar el sistema sin administradores activos mediante la interfaz/API.
5. El catálogo permite registrar, buscar y consultar datasets.
6. La búsqueda semántica opera con IA y conserva un fallback.
7. El score de calidad coincide con la fórmula definida.
8. Los datasets con score menor a 80 muestran una condición de alerta.
9. Las políticas pueden asignarse sin duplicarse.
10. El cumplimiento registra los cuatro estados definidos.
11. El linaje no permite autorrelaciones.
12. El monitoreo no inventa mediciones.
13. Las integraciones prueban y sincronizan metadatos mediante REST/JSON.
14. Las cuatro acciones principales de IA funcionan en modo real y simulado.
15. La actividad reciente respeta el alcance de cada rol.
16. La bitácora completa se mantiene restringida al Admin.
17. La API utiliza respuestas JSON controladas.
18. Las vistas principales funcionan en escritorio y móvil sin errores JavaScript no controlados.

---

## 10. Restricciones y consideraciones

- Las tecnologías indicadas originalmente en la propuesta del proyecto fueron consideradas como referencia. La implementación final utiliza las tecnologías autorizadas y conocidas por el estudiante.
- El sistema se encuentra orientado a un entorno académico/local y no debe considerarse listo para producción sin controles adicionales.
- La plataforma no implementa actualmente tokens anti-CSRF en los endpoints mutantes.
- `sql/database.sql` recrea la base de datos; debe utilizarse únicamente cuando se requiera una instalación nueva o una base que pueda ser reemplazada.
- Las pruebas con fuentes locales requieren habilitar explícitamente `INTEGRACION_PERMITIR_LOCALHOST`, opción que no debe mantenerse activa en operación normal.
- La calidad de las respuestas de IA depende del proveedor y modelo configurados.
- El programador de monitoreo se ejecuta externamente mediante el CLI y herramientas del sistema operativo.

---

## 11. Trazabilidad con el alcance del proyecto

| Necesidad del proyecto | Requerimientos relacionados |
|---|---|
| Catálogo y metadatos | RF-08 a RF-15 |
| Búsqueda semántica | RF-16 a RF-20 |
| Calidad de datos | RF-21 a RF-28 |
| Políticas | RF-29 a RF-32 |
| Cumplimiento normativo | RF-33 a RF-38 |
| Linaje | RF-39 a RF-43 |
| Monitoreo continuo | RF-44 a RF-52 |
| Fuentes externas | RF-53 a RF-60 |
| Clasificación y análisis con IA | RF-61 a RF-69 |
| Dashboard, reportes y auditoría | RF-70 a RF-76 |
| API y control de acceso | RF-77 a RF-80 |

---

## 12. Estado del documento

Los requerimientos descritos corresponden al alcance funcional final implementado de la Plataforma de Gobierno de Datos con Inteligencia Artificial. Este documento servirá como referencia para los documentos de arquitectura, pruebas QA, manual técnico, mantenimiento e informe final.

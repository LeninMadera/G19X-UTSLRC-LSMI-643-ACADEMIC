# PROPUESTA TÉCNICA
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Propuesta técnica  
**Versión:** 1.0  
**Estado:** Final  
**Tecnologías implementadas:** PHP 8.x, MySQL 8, HTML5, CSS3, JavaScript, API REST + JSON y API compatible con OpenAI (Groq/OpenAI)

---

## 1. Introducción

La presente propuesta técnica describe la estrategia utilizada para desarrollar una Plataforma de Gobierno de Datos con Inteligencia Artificial orientada a centralizar la administración de activos de datos, su calidad, políticas, cumplimiento, linaje, monitoreo, fuentes externas y análisis asistido por IA.

La solución fue planteada como una aplicación web modular, con una base de datos relacional y una API REST que permite separar las operaciones del sistema de la interfaz de usuario. El diseño busca facilitar la administración de información empresarial mediante controles de acceso por roles, trazabilidad de acciones y automatización de tareas de análisis.

---

## 2. Objetivo de la propuesta

Desarrollar una solución web funcional que permita administrar los principales procesos de gobierno de datos definidos para el proyecto, incluyendo:

- Catálogo y metadatos.
- Calidad de datos.
- Políticas de gobierno.
- Cumplimiento normativo interno.
- Linaje de datos.
- Monitoreo.
- Fuentes externas.
- Búsqueda semántica.
- Análisis mediante Inteligencia Artificial.
- Reportes y auditoría.

La propuesta busca que todos estos elementos puedan consultarse desde una misma plataforma y que las acciones disponibles dependan del rol del usuario.

---

## 3. Estrategia de solución

La estrategia técnica se basa en una arquitectura web cliente-servidor con los siguientes elementos:

1. **Interfaz web**, desarrollada con HTML5, CSS3 y JavaScript.
2. **Backend en PHP**, encargado de la lógica de negocio, autenticación, permisos y procesamiento de solicitudes.
3. **Base de datos MySQL**, utilizada para almacenar usuarios, datasets, calidad, políticas, relaciones, monitoreo, integraciones y registros de auditoría.
4. **API REST con JSON**, utilizada para comunicar la interfaz con las operaciones del servidor.
5. **Servicio de Inteligencia Artificial compatible con OpenAI**, configurable mediante variables de entorno.
6. **Modo simulado de IA**, disponible para pruebas y demostraciones sin depender de un proveedor externo.
7. **CLI de monitoreo**, utilizado para permitir ejecuciones programadas desde el sistema operativo.

La solución administra principalmente metadatos y elementos de gobierno. No realiza ingestión masiva del contenido completo de los datasets.

---

## 4. Tecnologías seleccionadas

| Componente | Tecnología | Uso |
|---|---|---|
| Backend | PHP 8.x | Lógica de negocio, sesiones, validaciones, API y acceso a datos |
| Base de datos | MySQL 8 | Persistencia de información |
| Acceso a datos | PDO | Consultas preparadas y conexión con MySQL |
| Frontend | HTML5 | Estructura de las vistas |
| Estilos | CSS3 | Diseño visual y comportamiento responsive |
| Interactividad | JavaScript vanilla | Consumo de API, formularios, modales y actualización de interfaz |
| Comunicación | REST + JSON | Intercambio de información entre frontend y backend |
| IA | API OpenAI-compatible | Clasificación, anomalías, resúmenes y sugerencias |
| Proveedor IA | Groq/OpenAI configurable | Ejecución de acciones de IA |
| Pruebas API | Postman | Validación manual de flujos principales |
| Versionado | Git | Control de cambios del proyecto |

Las tecnologías utilizadas corresponden a las herramientas manejadas durante el desarrollo y permiten cubrir el alcance funcional definido para el proyecto.

---

## 5. Módulos propuestos e implementados

### 5.1 Autenticación y usuarios

Permite iniciar y cerrar sesión, administrar usuarios y aplicar permisos según cuatro roles:

- Admin.
- Steward.
- Owner.
- Analyst.

El control de acceso se aplica en el servidor y no depende únicamente de la interfaz.

### 5.2 Catálogo de datasets

Centraliza la información principal de los activos de datos, incluyendo nombre, descripción, categoría, origen, responsable y etiquetas.

Incluye:

- Registro.
- Consulta.
- Edición.
- Búsqueda.
- Filtros.
- Ficha de detalle.
- Exportación disponible según el módulo.

### 5.3 Calidad de datos

Permite registrar mediciones sobre cinco dimensiones:

- Completitud.
- Exactitud.
- Consistencia.
- Vigencia.
- Unicidad.

El sistema calcula un score ponderado y muestra alertas cuando el resultado es menor al umbral establecido.

### 5.4 Políticas

Permite registrar políticas de gobierno y asignarlas a datasets para mantener una relación clara entre los activos de datos y las reglas que les aplican.

### 5.5 Cumplimiento

Permite registrar controles y evaluaciones internas mediante los estados:

- Cumple.
- No cumple.
- Pendiente.
- No aplica.

Este módulo funciona como un registro de seguimiento interno y no representa un dictamen legal automático.

### 5.6 Linaje de datos

Permite registrar relaciones entre datasets para representar dependencias, transformaciones, copias, integraciones u otras relaciones de origen y destino.

### 5.7 Búsqueda semántica

La solución incorpora búsqueda semántica asistida por IA.

La IA puede expandir la consulta original con términos relacionados y posteriormente se realiza la búsqueda sobre campos del catálogo. Si el proveedor de IA no está disponible, se utiliza la búsqueda tradicional como alternativa.

No se utiliza un motor vectorial ni embeddings en la implementación actual.

### 5.8 Inteligencia Artificial

El módulo de IA incluye cuatro acciones principales:

- Clasificación automática.
- Detección de anomalías.
- Generación de resúmenes.
- Sugerencias de mejora.

La plataforma puede utilizar un proveedor real compatible con OpenAI o un modo simulado local.

### 5.9 Monitoreo

Permite configurar y ejecutar revisiones sobre datasets utilizando la última medición real de calidad disponible.

El sistema registra:

- Configuración.
- Frecuencia.
- Estado.
- Ejecuciones.
- Historial.

La automatización periódica puede realizarse mediante el CLI incluido y el programador de tareas del sistema operativo.

### 5.10 Fuentes externas

Permite registrar fuentes externas REST/JSON mediante solicitudes GET.

El módulo permite:

- Registrar fuentes.
- Activarlas o desactivarlas.
- Probar conexión.
- Sincronizar metadatos.
- Vincular una fuente con un dataset.
- Consultar historial de sincronizaciones.

La plataforma no copia masivamente el contenido de las fuentes externas a MySQL.

### 5.11 Dashboard y reportes

El dashboard presenta información general del sistema mediante indicadores, alertas, actividad y visualizaciones.

El módulo de reportes permite consultar información de calidad, alertas y actividad. La bitácora completa permanece restringida al rol Admin.

---

## 6. Arquitectura general propuesta

La solución utiliza una arquitectura web dividida en capas lógicas:

### Capa de presentación

Formada por las vistas PHP, HTML, CSS y JavaScript.

Responsabilidades:

- Mostrar información al usuario.
- Capturar datos.
- Consumir la API.
- Presentar respuestas y mensajes.

### Capa de aplicación

Formada por los endpoints PHP y funciones compartidas.

Responsabilidades:

- Validar solicitudes.
- Comprobar sesión y permisos.
- Ejecutar reglas de negocio.
- Procesar operaciones CRUD.
- Coordinar IA, monitoreo e integraciones.

### Capa de datos

Formada por MySQL y PDO.

Responsabilidades:

- Persistir información.
- Mantener relaciones entre entidades.
- Proteger integridad mediante claves y restricciones.

### Servicios externos

Incluyen:

- Proveedor de IA compatible con OpenAI.
- Fuentes REST/JSON configuradas por el usuario.

---

## 7. Estrategia de gobierno de datos

La plataforma propone un flujo de gobierno basado en:

1. Registrar el dataset dentro del catálogo.
2. Asignar responsable y metadatos.
3. Registrar mediciones de calidad.
4. Asociar políticas.
5. Evaluar controles de cumplimiento.
6. Registrar relaciones de linaje.
7. Aplicar análisis de IA.
8. Configurar monitoreo.
9. Relacionar fuentes externas cuando corresponda.
10. Consultar indicadores, reportes y bitácora.

Este flujo permite mantener en un mismo sistema la información principal necesaria para conocer el estado y contexto de un activo de datos.

---

## 8. Estrategia de calidad de datos

La calidad se evalúa mediante cinco dimensiones ponderadas:

| Dimensión | Peso |
|---|---:|
| Completitud | 20 % |
| Exactitud | 25 % |
| Consistencia | 20 % |
| Vigencia | 15 % |
| Unicidad | 20 % |

La fórmula utilizada es:

`score = completitud·0.20 + exactitud·0.25 + consistencia·0.20 + vigencia·0.15 + unicidad·0.20`

Los resultados se clasifican como:

- **Excelente:** score ≥ 85.
- **Aceptable:** score entre 70 y 84.99.
- **Por mejorar:** score < 70.
- **Alerta:** score < 80.

El monitoreo utiliza estas mediciones ya registradas y no genera valores de calidad ficticios.

---

## 9. Estrategia de Inteligencia Artificial

La IA se utiliza como apoyo y no como sustituto de las reglas principales del sistema.

### Funciones

- Clasificar datasets.
- Generar etiquetas.
- Detectar anomalías en el historial de calidad.
- Resumir el estado de un dataset.
- Sugerir acciones de mejora.
- Apoyar la expansión de términos en búsqueda semántica.

### Configuración

La integración se realiza mediante variables de entorno:

- `AI_API_KEY`
- `AI_MODEL`
- `AI_BASE_URL`

Esto permite cambiar de proveedor compatible sin modificar la lógica principal del sistema.

Cuando no existe una clave disponible, el sistema puede utilizar el modo simulado.

---

## 10. Seguridad propuesta

La solución incorpora las siguientes medidas:

- Contraseñas protegidas con `password_hash()`.
- Sesiones de usuario.
- Regeneración de sesión durante autenticación.
- Control de acceso por rol.
- Validación de propiedad de recursos.
- PDO y consultas preparadas.
- Escapado de salida para reducir riesgo de XSS.
- Variables de entorno para secretos.
- `.env` excluido de Git.
- Respuestas de error controladas.
- Protección SSRF en fuentes externas.
- Restricción de destinos privados y locales en operación normal.
- Límites de tamaño y timeout en respuestas externas.
- Bitácora de acciones relevantes.
- Protección para impedir la desactivación del propio administrador.
- Protección para conservar al menos un administrador activo.

### Consideración conocida

La implementación actual no incorpora tokens anti-CSRF en los endpoints mutantes. Este punto debe considerarse antes de un posible despliegue productivo.

---

## 11. Integración con fuentes externas

Las integraciones se limitan a fuentes REST/JSON mediante solicitudes GET.

El flujo propuesto es:

1. Registrar URL y configuración.
2. Validar el destino.
3. Probar la conexión.
4. Obtener la respuesta dentro de los límites permitidos.
5. Analizar estructura y metadatos.
6. Registrar el resultado de la sincronización.
7. Asociar la fuente con un dataset cuando corresponda.

Para desarrollo o pruebas puede habilitarse `INTEGRACION_PERMITIR_LOCALHOST=true`. Esta opción no debe utilizarse en operación normal porque permite conexiones hacia localhost o redes privadas.

---

## 12. Estrategia de pruebas

La solución debe validarse mediante:

- Pruebas de sintaxis PHP.
- Pruebas de sintaxis JavaScript.
- Pruebas de autenticación.
- Pruebas de permisos por rol.
- Pruebas de CRUD.
- Pruebas de API.
- Pruebas de IA real y simulada.
- Pruebas de integraciones.
- Pruebas de protección SSRF.
- Pruebas de integridad de base de datos.
- Pruebas E2E por rol.
- Pruebas responsive.
- Revisión de exposición de secretos.

El detalle completo de resultados se documenta por separado en el Reporte QA.

---

## 13. Beneficios esperados

La implementación propuesta permite:

- Centralizar la información de gobierno de datos.
- Mantener responsables y metadatos identificados.
- Dar seguimiento a calidad.
- Relacionar políticas y cumplimiento.
- Visualizar linaje.
- Automatizar revisiones mediante monitoreo.
- Consultar metadatos de fuentes externas.
- Utilizar IA como apoyo para clasificación y análisis.
- Aplicar permisos diferenciados.
- Mantener trazabilidad de acciones.
- Disponer de una interfaz única para consulta y administración.

---

## 14. Limitaciones de la solución

La versión actual:

- No realiza ingestión masiva de datasets.
- No implementa ETL.
- No utiliza embeddings ni base vectorial.
- No emite dictámenes legales.
- No incluye notificaciones por correo o mensajería.
- No incluye un servicio interno permanente de programación.
- No se encuentra desplegada en un entorno productivo.
- Requiere controles adicionales como CSRF y HTTPS antes de una operación productiva real.

Estas limitaciones no impiden cubrir el alcance funcional definido para la entrega académica.

---

## 15. Entregables asociados

La propuesta técnica se relaciona con los siguientes entregables del proyecto:

- Documento de requerimientos.
- Documento de arquitectura.
- Plan del proyecto.
- Sistema funcional.
- Reporte QA.
- Manual técnico.
- Plan de mantenimiento.
- Acta de capacitación.
- Informe final.

---

## 16. Conclusión

La propuesta técnica plantea una solución web modular que centraliza los principales procesos de Gobierno de Datos definidos para el proyecto y utiliza Inteligencia Artificial como apoyo para tareas de análisis, clasificación y búsqueda.

La combinación de PHP, MySQL, JavaScript, API REST e integración con servicios de IA permite implementar una plataforma funcional con control por roles, trazabilidad, calidad, políticas, cumplimiento, linaje, monitoreo e integraciones externas.

La solución se encuentra alineada con el objetivo de desarrollar una plataforma inteligente para el gobierno de datos empresariales y establece una base clara para su documentación, mantenimiento y posible evolución futura.

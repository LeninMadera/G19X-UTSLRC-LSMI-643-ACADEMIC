# PLAN DEL PROYECTO
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Plan del proyecto  
**Versión:** 1.0  
**Estado:** Final  
**Enfoque de trabajo:** Desarrollo incremental por etapas, backlog priorizado, pruebas continuas y control de versiones con Git

---

## 1. Introducción

El presente documento define la planificación utilizada para desarrollar la Plataforma de Gobierno de Datos con Inteligencia Artificial. El plan organiza el trabajo por etapas, establece prioridades, dependencias, entregables, riesgos, criterios de calidad y actividades de cierre.

La planeación se elaboró con base en el alcance funcional del proyecto y en los documentos PRD, MVP y Documento de Requerimientos. El desarrollo se realizó de forma incremental, incorporando primero los componentes fundamentales y posteriormente los módulos de gobierno de datos, Inteligencia Artificial, monitoreo, integraciones y validación final.

---

## 2. Objetivo del plan

Organizar el desarrollo de la plataforma de manera controlada, asegurando que cada etapa produzca un resultado verificable y que el sistema completo cubra los siguientes elementos:

- Autenticación y control de acceso.
- Gestión de usuarios y roles.
- Catálogo de datasets.
- Calidad de datos.
- Políticas.
- Cumplimiento.
- Linaje.
- Búsqueda semántica.
- Inteligencia Artificial.
- Monitoreo.
- Fuentes externas.
- Dashboard, reportes y auditoría.
- Documentación técnica.
- Pruebas y cierre.

---

## 3. Alcance del proyecto

El proyecto comprende el diseño, desarrollo y validación de una aplicación web para Gobierno de Datos con Inteligencia Artificial.

### 3.1 Incluido

- Aplicación web funcional.
- Base de datos MySQL.
- API REST JSON.
- Cuatro roles de usuario.
- Módulos de gobierno de datos.
- Integración con proveedor de IA compatible con OpenAI.
- Modo simulado de IA.
- Monitoreo mediante interfaz y CLI.
- Integraciones REST/JSON.
- Pruebas funcionales y de permisos.
- Documentación técnica y de entrega.

### 3.2 No incluido

- Despliegue productivo con dominio y HTTPS.
- Alta disponibilidad.
- Procesos ETL completos.
- Ingesta masiva del contenido de datasets.
- Integraciones bidireccionales.
- Notificaciones por correo o mensajería.
- Motor vectorial de embeddings.
- Dictamen legal automático de cumplimiento.

---

## 4. Enfoque de desarrollo

El desarrollo se organizó de forma incremental. Cada etapa se completó antes de avanzar a la siguiente, manteniendo revisiones y pruebas de regresión sobre los módulos ya existentes.

El flujo general fue:

1. Definir necesidades y alcance.
2. Elaborar PRD y MVP.
3. Preparar arquitectura y base de datos.
4. Implementar autenticación y usuarios.
5. Implementar módulos funcionales.
6. Incorporar IA.
7. Incorporar módulos avanzados de gobierno de datos.
8. Mejorar interfaz y experiencia de uso.
9. Ejecutar pruebas funcionales y de seguridad.
10. Corregir hallazgos.
11. Preparar documentación y entrega final.

Git se utilizó para controlar cambios y conservar puntos estables durante el desarrollo.

---

## 5. Organización del trabajo

El proyecto se dividió en los siguientes bloques:

| Bloque | Contenido |
|---|---|
| **B1. Definición** | Alcance, PRD, MVP y requerimientos |
| **B2. Base técnica** | Estructura, configuración, MySQL y autenticación |
| **B3. Núcleo funcional** | Usuarios, catálogo, calidad y políticas |
| **B4. Inteligencia Artificial** | Clasificación, anomalías, resumen y sugerencias |
| **B5. Gobierno avanzado** | Búsqueda semántica, linaje y cumplimiento |
| **B6. Automatización** | Monitoreo e integraciones externas |
| **B7. Presentación y reportes** | Dashboard, reportes, bitácora y diseño responsive |
| **B8. Validación** | Pruebas, auditoría funcional y correcciones |
| **B9. Cierre** | Documentación y paquete final de entrega |

---

## 6. Backlog del proyecto

### 6.1 Prioridad alta

| ID | Elemento | Estado |
|---|---|---|
| BL-01 | Autenticación y sesiones | Completado |
| BL-02 | Gestión de usuarios y roles | Completado |
| BL-03 | Catálogo de datasets | Completado |
| BL-04 | Calidad de datos | Completado |
| BL-05 | Políticas y asignaciones | Completado |
| BL-06 | IA: clasificación y anomalías | Completado |
| BL-07 | Dashboard y reportes | Completado |
| BL-08 | Bitácora de actividad | Completado |
| BL-09 | Linaje de datos | Completado |
| BL-10 | Cumplimiento | Completado |
| BL-11 | Monitoreo | Completado |
| BL-12 | Integraciones REST/JSON | Completado |
| BL-13 | Control de permisos por rol | Completado |
| BL-14 | Pruebas funcionales E2E | Completado |
| BL-15 | Documentación formal | En cierre |

### 6.2 Prioridad media

| ID | Elemento | Estado |
|---|---|---|
| BL-16 | Búsqueda semántica asistida por IA | Completado |
| BL-17 | IA: resumen y sugerencias | Completado |
| BL-18 | Historial de monitoreo | Completado |
| BL-19 | Historial de sincronizaciones | Completado |
| BL-20 | Exportaciones disponibles | Completado |
| BL-21 | Diseño responsive | Completado |
| BL-22 | Accesibilidad básica de modales y formularios | Completado |

---

## 7. Cronograma por etapas

El cronograma se expresa por etapas de trabajo y representa el orden real de construcción del proyecto. No se asignan fechas ficticias; cada etapa depende de la anterior y cuenta con un entregable verificable.

| Etapa | Actividad principal | Dependencia | Entregable |
|---|---|---|---|
| **E0** | Estructura base, configuración y esquema SQL | — | Proyecto inicial y BD |
| **E1** | Login, logout, sesión y plantilla base | E0 | Autenticación funcional |
| **E2** | Gestión de usuarios y roles | E1 | CRUD de usuarios |
| **E3** | Catálogo de datasets | E1 | Catálogo funcional |
| **E4** | Calidad de datos | E3 | Historial y score ponderado |
| **E5** | Políticas de gobierno | E3 | CRUD y asignaciones |
| **E6** | Módulo de IA | E3, E4 | 4 acciones de IA |
| **E7** | Dashboard, reportes y bitácora | E3–E6 | Visualización y auditoría |
| **E8** | Seguridad y colección Postman | E1–E7 | Base endurecida y pruebas API |
| **E9** | Proveedor IA configurable | E6 | Groq/OpenAI-compatible |
| **E10** | Búsqueda semántica y linaje | E3, E6 | Consulta semántica y relaciones |
| **E11** | Cumplimiento | E5 | Controles y evaluaciones |
| **E12** | Monitoreo | E4, E6 | Configuración, ejecución e historial |
| **E13** | Integraciones externas | E3 | REST/JSON, prueba y sincronización |
| **E14** | Revisión visual, auditoría y correcciones finales | E0–E13 | Sistema funcional validado |
| **E15** | Documentación formal y paquete final | E14 | Entrega final |

---

## 8. Dependencias principales

Las dependencias más importantes del proyecto son:

- Usuarios depende de autenticación y base de datos.
- Calidad depende de datasets.
- Políticas pueden existir de forma independiente, pero sus asignaciones dependen de datasets.
- Cumplimiento depende de datasets y puede relacionarse con políticas.
- Linaje depende de la existencia de datasets.
- IA utiliza información del catálogo y calidad.
- Monitoreo depende de datasets y mediciones de calidad.
- Integraciones pueden vincularse con datasets.
- Dashboard y reportes dependen de información generada por los demás módulos.
- La auditoría funcional final depende de que todos los módulos principales estén disponibles.

---

## 9. Hitos del proyecto

| Hito | Criterio |
|---|---|
| **H1. Base operativa** | Aplicación conecta a MySQL y permite autenticación |
| **H2. Gobierno básico** | Usuarios, datasets, calidad y políticas funcionan |
| **H3. IA operativa** | Las cuatro acciones principales funcionan |
| **H4. Gobierno avanzado** | Linaje, cumplimiento y búsqueda semántica funcionan |
| **H5. Automatización** | Monitoreo e integraciones funcionan |
| **H6. Interfaz final** | Vistas responsive y consistentes |
| **H7. Validación funcional** | Roles, API y flujos E2E validados |
| **H8. Cierre técnico** | Hallazgos críticos corregidos |
| **H9. Entrega** | Documentación y ZIP limpio finalizados |

---

## 10. Plan de pruebas

Las pruebas se integran al desarrollo y no se dejan únicamente para el final.

### 10.1 Pruebas técnicas

- Sintaxis PHP.
- Sintaxis JavaScript.
- Validación de JSON.
- Revisión de SQL.
- Revisión de secretos y configuración.

### 10.2 Pruebas funcionales

- Login y logout.
- Sesiones.
- CRUD.
- Permisos.
- Propiedad de recursos.
- Calidad.
- Políticas.
- IA.
- Cumplimiento.
- Linaje.
- Monitoreo.
- Integraciones.
- Dashboard.
- Reportes.

### 10.3 Pruebas de integración

- Frontend con API.
- API con MySQL.
- API con proveedor de IA.
- API con fuentes REST/JSON.
- CLI con monitoreo.

### 10.4 Pruebas por rol

Se validan los cuatro perfiles:

- Admin.
- Steward.
- Owner.
- Analyst.

Las pruebas comprueban tanto acceso permitido como rechazo de operaciones no autorizadas.

---

## 11. Criterios de calidad

El proyecto se considera aceptable cuando:

- No existen errores de sintaxis en PHP o JavaScript.
- Las vistas principales pueden abrirse sin errores no controlados.
- La API devuelve respuestas JSON controladas.
- Los cuatro roles respetan la matriz de permisos.
- Las operaciones críticas no generan errores 500.
- Los datos permanecen relacionados de forma válida.
- Las claves y secretos no se encuentran en el repositorio.
- Las integraciones aplican controles de seguridad.
- La interfaz funciona en escritorio y móvil.
- Los módulos requeridos por el alcance se encuentran disponibles.

---

## 12. Gestión de riesgos

| Riesgo | Probabilidad | Impacto | Tratamiento |
|---|---|---|---|
| Falla del proveedor de IA | Media | Medio | Modo simulado y proveedor configurable |
| Límite de solicitudes de IA | Media | Medio | Reintentos y uso controlado |
| Exposición de credenciales | Baja | Alto | `.env` fuera de Git |
| Error de permisos | Media | Alto | Validaciones backend y pruebas por rol |
| Pérdida de datos durante pruebas | Baja | Alto | Uso de sandbox y base separada para pruebas destructivas |
| SSRF en integraciones | Media | Alto | Validación de esquema, DNS y redes |
| Fuente externa lenta o grande | Media | Medio | Timeout y límite de bytes |
| Monitoreo sin mediciones | Media | Bajo | Registrar estado sin inventar métricas |
| Bloqueo administrativo | Baja | Alto | Evitar auto-desactivación y conservar un admin activo |
| Regresión visual | Media | Bajo | Pruebas responsive y revisión de vistas |

---

## 13. Gestión de cambios

Los cambios se controlan mediante Git.

Proceso utilizado:

1. Identificar necesidad o defecto.
2. Revisar impacto.
3. Implementar el cambio.
4. Ejecutar pruebas específicas.
5. Ejecutar pruebas de regresión cuando corresponde.
6. Revisar diferencias.
7. Crear commit descriptivo.
8. Mantener el árbol de trabajo limpio.

Los cambios de base de datos posteriores al esquema inicial se realizan mediante migraciones para evitar modificar de forma descontrolada instalaciones existentes.

---

## 14. Gestión de configuración

Los archivos de configuración se separan del código fuente.

### Archivos públicos

- `.env.example`
- Código PHP.
- JavaScript.
- CSS.
- SQL.
- Documentación.

### Archivos privados

- `.env`

El paquete final de entrega no debe contener:

- `.env`
- `.git/`
- `.vscode/`
- Archivos temporales.
- Logs.
- Cachés.
- Claves privadas.
- Credenciales reales.

---

## 15. Entregables del proyecto

| Entregable | Estado |
|---|---|
| PRD | Completado / actualizado |
| MVP | Completado |
| Documento de requerimientos | Completado |
| Propuesta técnica | Completado |
| Documento de arquitectura | Completado |
| Plan del proyecto | Completado |
| Sistema funcional | Completado |
| Reporte QA | Pendiente de documento formal |
| Manual técnico | Pendiente |
| Plan de mantenimiento | Pendiente |
| Acta de capacitación | Pendiente |
| Informe final | Pendiente |
| ZIP limpio de entrega | Pendiente |

---

## 16. Criterios de cierre

El proyecto puede pasar a cierre cuando:

- El alcance funcional se encuentra implementado.
- Los hallazgos de prioridad alta han sido corregidos.
- Los módulos principales funcionan de extremo a extremo.
- La matriz de permisos ha sido validada.
- La base de datos permanece íntegra.
- El README refleja la implementación actual.
- La documentación formal se encuentra completa.
- El paquete final no contiene secretos ni archivos de desarrollo innecesarios.

---

## 17. Estado actual del proyecto

El desarrollo funcional se encuentra cerrado. La plataforma cuenta con los módulos previstos y pasó por una revisión funcional final que incluyó validación de permisos, API, vistas, integridad de datos, IA e integraciones.

Los últimos ajustes funcionales reforzaron la administración de usuarios para impedir que un administrador desactive su propia cuenta o que el sistema quede sin administradores activos.

La etapa actual corresponde a la preparación de la documentación formal y, posteriormente, a la generación del paquete limpio de entrega.

---

## 18. Conclusión

El Plan del Proyecto organiza el desarrollo de la Plataforma de Gobierno de Datos con Inteligencia Artificial mediante etapas dependientes, backlog priorizado, pruebas continuas, control de versiones y criterios claros de cierre.

El enfoque incremental permitió construir primero la base operativa y posteriormente incorporar los módulos especializados de gobierno de datos, Inteligencia Artificial, monitoreo e integraciones. Con el sistema funcional ya validado, las actividades restantes se concentran en la documentación y preparación de la entrega final.

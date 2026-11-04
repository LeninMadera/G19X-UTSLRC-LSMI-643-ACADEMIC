# Guía de demostración - 10 minutos

Plataforma de Gobierno de Datos con Inteligencia Artificial

## Índice

1. [Preparación previa](#1-preparación-previa)
2. [Distribución por tiempos](#2-distribución-por-tiempos)
3. [Guion detallado](#3-guion-detallado)
4. [Alternativa si falla IA o internet](#4-alternativa-si-falla-ia-o-internet)
5. [Notas para la demostración](#5-notas-para-la-demostración)
6. [Duración total](#6-duración-total)

## 1. Preparación previa

Antes de iniciar la demostración, verificar lo siguiente:

- [ ] Servidor PHP y MySQL funcionando. Levantar con `php -S 127.0.0.1:8000 -t .` o `iniciar.bat`.
- [ ] Base de datos instalada y con datos de demostración. Ejecutar `sql/database.sql` y `sql/seed.sql`. Las migraciones adicionales (`migration_lineage.sql`, `migration_compliance.sql`, `migration_monitoring.sql`, `migration_integrations.sql`) ya deben estar aplicadas si se usan esos módulos.
- [ ] Cuenta de demostración con permisos de administrador disponible. Sus credenciales deben obtenerse del entorno de pruebas.
- [ ] Navegador abierto en la plataforma en `http://127.0.0.1:8000`.
- [ ] Modo de IA disponible. Si hay `AI_API_KEY` configurada en `.env`, se usará modo real; si está vacía, el sistema funcionará en modo simulado (determinista, sin conexión externa). El panel de IA muestra el modo activo.

Importante: No es necesario crear datos permanentes para la demo. Si se crean registros temporales durante la presentación, eliminarlos al finalizar.

## 2. Distribución por tiempos

| Sección | Tiempo | Objetivo |
|---|---|---|
| 1. Presentación del problema y objetivo | 0:00 - 1:00 (1 min) | Contextualizar la plataforma |
| 2. Inicio de sesión y Dashboard | 1:00 - 2:00 (1 min) | Mostrar acceso, roles y KPIs |
| 3. Catálogo de datos y administración de datasets | 2:00 - 3:15 (1m 15s) | Mostrar catálogo y ficha de detalle |
| 4. Evaluación de calidad de datos | 3:15 - 4:15 (1 min) | Mostrar mediciones, score ponderado y umbral |
| 5. Políticas y cumplimiento | 4:15 - 5:00 (0m 45s) | Mostrar políticas, asignación y evaluaciones |
| 6. Monitoreo, linaje y fuentes externas | 5:00 - 6:00 (1 min) | Mostrar monitoreo, linaje y fuentes externas |
| 7. Funciones de inteligencia artificial | 6:00 - 7:15 (1m 15s) | Mostrar clasificación, anomalías, resumen y sugerencias con resultados existentes |
| 8. Reportes y exportación CSV | 7:15 - 8:15 (1 min) | Mostrar reportes y exportación CSV |
| 9. Seguridad, roles y permisos | 8:15 - 9:00 (0m 45s) | Demostrar restricciones por rol |
| 10. Conclusión de la demostración | 9:00 - 10:00 (1 min) | Cierre y resumen |

Duración total: 10 minutos

## 3. Guion detallado

### 1. Presentación del problema y objetivo (1 min) | 0:00 - 1:00

Pantalla: Página inicial o introducción

Acción: Explicar brevemente el contexto

Resultado esperado: Audiencia entiende el propósito

Texto a decir:
> "Esta plataforma tiene como objetivo catalogar, medir la calidad y aplicar políticas de gobierno de datos, con apoyo de Inteligencia Artificial. Permite tener un inventario centralizado, evaluar la calidad con un score ponderado, gestionar políticas de cumplimiento y obtener asistencia inteligente para mejorar la calidad de los datos."

### 2. Inicio de sesión y Dashboard (1 min) | 1:00 - 2:00

Pantalla: views/login.php → views/dashboard.php

Acción:
1. Iniciar sesión con la cuenta de demostración con permisos de administrador.
2. Revisar el Dashboard.

Resultado esperado: Se muestra el dashboard con KPIs (datasets registrados, calidad promedio, alertas pendientes, políticas activas), gráfico de score por dataset, distribución por categoría, alertas IA y actividad reciente.

Texto a decir:
> "Al iniciar sesión accedemos al dashboard, donde podemos ver de un vistazo el estado general de los datos: cantidad de datasets, calidad promedio, alertas pendientes y actividad reciente. Esto nos permite tener visibilidad rápida del gobierno de datos."

### 3. Catálogo de datos y administración de datasets (1m 15s) | 2:00 - 3:15

Pantalla: views/datasets.php → views/dataset_detail.php

Acción:
1. Ir a Catálogo de datos. Mostrar el listado con búsqueda y filtros por categoría.
2. Abrir la ficha de detalle de un dataset para mostrar score, dimensiones, políticas asignadas y etiquetas IA.

Resultado esperado: Listado funcional y ficha de detalle completa.

Texto a decir:
> "El catálogo centraliza todos los datasets. Desde aquí podemos buscarlos, filtrar por categoría y acceder a su ficha detallada para ver su calidad, políticas asignadas, linaje y sugerencias de la IA."

### 4. Evaluación de calidad de datos (1 min) | 3:15 - 4:15

Pantalla: views/quality.php

Acción:
1. Seleccionar un dataset y revisar el historial de mediciones existente.
2. Mostrar las 5 dimensiones (completitud, exactitud, consistencia, vigencia, unicidad) y el score ponderado.
3. Señalar el umbral visual cuando el score está bajo 80.

Resultado esperado: Se visualizan mediciones, cálculo ponderado y alerta si aplica.

Texto a decir:
> "La calidad se evalúa con cinco dimensiones ponderadas que generan un score global. Cuando este baja de 80, el sistema lo destaca para llamar la atención sobre datasets que requieren mejora."

### 5. Políticas y cumplimiento (0m 45s) | 4:15 - 5:00

Pantalla: views/policies.php → views/compliance.php

Acción:
1. Revisar políticas existentes y su asignación a datasets.
2. Ir a Cumplimiento y mostrar evaluaciones de controles por dataset (cumple/no cumple/pendiente/no aplica).

Resultado esperado: Políticas gestionadas y evaluaciones registradas por dataset.

Texto a decir:
> "Podemos definir políticas de gobierno y asignarlas a datasets. Luego, desde el módulo de cumplimiento, evaluamos controles normativos asociados para llevar un seguimiento de su estado."

### 6. Monitoreo, linaje y fuentes externas (1 min) | 5:00 - 6:00

Pantalla: views/monitoring.php → views/lineage.php → views/integrations.php

Acción:
1. Monitoreo: Mostrar configuración existente y la opción Monitorear ahora.
2. Linaje: Mostrar relaciones origen-destino entre datasets.
3. Fuentes externas: Mostrar catálogo de fuentes y las opciones Probar conexión o Sincronizar ahora, sin forzar una sincronización con datos sensibles.

Resultado esperado: Se visualizan los tres módulos con su funcionalidad principal.

Texto a decir:
> "El monitoreo permite revalidar la calidad periódicamente. El linaje muestra cómo fluyen los datos entre datasets. Las fuentes externas permiten conectar APIs REST/JSON para tener trazabilidad, con validaciones de seguridad."

### 7. Funciones de inteligencia artificial (1m 15s) | 6:00 - 7:15

Pantalla: views/ai.php

Acción:
1. Seleccionar un dataset y mostrar el estado del modo IA (simulado o real).
2. Mostrar resultados existentes de Clasificar, Detectar anomalías, Resumen inteligente y Sugerencias de mejora. Ejecutar solo una acción si es necesario para mostrar el flujo, priorizando registros ya generados.

Resultado esperado: Se muestran resultados de las 4 acciones desde datos existentes cuando es posible. Los registros se guardan en ai_detections.

Texto a decir:
> "La IA asiste en la clasificación, detecta anomalías en el historial de calidad, genera un resumen del estado del dataset y propone sugerencias concretas de mejora. Todo esto funciona en modo simulado si no hay clave configurada, manteniendo la misma interfaz."

### 8. Reportes y exportación CSV (1 min) | 7:15 - 8:15

Pantalla: views/reports.php

Acción:
1. Revisar reporte de estado de calidad por dataset.
2. Revisar alertas y recomendaciones de IA.
3. Mostrar la exportación CSV (descarga de reporte).

Resultado esperado: Reportes visibles y exportación CSV funcional.

Texto a decir:
> "Los reportes consolidan la información para análisis o seguimiento. Además, es posible exportar la información a CSV para compartirla fuera de la plataforma."

### 9. Seguridad, roles y permisos (0m 45s) | 8:15 - 9:00

Pantalla: views/users.php (solo visible para admin) / navegación

Acción:
1. Mostrar que el menú respeta roles (usuarios solo para admin).
2. Explicar brevemente: contraseñas protegidas mediante hash, consultas preparadas, protección CSRF, control por roles y sesiones seguras.
3. Señalar restricciones según el rol visible en el menú.

Resultado esperado: Se evidencian restricciones por rol y medidas de seguridad.

Texto a decir:
> "El sistema aplica control de roles y permisos en cada módulo. Se usan contraseñas protegidas mediante hash, consultas preparadas, protección CSRF y control de acceso en el servidor. Las opciones restringidas no se muestran ni son accesibles según el rol del usuario."

### 10. Conclusión de la demostración (1 min) | 9:00 - 10:00

Pantalla: views/dashboard.php

Acción: Resumir puntos clave

Resultado esperado: Cierre claro

Texto a decir:
> "En resumen, esta plataforma integra catálogo, calidad, políticas, cumplimiento, monitoreo, linaje, fuentes externas e inteligencia artificial en una sola solución. Ofrece visibilidad, trazabilidad y apoyo para tomar decisiones orientadas al gobierno de datos. Gracias por su atención."

## 4. Alternativa si falla IA o internet

Si hay problemas de conexión a internet o con el servicio externo de IA:

- Modo simulado: Si AI_API_KEY está vacía, el sistema opera automáticamente en modo simulado usando heurísticas locales deterministas. El panel de IA indica claramente el modo simulado.
- Sin llamada externa: Las funciones de clasificación, anomalías, resumen y sugerencias seguirán funcionando con datos locales, generando resultados consistentes para la demostración.
- Fuentes externas: Si se intenta probar una fuente pública sin internet, puede fallar. En ese caso, mostrar la configuración de la fuente y explicar las validaciones de seguridad (SSRF, timeout, tamaño de respuesta) sin forzar la sincronización.
- Importante: El modo simulado no utiliza un servicio real, es completamente local y está diseñado para demostración.

## 5. Notas para la demostración

- Tiempo: Seguir el cronograma. Evitar entrar en detalles de código.
- Datos: Usar datos del seed siempre que sea posible. Si se crean registros temporales, anotarlos y eliminarlos al finalizar.
- Roles: Mostrar restricciones por rol desde el menú; cambiar de usuario solo si hay tiempo.
- IA: Mostrar que el modo (simulado/real) se identifica en la interfaz. Priorizar resultados existentes.
- Seguridad: Mencionar CSRF, roles y consultas preparadas sin entrar en implementación.
- Repetibilidad: La colección de Postman/Newman demuestra que las APIs son funcionales y reproducibles.

## 6. Duración total

10 minutos (con margen para preguntas si es necesario)

Ruta exacta del documento: docs/GUIA-DEMOSTRACION-10-MINUTOS.md

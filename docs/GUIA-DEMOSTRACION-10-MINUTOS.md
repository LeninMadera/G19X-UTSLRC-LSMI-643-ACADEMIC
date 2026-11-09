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
- [ ] Cuentas del seed disponibles: `admin@example.com` (administrador) y `owner@example.com` (propietario). No incluir contraseñas en el guion.
- [ ] Tener preparado un dataset de demostración que pertenezca al administrador (distinto al propietario del owner). Si en el entorno actual no existe, crearlo temporalmente antes de la demo y asegurarse de tenerlo identificado para esta prueba.
- [ ] Navegador abierto en la plataforma en `http://127.0.0.1:8000`.
- [ ] Modo de IA disponible (simulado o real). El panel de IA muestra el modo activo.

Importante: No es necesario crear datos permanentes para la demo. Si se crean registros temporales durante la preparación, eliminarlos al finalizar. No modificar datos reales.

## 2. Distribución por tiempos

| Sección | Tiempo | Objetivo |
|---|---|---|
| 1. Presentación del problema y objetivo | 0:00 - 0:55 (0m 55s) | Contextualizar la plataforma |
| 2. Inicio de sesión y Dashboard (admin) | 0:55 - 1:50 (0m 55s) | Mostrar acceso, roles y KPIs |
| 3. Catálogo de datos y administración de datasets (admin) | 1:50 - 3:00 (1m 10s) | Mostrar catálogo y ficha de detalle |
| 4. Evaluación de calidad de datos (admin) | 3:00 - 3:55 (0m 55s) | Mostrar mediciones, score ponderado y umbral |
| 5. Políticas y cumplimiento (admin) | 3:55 - 4:40 (0m 45s) | Mostrar políticas, asignación y evaluaciones |
| 6. Monitoreo, linaje y fuentes externas (admin) | 4:40 - 5:40 (1 min) | Mostrar monitoreo, linaje y fuentes externas |
| 7. Funciones de inteligencia artificial (admin) | 5:40 - 6:40 (1 min) | Mostrar clasificación, anomalías, resumen y sugerencias |
| 8. Reportes y exportación CSV (admin) | 6:40 - 7:25 (0m 45s) | Mostrar reportes y exportación CSV |
| 9. Seguridad, roles y permisos - Restricción de propiedad del owner (cambio a owner) | 7:25 - 8:40 (1m 15s) | Demostrar restricción: owner no puede modificar dataset ajeno al administrador |
| 10. Conclusión de la demostración (admin u owner según corresponda) | 8:40 - 10:00 (1m 20s) | Cierre y resumen |

Duración total: 10 minutos

## 3. Guion detallado

### 1. Presentación del problema y objetivo (0m 55s) | 0:00 - 0:55
Cuenta utilizada: N/A (sin sesión o preparando)

Pantalla: Página inicial o introducción

Acción: Explicar brevemente el contexto del proyecto.

Resultado esperado: Audiencia entiende el propósito de la plataforma.

Texto a decir:
> "Esta plataforma tiene como objetivo catalogar, medir la calidad y aplicar políticas de gobierno de datos, con apoyo de Inteligencia Artificial. Permite tener un inventario centralizado, evaluar la calidad con un score ponderado, gestionar políticas de cumplimiento y obtener asistencia inteligente para mejorar la calidad de los datos."

### 2. Inicio de sesión y Dashboard (admin) (0m 55s) | 0:55 - 1:50
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/login.php → views/dashboard.php

Acción:
1. Iniciar sesión con la cuenta de administrador del seed.
2. Revisar el Dashboard mostrando KPIs, gráfico, distribución y actividad reciente.

Resultado esperado: Se muestra el dashboard con información general del sistema.

Texto a decir:
> "Iniciamos sesión como administrador para mostrar la vista completa de la plataforma. Desde el dashboard tenemos visibilidad rápida del estado general de los datos."

### 3. Catálogo de datos y administración de datasets (admin) (1m 10s) | 1:50 - 3:00
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/datasets.php → views/dataset_detail.php

Acción:
1. Ir a Catálogo de datos y mostrar listado con búsqueda y filtros.
2. Abrir ficha de detalle de un dataset.

Resultado esperado: Listado y ficha de detalle visibles.

Texto a decir:
> "El catálogo centraliza los datasets. Desde la ficha de detalle podemos ver calidad, políticas, linaje y elementos relacionados con IA."

### 4. Evaluación de calidad de datos (admin) (0m 55s) | 3:00 - 3:55
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/quality.php

Acción:
1. Seleccionar un dataset y revisar historial de mediciones existente.
2. Mostrar dimensiones ponderadas y score global, señalando el umbral.

Resultado esperado: Se visualizan mediciones y score con umbral.

Texto a decir:
> "La calidad se evalúa con cinco dimensiones ponderadas que generan un score global. Cuando baja de 80, el sistema lo resalta para identificar datasets que requieren mejora."

### 5. Políticas y cumplimiento (admin) (0m 45s) | 3:55 - 4:40
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/policies.php → views/compliance.php

Acción:
1. Revisar políticas y asignaciones.
2. Revisar evaluaciones de cumplimiento por dataset.

Resultado esperado: Políticas y evaluaciones visibles.

Texto a decir:
> "Definimos políticas de gobierno y las asignamos a datasets. Desde cumplimiento podemos hacer seguimiento a los controles normativos evaluados por dataset."

### 6. Monitoreo, linaje y fuentes externas (admin) (1 min) | 4:40 - 5:40
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/monitoring.php → views/lineage.php → views/integrations.php

Acción:
1. Mostrar monitoreo (configuración y ejecución).
2. Mostrar linaje entre datasets.
3. Mostrar fuentes externas con sus opciones sin forzar sincronización con datos sensibles.

Resultado esperado: Los tres módulos se muestran correctamente.

Texto a decir:
> "El monitoreo revalida la calidad, el linaje muestra el flujo entre datasets y las fuentes externas permiten conectar APIs con trazabilidad y validaciones de seguridad."

### 7. Funciones de inteligencia artificial (admin) (1 min) | 5:40 - 6:40
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/ai.php

Acción:
1. Mostrar modo IA (simulado o real).
2. Mostrar resultados existentes de clasificar, anomalías, resumen y sugerencias, priorizando registros ya generados.

Resultado esperado: Se visualizan las cuatro acciones con sus resultados.

Texto a decir:
> "La IA asiste con clasificación, detección de anomalías, resumen y sugerencias de mejora. Funciona en modo simulado si no hay clave configurada, manteniendo la misma interfaz."

### 8. Reportes y exportación CSV (admin) (0m 45s) | 6:40 - 7:25
Cuenta utilizada: admin@example.com (administrador)

Pantalla: views/reports.php

Acción:
1. Revisar reportes de calidad y alertas de IA.
2. Mostrar exportación CSV.

Resultado esperado: Reportes y exportación CSV visibles.

Texto a decir:
> "Los reportes consolidan la información y permiten exportarla a CSV para su análisis."

### 9. Seguridad, roles y permisos - Restricción de propiedad del owner (cambio a owner) (1m 15s) | 7:25 - 8:40
Cuenta utilizada: Cambio de sesión - primero admin@example.com (para identificar dataset), luego owner@example.com (para demostrar restricción)

Pantalla: views/datasets.php → vista de detalle/edición del dataset ajeno → herramienta Postman o herramientas de desarrollador (si no es visible en UI)

Acción:
1. **Con admin@example.com:** Identificar un dataset que pertenezca al administrador (crear uno temporal antes de la demo si no existe en el entorno). Verificar owner_id distinto al de owner@example.com.
2. **Cerrar sesión / cambiar a owner@example.com:** Iniciar sesión con la cuenta owner del seed.
3. **Desde owner@example.com:** Intentar acceder/editar ese dataset ajeno al owner. Mostrar que no aparece como editable o que la acción no está permitida.
4. **Si el rechazo 403 no es observable desde la interfaz:** Realizar una petición autorizada con Postman o desde las herramientas del navegador (con la sesión/cookies de owner@example.com) al endpoint correspondiente (por ejemplo `api/datasets.php` con acción de actualización/edición) mostrando respuesta HTTP 403. No inventar mensajes visuales; basarse en la respuesta real.
5. **Confirmar:** El owner solo puede modificar sus propios datasets; no puede modificar datasets ajenos al propietario.

Resultado esperado: Se demuestra claramente la regla de propiedad. El owner no puede modificar un dataset ajeno. Si la UI no muestra el 403, se demuestra mediante petición autorizada mostrando la respuesta 403.

Texto a decir:
> "Ahora cambiamos a la cuenta owner para demostrar la restricción por propiedad. Tomamos un dataset que pertenece al administrador (distinto al owner). El owner autenticado no es el propietario, por lo que no puede modificarlo. Si esta restricción no es visible en la interfaz, la demostramos con una petición autorizada mostrando la respuesta HTTP 403, tal como lo aplica el sistema."

### 10. Conclusión de la demostración (1m 20s) | 8:40 - 10:00
Cuenta utilizada: owner@example.com o admin@example.com según estado de sesión (volver a admin si se desea, o mantener según se prefiera)

Pantalla: views/dashboard.php

Acción: Resumir los puntos clave, incluyendo la restricción de propiedad demostrada.

Resultado esperado: Cierre claro de la demostración.

Texto a decir:
> "En resumen, la plataforma integra catálogo, calidad, políticas, cumplimiento, monitoreo, linaje, fuentes externas e IA. Se ha demostrado el control por roles y la restricción de propiedad: un owner solo puede actuar sobre sus propios datasets, mientras que el administrador tiene alcance completo. Gracias por su atención."

## 4. Alternativa si falla IA o internet

Si hay problemas de conexión a internet o con el servicio externo de IA:

- Modo simulado: Si AI_API_KEY está vacía, el sistema opera automáticamente en modo simulado usando heurísticas locales deterministas. El panel de IA indica claramente el modo simulado.
- Sin llamada externa: Las funciones de clasificación, anomalías, resumen y sugerencias seguirán funcionando con datos locales.
- Fuentes externas: Si falla la conexión, mostrar la configuración y explicar las validaciones de seguridad sin forzar la sincronización.
- Importante: El modo simulado no utiliza un servicio real.

## 5. Notas para la demostración

- Tiempo: Seguir estrictamente la distribución para mantener 10 minutos.
- Cuentas: Usar `admin@example.com` para la mayor parte de la demo y cambiar a `owner@example.com` únicamente en el paso 9 para demostrar la restricción. Las credenciales deben obtenerse del entorno de pruebas, no incluirlas en el guion.
- Propiedad: Para el paso 9, tener preparado un dataset perteneciente al administrador (distinto al owner). Verificar que el owner autenticado sea distinto del propietario. No alterar datos reales.
- Restricción: Si el 403 no se observa desde la interfaz, demostrarlo con Postman o herramientas del navegador mostrando la respuesta real.
- Datos: Usar datos del seed siempre que sea posible.
- IA: Priorizar mostrar resultados existentes.
- Seguridad: Mencionar CSRF, roles y consultas preparadas sin entrar en detalles de implementación.

## 6. Duración total

10 minutos (con margen para preguntas si es necesario)

Ruta exacta del documento: docs/GUIA-DEMOSTRACION-10-MINUTOS.md

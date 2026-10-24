# PLAN DE MANTENIMIENTO
## Plataforma de Gobierno de Datos con Inteligencia Artificial

**Proyecto:** Plataforma de Gobierno de Datos con Inteligencia Artificial  
**Empresa:** Develop Talent & Technology  
**Tipo de documento:** Plan de mantenimiento  
**Versión:** 1.0  
**Estado:** Final  
**Enfoque:** Mantenimiento preventivo, correctivo, adaptativo y evolutivo

---

## 1. Introducción

El presente Plan de Mantenimiento establece las actividades necesarias para conservar la estabilidad, seguridad, disponibilidad y funcionamiento de la Plataforma de Gobierno de Datos con Inteligencia Artificial después de su implementación.

El plan se enfoca en el seguimiento técnico de la aplicación, la base de datos, la configuración, los servicios de Inteligencia Artificial, el monitoreo y las integraciones externas. También define procedimientos para atender errores, aplicar cambios, respaldar información y comprobar que una modificación no afecte módulos existentes.

La plataforma se encuentra orientada actualmente a un entorno académico/local. Por este motivo, las actividades descritas corresponden al alcance actual del sistema y sirven como base para una posible operación posterior.

---

## 2. Objetivo

Mantener la plataforma en condiciones funcionales mediante revisiones periódicas, corrección de errores, respaldo de información, actualización controlada de componentes y validación de los módulos principales.

Los objetivos específicos son:

- Reducir el riesgo de fallos.
- Mantener la integridad de la base de datos.
- Proteger credenciales y archivos sensibles.
- Detectar problemas en módulos críticos.
- Mantener funcionando la integración con IA.
- Revisar fuentes externas y monitoreo.
- Aplicar cambios sin provocar regresiones.
- Conservar documentación y configuración actualizadas.
- Facilitar la recuperación ante errores.

---

## 3. Alcance del mantenimiento

El plan cubre:

- Código PHP.
- JavaScript.
- CSS.
- Base de datos MySQL.
- API REST.
- Autenticación y sesiones.
- Usuarios y roles.
- Catálogo de datasets.
- Calidad.
- Políticas.
- Cumplimiento.
- Linaje.
- Búsqueda semántica.
- Inteligencia Artificial.
- Monitoreo.
- Fuentes externas.
- Dashboard.
- Reportes y bitácora.
- Variables de entorno.
- Documentación técnica.

No incluye actualmente:

- Administración de infraestructura en nube.
- Balanceadores de carga.
- Alta disponibilidad.
- Servicios de correo o mensajería.
- Administración de servidores productivos externos.
- Recuperación ante desastres de infraestructura empresarial.

---

## 4. Tipos de mantenimiento

### 4.1 Mantenimiento preventivo

Se realiza para evitar fallos antes de que ocurran.

Incluye:

- Revisar logs y bitácora.
- Verificar estado de la base de datos.
- Comprobar respaldos.
- Revisar espacio disponible.
- Verificar configuración `.env`.
- Comprobar que no existan secretos dentro del repositorio.
- Revisar funcionamiento de IA.
- Revisar integraciones externas.
- Revisar tareas de monitoreo.
- Ejecutar pruebas básicas de regresión.

---

### 4.2 Mantenimiento correctivo

Se aplica cuando existe un error o comportamiento inesperado.

Ejemplos:

- Error en login.
- Permisos incorrectos.
- Fallo de una API.
- Error de cálculo de calidad.
- Vista que no carga.
- Error de integración.
- Falla en una ejecución de monitoreo.
- Respuesta incorrecta del proveedor de IA.

El mantenimiento correctivo debe incluir identificación, corrección, prueba y registro del cambio.

---

### 4.3 Mantenimiento adaptativo

Se realiza cuando cambia el entorno técnico.

Ejemplos:

- Nueva versión de PHP.
- Nueva versión de MySQL.
- Cambio en el proveedor de IA.
- Cambio de modelo de IA.
- Cambio de endpoint compatible con OpenAI.
- Modificación de una API REST externa.
- Cambio de navegador o entorno local.

---

### 4.4 Mantenimiento evolutivo

Corresponde a mejoras futuras que amplíen la solución sin modificar su objetivo principal.

Ejemplos posibles:

- Protección CSRF para peticiones POST mutantes (token en sesión, envío vía `X-CSRF-Token`, validación en backend; excepción `login`, sin validación en GET).
- Nuevos reportes.
- Nuevos formatos de exportación.
- Nuevas fuentes externas.
- Mejoras de accesibilidad.
- Nuevos controles de seguridad.
- Automatización adicional del monitoreo.

Estas mejoras deben evaluarse antes de agregarse para evitar ampliar innecesariamente el alcance.

---

## 5. Componentes críticos

| Componente | Riesgo principal | Acción de mantenimiento |
|---|---|---|
| Autenticación | Acceso indebido | Revisar sesiones, roles y permisos |
| Usuarios | Bloqueo administrativo | Verificar que exista al menos un Admin activo |
| MySQL | Pérdida o corrupción de datos | Respaldos y revisión de integridad |
| API | Respuestas incorrectas | Pruebas funcionales y de errores |
| IA | Caída o cambio del proveedor | Verificar configuración y modo simulado |
| Monitoreo | Ejecuciones fallidas | Revisar historial y tarea programada |
| Integraciones | Fuente caída o riesgo SSRF | Revisar URL, límites y resultados |
| `.env` | Exposición de secretos | Mantenerlo fuera de Git y del ZIP público |
| Bitácora | Pérdida de trazabilidad | Revisar registros y crecimiento |
| Documentación | Desactualización | Actualizar después de cambios relevantes |

---

## 6. Frecuencia de mantenimiento

### Diario o antes de una demostración importante

- Comprobar que MySQL esté disponible.
- Verificar login.
- Abrir dashboard.
- Revisar que no existan errores visibles.
- Confirmar estado del proveedor de IA si se utilizará modo real.
- Revisar las integraciones que serán demostradas.

### Semanal

- Revisar bitácora.
- Revisar ejecuciones de monitoreo.
- Revisar sincronizaciones de fuentes externas.
- Confirmar que el respaldo más reciente sea válido.
- Revisar cambios pendientes en Git.

### Mensual

- Generar respaldo completo de MySQL.
- Revisar usuarios activos.
- Revisar configuraciones de monitoreo.
- Revisar fuentes externas registradas.
- Comprobar variables del `.env`.
- Ejecutar pruebas básicas de todos los módulos.
- Revisar espacio de almacenamiento de logs e historiales.

### Después de cada cambio importante

- Validar sintaxis.
- Ejecutar pruebas específicas.
- Ejecutar regresión.
- Revisar permisos.
- Actualizar documentación.
- Crear commit descriptivo.

---

## 7. Plan de respaldos

### 7.1 Elementos a respaldar

Se deben respaldar:

- Base de datos MySQL.
- Código fuente.
- `.env` en ubicación privada.
- Documentación.
- Archivos SQL.
- Migraciones.
- Colección Postman.

### 7.2 Elementos que no deben publicarse

No deben incluirse en respaldos públicos o entregas:

- `.env`
- Claves de API.
- Contraseñas reales.
- `.git/`
- Archivos temporales.
- Logs con información sensible.

### 7.3 Respaldo de MySQL

Ejemplo:

```bash
mysqldump -u USUARIO -p gobierno_datos_ia > respaldo_gobierno_datos_ia.sql
```

La ubicación del archivo debe ser segura.

### 7.4 Restauración

Ejemplo:

```bash
mysql -u USUARIO -p gobierno_datos_ia < respaldo_gobierno_datos_ia.sql
```

Antes de restaurar se recomienda conservar una copia del estado actual.

---

## 8. Mantenimiento de la base de datos

Actividades principales:

- Verificar integridad de tablas.
- Revisar llaves foráneas.
- Revisar duplicados no permitidos.
- Revisar usuarios activos.
- Revisar crecimiento de `activity_logs`.
- Revisar crecimiento de historiales de monitoreo.
- Revisar crecimiento de sincronizaciones externas.
- Realizar respaldos antes de aplicar migraciones.

### Regla importante

`sql/database.sql` recrea la base de datos.

No debe utilizarse para actualizar una instalación que contenga información importante.

Para una base existente deben utilizarse las migraciones correspondientes.

---

## 9. Mantenimiento de usuarios y permisos

Se debe comprobar periódicamente que:

- Exista al menos un Admin activo.
- No existan usuarios con roles incorrectos.
- Los Owners solo modifiquen recursos dentro de su alcance.
- Analyst mantenga permisos de consulta.
- Steward no tenga acceso a administración de usuarios.
- La bitácora completa permanezca restringida al Admin.

Las pruebas de permisos deben realizarse desde backend y no depender únicamente de botones visibles en la interfaz.

---

## 10. Mantenimiento de Inteligencia Artificial

Variables relacionadas:

```text
AI_API_KEY
AI_MODEL
AI_BASE_URL
```

Actividades:

- Verificar que la clave continúe vigente.
- Confirmar que el modelo configurado esté disponible.
- Verificar la URL del proveedor.
- Revisar errores 429 o 5xx.
- Probar clasificación.
- Probar anomalías.
- Probar resumen.
- Probar sugerencias.
- Confirmar funcionamiento del modo simulado.

Si el proveedor real falla, el modo simulado permite conservar la demostración del módulo.

---

## 11. Mantenimiento de búsqueda semántica

Se debe comprobar:

- Que la búsqueda normal siga funcionando.
- Que la expansión por IA responda cuando el proveedor esté disponible.
- Que el fallback funcione cuando la IA falle.
- Que no se envíe contenido completo de datasets al proveedor.
- Que los resultados sigan siendo relevantes para los campos del catálogo.

---

## 12. Mantenimiento de monitoreo

Revisar:

- Configuraciones activas.
- Frecuencias.
- Próximas ejecuciones.
- Últimas ejecuciones.
- Errores.
- Dataset asociado.
- Última medición de calidad disponible.

El monitoreo no debe crear métricas inexistentes.

Para ejecución programada revisar:

```text
cli/monitor.php
cli/monitor.bat
```

y la tarea configurada en el sistema operativo.

---

## 13. Mantenimiento de integraciones externas

Revisar:

- URL de la fuente.
- Estado activo.
- Última sincronización.
- Historial de errores.
- Tiempo de respuesta.
- Tamaño de respuesta.
- Vinculación con dataset.

También debe comprobarse que permanezcan activas las protecciones:

- Validación HTTP/HTTPS.
- Bloqueo de destinos privados.
- Bloqueo de loopback.
- Validación DNS.
- Redirecciones desactivadas.
- Timeout.
- Límite máximo de bytes.

La variable:

```text
INTEGRACION_PERMITIR_LOCALHOST=true
```

debe utilizarse únicamente para pruebas o desarrollo.

---

## 14. Mantenimiento de seguridad

Actividades recomendadas:

- Revisar que `.env` continúe ignorado por Git.
- No guardar claves en PHP o JavaScript.
- Revisar permisos por rol.
- Verificar consultas preparadas.
- Revisar escapado de contenido.
- Mantener `APP_DEBUG=0` fuera del desarrollo.
- Revisar cabeceras de seguridad.
- Revisar controles SSRF.
- Cambiar claves comprometidas.
- Mantener respaldos privados.

### Mejora futura

Antes de utilizar la plataforma en producción se recomienda asegurar el uso de HTTPS y aplicar endurecimiento adicional. La protección CSRF ya está implementada para las peticiones POST mutantes (con excepción deliberada de `login` y sin validación para solicitudes GET).

---

## 15. Procedimiento de corrección de errores

Cuando se detecte un error:

1. Reproducir el problema.
2. Identificar módulo y usuario afectado.
3. Revisar bitácora y respuesta de API.
4. Crear respaldo si el cambio afecta datos.
5. Corregir únicamente la causa necesaria.
6. Validar sintaxis.
7. Ejecutar prueba específica.
8. Ejecutar pruebas de regresión.
9. Verificar permisos.
10. Registrar el cambio en Git.
11. Actualizar documentación si corresponde.

---

## 16. Clasificación de incidentes

| Nivel | Descripción | Ejemplo | Prioridad |
|---|---|---|---|
| **Crítico** | Impide usar el sistema o compromete datos/seguridad | Login inutilizable, pérdida de datos | Inmediata |
| **Alto** | Afecta una función principal | Permiso incorrecto, módulo principal caído | Alta |
| **Medio** | Existe alternativa temporal | Integración externa no disponible | Media |
| **Bajo** | Error visual o menor | Texto incorrecto, detalle de interfaz | Baja |

---

## 17. Procedimiento de actualización

Antes de actualizar:

1. Respaldar MySQL.
2. Respaldar `.env`.
3. Confirmar árbol de Git limpio.
4. Revisar cambios.
5. Aplicar migración si existe.
6. Validar configuración.
7. Ejecutar sintaxis PHP y JavaScript.
8. Probar login.
9. Probar módulos afectados.
10. Ejecutar regresión.
11. Confirmar que no existan secretos nuevos en Git.
12. Crear commit.

---

## 18. Pruebas posteriores al mantenimiento

Después de una modificación importante se debe revisar como mínimo:

- Login.
- Logout.
- Dashboard.
- Usuarios.
- Datasets.
- Calidad.
- Políticas.
- IA.
- Cumplimiento.
- Linaje.
- Monitoreo.
- Integraciones.
- Reportes.
- Permisos de los cuatro roles.

Cuando el cambio corresponda a una API, comprobar además:

- Respuesta JSON.
- Código HTTP.
- Sesión.
- Rol.
- Manejo de error.

---

## 19. Control de versiones

El proyecto utiliza Git.

Buenas prácticas:

- Commits pequeños y descriptivos.
- Un propósito principal por commit.
- No incluir `.env`.
- Revisar `git diff`.
- Ejecutar `git diff --check`.
- Mantener el árbol limpio antes de una entrega.
- Etiquetar o conservar un commit estable antes de cambios grandes.

---

## 20. Registro de mantenimiento

Se recomienda conservar un registro con:

| Campo | Descripción |
|---|---|
| Fecha | Día de mantenimiento |
| Responsable | Persona que realizó el cambio |
| Tipo | Preventivo, correctivo, adaptativo o evolutivo |
| Módulo | Parte afectada |
| Problema | Descripción |
| Acción | Cambio aplicado |
| Pruebas | Validaciones realizadas |
| Resultado | Aprobado / Pendiente |
| Commit | Identificador Git cuando aplique |

Ejemplo:

```text
Fecha: 2026-09-29
Tipo: Correctivo
Módulo: Usuarios
Problema: Un administrador podía desactivar su propia cuenta.
Acción: Se agregó validación de auto-desactivación y último admin activo.
Pruebas: Permisos, API y E2E.
Resultado: Aprobado.
Commit: f6da48d
```

---

## 21. Soporte por niveles

### Nivel 1 — Operación

Problemas básicos:

- Acceso.
- Navegación.
- Configuración incorrecta.
- Datos de prueba.
- Uso general.

### Nivel 2 — Aplicación

Problemas de:

- API.
- Reglas de negocio.
- Roles.
- JavaScript.
- IA.
- Monitoreo.
- Integraciones.

### Nivel 3 — Base de datos y cambios técnicos

Problemas de:

- SQL.
- Migraciones.
- Integridad.
- Recuperación de información.
- Cambios estructurales.
- Actualizaciones de arquitectura.

---

## 22. Responsabilidades

### Administrador del sistema

- Revisar usuarios.
- Revisar bitácora.
- Confirmar funcionamiento general.
- Mantener configuración segura.

### Responsable técnico

- Corregir errores.
- Revisar APIs.
- Mantener base de datos.
- Aplicar migraciones.
- Revisar integraciones.
- Mantener IA.
- Ejecutar pruebas.
- Actualizar documentación.

### Usuarios

- Reportar errores.
- No compartir credenciales.
- Utilizar las funciones de acuerdo con su rol.
- Registrar información válida.

---

## 23. Indicadores de mantenimiento

Se pueden utilizar los siguientes indicadores:

- Número de errores críticos abiertos.
- Número de errores corregidos.
- Disponibilidad de login.
- Ejecuciones de monitoreo exitosas.
- Sincronizaciones externas exitosas.
- Errores del proveedor de IA.
- Último respaldo válido.
- Número de administradores activos.
- Regresiones detectadas después de cambios.

---

## 24. Criterios de aceptación de mantenimiento

Una actividad de mantenimiento se considera cerrada cuando:

- El problema original deja de reproducirse.
- No aparecen errores nuevos relacionados.
- Las pruebas del módulo pasan.
- Los permisos continúan correctos.
- La base de datos mantiene integridad.
- No se incorporan secretos al repositorio.
- La documentación se actualiza cuando el comportamiento cambió.
- Existe un commit o registro del cambio cuando corresponde.

---

## 25. Plan de mejora continua

Las mejoras deben priorizarse según:

1. Seguridad.
2. Integridad de datos.
3. Funcionalidad requerida.
4. Estabilidad.
5. Usabilidad.
6. Mejoras visuales.
7. Funciones nuevas.

Posibles mejoras futuras:

- Protección CSRF para peticiones POST mutantes (token en sesión, envío vía `X-CSRF-Token`, validación en backend; excepción `login`, sin validación en GET).
- Despliegue HTTPS.
- Pruebas automatizadas unitarias.
- Mayor cobertura automatizada E2E.
- Rotación centralizada de secretos.
- Nuevos conectores externos.
- Nuevos reportes.
- Políticas adicionales de auditoría.

Estas mejoras deben incorporarse únicamente cuando exista una necesidad real.

---

## 26. Recuperación ante fallos

### Fallo de aplicación

1. Revisar último cambio.
2. Consultar Git.
3. Volver temporalmente a un commit estable si es necesario.
4. Probar nuevamente.

### Fallo de base de datos

1. Detener cambios.
2. Crear copia del estado actual si es posible.
3. Identificar el problema.
4. Restaurar respaldo.
5. Aplicar migraciones necesarias.
6. Ejecutar validación de integridad.

### Fallo de IA

1. Revisar proveedor y credenciales.
2. Confirmar acceso a Internet.
3. Revisar modelo.
4. Utilizar modo simulado si es necesario.

### Fallo de integración externa

1. Revisar URL.
2. Revisar disponibilidad del servicio.
3. Revisar controles SSRF.
4. Revisar timeout y límite.
5. Reintentar cuando la fuente vuelva a estar disponible.

---

## 27. Revisión antes de una nueva entrega

Antes de generar una nueva versión del proyecto:

- Confirmar que Git esté limpio.
- Ejecutar pruebas principales.
- Revisar base de datos.
- Revisar README.
- Revisar documentos.
- Quitar `.env`.
- Quitar `.git/`.
- Quitar `.vscode/`.
- Quitar temporales y logs.
- Confirmar `.env.example`.
- Confirmar SQL y migraciones.
- Generar ZIP limpio.
- Probar el ZIP en una carpeta nueva.

---

## 28. Estado actual

Al momento de elaborar este plan:

- El sistema funcional se encuentra cerrado.
- Los hallazgos de prioridad alta y media identificados en la auditoría final fueron corregidos.
- Los cuatro roles fueron validados.
- Los módulos principales fueron probados.
- El repositorio se encontraba limpio después de la última corrección funcional.
- La etapa actual corresponde a documentación y preparación del paquete final.

---

## 29. Conclusión

El mantenimiento de la Plataforma de Gobierno de Datos con Inteligencia Artificial debe enfocarse en conservar la estabilidad del sistema, proteger la información, revisar los servicios externos y aplicar cambios de forma controlada.

La combinación de respaldos, revisión periódica, pruebas de regresión, control de versiones y registro de mantenimiento permite reducir riesgos y facilitar futuras correcciones.

Este plan proporciona una base práctica para el soporte y mejora continua de la plataforma dentro de su alcance académico actual.

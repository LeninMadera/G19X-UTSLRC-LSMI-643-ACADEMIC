# MVP — Minimum Viable Product
## Plataforma de Gobierno de Datos con Inteligencia Artificial

| Campo | Detalle |
|---|---|
| **Producto** | Plataforma de Gobierno de Datos con IA |
| **Versión** | 1.0 |
| **Definición de MVP** | Versión mínima que demuestra de extremo a extremo el ciclo completo: catalogar un dataset → medir su calidad → aplicar política → analizar con IA → visualizar en dashboard/reportes, con roles y auditoría. |

---

## 1. Propuesta de valor

> "En menos de 5 minutos, un steward puede catalogar un dataset, registrar una medición de calidad, asignar una política y obtener un análisis con IA — todo queda trazado."

El MVP demuestra el núcleo del gobierno de datos con ayuda de IA, sin depender de conectores externos ni de gastar tokens (gracias al modo simulado).

## 2. Objetivo del MVP

Validar que el flujo central funciona de forma real (con base de datos real, sesiones reales y API real), que el control de roles es efectivo y que el módulo de IA aporta valor visible en el dashboard y los reportes.

## 3. Alcance del MVP

### 3.1 Incluido
1. **Autenticación y sesiones** con 4 roles (admin, steward, owner, analyst).
2. **Catálogo de datasets**: alta, edición, baja, búsqueda, ficha de detalle.
3. **Calidad de datos**: registro de mediciones (5 dimensiones), score ponderado automático, alerta visual < 80.
4. **Políticas**: CRUD y asignación a datasets.
5. **IA (4 acciones)**: classify, anomalies, summary, suggest — en modo real y modo simulado.
6. **Dashboard**: KPIs y gráficos básicos.
7. **Reportes**: estado de calidad, alertas IA y bitácora.
8. **Bitácora de actividad** y **API REST** con colección de Postman.

### 3.2 Excluido (post-MVP)
- Ingesta automática de contenido de datasets.
- Notificaciones por correo.
- Exportación de reportes (PDF/CSV).
- Despliegue productivo (HTTPS, dominio).
- Pruebas automatizadas unitarias.

## 4. Historias de usuario

| ID | Historia | Criterio de aceptación | Prioridad |
|---|---|---|---|
| HU-01 | Como usuario quiero iniciar sesión para acceder a la plataforma | Login valida credenciales, regenera ID de sesión y devuelve el rol; mensaje genérico ante fallo | Must |
| HU-02 | Como admin quiero gestionar usuarios y roles | CRUD completo visible solo para admin; otros roles reciben 403 | Must |
| HU-03 | Como steward quiero registrar un dataset con su metadata | El dataset queda guardado con dueño y es visible en el catálogo | Must |
| HU-04 | Como owner quiero editar solo mis datasets | Un owner no puede editar datasets ajenos (403 / sin permiso) | Must |
| HU-05 | Como steward quiero registrar una medición de calidad | El score se calcula solo con la fórmula ponderada y aparece en el historial | Must |
| HU-06 | Como analista quiero ver alertas cuando la calidad baja del umbral | Score < 80 muestra alerta visual y aparece en reportes de alertas | Must |
| HU-07 | Como steward quiero crear y asignar políticas a datasets | La asignación persiste y se ve en la ficha del dataset | Must |
| HU-08 | Como steward quiero clasificar un dataset con IA | Se sugiere categoría + etiquetas y se guardan en el dataset | Must |
| HU-09 | Como owner quiero detectar anomalías en mis datasets | Las anomalías se guardan con severidad y estado pendiente | Must |
| HU-10 | Como usuario quiero un resumen inteligente del estado de un dataset | El resumen se genera con IA (real o simulada) y se muestra en pantalla | Should |
| HU-11 | Como steward quiero sugerencias de mejora según las dimensiones débiles | La IA propone acciones basadas en la última medición | Should |
| HU-12 | Como usuario quiero ver KPIs en el dashboard | Total datasets, calidad promedio, alertas pendientes y políticas activas se muestran con datos reales | Must |
| HU-13 | Como admin quiero ver la bitácora de actividad | Cada acción relevante queda registrada con usuario, detalle e IP | Must |
| HU-14 | Como evaluador quiero probar la API con Postman | La colección ejecuta login y todos los endpoints con la cookie de sesión | Must |

## 5. Flujo E2E del MVP (demo en 5 minutos)

1. Login como **admin** (`admin@example.com`).
2. Crear un dataset nuevo como steward/owner.
3. Registrar una medición de calidad con dimensiones variadas (alguna baja para disparar el umbral).
4. Crear una política y asignarla al dataset.
5. Ejecutar las 4 acciones de IA (classify → anomalies → summary → suggest).
6. Mostrar el dashboard (KPIs, alertas) y los reportes con la bitácora.
7. Cerrar sesión y entrar como **analyst** para demostrar el control de roles (solo lectura).

## 6. Métricas del MVP

| Métrica | Meta |
|---|---|
| Flujo E2E completo | Ejecutable en ≤ 5 minutos sin errores |
| Historias Must | 100% implementadas y aceptadas |
| Roles funcionando | 4/4 con permisos correctos |
| Acciones de IA | 4/4 operativas (modo real y simulado) |
| API | Todos los endpoints responden JSON con sesión válida |
| Seguridad básica | Sin SQLi/XSS en revisión manual; claves fuera del repo |

## 7. Stack del MVP

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.x puro (sin frameworks) |
| Base de datos | MySQL 8 (PDO + prepared statements) |
| Frontend | HTML5, CSS3, JavaScript vanilla, canvas nativo |
| Comunicación | API REST + JSON |
| IA | API OpenAI-compatible (Groq u OpenAI) + modo simulado local |
| Pruebas | Postman (colección incluida) |
| Versionado | Git |

## 8. Riesgos del MVP

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Sin clave de IA en la demo | Media | Bajo | Modo simulado determinista, mismo resultado visual |
| Límites de tasa del proveedor (429) | Media | Medio | Reintentos automáticos con backoff |
| Modelo sin JSON mode | Baja | Medio | Reintento automático sin `response_format` |
| Fuga de credenciales | Baja | Alto | `.env` fuera de Git; regeneración de claves |

## 9. Criterio de "terminado" (Definition of Done)

- [x] Las 8 etapas (E0–E8) probadas manualmente E2E contra la BD real
- [x] Colección de Postman ejecutando el flujo completo
- [x] README con instalación, roles, API y seguridad documentada
- [x] Seed con 4 usuarios de prueba (uno por rol)
- [x] Módulo de IA funcional con y sin clave de API

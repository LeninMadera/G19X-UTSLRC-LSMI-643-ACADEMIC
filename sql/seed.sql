-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON INTELIGENCIA ARTIFICIAL
--  Datos de prueba (seed)
--
--  Ejecutar DESPUES de database.sql.
--
--  Usuarios de prueba (contrasenas de ejemplo):
--    admin@example.com    / Admin123!
--    steward@example.com  / Steward123!
--    owner@example.com    / Owner123!
--    analyst@example.com  / Analyst123!
-- ============================================================

USE gobierno_datos_ia;

-- ------------------------------------------------------------
--  Roles
-- ------------------------------------------------------------
INSERT INTO roles (nombre, descripcion, permisos) VALUES
('admin',   'Administrador del sistema. Control total.',                                 '["*"]'),
('steward', 'Responsable de la calidad y el catalogo de datos.',                        '["datasets:crud","calidad:crud","politicas:crud","ia:usar","reportes:ver"]'),
('owner',   'Data Owner. Gestiona sus datasets y revisa politicas.',                   '["datasets:list","datasets:update","calidad:list","calidad:register","ia:usar","reportes:ver","politicas:list"]'),
('analyst', 'Analista. Consulta datos y genera reportes.',                              '["datasets:list","calidad:list","ia:usar","reportes:ver"]');

-- ------------------------------------------------------------
--  Usuarios (hashes generados con password_hash / bcrypt)
-- ------------------------------------------------------------
INSERT INTO users (nombre, email, pass_hash, rol_id, activo) VALUES
('Sistemas Admin',      'admin@example.com',   '$2y$10$V3brtU.bOqILVdCVmUEnDOyVK1.70XrSJ1QwTRBFotj2OfXT5q2MO',  1, 1),
('Maria Data Steward',  'steward@example.com', '$2y$10$4fSjt/WGnAcJ36KROJ1yYu3ydyau6mOWz4dXDHdC7KfBnEP.g1scS', 2, 1),
('Carlos Data Owner',   'owner@example.com',   '$2y$10$pPsWoGQrko2/IxBqEHme1.1mk/wFrLMW.TNQWg7azLCGeH2Bq9K86',  3, 1),
('Lucia Analyst',       'analyst@example.com', '$2y$10$yUSYOyWl9m4LdT/ZOEa96uTCY15xAS9gMSL5.HCwecxW0tyErZaIu',   4, 1);

-- ------------------------------------------------------------
--  Datasets del catalogo
-- ------------------------------------------------------------
INSERT INTO datasets (nombre, descripcion, categoria, origen, owner_id, categoria_ia, etiquetas_ia, clasificacion, activo) VALUES
('Ventas mensuales 2024',     'Registro de ventas por sucursal y categoria de producto durante 2024.',       'Finanzas',     'SQL Server',   3, 'ventas', '["ventas","finanzas","comercial"]', 'confidencial', 1),
('Clientes CRM',              'Maestro de clientes con datos de contacto y segmentacion de mercado.',         'Clientes',     'CRM',          3, 'clientes', '["clientes","crm","segmentacion"]', 'confidencial', 1),
('Inventario de productos',   'Stock actual de productos por almacen, con costos y proveedores.',             'Operaciones',  'Base de datos', 3, 'inventario', '["inventario","almacen","productos"]', 'interno', 1),
('Encuesta de satisfaccion',  'Resultados de encuestas de satisfaccion al cliente por trimestre.',            'Clientes',     'CSV',          3, 'clientes', '["satisfaccion","encuestas","atencion"]', 'publico', 1);

-- ------------------------------------------------------------
--  Historial de metricas de calidad (varias mediciones por dataset)
-- ------------------------------------------------------------
INSERT INTO quality_metrics (dataset_id, completitud, exactitud, consistencia, vigencia, unicidad, score, user_id) VALUES
(1, 95.00, 98.00, 92.00, 90.00, 97.00, 94.80, 2),
(1, 93.00, 97.00, 91.00, 88.00, 95.00, 93.25, 2),
(1, 90.00, 96.00, 89.00, 85.00, 93.00, 91.15, 3),
(2, 98.00, 99.00, 97.00, 96.00, 95.00, 97.15, 2),
(2, 97.00, 98.00, 96.00, 95.00, 94.00, 96.15, 2),
(3, 88.00, 95.00, 90.00, 92.00, 89.00, 90.95, 2),
(4, 76.00, 80.00, 85.00, 70.00, 82.00, 79.10, 2);

-- ------------------------------------------------------------
--  Politicas de gobierno
-- ------------------------------------------------------------
INSERT INTO policies (titulo, descripcion, tipo, estado, creado_por) VALUES
('Naming estandar de tablas',      'Los datasets deben usar el prefijo entidad_tema (ej: clientes_principales).', 'naming',     'activa',   2),
('Retencion de datos 5 anios',     'Los datos operativos se conservan 5 anios salvo excepcion legal.',           'retencion', 'activa',   2),
('Acceso minimo por rol',          'El acceso a datos sensibles se otorga bajo el principio de minimo privilegio.', 'acceso',   'activa',   1),
('Clasificacion confidencial',     'Todo dataset con datos personales debe marcarse como confidencial.',           'clasificacion', 'inactiva', 1);

-- ------------------------------------------------------------
--  Vinculacion de politicas con datasets
-- ------------------------------------------------------------
INSERT INTO policy_datasets (policy_id, dataset_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 2),
(3, 2),
(3, 4);

-- ------------------------------------------------------------
--  Relaciones de linaje entre datasets
--  Solo aplica a instalaciones nuevas o ambientes de prueba:
--  usa los IDs 1-4 de los datasets creados mas arriba.
--
--  La pareja (2 -> 1) aparece dos veces con tipo distinto, algo
--  permitido por UNIQUE (origen_id, destino_id, tipo): el dataset
--  1 se deriva del 2 y ademas el 2 lo alimenta.
-- ------------------------------------------------------------
INSERT INTO data_lineage (origen_id, destino_id, tipo, descripcion, creado_por) VALUES
(2, 1, 'derivado_de', 'Las ventas mensuales se calculan cruzando los clientes del CRM con su historial de compras.', 2),
(2, 1, 'alimenta',    'El maestro de clientes alimenta la segmentacion por sucursal del reporte de ventas.',      2),
(3, 1, 'alimenta',    'El inventario por almacen alimenta las projections de venta por categoria.',              2),
(2, 4, 'integra',     'La segmentacion de clientes se integra en la encuesta de satisfaccion por tipo de cliente.', 2),
(4, 1, 'alimenta',    'Los resultados de satisfaccion alimentan el analisis de ventas por region.',              2);

-- ------------------------------------------------------------
--  Detecciones de IA de ejemplo (para el dashboard)
-- ------------------------------------------------------------
INSERT INTO ai_detections (dataset_id, tipo, severidad, descripcion_ia, recomendacion_ia, estado) VALUES
(1, 'anomalia',    'alta',        'La dimension de vigencia bajo 5 puntos entre la ultima medicion y la anterior.', 'Revisar el proceso de actualizacion de datos del dataset.', 'pendiente'),
(4, 'anomalia',    'critica',     'El score de calidad (79.1) esta por debajo del umbral minimo de 80.',            'Definir plan de completitud y vigencia de la encuesta.',    'pendiente'),
(2, 'clasificacion', 'baja',      'Categoria sugerida: Clientes.', NULL,                                            'aplicada'),
(3, 'sugerencia',  'media',       'La consistencia puede mejorar comparando inventario fisico vs sistema.',          'Programar conteos ciclicos y conciliacion semanal.',        'revisada');

-- ------------------------------------------------------------
--  Bitacora de ejemplo
-- ------------------------------------------------------------
INSERT INTO activity_logs (user_id, accion, detalle, ip) VALUES
(1, 'login',     '{"rol":"admin"}',    '127.0.0.1'),
(2, 'calidad:register', '{"dataset_id":1,"score":92.8}', '127.0.0.1'),
(3, 'datasets:update',  '{"dataset_id":2}', '127.0.0.1');

-- ------------------------------------------------------------
--  Controles de cumplimiento (ejemplos academicos)
--  Los controles son un registro declarativo interno del equipo;
--  NO constituyen un dictamen legal. La columna referencia indica
--  la norma/marco/categoria de referencia y policy_id reutiliza una
--  politica de gobierno existente.
-- ------------------------------------------------------------
INSERT INTO compliance_controls (nombre, descripcion, referencia, severidad, estado, policy_id, creado_por) VALUES
('Retencion de datos personales', 'Conservar los datos personales solo mientras se usan, sin retener mas de lo necesario.', 'LFPDPPP art. 9', 'alta', 'activo', 2, 2),
('Acceso minimo a datos sensibles', 'El acceso a datos sensibles se otorga bajo el principio de minimo privilegio.', 'ISO 27001 A.9', 'media', 'activo', 3, 2),
('Naming estandar de tablas', 'Los nombres de tablas y columnas deben seguir el estandar interno de gobierno.', 'Marco interno de gobierno de datos', 'baja', 'activo', 1, 2),
('Clasificacion de informacion', 'Todo dataset con datos personales debe marcarse como confidencial o restringido.', 'LFPDPPP art. 3', 'critica', 'activo', 4, 1);

-- ------------------------------------------------------------
--  Evaluaciones de ejemplo (una fila por pareja control/dataset)
--  La fila define que el control aplica a ese dataset y su estado.
--  Usa los IDs 1-4 de los controles y datasets creados mas arriba.
-- ------------------------------------------------------------
INSERT INTO compliance_evaluations (control_id, dataset_id, estado, observaciones, evidencia, evaluador_id) VALUES
(1, 1, 'cumple',    'Se conserva 5 anios y se elimina el historico anterior en el cierre anual.', 'SOP-RET-001',  2),
(1, 2, 'cumple',    'Los datos de contacto se purgan al cierre del ciclo.',                          'SOP-RET-001',  2),
(2, 1, 'cumple',    'Acceso restringido al equipo comercial.',                                       'AUD-2026-010', 2),
(2, 2, 'no_cumple', 'El CRM expone campos de contacto a todo el rol analyst.',                       'AUD-2026-014', 2),
(2, 4, 'no_aplica', 'La encuesta no contiene datos personales directos.',                            NULL,          2),
(3, 1, 'cumple',    'Nombre en formato entidad_tema.',                                               NULL,          3),
(3, 2, 'cumple',    NULL,                                                                            NULL,          3),
(3, 3, 'no_cumple', 'El nombre no cumple el prefijo estandar.',                                      NULL,          3),
(4, 1, 'pendiente', 'En revision de la clasificacion asignada.',                                     NULL,          2),
(4, 2, 'cumple',    NULL,                                                                            NULL,          2);

-- ------------------------------------------------------------
--  Configuraciones de monitoreo continuo
--  Una por dataset (UNIQUE dataset_id). Los datasets 1 y 4 estan
--  activos y con su proxima ejecucion programada; el dataset 2
--  esta inactivo, por lo que NO sera elegible para el programador
--  del CLI hasta reactivarse (proxima_ejecucion = NULL).
-- ------------------------------------------------------------
INSERT INTO monitoring_configs (dataset_id, frecuencia, activo, corriendo, proxima_ejecucion, estado_ultima, score_ultimo, anomalias_ultimas, ultima_ejecucion, creado_por) VALUES
(1, 1440, 1, 0, NOW() + INTERVAL 1440 MINUTE, 'ok',    91.15, 1, NOW() - INTERVAL 1 DAY, 2),
(2,  720, 0, 0, NULL,                              NULL,  NULL,  0, NULL,                2),
(4,   60, 1, 0, NOW() + INTERVAL 60 MINUTE,  'ok',    79.10, 2, NOW() - INTERVAL 5 MINUTE, 2);

-- ------------------------------------------------------------
--  Historial de ejecuciones de monitoreo (ejemplo)
--  La configuracion 1 (dataset 1) tuvo una corrida automatica y
--  una manual; la configuracion 3 (dataset 4, frecuencia de 60
--  minutos) una corrida automatica reciente.
-- ------------------------------------------------------------
INSERT INTO monitoring_runs (config_id, dataset_id, causa, estado, score, anomalias_nuevas, resumen) VALUES
(1, 1, 'automatico', 'ok',    91.15, 1, 'Revalidacion de calidad completada; ultimo score 91.15; 1 anomalias nuevas.'),
(1, 1, 'manual',     'ok',    91.15, 0, 'Revalidacion de calidad completada; ultimo score 91.15; 0 anomalias nuevas.'),
(3, 4, 'automatico', 'ok',    79.10, 2, 'Revalidacion de calidad completada; ultimo score 79.10; 2 anomalias nuevas.');
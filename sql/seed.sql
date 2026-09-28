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
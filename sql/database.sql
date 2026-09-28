-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON INTELIGENCIA ARTIFICIAL
--  Script de creacion de la base de datos (MySQL 8+)
--
--  Ejecutar primero database.sql y luego seed.sql
--  (phpMyAdmin o desde la consola de MySQL).
-- ============================================================

DROP DATABASE IF EXISTS gobierno_datos_ia;
CREATE DATABASE gobierno_datos_ia
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gobierno_datos_ia;

-- ------------------------------------------------------------
--  roles
--  Define los roles del sistema. El campo permisos guarda un
--  JSON con las acciones permitidas para cada rol.
-- ------------------------------------------------------------
CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(30)  NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL,
    permisos    JSON         NULL
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  users
--  Usuarios del sistema. La contrasena se almacena como hash
--  de password_hash() de PHP (bcrypt), nunca en texto plano.
-- ------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(80)  NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    pass_hash     VARCHAR(255) NOT NULL,
    rol_id        INT UNSIGNED NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_rol
        FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  datasets
--  Catalogo central de activos de datos. Guarda la metadata
--  del dataset, su dueno y la clasificacion propuesta por IA
--  (categoria_ia y etiquetas_ia), que el humano puede confirmar.
-- ------------------------------------------------------------
CREATE TABLE datasets (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(120) NOT NULL,
    descripcion        TEXT         NULL,
    categoria          VARCHAR(50)  NULL,
    origen             VARCHAR(50)  NULL,       -- base de datos, archivo, api...
    owner_id           INT UNSIGNED NOT NULL,    -- Data Owner responsable
    categoria_ia       VARCHAR(50)  NULL,        -- sugerida por IA
    etiquetas_ia       JSON         NULL,        -- JSON array de etiquetas
    clasificacion      VARCHAR(20)  NULL         DEFAULT 'publico', -- publico|interno|confidencial|restringido
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_creacion     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_datasets_owner
        FOREIGN KEY (owner_id) REFERENCES users (id)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  quality_metrics
--  Historial de mediciones de calidad por dataset.
--  Cada dimension se guarda como porcentaje 0-100 y el score
--  es el promedio ponderado calculado por el backend.
-- ------------------------------------------------------------
CREATE TABLE quality_metrics (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_id    INT UNSIGNED NOT NULL,
    completitud   DECIMAL(5, 2) NOT NULL, -- % de datos presentes
    exactitud     DECIMAL(5, 2) NOT NULL, -- % de datos correctos
    consistencia  DECIMAL(5, 2) NOT NULL, -- % de coherencia entre fuentes
    vigencia      DECIMAL(5, 2) NOT NULL, -- % de datos vigentes/actuales
    unicidad      DECIMAL(5, 2) NOT NULL, -- % de datos sin duplicados
    score         DECIMAL(5, 2) NOT NULL, -- promedio ponderado
    fecha_medicion TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id       INT UNSIGNED NULL,       -- quien registro la medicion
    CONSTRAINT fk_quality_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT fk_quality_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  policies
--  Politicas de gobierno de datos (naming, retencion, acceso...)
-- ------------------------------------------------------------
CREATE TABLE policies (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo        VARCHAR(120) NOT NULL,
    descripcion   TEXT         NULL,
    tipo          VARCHAR(40)  NOT NULL,
    estado        VARCHAR(20)  NOT NULL DEFAULT 'activa',
    creado_por    INT UNSIGNED NULL,
    fecha_creacion TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_policies_creator
        FOREIGN KEY (creado_por) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_tipo CHECK (tipo IN ('naming', 'retencion', 'acceso', 'clasificacion')),
    CONSTRAINT chk_estado CHECK (estado IN ('activa', 'inactiva'))
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  policy_datasets
--  Tabla puente: relacion muchos a muchos entre politicas y
--  datasets (una politica aplica a varios datasets y viceversa).
-- ------------------------------------------------------------
CREATE TABLE policy_datasets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_id  INT UNSIGNED NOT NULL,
    dataset_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_pd_policy
        FOREIGN KEY (policy_id) REFERENCES policies (id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    UNIQUE KEY uq_policy_dataset (policy_id, dataset_id)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  ai_detections
--  Resultados del analisis de IA por dataset: anomalias,
--  clasificaciones y sugerencias. El estado permite hacer
--  seguimiento de pendiente -> revisada.
-- ------------------------------------------------------------
CREATE TABLE ai_detections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_id      INT UNSIGNED NOT NULL,
    tipo            VARCHAR(30) NOT NULL, -- anomalia | clasificacion | sugerencia | resumen
    severidad       VARCHAR(20) NOT NULL DEFAULT 'media',
    descripcion_ia  TEXT NULL,
    recomendacion_ia TEXT NULL,
    estado          VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    fecha           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT chk_severidad CHECK (severidad IN ('baja', 'media', 'alta', 'critica')),
    CONSTRAINT chk_estado_ia CHECK (estado IN ('pendiente', 'revisada', 'aplicada', 'descartada'))
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  data_lineage
--  Relaciones de linaje entre datasets del catalogo.
--  Cada fila indica que el dataset origen aporta informacion al
--  dataset destino segun el tipo de relacion.
--
--  Ejemplo: 1 -> 2 con tipo 'alimenta' significa que el dataset 1
--  alimenta al dataset 2.
--  Al borrar un dataset sus relaciones se eliminan solas
--  (ON DELETE CASCADE). La misma pareja origen/destino puede
--  repetirse siempre que cambie el tipo de relacion.
-- ------------------------------------------------------------
CREATE TABLE data_lineage (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    origen_id     INT UNSIGNED NOT NULL,
    destino_id    INT UNSIGNED NOT NULL,
    tipo          VARCHAR(30)  NOT NULL,
    descripcion   VARCHAR(500) NULL,
    creado_por    INT UNSIGNED NULL,
    fecha_creacion TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dl_origen
        FOREIGN KEY (origen_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT fk_dl_destino
        FOREIGN KEY (destino_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT fk_dl_creator
        FOREIGN KEY (creado_por) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_dl_tipo CHECK (tipo IN (
        'derivado_de', 'alimenta', 'integra', 'transforma', 'copia', 'version_de'
    )),
    CONSTRAINT chk_dl_distintos CHECK (origen_id <> destino_id),
    UNIQUE KEY uq_dl_relacion (origen_id, destino_id, tipo)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  activity_logs
--  Bitacora de actividad del sistema para auditoria.
--  user_id puede ser NULL (acciones no autenticadas).
-- ------------------------------------------------------------
CREATE TABLE activity_logs (
    id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id  INT UNSIGNED NULL,
    accion   VARCHAR(80) NOT NULL,
    detalle  JSON NULL,
    ip       VARCHAR(45) NULL,
    fecha    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE = InnoDB;

-- Índice para acelerar la consulta del historial por dataset
CREATE INDEX idx_quality_dataset_fecha
    ON quality_metrics (dataset_id, fecha_medicion DESC);

-- ------------------------------------------------------------
--  compliance_controls
--  Catalogo de controles de cumplimiento normativo.
--  El control se relaciona opcionalmente con una politica de
--  gobierno de datos existente (reutilizacion) pero NO emite un
--  dictamen legal: es un registro declarativo del equipo.
-- ------------------------------------------------------------
CREATE TABLE compliance_controls (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL,
    descripcion    TEXT         NULL,
    referencia     VARCHAR(150) NULL,   -- norma/marco/categoria de referencia
    severidad      VARCHAR(20)  NOT NULL DEFAULT 'media',
    estado         VARCHAR(20)  NOT NULL DEFAULT 'activo',
    policy_id      INT UNSIGNED NULL,
    creado_por     INT UNSIGNED NULL,
    fecha_creacion TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cc_policy
        FOREIGN KEY (policy_id) REFERENCES policies (id) ON DELETE SET NULL,
    CONSTRAINT fk_cc_creator
        FOREIGN KEY (creado_por) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_cc_severidad CHECK (severidad IN ('baja', 'media', 'alta', 'critica')),
    CONSTRAINT chk_cc_estado CHECK (estado IN ('activo', 'inactivo'))
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  compliance_evaluations
--  Evaluacion de un control sobre un dataset (una por pareja,
--  se actualiza al reevaluar). La fila define que el control
--  aplica a ese dataset y su estado de cumplimiento.
-- ------------------------------------------------------------
CREATE TABLE compliance_evaluations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    control_id    INT UNSIGNED NOT NULL,
    dataset_id    INT UNSIGNED NOT NULL,
    estado        VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    observaciones TEXT         NULL,
    evidencia     VARCHAR(255) NULL,
    evaluador_id  INT UNSIGNED NULL,
    fecha         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ce_control
        FOREIGN KEY (control_id) REFERENCES compliance_controls (id) ON DELETE CASCADE,
    CONSTRAINT fk_ce_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT fk_ce_evaluador
        FOREIGN KEY (evaluador_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_ce_estado CHECK (estado IN ('cumple', 'no_cumple', 'pendiente', 'no_aplica')),
    UNIQUE KEY uq_ce_control_dataset (control_id, dataset_id)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  monitoring_configs
--  Configuracion de monitoreo automatico de la calidad de un
--  dataset (una por dataset). `activo` controla si el dataset
--  es ejecutable; `corriendo` es un lock TINYINT que evita
--  ejecuciones simultaneas (UPDATE atomico). `proxima_ejecucion`
--  indica el siguiente vencimiento para el programador del CLI.
-- ------------------------------------------------------------
CREATE TABLE monitoring_configs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_id       INT UNSIGNED NOT NULL,
    frecuencia       INT UNSIGNED NOT NULL DEFAULT 1440,
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    corriendo        TINYINT(1)   NOT NULL DEFAULT 0,
    proxima_ejecucion TIMESTAMP   NULL,
    estado_ultima    VARCHAR(10)  NULL,        -- ok | fallo
    score_ultimo     DECIMAL(5,2) NULL,
    anomalias_ultimas INT          NOT NULL DEFAULT 0,
    ultima_ejecucion TIMESTAMP    NULL,
    creado_por       INT UNSIGNED NULL,
    fecha_creacion   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mc_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT fk_mc_creator
        FOREIGN KEY (creado_por) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT uq_mc_dataset UNIQUE (dataset_id),
    CONSTRAINT chk_mc_frecuencia CHECK (frecuencia >= 1),
    CONSTRAINT chk_mc_estado_ultima CHECK (estado_ultima IN ('ok', 'fallo') OR estado_ultima IS NULL),
    INDEX idx_mc_proxima (activo, proxima_ejecucion)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  monitoring_runs
--  Historial de ejecuciones del monitoreo: causa (manual o
--  automatico), resultado (ok/fallo), el score revalidado (la
--  ultima medicion almacenada; NULL si no habia mediciones),
--  las anomalias nuevas insertadas en ai_detections y un resumen
--  del motivo.
-- ------------------------------------------------------------
CREATE TABLE monitoring_runs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_id        INT UNSIGNED NOT NULL,
    dataset_id       INT UNSIGNED NOT NULL,
    causa            VARCHAR(20)  NOT NULL DEFAULT 'automatico',
    estado           VARCHAR(10)  NOT NULL,    -- ok | fallo
    score            DECIMAL(5,2) NULL,
    anomalias_nuevas INT          NOT NULL DEFAULT 0,
    resumen          VARCHAR(1000) NULL,
    fecha            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mr_config
        FOREIGN KEY (config_id) REFERENCES monitoring_configs (id) ON DELETE CASCADE,
    CONSTRAINT fk_mr_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE CASCADE,
    CONSTRAINT chk_mr_causa CHECK (causa IN ('manual', 'automatico')),
    CONSTRAINT chk_mr_estado CHECK (estado IN ('ok', 'fallo')),
    INDEX idx_mr_dataset_fecha (dataset_id, fecha DESC)
) ENGINE = InnoDB;
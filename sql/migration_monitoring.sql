-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON IA - Modulo de Monitoreo
--  Migracion: configuraciones de monitoreo y sus ejecuciones
--  (MySQL 8+)
--
--  Para que sirve:
--    Este archivo SOLO crea la estructura. No inserta datos de
--    ejemplo. Los datos de ejemplo estan en sql/seed.sql y solo
--    aplican a instalaciones nuevas o ambientes de prueba recien
--    creados.
--
--  Como usarlo:
--    1. Ejecuta sql/database.sql y sql/seed.sql (base nueva).
--    2. O, si tu base YA existe y solo le falta este modulo,
--       ejecuta UNICAMENTE este archivo. No borra ni altera
--       ninguna tabla previa.
--
--  Reejecucion: es idempotente (CREATE TABLE IF NOT EXISTS),
--  asi que puedes volver a ejecutarlo sin riesgo.
-- ============================================================

USE gobierno_datos_ia;

-- ------------------------------------------------------------
--  monitoring_configs
--  Configuracion de monitoreo automatico de la calidad de un
--  dataset (una por dataset).
--
--  · dataset_id      dataset monitoreado (UNIQUE: 1:1)
--  · frecuencia      cada cuantas MINUTOS se revalida la calidad
--                    (validado por CHECK: >= 1; rango 1..43200
--                    aplicado en API/CLI)
--  · activo          TINYINT 0/1: solo activo se puede ejecutar
--                    (manualmente o por el programador del CLI)
--  · corriendo       lock TINYINT 0/1: evita ejecuciones
--                    simultaneas sobre el mismo dataset. Se
--                    marca SIEMPRE con un UPDATE atomico
--                    (SET corriendo=1 WHERE id=? AND corriendo=0)
--  · proxima_ejecucion  cuando toca la proxima ejecucion
--                    automatica. NULL si el dataset esta
--                    inactivo. Se recalcula con NOW()+frecuencia
--                    al crear, al activar y tras cada ejecucion.
--  · estado_ultima / score_ultimo / anomalias_ultimas /
--    ultima_ejecucion  espejo resumido de la ultima corrida para
--                    no depender de un JOIN en cada listado.
--
--  Al borrar un dataset su configuracion (y sus corridas) se
--  eliminan solas (ON DELETE CASCADE).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS monitoring_configs (
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
    -- Indice para el programador del CLI: recupera de forma barata
    -- las configuraciones activas y vencidas.
    INDEX idx_mc_proxima (activo, proxima_ejecucion)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  monitoring_runs
--  Historial de ejecuciones del monitoreo.
--
--  · causa         manual (boton "Monitorear ahora" o CLI --run-id)
--                  | automatico (programador del CLI)
--  · estado        ok | fallo. Un fallo quiere decir que NO se
--                  pudo revalidar: no habia mediciones de calidad
--                  o la deteccion de anomalias fallo.
--  · score         ultimo score de calidad almacenado del dataset
--                  en el momento de la corrida. NULL si el dataset
--                  no tenia ninguna medicion de calidad.
--  · anomalias_nuevas  anomalias insertadas en ai_detections
--                  durante la corrida (0 si fallo o no habia
--                  mediciones).
--  · resumen       texto conciso del resultado (motivo del fallo o
--                  resumen de la revalidacion).
--
--  Al borrar la configuracion o el dataset, su historial se
--  elimina solo (ON DELETE CASCADE).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS monitoring_runs (
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
    -- Indice para el historial reciente por dataset o configuracion.
    INDEX idx_mr_dataset_fecha (dataset_id, fecha DESC)
) ENGINE = InnoDB;
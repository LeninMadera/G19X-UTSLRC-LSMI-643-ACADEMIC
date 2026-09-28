-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON IA - Modulo de
--  Fuentes Externas / Integraciones
--  Migracion: fuentes externas y su historial de sincronizaciones
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
--  external_sources
--  Catalogo de fuentes externas consumidas por GET (REST/JSON).
--
--  Este modulo NO copia los datos externos a MySQL: solo
--  valida la conexion y registra metadatos de la sincronizacion.
--
--  · url            URL http/https de la fuente. En create/update
--                    se valida contra riesgos SSRF (esquema, host
--                    local y resolucion DNS a IP no publica).
--  · tipo           por ahora SIEMPRE 'api' (REST/JSON).
--  · dataset_id     vinculo OPCIONAL con un dataset del catalogo
--                    (el id de un dataset referenciado se usa para
--                    aplicar la regla de propiedad del owner).
--                    NULL si la fuente aun no se vincula.
--  · timeout_seg    limite de espera total por peticion (1..60);
--                    el connect timeout es fijo (INTEGRACION_CONNECT_TIMEOUT).
--  · activo         TINYINT 0/1: activo no condiciona probar ni
--                    sincronizar (se pueden probar fuentes inactivas),
--                    solo habilita la fuente en la interfaz.
--  · ultimo_estado / ultima_ejecucion / ultimo_resumen /
--    total_ejecuciones  espejo resumido de la ultima corrida de
--                    tipo 'sync' para KPIs rapidos (patron de
--                    monitoring_configs).
--
--  Al borrar la fuente, su historial se elimina solo
--  (ON DELETE CASCADE); al borrar el dataset vinculado la fuente
--  queda sin vinculo (ON DELETE SET NULL).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS external_sources (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(150)  NOT NULL,
    descripcion      TEXT          NULL,
    url              VARCHAR(500)  NOT NULL,
    tipo             VARCHAR(20)   NOT NULL DEFAULT 'api',
    dataset_id       INT UNSIGNED  NULL,
    timeout_seg      TINYINT UNSIGNED NOT NULL DEFAULT 15,
    activo           TINYINT(1)    NOT NULL DEFAULT 1,
    ultimo_estado    VARCHAR(10)   NULL,        -- ok | error
    ultima_ejecucion TIMESTAMP     NULL,
    ultimo_resumen   VARCHAR(500)  NULL,
    total_ejecuciones INT UNSIGNED NOT NULL DEFAULT 0,
    creado_por       INT UNSIGNED  NULL,
    fecha_creacion   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_es_dataset
        FOREIGN KEY (dataset_id) REFERENCES datasets (id) ON DELETE SET NULL,
    CONSTRAINT fk_es_creator
        FOREIGN KEY (creado_por) REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT chk_es_tipo CHECK (tipo IN ('api')),
    CONSTRAINT chk_es_timeout CHECK (timeout_seg BETWEEN 1 AND 60),
    CONSTRAINT chk_es_estado_ultima CHECK (ultimo_estado IN ('ok', 'error') OR ultimo_estado IS NULL),
    INDEX idx_es_dataset (dataset_id)
) ENGINE = InnoDB;

-- ------------------------------------------------------------
--  external_sync_runs
--  Historial de ejecuciones contra la fuente externa.
--
--  · tipo_ejecucion  'prueba' (Probar conexion: NO toca el espejo
--                    de la fuente) | 'sync' (Sincronizar ahora:
--                    si ok/error actualiza ultimo_estado y el total).
--  · estado          ok | error.
--  · registros_leidos  cantidad de elementos JSON leidos (0 si fallo).
--  · metadatos       JSON resumido: {"registros":N,"campos":[...]}
--                    con los campos del primer objeto (max 20).
--                    La interfaz muestra este resumen, NUNCA los
--                    registros completos (no se almacenan datos).
--  · mensaje         texto corto del resultado (motivo del fallo o
--                    resumen del exito). Sin contenido sensible.
--  · duracion_ms     latencia de la peticion para la demo.
--
--  Al borrar la fuente, su historial se elimina solo
--  (ON DELETE CASCADE).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS external_sync_runs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id        INT UNSIGNED NOT NULL,
    tipo_ejecucion   VARCHAR(10)  NOT NULL,
    estado           VARCHAR(10)  NOT NULL,
    registros_leidos INT UNSIGNED NOT NULL DEFAULT 0,
    metadatos        TEXT         NULL,
    mensaje          VARCHAR(500) NULL,
    duracion_ms      INT UNSIGNED NOT NULL DEFAULT 0,
    ejecutado_por    INT UNSIGNED NULL,
    fecha            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_esr_source
        FOREIGN KEY (source_id) REFERENCES external_sources (id) ON DELETE CASCADE,
    CONSTRAINT fk_esr_user
        FOREIGN KEY (ejecutado_por) REFERENCES users (id) ON DELETE SET NULL,

    CONSTRAINT chk_esr_tipo CHECK (tipo_ejecucion IN ('prueba', 'sync')),
    CONSTRAINT chk_esr_estado CHECK (estado IN ('ok', 'error')),
    -- Indice para el historial reciente por fuente.
    INDEX idx_esr_source_fecha (source_id, fecha DESC)
) ENGINE = InnoDB;
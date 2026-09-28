-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON IA - Modulo de Cumplimiento
--  Migracion: controles de cumplimiento y sus evaluaciones
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
--  compliance_controls
--  Catalogo de controles de cumplimiento normativo.
--
--  · referencia       norma/marco/categoria de referencia (texto
--                     libre: ej. "LFPDPPP art. 3", "ISO 27001")
--  · severidad        catalogo cerrado (baja/media/alta/critica,
--                     mismo set que ai_detections)
--  · estado           activo/inactivo del control
--  · policy_id        politica de gobierno de datos relacionada
--                     (reutiliza el modulo de Politicas; si la
--                     politica se borra, queda NULL: SET NULL).
--                     El control NO es un dictamen legal.
--  · creado_por       usuario que registro el control
--
--  Al borrar una politica el control se conserva, al borrar un
--  usuario su referencia queda NULL.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS compliance_controls (
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
--  Evaluacion de un control sobre un dataset: la fila define
--  que el control aplica a ese dataset y cual es su estado.
--  Una sola evaluacion por pareja (control, dataset): reevaluar
--  actualiza la fila (UNIQUE). Sin fila = el control no se ha
--  evaluado (ni aplica formalmente) para ese dataset.
--
--  · estado      cumple | no_cumple | pendiente | no_aplica
--  · observaciones / evidencia  detalle o URL/identificador
--  · evaluador_id usuario que registro la ultima evaluacion
--  · fecha       creacion y se refresca al reevaluar (ON UPDATE)
--
--  Al borrar un control o un dataset sus evaluaciones se
--  eliminan solas (ON DELETE CASCADE), igual que en
--  policy_datasets y quality_metrics.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS compliance_evaluations (
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

    -- Evita duplicar la evaluacion del mismo control sobre el dataset
    UNIQUE KEY uq_ce_control_dataset (control_id, dataset_id)
) ENGINE = InnoDB;

-- Nota de indices: InnoDB crea automaticamente un indice por cada
-- columna de llave foranea, por lo que control_id y dataset_id ya
-- estan indexados (el detalle de un dataset filtra por dataset_id).
-- No hacen falta indices adicionales.
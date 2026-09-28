-- ============================================================
--  PLATAFORMA DE GOBIERNO DE DATOS CON IA - Modulo de Linaje
--  Migracion: tabla de relaciones de linaje entre datasets
--  (MySQL 8+)
--
--  Para que sirve:
--    Este archivo SOLO crea la estructura. No inserta datos de
--    ejemplo, porque las relaciones de linaje apuntan a datasets
--    concretos y sus IDs cambian segun la base ya existente.
--
--  Los datos de ejemplo estan en sql/seed.sql y solo aplican a
--  instalaciones nuevas o ambientes de prueba recien creados.
--
--  Como usarlo:
--    1. Ejecuta sql/database.sql y sql/seed.sql (base nueva).
--    2. O, si tu base YA existe y solo le falta esta tabla,
--       ejecuta UNICAMENTE este archivo. No borra ni altera
--       ninguna tabla previa.
--
--  Reejecucion: es idempotente (CREATE TABLE IF NOT EXISTS),
--  asi que puedes volver a ejecutarlo sin riesgo.
-- ============================================================

USE gobierno_datos_ia;

-- ------------------------------------------------------------
--  data_lineage
--  Relacion de linaje entre dos datasets del catalogo.
--  La fila significa: "el dataset origen aporta informacion al
--  dataset destino" segun el tipo indicado.
--
--  · origen_id / destino_id  datasets reales (FK, nunca ids sueltos)
--  · tipo                   fk_nombre 6 valores cerrados
--  · descripcion             opcional, detalle de la relacion
--  · fecha_creacion          momento del registro en la bitacora
--
--  Al borrar un dataset sus relaciones se eliminan solas
--  (ON DELETE CASCADE), igual que en policy_datasets.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS data_lineage (
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

    -- Catalogo cerrado de tipos de relacion
    CONSTRAINT chk_dl_tipo CHECK (tipo IN (
        'derivado_de', 'alimenta', 'integra', 'transforma', 'copia', 'version_de'
    )),

    -- Un dataset no puede depender de si mismo
    CONSTRAINT chk_dl_distintos CHECK (origen_id <> destino_id),

    -- Evita duplicar exactamente la misma relacion; la misma pareja
    -- origen/destino puede repetirse con un tipo diferente
    UNIQUE KEY uq_dl_relacion (origen_id, destino_id, tipo)
) ENGINE = InnoDB;

-- Nota de indices: InnoDB crea automaticamente un indice por cada
-- columna de llave foranea, por lo que origen_id y destino_id ya
-- estan indexados (ambos aparecen en la busqueda del detalle y del
-- listado filtrado). No hacen falta indices adicionales.

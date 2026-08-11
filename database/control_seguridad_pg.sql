-- ============================================================
--  CONTROL DE SEGURIDAD VEHICULAR — CONALCA
--  Script PostgreSQL (Render)
-- ============================================================

DROP TABLE IF EXISTS inspecciones;
DROP TABLE IF EXISTS inspectores;
DROP TABLE IF EXISTS vehiculos;

-- ============================================================
--  TABLA: vehiculos
-- ============================================================
CREATE TABLE vehiculos (
  id         SERIAL PRIMARY KEY,
  placa      VARCHAR(10)  NOT NULL UNIQUE,
  tipo       VARCHAR(30)  NOT NULL,
  marca      VARCHAR(50)  NOT NULL,
  linea      VARCHAR(60)  NOT NULL,
  anio       SMALLINT     NOT NULL,
  zona       VARCHAR(80)  NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLA: inspectores
-- ============================================================
CREATE TABLE inspectores (
  id         SERIAL PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL UNIQUE,
  activo     SMALLINT     DEFAULT 1,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLA: inspecciones
-- ============================================================
CREATE TABLE inspecciones (
  id            SERIAL PRIMARY KEY,
  tipo          VARCHAR(30) NOT NULL CHECK (tipo IN ('botiquin','carretilla','extintor','caja_fuerte','boton_panico','inspeccion_vehiculo')),
  placa         VARCHAR(10)  NOT NULL,
  inspector     VARCHAR(100) NOT NULL,
  fecha         DATE         NOT NULL,
  hora          TIME         NOT NULL,
  observaciones TEXT,
  items         JSONB        NOT NULL,
  fotos         JSONB        DEFAULT '[]'::jsonb,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_placa FOREIGN KEY (placa) REFERENCES vehiculos(placa)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE INDEX idx_placa ON inspecciones(placa);
CREATE INDEX idx_tipo  ON inspecciones(tipo);
CREATE INDEX idx_fecha ON inspecciones(fecha);
CREATE INDEX idx_placa_tipo ON inspecciones(placa, tipo);

-- ============================================================
--  DATOS INICIALES: 79 vehículos (flota CONALCA)
-- ============================================================
INSERT INTO vehiculos (placa, tipo, marca, linea, anio, zona) VALUES
('JTY664', 'Sencillo', 'INTERNATIONAL 4300', '4300', 2013, 'Tequendama'),
('JTZ266', 'Sencillo', 'SITOM', 'STQ125', 2020, 'Sin información'),
('KSP188', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSP844', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSP846', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSP862', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSP868', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Sabana'),
('KSP882', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Tequendama'),
('KSP886', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSQ059', 'Sencillo', 'MERCEDES BENZ', 'ATEGO 1726 AUB L', 2022, 'Bogotá'),
('KSR034', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'KA OFF NorOccidente'),
('KSR043', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Tequendama'),
('KSR044', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Tequendama'),
('KSR045', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Bogotá'),
('KSR048', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Tequendama'),
('LCN234', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Sabana / Tequendama / Gualivá'),
('LCN235', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Gualivá / Sabana'),
('LCN261', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Bogotá'),
('LCN262', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Sabana'),
('LCN264', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Gualivá'),
('LCN266', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Sabana'),
('LCN268', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Bogotá'),
('LCN269', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Bogotá'),
('LGU425', 'Camioneta', 'STARK', 'STARK', 2022, 'KA OFF NorOccidente'),
('LGU430', 'Camioneta', 'STARK', 'STARK', 2022, 'Sabana'),
('LGU478', 'Camioneta', 'STARK', 'STARK', 2022, 'Bogotá'),
('LJS648', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2022, 'Bogotá'),
('LJT515', 'Camioneta', 'DONGFENG', 'DFA5030XXYABEV7', 2022, 'Sin información'),
('LJT525', 'Camioneta', 'DONGFENG', 'DFA5030XXYABEV7', 2021, 'Sin información'),
('LJT540', 'Camioneta', 'DONGFENG', 'DFA5030XXYABEV7', 2021, 'Sin información'),
('LJT767', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Tequendama / Gualivá / Sabana'),
('LJT768', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Tequendama'),
('LJT769', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Sin información'),
('LJT770', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJT771', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJT772', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJT774', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJT775', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJT842', 'Camioneta', 'RENAULT', 'KANGOO', 2023, 'Sin información'),
('LJU881', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU882', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU883', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU884', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU885', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Sabana'),
('LJU886', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Gualivá'),
('LJU953', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU954', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU955', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LJU984', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Bogotá'),
('LPZ380', 'Sencillo', 'INTERNATIONAL', 'MV607 SBA 4X2', 2023, 'Gualivá'),
('PRZ958', 'Sencillo', 'CHEVROLET', 'FVR', 2026, 'Tequendama'),
('PSX002', 'Sencillo', 'CHEVROLET', 'FVR', 2026, 'Gualivá'),
('PSX025', 'Sencillo', 'CHEVROLET', 'FVR', 2026, 'Gualivá'),
('PSX028', 'Sencillo', 'CHEVROLET', 'FVR', 2026, 'Gualivá'),
('PSX932', 'Sencillo', 'CHEVROLET', 'FVR', 2026, 'Tequendama'),
('QJX779', 'Sencillo', 'KENWORTH', '—', 2026, '—'),
('TRK559', 'Sencillo', 'INTERNATIONAL 4300', '4300', 2013, 'KA OFF NorOccidente'),
('TRK560', 'Sencillo', 'INTERNATIONAL 4300', '4300', 2013, 'KA OFF NorOccidente'),
('VEM381', 'Sencillo', 'MERCEDES BENZ', 'Atego 1725', 2007, 'Tequendama'),
('VEN997', 'Sencillo', 'MERCEDES BENZ', 'Atego 1725', 2008, 'Sin información'),
('VEO287', 'Sencillo', 'MERCEDES BENZ', 'Atego 1725', 2007, 'KA OFF NorOccidente'),
('WOW238', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Sin información'),
('WOW239', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOW313', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Bogotá'),
('WOW327', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOW560', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOW762', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Tequendama'),
('WOW798', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Tequendama'),
('WOW870', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'KA OFF NorOccidente'),
('WOW873', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOW874', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Sin información'),
('WOW877', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOW911', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Sin información'),
('WOX439', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOX440', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOX498', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOX624', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'KA OFF NorOccidente'),
('WOX730', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá'),
('WOY316', 'Sencillo', 'CHEVROLET FVR', 'FVR', 2017, 'Gualivá');

-- ============================================================
--  DATOS INICIALES: 4 inspectores
-- ============================================================
INSERT INTO inspectores (nombre) VALUES
('LISED GAITAN'),
('JOHAN BALLEN'),
('YENNY MORENO'),
('JULIANA FORERO');

-- Verificación
SELECT 'Vehículos:' AS info, COUNT(*) AS total FROM vehiculos;
SELECT 'Inspectores:' AS info, COUNT(*) AS total FROM inspectores;

-- ============================================================
-- Agriculture Information System (AIS) - Database Schema v2
-- AP 3 Web Application Development | Supabase & Local PostgreSQL
-- 13 Tables = 6 Core Entities + 7 Lookup Tables (Full 3NF)
-- Soft delete: is_deleted flag on every table (no hard deletes)
-- ============================================================

-- ---------- Drop old tables (safe re-run) ----------
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS harvest;
DROP TABLE IF EXISTS planting;
DROP TABLE IF EXISTS crop;
DROP TABLE IF EXISTS farm;
DROP TABLE IF EXISTS farmer;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS harvest_remarks;
DROP TABLE IF EXISTS soil_types;
DROP TABLE IF EXISTS irrigation_types;
DROP TABLE IF EXISTS crop_categories;
DROP TABLE IF EXISTS seasons;
DROP TABLE IF EXISTS planting_status;
DROP TABLE IF EXISTS quality_grades;

-- ============================================================
-- LOOKUP / REFERENCE TABLES (7)
-- ============================================================

-- ---------- L1. SOIL_TYPES ----------
CREATE TABLE soil_types (
    soil_type_id   SERIAL PRIMARY KEY,
    soil_type_name VARCHAR(50) NOT NULL,
    is_deleted     BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L2. IRRIGATION_TYPES ----------
CREATE TABLE irrigation_types (
    irrigation_type_id   SERIAL PRIMARY KEY,
    irrigation_type_name VARCHAR(50) NOT NULL,
    is_deleted           BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L3. CROP_CATEGORIES ----------
CREATE TABLE crop_categories (
    category_id   SERIAL PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    is_deleted    BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L4. SEASONS ----------
CREATE TABLE seasons (
    season_id   SERIAL PRIMARY KEY,
    season_name VARCHAR(50) NOT NULL,
    is_deleted  BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L5. PLANTING_STATUS ----------
CREATE TABLE planting_status (
    status_id   SERIAL PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL,
    is_deleted  BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L6. QUALITY_GRADES ----------
CREATE TABLE quality_grades (
    grade_id   SERIAL PRIMARY KEY,
    grade_name VARCHAR(50) NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---------- L7. HARVEST_REMARKS ----------
CREATE TABLE harvest_remarks (
    remark_id          SERIAL PRIMARY KEY,
    remark_description VARCHAR(100) NOT NULL,
    is_deleted         BOOLEAN NOT NULL DEFAULT FALSE
);

-- ============================================================
-- CORE ENTITY TABLES (6)
-- ============================================================

-- ---------- 1. FARMER ----------
CREATE TABLE farmer (
    farmer_id       SERIAL PRIMARY KEY,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    contact_number  VARCHAR(20)  NOT NULL,
    address         VARCHAR(150) NOT NULL,
    date_registered DATE NOT NULL DEFAULT CURRENT_DATE,
    is_deleted      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------- 2. FARM ----------
CREATE TABLE farm (
    farm_id            SERIAL PRIMARY KEY,
    farmer_id          INT NOT NULL REFERENCES farmer(farmer_id),
    farm_name          VARCHAR(100) NOT NULL,
    location           VARCHAR(150) NOT NULL,
    area_hectares      NUMERIC(10,2) NOT NULL CHECK (area_hectares > 0),
    soil_type_id       INT NOT NULL REFERENCES soil_types(soil_type_id),
    irrigation_type_id INT NOT NULL REFERENCES irrigation_types(irrigation_type_id),
    is_deleted         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------- 3. CROP ----------
CREATE TABLE crop (
    crop_id             SERIAL PRIMARY KEY,
    crop_name           VARCHAR(100) NOT NULL,
    variety             VARCHAR(100) NOT NULL,
    category_id         INT NOT NULL REFERENCES crop_categories(category_id),
    growing_period_days INT NOT NULL CHECK (growing_period_days > 0),
    season_id           INT NOT NULL REFERENCES seasons(season_id),
    is_deleted          BOOLEAN NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------- 4. PLANTING ----------
CREATE TABLE planting (
    planting_id           SERIAL PRIMARY KEY,
    farm_id               INT NOT NULL REFERENCES farm(farm_id),
    crop_id               INT NOT NULL REFERENCES crop(crop_id),
    planting_date         DATE NOT NULL,
    area_planted          NUMERIC(10,2) NOT NULL CHECK (area_planted > 0),
    seed_quantity         NUMERIC(10,2) NOT NULL CHECK (seed_quantity > 0),
    expected_harvest_date DATE NOT NULL,
    status_id             INT NOT NULL REFERENCES planting_status(status_id),
    is_deleted            BOOLEAN NOT NULL DEFAULT FALSE,
    created_at            TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_harvest_after_planting CHECK (expected_harvest_date > planting_date)
);

-- ---------- 5. HARVEST ----------
CREATE TABLE harvest (
    harvest_id           SERIAL PRIMARY KEY,
    planting_id          INT NOT NULL REFERENCES planting(planting_id),
    harvest_date         DATE NOT NULL,
    quantity_kg          NUMERIC(10,2) NOT NULL CHECK (quantity_kg > 0),
    selling_price_per_kg NUMERIC(10,2) NOT NULL CHECK (selling_price_per_kg > 0),
    grade_id             INT NOT NULL REFERENCES quality_grades(grade_id),
    remark_id            INT NOT NULL REFERENCES harvest_remarks(remark_id),
    is_deleted           BOOLEAN NOT NULL DEFAULT FALSE,
    created_at           TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------- 6. USERS ----------
CREATE TABLE users (
    user_id       SERIAL PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          VARCHAR(20) NOT NULL DEFAULT 'staff' CHECK (role IN ('admin', 'staff')),
    is_deleted    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------- 7. ACTIVITY_LOG (audit trail) ----------
-- Append-only record of every create / update / delete performed in the app.
CREATE TABLE activity_log (
    log_id     SERIAL PRIMARY KEY,
    user_id    INT NOT NULL REFERENCES users(user_id),
    action     VARCHAR(20)  NOT NULL CHECK (action IN ('create', 'update', 'delete')),
    entity     VARCHAR(50)  NOT NULL,
    entity_id  INT NOT NULL,
    details    VARCHAR(200) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Lookups
INSERT INTO soil_types (soil_type_name) VALUES
('Clay Loam'), ('Sandy Loam'), ('Silt Loam'), ('Loam'), ('Clay'), ('Sandy');

INSERT INTO irrigation_types (irrigation_type_name) VALUES
('Irrigated'), ('Rainfed'), ('Drip'), ('Sprinkler');

INSERT INTO crop_categories (category_name) VALUES
('Cereal'), ('Legume'), ('Root Crop'), ('Fruit'), ('Vegetable');

INSERT INTO seasons (season_name) VALUES
('Dry Season'), ('Wet Season'), ('Year Round');

INSERT INTO planting_status (status_name) VALUES
('Planted'), ('Growing'), ('Harvested');

INSERT INTO quality_grades (grade_name) VALUES
('Grade A'), ('Grade B'), ('Grade C');

INSERT INTO harvest_remarks (remark_description) VALUES
('Good Harvest'), ('Acceptable Quality'), ('Low Yield'), ('Poor Quality'), ('Damaged Harvest');

-- Users (passwords: admin123 / staff123 - pre-hashed with PHP bcrypt standard)
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$cbOT.c2K0uMxRACoDIuta.OVvues62RChUZpAetvaypjLt7ZweSjS', 'System Administrator', 'admin'),
('staff', '$2y$10$CN8.2Mr2GHmuVSx75yObDeiP8XHmKsh.5Z00yWHJSy5oU2adt/b/2', 'Field Staff', 'staff');

-- Sample farmers (20 entries)
INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered) VALUES
('Juan', 'Dela Cruz', '09171234567', 'Poblacion, Carcar City, Cebu', '2024-01-15'),
('Maria', 'Santos', '09182345678', 'Mantalongon, Dalaguete, Cebu', '2024-02-10'),
('Pedro', 'Penduko', '09203456789', 'Can-asujan, Carcar City, Cebu', '2024-03-05'),
('Rosa', 'Flores', '09224567890', 'Poblacion, Argao, Cebu', '2024-03-22'),
('Mateo', 'Alcantara', '09155678901', 'Santa Ana, Barili, Cebu', '2024-04-12'),
('Elena', 'Bautista', '09166789012', 'Poog, Toledo City, Cebu', '2024-05-08'),
('Danilo', 'Navarro', '09177890123', 'Buanoy, Balamban, Cebu', '2024-05-30'),
('Corazon', 'Reyes', '09288901234', 'Colonia, Tuburan, Cebu', '2024-06-14'),
('Ramon', 'Garcia', '09399012345', 'Dakit, Bogo City, Cebu', '2024-07-01'),
('Teresa', 'Mendoza', '09450123456', 'South Poblacion, San Fernando, Cebu', '2024-07-19'),
('Fernando', 'Ramos', '09171122334', 'Bahay, Sibonga, Cebu', '2024-08-05'),
('Carmen', 'Villanueva', '09182233445', 'Sabang, Danao City, Cebu', '2024-08-27'),
('Rodrigo', 'Perez', '09193344556', 'Uling, Naga City, Cebu', '2024-09-11'),
('Anita', 'Gonzales', '09204455667', 'Tunghaan, Minglanilla, Cebu', '2024-09-29'),
('Gabriel', 'Aquino', '09215566778', 'Pandacan, Pinamungajan, Cebu', '2024-10-15'),
('Lucila', 'Mercado', '09226677889', 'Angilan, Aloguinsan, Cebu', '2024-11-02'),
('Jaime', 'Castillo', '09237788990', 'Langub, Asturias, Cebu', '2024-11-20'),
('Patricia', 'Cruz', '09248899001', 'Maravilla, Tabuelan, Cebu', '2024-12-05'),
('Victoriano', 'Gomez', '09259900112', 'Hagnaya, San Remigio, Cebu', '2025-01-08'),
('Esperanza', 'Dizon', '09260011223', 'Canhabagat, Medellin, Cebu', '2025-01-25');

-- Sample farms (20 entries)
INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id) VALUES
(1, 'Green Valley Rice Farm', 'Carcar City, Cebu', 3.50, 1, 1),
(2, 'Sunrise Highland Vegetable Farm', 'Dalaguete, Cebu', 2.80, 2, 2),
(3, 'Golden Field Agro Plantation', 'Argao, Cebu', 5.00, 4, 1),
(4, 'Highland Produce Orchard', 'Barili, Cebu', 4.20, 3, 4),
(5, 'Toledo Eco Plains', 'Toledo City, Cebu', 6.50, 5, 1),
(6, 'Balamban Mountain Farm', 'Balamban, Cebu', 3.00, 2, 3),
(7, 'Tuburan Riverbend Fields', 'Tuburan, Cebu', 4.80, 4, 1),
(8, 'Northern Crest Agro Farm', 'Bogo City, Cebu', 7.20, 1, 2),
(9, 'San Fernando Organic Garden', 'San Fernando, Cebu', 2.50, 4, 3),
(10, 'Sibonga Heritage Farm', 'Sibonga, Cebu', 3.80, 3, 2),
(11, 'Danao Hillside Agro', 'Danao City, Cebu', 4.00, 1, 1),
(12, 'Naga Valley Terraces', 'Naga City, Cebu', 2.20, 4, 4),
(13, 'Minglanilla Fresh Acres', 'Minglanilla, Cebu', 1.80, 2, 3),
(14, 'Pinamungajan Terra Rich Farm', 'Pinamungajan, Cebu', 5.50, 5, 1),
(15, 'Aloguinsan Coastal Ridge', 'Aloguinsan, Cebu', 3.20, 6, 2),
(16, 'Asturias Golden Harvest', 'Asturias, Cebu', 4.50, 4, 1),
(17, 'Tabuelan Green Meadows', 'Tabuelan, Cebu', 3.60, 2, 2),
(18, 'San Remigio Agri Fields', 'San Remigio, Cebu', 6.00, 1, 1),
(19, 'Medellin Cane & Corn Estates', 'Medellin, Cebu', 8.00, 5, 1),
(20, 'Daanbantayan Sunshine Fields', 'Daanbantayan, Cebu', 4.10, 2, 3);

-- Sample crops (20 entries)
INSERT INTO crop (crop_name, variety, category_id, growing_period_days, season_id) VALUES
('Rice', 'RC-222 (Tubigan 21)', 1, 115, 2),
('Rice', 'NSIC Rc160', 1, 120, 1),
('Yellow Corn', 'Pioneer 30T80', 1, 105, 1),
('White Corn', 'Lagkitan Glutinous', 1, 90, 3),
('Tomato', 'Diamante Max', 5, 75, 1),
('Eggplant', 'Dumaguete Long Purple', 5, 85, 3),
('Cabbage', 'Scorpio Hybrid', 5, 65, 1),
('Bell Pepper', 'Emperor F1', 5, 80, 1),
('Red Onion', 'Red Pinoy', 5, 110, 1),
('Pole Sitao', 'Sandigan', 2, 55, 3),
('Mungbean', 'Pag-asa 7', 2, 60, 1),
('Peanut', 'NSIC Pn 14', 2, 100, 1),
('Sweet Potato', 'Super SP Yellow', 3, 120, 3),
('Cassava', 'Golden Yellow Lakan', 3, 270, 2),
('Carrot', 'Chantenay Kuroda', 3, 90, 1),
('Watermelon', 'Sugar Baby', 4, 80, 1),
('Honeydew Melon', 'Jade Dew F1', 4, 75, 1),
('Papaya', 'Red Lady 786', 4, 240, 3),
('Banana', 'Cavendish', 4, 300, 3),
('Ampalaya', 'Galaxy Max F1', 5, 60, 3);

-- Sample plantings (20 entries: 18 harvested, 1 growing, 1 planted)
INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id) VALUES
(1, 1, '2025-06-10', 2.50, 100.0, '2025-10-05', 3),
(2, 5, '2025-07-01', 1.20, 0.5,   '2025-09-15', 3),
(3, 3, '2025-07-15', 3.00, 60.0,  '2025-10-30', 3),
(4, 7, '2025-08-05', 1.80, 0.8,   '2025-10-10', 3),
(5, 4, '2025-08-20', 2.20, 45.0,  '2025-11-20', 3),
(6, 6, '2025-09-01', 1.50, 0.4,   '2025-11-25', 3),
(7, 10, '2025-09-15', 2.00, 30.0, '2025-11-10', 3),
(8, 2, '2025-10-01', 4.00, 160.0, '2026-01-30', 3),
(9, 11, '2025-10-15', 1.50, 35.0, '2025-12-15', 3),
(10, 13, '2025-11-01', 2.50, 50.0, '2026-03-01', 3),
(11, 8, '2025-11-15', 1.80, 0.6,   '2026-02-05', 3),
(12, 16, '2025-12-01', 1.50, 2.0,  '2026-02-20', 3),
(13, 9, '2025-12-10', 1.00, 5.0,   '2026-03-30', 3),
(14, 12, '2026-01-05', 2.00, 80.0, '2026-04-15', 3),
(15, 15, '2026-01-15', 1.40, 4.0,  '2026-04-15', 3),
(16, 20, '2026-02-01', 1.60, 1.2,  '2026-04-05', 3),
(17, 17, '2026-02-10', 1.50, 1.8,  '2026-04-25', 3),
(18, 1, '2026-03-01', 3.00, 120.0, '2026-06-25', 3),
(19, 3, '2026-09-15', 3.50, 70.0,  '2026-12-30', 2),
(20, 7, '2026-10-20', 2.00, 0.9,   '2026-12-25', 1);

-- Sample harvests (20 entries)
INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id) VALUES
(1, '2025-10-06', 8200.00, 24.50, 1, 1),
(2, '2025-09-16', 4500.00, 45.00, 1, 1),
(3, '2025-10-31', 7200.00, 28.00, 2, 2),
(4, '2025-10-12', 6100.00, 55.00, 1, 1),
(5, '2025-11-21', 5400.00, 32.00, 2, 2),
(6, '2025-11-26', 3800.00, 48.00, 1, 1),
(7, '2025-11-12', 3200.00, 60.00, 1, 1),
(8, '2026-02-01', 12500.00, 26.00, 1, 1),
(9, '2025-12-16', 2100.00, 85.00, 2, 2),
(10, '2026-03-02', 6800.00, 38.00, 1, 1),
(11, '2026-02-06', 2900.00, 95.00, 1, 1),
(12, '2026-02-22', 7500.00, 35.00, 1, 1),
(13, '2026-04-01', 3100.00, 120.00, 2, 2),
(14, '2026-04-16', 3400.00, 75.00, 3, 3),
(15, '2026-04-16', 4200.00, 65.00, 1, 1),
(16, '2026-04-06', 3600.00, 52.00, 1, 1),
(17, '2026-04-26', 4800.00, 42.00, 1, 1),
(18, '2026-06-26', 9600.00, 25.00, 1, 1),
(1, '2025-10-18', 1400.00, 22.00, 3, 2),
(6, '2025-12-10', 1200.00, 44.00, 2, 2);

-- ============================================================
-- Agriculture Information System (AIS) - Database Schema v2
-- AP 3 Web Application Development | Supabase & Local PostgreSQL
-- 13 Tables = 6 Core Entities + 7 Lookup Tables (Full 3NF)
-- Soft delete: is_deleted flag on every table (no hard deletes)
-- ============================================================

-- ---------- Drop old tables (safe re-run) ----------
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

-- Sample farmers
INSERT INTO farmer (first_name, last_name, contact_number, address, date_registered) VALUES
('Juan',  'Dela Cruz', '09171234567', 'Brgy. Talamban, Cebu City',   '2025-06-10'),
('Maria', 'Santos',    '09182345678', 'Brgy. Busay, Cebu City',      '2025-07-02'),
('Pedro', 'Reyes',     '09193456789', 'Brgy. Guadalupe, Cebu City',  '2025-08-15'),
('Ana',   'Villanueva','09204567890', 'Brgy. Pardo, Cebu City',      '2025-09-01');

-- Sample farms (soil_type_id / irrigation_type_id reference the lookups)
INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id) VALUES
(1, 'Dela Cruz Rice Farm',   'Talamban, Cebu City',  2.50, 1, 1),
(2, 'Santos Vegetable Farm', 'Busay, Cebu City',     1.20, 2, 2),
(3, 'Reyes Corn Field',      'Guadalupe, Cebu City', 3.00, 4, 1),
(1, 'Dela Cruz Upland Plot', 'Talamban, Cebu City',  0.75, 3, 2);

-- Sample crops (category_id / season_id reference the lookups)
INSERT INTO crop (crop_name, variety, category_id, growing_period_days, season_id) VALUES
('Rice',         'NSIC Rc222', 1, 110, 2),
('Corn',         'IPB Var 6',  1, 100, 1),
('Eggplant',     'Casino 901', 5,  90, 3),
('Sweet Potato', 'NSIC Sp30',  3, 120, 1),
('Mung Bean',    'Pag-asa 19', 2,  65, 1);

-- Sample plantings (status_id: 1=Planted, 2=Growing, 3=Harvested)
INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id) VALUES
(1, 1, '2025-06-15', 2.00, 80.00, '2025-10-03', 3),
(2, 3, '2025-08-01', 0.50,  2.50, '2025-10-30', 2),
(3, 2, '2025-09-10', 2.50, 45.00, '2025-12-19', 2),
(4, 5, '2025-10-01', 0.75, 15.00, '2025-12-05', 1);

-- Sample harvests (grade_id: 1=Grade A, 2=Grade B, 3=Grade C)
INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, grade_id, remark_id) VALUES
(1, '2025-10-05', 9500.00, 22.50, 1, 1),
(1, '2025-10-06', 1200.00, 18.00, 2, 2);

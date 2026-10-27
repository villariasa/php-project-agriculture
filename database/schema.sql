-- ============================================================
-- Agriculture Information System (AIS) - Database Schema
-- AP 3 Web Application Development | Supabase & Local PostgreSQL
-- ============================================================

-- ---------- Drop old tables (safe re-run) ----------
DROP TABLE IF EXISTS harvest;
DROP TABLE IF EXISTS harvest_remarks CASCADE;
DROP TABLE IF EXISTS planting;
DROP TABLE IF EXISTS crop CASCADE;
DROP TABLE IF EXISTS farm CASCADE;
DROP TABLE IF EXISTS farmer CASCADE;
DROP TABLE IF EXISTS users;

-- ---------- 1. FARMER ----------
CREATE TABLE farmer (
    farmer_id       SERIAL PRIMARY KEY,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    contact_number  VARCHAR(20)  NOT NULL,
    address         VARCHAR(150) NOT NULL,
    date_registered DATE NOT NULL DEFAULT CURRENT_DATE
);

-- ---------- 2. FARM ----------
CREATE TABLE farm (
    farm_id         SERIAL PRIMARY KEY,
    farmer_id       INT NOT NULL REFERENCES farmer(farmer_id) ON DELETE RESTRICT,
    farm_name       VARCHAR(100) NOT NULL,
    location        VARCHAR(150) NOT NULL,
    area_hectares   NUMERIC(10,2) NOT NULL,
    soil_type       VARCHAR(50)  NOT NULL,
    irrigation_type VARCHAR(50)  NOT NULL
);

-- ---------- 3. CROP ----------
CREATE TABLE crop (
    crop_id             SERIAL PRIMARY KEY,
    crop_name           VARCHAR(100) NOT NULL,
    variety             VARCHAR(100) NOT NULL,
    crop_category       VARCHAR(50)  NOT NULL,  -- Cereal, Legume, Root Crop, Fruit, Vegetable
    growing_period_days INT NOT NULL,
    recommended_season  VARCHAR(50)  NOT NULL   -- Dry Season, Wet Season, Year Round
);

-- ---------- 4. PLANTING ----------
CREATE TABLE planting (
    planting_id           SERIAL PRIMARY KEY,
    farm_id               INT NOT NULL REFERENCES farm(farm_id) ON DELETE RESTRICT,
    crop_id               INT NOT NULL REFERENCES crop(crop_id) ON DELETE RESTRICT,
    planting_date         DATE NOT NULL,
    area_planted          NUMERIC(10,2) NOT NULL,
    seed_quantity         NUMERIC(10,2) NOT NULL,
    expected_harvest_date DATE NOT NULL,
    status                VARCHAR(20) NOT NULL DEFAULT 'Planted',  -- Planted, Growing, Harvested
    CONSTRAINT chk_harvest_after_planting CHECK (expected_harvest_date > planting_date)
);

-- ---------- 5. HARVEST_REMARKS (Lookup Table) ----------
CREATE TABLE harvest_remarks (
    remark_id          SERIAL PRIMARY KEY,
    remark_description VARCHAR(100) NOT NULL
);

-- ---------- 6. HARVEST ----------
CREATE TABLE harvest (
    harvest_id           SERIAL PRIMARY KEY,
    planting_id          INT NOT NULL REFERENCES planting(planting_id) ON DELETE RESTRICT,
    harvest_date         DATE NOT NULL,
    quantity_kg          NUMERIC(10,2) NOT NULL,
    selling_price_per_kg NUMERIC(10,2) NOT NULL,
    quality_grade        VARCHAR(20) NOT NULL,   -- Grade A, Grade B, Grade C
    remark_id            INT NOT NULL REFERENCES harvest_remarks(remark_id) ON DELETE RESTRICT
);

-- ---------- 7. USERS ----------
CREATE TABLE users (
    user_id       SERIAL PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          VARCHAR(20) NOT NULL DEFAULT 'staff',  -- admin, staff
    created_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Lookup: harvest remarks
INSERT INTO harvest_remarks (remark_description) VALUES
('Good Harvest'),
('Acceptable Quality'),
('Low Yield'),
('Poor Quality'),
('Damaged Harvest');

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

-- Sample farms
INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type, irrigation_type) VALUES
(1, 'Dela Cruz Rice Farm',   'Talamban, Cebu City',  2.50, 'Clay Loam',  'Irrigated'),
(2, 'Santos Vegetable Farm', 'Busay, Cebu City',     1.20, 'Sandy Loam', 'Rainfed'),
(3, 'Reyes Corn Field',      'Guadalupe, Cebu City', 3.00, 'Loam',       'Irrigated'),
(1, 'Dela Cruz Upland Plot', 'Talamban, Cebu City',  0.75, 'Silt Loam',  'Rainfed');

-- Sample crops
INSERT INTO crop (crop_name, variety, crop_category, growing_period_days, recommended_season) VALUES
('Rice',         'NSIC Rc222',   'Cereal',    110, 'Wet Season'),
('Corn',         'IPB Var 6',    'Cereal',    100, 'Dry Season'),
('Eggplant',     'Casino 901',   'Vegetable',  90, 'Year Round'),
('Sweet Potato', 'NSIC Sp30',    'Root Crop', 120, 'Dry Season'),
('Mung Bean',    'Pag-asa 19',   'Legume',     65, 'Dry Season');

-- Sample plantings
INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status) VALUES
(1, 1, '2025-06-15', 2.00, 80.00, '2025-10-03', 'Harvested'),
(2, 3, '2025-08-01', 0.50,  2.50, '2025-10-30', 'Growing'),
(3, 2, '2025-09-10', 2.50, 45.00, '2025-12-19', 'Growing'),
(4, 5, '2025-10-01', 0.75, 15.00, '2025-12-05', 'Planted');

-- Sample harvests
INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remark_id) VALUES
(1, '2025-10-05', 9500.00, 22.50, 'Grade A', 1),
(1, '2025-10-06', 1200.00, 18.00, 'Grade B', 2);

# GehTanum: Agriculture Information System (AIS) — System Documentation

**Institution:** Cebu Technological University — Main Campus  
**College:** College of Computer, Information and Communications Technology (CCICT)  
**Course:** AP3 — Web Application Development  
**Document Specification:** Course Portfolio & Midterm Documentation  
**Paper Specification:** Long-Size Bond Paper (8.5" × 13" / Philippine Folio)  
**Database Platform:** Supabase (Cloud PostgreSQL 15+)  
**Application Stack:** PHP 8.2+, Tailwind CSS / Bootstrap 5, Vanilla JavaScript  
**Architecture:** Fully Normalized Relational Model (3NF) — 14 Core & Lookup Tables  
**Academic Term:** First Semester, Academic Year 2024–2025  

---

## Table of Contents

1. [Project Title, Description, and Objectives](#1-project-title-description-and-objectives)
2. [Functional Requirements](#2-functional-requirements)
3. [Entity-Relationship Diagram (ERD)](#3-entity-relationship-diagram-erd)
4. [Database Normalization from UNF through 1NF, 2NF, and 3NF](#4-database-normalization-from-unf-through-1nf-2nf-and-3nf)
5. [Data Dictionary](#5-data-dictionary)
6. [Table Relationships, Constraints, and Lookup Data](#6-table-relationships-constraints-and-lookup-data)
7. [User Guide or Basic Operating Instructions](#7-user-guide-or-basic-operating-instructions)
8. [Screenshots or Evidence of All Major System Modules](#8-screenshots-or-evidence-of-all-major-system-modules)
9. [Test Cases and Results Showing that the Required Functions Work](#9-test-cases-and-results-showing-that-the-required-functions-work)
10. [Appendix: Deployment Architecture & Technical Specifications](#10-appendix-deployment-architecture--technical-specifications)

---

## 1. Project Title, Description, and Objectives

### 1.1 Project Title & Overview
**GehTanum (Agriculture Information System)** — Centralized Web-Based Agricultural Operations, Farmland Mapping, Crop Lifecycle Tracking, and Yield Analytics Platform.

### 1.2 Project Description
**GehTanum** (Agriculture Information System) is an enterprise-grade agricultural management web platform engineered to modernize rural and cooperative farm records administration. Traditional farming operations frequently suffer from fragmented paper records, uncoordinated planting schedules, manual calculations of farm yield, and inaccurate profitability estimates. GehTanum resolves these operational inefficiencies by establishing a centralized, relational database architecture that unifies farmer profiles, farm land boundaries, crop master catalogs, seasonal planting cycles, and harvest transactions.

The system enforces strict domain integrity through controlled foreign key lookup tables, eliminates data redundancy via Third Normal Form (3NF) normalization, and delivers live operational intelligence through real-time KPI dashboards, automated cycle status workflows, and customizable reports with CSV data export capabilities. Built on PHP 8.2 and cloud-hosted Supabase PostgreSQL, GehTanum provides role-based access control, responsive mobile-first navigation, and an immutable transaction audit log to ensure administrative accountability across all farming communities.

### 1.3 Project Objectives
#### 1.3.1 General Objective
To architect, develop, and deploy a secure, fully normalized, web-based Agriculture Information System (**GehTanum**) that centralizes agricultural operations, streamlines field transaction logging from planting through harvest, and delivers real-time operational analytics for farm administrators and extension workers.

#### 1.3.2 Specific Objectives
1. **Farmer Directory & Land Tenure Management:** Establish a relational repository of agricultural producers and their corresponding farmer lands, recording geographic locations, land acreage in hectares, soil classifications, and irrigation systems.
2. **Agronomic Catalog Standardization:** Maintain a master catalog of crops, hybrid varieties, botanical categories, maturity durations (in days), and recommended growing seasons.
3. **Planting Lifecycle Monitoring:** Track field planting activities with automated calculation of expected harvest dates, land allocation constraints, and seed stock consumption.
4. **Harvest Production & Valuation:** Record harvest outputs, gross yields (kg), commercial selling prices, quality grading, and condition assessments, with automatic status updates upon yield completion.
5. **Operational Intelligence & Reporting:** Deliver dynamic reporting suites (Farmer Land Ownership, Crop Production per Farm, Planting Cycle Status, Harvest Yield & Revenue, Farmer Profitability, Seasonal Performance, and Variety Yield Efficiency) equipped with instant CSV data exports.
6. **Enterprise Security & Auditability:** Enforce bcrypt credential encryption, session state management, role-based access control (Admin vs. Staff), and an append-only transaction audit log recording all insert, update, and soft-delete operations.

## 2. Functional Requirements

The system architecture satisfies fourteen comprehensive functional requirements spanning core agronomic transactions, administrative controls, data integrity, and analytical reporting.

| # | Requirement Name | Description & Functional Scope | Implementation Files | Authorized Roles |
| --- | --- | --- | --- | --- |
| FR1 | Farmer Profile Management | Register new farmers, view profile directories, edit personal/contact info, search/filter by name/address, and perform soft-deletion. | farmers/index.php, models/Farmer.php | Admin, Staff |
| FR2 | Farm Land Management | Register and maintain farmer lands linked to owning farmers, recording land area in hectares, and selecting soil/irrigation lookup types. | farms/index.php, models/Farm.php | Admin, Staff |
| FR3 | Crop Master Catalog | Manage catalog of agricultural crops, varieties, botanical categories, maturity cycle durations (days), and optimal growing seasons. | crops/index.php, models/Crop.php | Admin, Staff |
| FR4 | Planting Transactions | Record agricultural planting activities linking farmer lands to crops, tracking ground area planted, seed quantities, and expected harvest dates. | plantings/index.php, models/Planting.php | Admin, Staff |
| FR5 | Harvest Transactions | Record harvest outputs linked to plantings, logging yield quantity (kg), unit selling price, quality grades, and remarks. Automatically updates planting status. | harvests/index.php, models/Harvest.php | Admin, Staff |
| FR6 | Executive Dashboard | Real-time summary KPI cards (total farmers, farms, crops, active plantings, harvests, revenue), date range filtering, recent activity, and quick navigation. | index.php | Admin, Staff |
| FR7 | Authentication & Session | Secure authentication enforcing encrypted bcrypt passwords, session token management, and automatic unauthenticated redirection. | auth/login.php, auth/logout.php, includes/auth_check.php | All Users |
| FR8 | Role-Based Access Control | Enforce strict privilege boundaries where Admin accounts access user management and lookup settings, while Staff is restricted to operational modules. | includes/auth_check.php, users/*.php, settings/*.php | Enforced System-wide |
| FR9 | Search, Filter & Sorting | Multi-column live search, dropdown filtering (by address, irrigation, category, status, grade), and ascending/descending table sorting. | includes/table_helpers.php, all module index.php files | Admin, Staff |
| FR10 | Operational Reports & CSV | Generate four dedicated reporting suites: Farmers & Farms, Crop Production, Planting Status, and Harvest & Revenue with live CSV export capabilities. | reports/*.php, includes/report_helpers.php | Admin, Staff |
| FR11 | Controlled Dropdowns | Enforce data integrity by replacing arbitrary text fields with controlled dropdowns backed by foreign key lookup tables. | models/Lookup.php, all create/edit modal views | Admin, Staff |
| FR12 | Destructive Action Safety | Prevent accidental data loss via client-side confirmation modals before soft-deleting any record, with referential integrity enforcement. | includes/confirm_modal.php, assets/js/main.js | Admin, Staff |
| FR13 | Audit Trail Logging | Persist comprehensive audit logs recording who (user ID), what action (create/update/delete), which entity, and timestamp for all transactions. | includes/activity_log.php, database activity_log, users/activity.php | Admin Only |
| FR14 | Responsive Interface | Adaptive interface supporting mobile, tablet, and desktop views with persistent bottom navigation bar on mobile viewports. | includes/header.php, assets/css/style.css | All Users |


## 3. Entity-Relationship Diagram (ERD)

### 3.1 Entity-Relationship Diagram Model
The relational database schema is structured into six core transactional entities and seven reference lookup entities connected via strict foreign key constraints.

![Figure 3.1: Complete Entity-Relationship Diagram (ERD)](images/erd.png)

*Figure 3.1: Complete Crow's Foot Entity-Relationship Diagram (ERD) — Conceptual & Physical Relational Model.*

### 3.2 Conceptual Entity Architecture
```
[FARMER] (1) ──< (M) [FARM] (1) ──< (M) [PLANTING] (1) ──< (M) [HARVEST]
                       │                       │                     │
               [soil_types] (M:1)         [crop] (M:1)       [quality_grades] (M:1)
            [irrigation_types] (M:1)           │             [harvest_remarks] (M:1)
                                        [crop_categories] (M:1)
                                           [seasons] (M:1)
                                       [planting_status] (M:1)

[USERS] (1) ──< (M) [ACTIVITY_LOG]
```

### 3.3 Relationship & Cardinality Summary
| Parent Table | Child Table | Cardinality | Foreign Key Column | Relationship Description |
| --- | --- | --- | --- | --- |
| farmer | farm | 1 : M (One-to-Many) | farm.farmer_id | A single farmer can own and manage multiple registered farmer lands. |
| farm | planting | 1 : M (One-to-Many) | planting.farm_id | A farm land hosts multiple seasonal planting cycles over time. |
| crop | planting | 1 : M (One-to-Many) | planting.crop_id | A crop master entry is referenced across numerous planting records. |
| planting | harvest | 1 : M (One-to-Many) | harvest.planting_id | A planting cycle yields one or more harvest batches. |
| soil_types | farm | 1 : M (One-to-Many) | farm.soil_type_id | A soil type classification characterizes multiple farmer lands. |
| irrigation_types | farm | 1 : M (One-to-Many) | farm.irrigation_type_id | An irrigation infrastructure method is applied to multiple farms. |
| crop_categories | crop | 1 : M (One-to-Many) | crop.category_id | A crop category groups multiple crop species. |
| seasons | crop | 1 : M (One-to-Many) | crop.season_id | A seasonal climate condition applies to multiple crop types. |
| planting_status | planting | 1 : M (One-to-Many) | planting.status_id | A workflow status tracks the lifecycle state of plantings. |
| quality_grades | harvest | 1 : M (One-to-Many) | harvest.grade_id | A commercial quality standard categorizes harvest yields. |
| harvest_remarks | harvest | 1 : M (One-to-Many) | harvest.remark_id | A controlled assessment remark describes harvest outcomes. |
| users | activity_log | 1 : M (One-to-Many) | activity_log.user_id | A system user account generates multiple audit log entries. |


## 4. Database Normalization from UNF through 1NF, 2NF, and 3NF

Normalization eliminates data redundancy, anomalies (insertion, update, and deletion), and establishes relational integrity across all operational tables.

### 4.1 Unnormalized Form (UNF)
The unnormalized structure contains repeating groups for crops planted and harvests logged under each farmer land record.

| Farmer Name | Contact | Farm Location | Farm Name & Type | Crops Planted (Repeating Group) | Harvest Transactions (Repeating Group) |
| --- | --- | --- | --- | --- | --- |
| Juan Dela Cruz | 09171234567 | Poblacion, Carcar City, Cebu | Green Valley Rice Farm<br>(Clay Loam, Irrigated) | • Rice (RC-222, Cereal, Wet Season, 115d): 2024-06-10, 2.5 ha, 100 kg<br>• Yellow Corn (Pioneer 30T80, Dry Season, 105d): 2024-10-15, 1.0 ha, 20 kg | • Rice: 2024-10-05, 8,200 kg @ ₱24.50/kg, Grade A, Good Harvest<br>• Corn: 2025-01-28, 3,500 kg @ ₱18.00/kg, Grade B, Acceptable Quality |
| Maria Santos | 09182345678 | Mantalongon, Dalaguete, Cebu | Sunrise Highland Vegetable Farm<br>(Sandy Loam, Rainfed) | • Cabbage (Scorpio Hybrid, Vegetable, Dry Season, 65d): 2024-08-01, 1.5 ha, 15 kg<br>• Tomato (Diamante Max, Dry Season, 75d): 2024-11-01, 1.3 ha, 10 kg | • Cabbage: 2024-10-10, 6,400 kg @ ₱35.00/kg, Grade A, Good Harvest<br>• Tomato: 2025-01-20, 4,200 kg @ ₱45.00/kg, Grade A, Good Harvest |


### 4.2 First Normal Form (1NF)
First Normal Form eliminates repeating groups by ensuring all attributes are atomic, establishing primary keys, and introducing surrogate identifiers.

| Farmer | Contact | Farm Name | Soil | Irrigation | Crop | Variety | Planted | Area | Harvest Date | Yield (kg) | Price | Grade | Remark |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Juan Dela Cruz | 09171234567 | Green Valley Rice Farm | Clay Loam | Irrigated | Rice | RC-222 | 2024-06-10 | 2.5 ha | 2024-10-05 | 8,200 | ₱24.50 | Grade A | Good Harvest |
| Juan Dela Cruz | 09171234567 | Green Valley Rice Farm | Clay Loam | Irrigated | Yellow Corn | Pioneer 30T80 | 2024-10-15 | 1.0 ha | 2025-01-28 | 3,500 | ₱18.00 | Grade B | Acceptable Quality |
| Maria Santos | 09182345678 | Sunrise Highland Farm | Sandy Loam | Rainfed | Cabbage | Scorpio Hybrid | 2024-08-01 | 1.5 ha | 2024-10-10 | 6,400 | ₱35.00 | Grade A | Good Harvest |
| Maria Santos | 09182345678 | Sunrise Highland Farm | Sandy Loam | Rainfed | Tomato | Diamante Max | 2024-11-01 | 1.3 ha | 2025-01-20 | 4,200 | ₱45.00 | Grade A | Good Harvest |


### 4.3 Second Normal Form (2NF)
Second Normal Form eliminates partial functional dependencies. Attributes depending only on a portion of a composite key are decoupled into distinct tables: `farmer`, `farm`, `crop`, `planting`, and `harvest`.

### 4.4 Third Normal Form (3NF)
Third Normal Form eliminates transitive dependencies (non-key attributes depending on other non-key attributes). Controlled vocabulary strings (soil types, irrigation methods, crop categories, seasons, planting statuses, quality grades, harvest remarks) are extracted into independent lookup reference tables.

### 4.5 Normalization Synthesis & Verification
The final schema achieves 3NF compliance with zero non-atomic fields, zero partial dependencies, and zero transitive dependencies.

## 5. Data Dictionary

The data dictionary provides complete technical definitions, data types, nullability, constraints, default values, and operational descriptions for all database tables.

### Table: `farmer`
*Stores core biographical and contact profiles of registered agricultural producers.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| farmer_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key uniquely identifying each farmer. |
| first_name | VARCHAR(50) | NOT NULL | None | Given legal first name of the registered farmer. |
| last_name | VARCHAR(50) | NOT NULL | None | Family name or surname of the registered farmer. |
| contact_number | VARCHAR(20) | NOT NULL | None | Active primary mobile or telephone contact number. |
| address | VARCHAR(150) | NOT NULL | None | Barangay, municipality, and provincial residence address. |
| date_registered | DATE | NOT NULL | CURRENT_DATE | Calendar date the farmer was registered into the system. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag: TRUE marks record archived/hidden. |
| created_at | TIMESTAMP | NOT NULL | NOW() | System timestamp marking initial record creation. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | System timestamp recording most recent record update. |


### Table: `farm`
*Stores farmland information, agricultural properties, and references to the owning farmer.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| farm_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key uniquely identifying each farm land. |
| farmer_id | INT | NOT NULL, FK -> farmer(farmer_id) | None | Foreign key linking land to registered owner (1:M). |
| farm_name | VARCHAR(100) | NOT NULL | None | Official or popular name of the agricultural farm land. |
| location | VARCHAR(150) | NOT NULL | None | Sitio/Barangay and Municipality where farm is situated. |
| area_hectares | NUMERIC(10,2) | NOT NULL, CHECK (> 0) | None | Total cultivable land area in hectares (must be positive). |
| soil_type_id | INT | NOT NULL, FK -> soil_types(soil_type_id) | None | Foreign key reference to controlled soil type lookup. |
| irrigation_type_id | INT | NOT NULL, FK -> irrigation_types(irrigation_type_id) | None | Foreign key reference to controlled irrigation lookup. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for safe record deactivation. |
| created_at | TIMESTAMP | NOT NULL | NOW() | System timestamp recording initial insertion. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | System timestamp recording latest modification. |


### Table: `crop`
*Maintains agricultural crop master data, botanical varieties, and agronomic cycles.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| crop_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key identifying the crop species/variety. |
| crop_name | VARCHAR(100) | NOT NULL | None | Common commodity name (e.g., Rice, Yellow Corn, Tomato). |
| variety | VARCHAR(100) | NOT NULL | None | Specific cultivar or hybrid designation (e.g., RC-222, Diamante Max). |
| category_id | INT | NOT NULL, FK -> crop_categories(category_id) | None | Foreign key categorizing crop (Cereal, Legume, Fruit, etc.). |
| growing_period_days | INT | NOT NULL, CHECK (> 0) | None | Average days required from planting to harvest maturity. |
| season_id | INT | NOT NULL, FK -> seasons(season_id) | None | Foreign key specifying optimal agronomic climate season. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for archiving retired crop varieties. |
| created_at | TIMESTAMP | NOT NULL | NOW() | System audit timestamp at creation. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | System audit timestamp at latest update. |


### Table: `planting`
*Records agricultural planting operational cycles, linking farmer lands to crops and tracking schedule.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| planting_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for the planting transaction record. |
| farm_id | INT | NOT NULL, FK -> farm(farm_id) | None | Foreign key linking planting event to host farm land. |
| crop_id | INT | NOT NULL, FK -> crop(crop_id) | None | Foreign key linking to specific crop variety planted. |
| planting_date | DATE | NOT NULL | None | Calendar date on which seeds or seedlings were planted. |
| area_planted | NUMERIC(10,2) | NOT NULL, CHECK (> 0) | None | Physical ground area allocated in hectares. |
| seed_quantity | NUMERIC(10,2) | NOT NULL, CHECK (> 0) | None | Weight of seed stock or seedlings utilized in kilograms. |
| expected_harvest_date | DATE | NOT NULL, CHECK (> planting_date) | None | Projected completion date; must strictly succeed planting_date. |
| status_id | INT | NOT NULL, FK -> planting_status(status_id) | None | Lifecycle phase: Planted, Growing, or Harvested. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag preserving historical relationship integrity. |
| created_at | TIMESTAMP | NOT NULL | NOW() | Audit creation timestamp. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | Audit last-modified timestamp. |


### Table: `harvest`
*Captures agricultural harvest production yields, commodity valuation, and quality remarks.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| harvest_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key identifying the harvest yield transaction. |
| planting_id | INT | NOT NULL, FK -> planting(planting_id) | None | Foreign key identifying originating planting cycle. |
| harvest_date | DATE | NOT NULL | None | Actual calendar date on which crops were harvested. |
| quantity_kg | NUMERIC(10,2) | NOT NULL, CHECK (> 0) | None | Net weight of crops harvested in kilograms. |
| selling_price_per_kg | NUMERIC(10,2) | NOT NULL, CHECK (> 0) | None | Realized or market price per kilogram in Philippine Pesos (PHP). |
| grade_id | INT | NOT NULL, FK -> quality_grades(grade_id) | None | Foreign key referencing commercial grading tier. |
| remark_id | INT | NOT NULL, FK -> harvest_remarks(remark_id) | None | Foreign key referencing standardized condition remark. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag. |
| created_at | TIMESTAMP | NOT NULL | NOW() | Audit creation timestamp. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | Audit last-modified timestamp. |


### Table: `users`
*Stores authorized system user credentials, display names, and role-based permissions.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| user_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key identifying the user account. |
| username | VARCHAR(50) | NOT NULL, UNIQUE | None | Unique alphanumeric operator login identifier. |
| password_hash | VARCHAR(255) | NOT NULL | None | One-way cryptographic hash generated via bcrypt. |
| full_name | VARCHAR(100) | NOT NULL | None | Official full name displayed in application headers and logs. |
| role | VARCHAR(20) | NOT NULL, CHECK IN ('admin','staff') | 'staff' | Access tier: 'admin' has full privileges; 'staff' has standard CRUD. |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for terminating account access. |
| created_at | TIMESTAMP | NOT NULL | NOW() | Account registration timestamp. |
| updated_at | TIMESTAMP | NOT NULL | NOW() | Account update timestamp. |


### Table: `activity_log`
*Maintains an immutable audit trail of all insert, update, and soft-delete transactions.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| log_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Sequential log event identifier. |
| user_id | INT | NOT NULL, FK -> users(user_id) | None | Identifies the user account that triggered the system change. |
| action | VARCHAR(20) | NOT NULL, CHECK IN ('create','update','delete') | None | Classification of mutation performed. |
| entity | VARCHAR(50) | NOT NULL | None | Target module or table name (e.g., 'farmer', 'harvest'). |
| entity_id | INT | NOT NULL | None | Primary key integer of the record impacted by the operation. |
| details | VARCHAR(200) | NOT NULL | '' | Human-readable summary or audit snapshot of altered values. |
| created_at | TIMESTAMP | NOT NULL | NOW() | System timestamp indicating exact time of mutation. |


### Table: `soil_types`
*Lookup table specifying standardized soil classifications for agricultural lands.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| soil_type_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for soil classification code. |
| soil_type_name | VARCHAR(50) | NOT NULL | None | Soil name (Clay Loam, Sandy Loam, Silt Loam, Loam, Clay, Sandy). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `irrigation_types`
*Lookup table specifying recognized agricultural water delivery systems.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| irrigation_type_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for irrigation type code. |
| irrigation_type_name | VARCHAR(50) | NOT NULL | None | Irrigation system (Irrigated, Rainfed, Drip, Sprinkler). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `crop_categories`
*Lookup table grouping crop botanical classifications.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| category_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for crop category code. |
| category_name | VARCHAR(50) | NOT NULL | None | Category label (Cereal, Legume, Root Crop, Fruit, Vegetable). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `seasons`
*Lookup table specifying tropical climate seasons in the Philippines.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| season_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for season code. |
| season_name | VARCHAR(50) | NOT NULL | None | Season designation (Dry Season, Wet Season, Year Round). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `planting_status`
*Lookup table governing the operational status of crop planting batches.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| status_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for planting status code. |
| status_name | VARCHAR(50) | NOT NULL | None | Lifecycle status name (Planted, Growing, Harvested). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `quality_grades`
*Lookup table establishing commercial commodity quality grades.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| grade_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for produce quality grade. |
| grade_name | VARCHAR(50) | NOT NULL | None | Commercial standard designation (Grade A, Grade B, Grade C). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


### Table: `harvest_remarks`
*Lookup table establishing standardized crop condition remarks upon harvest.*

| Column Name | Data Type | Constraints | Default | Description & Field Rules |
| --- | --- | --- | --- | --- |
| remark_id | SERIAL | PRIMARY KEY, NOT NULL | NextVal() | Surrogate primary key for harvest remark code. |
| remark_description | VARCHAR(100) | NOT NULL | None | Standardized outcome remark (Good Harvest, Acceptable Quality, Low Yield, Poor Quality, Damaged Harvest). |
| is_deleted | BOOLEAN | NOT NULL | FALSE | Soft delete flag for lookup value maintenance. |


## 6. Table Relationships, Constraints, and Lookup Data

### 6.1 Domain & Relational Integrity Constraints
The system enforces database-level CHECK constraints, unique indexes, and foreign key validations to guarantee data validity at the database engine level.

| Table Name | Constraint Name / Type | Constraint Expression / Condition | Enforced Business Rule |
| --- | --- | --- | --- |
| farm | CHECK (area_hectares) | area_hectares > 0 | Enforces that farm farmland size must strictly be a positive real number. |
| crop | CHECK (growing_period_days) | growing_period_days > 0 | Ensures maturity cycle in days is strictly positive. |
| planting | CHECK (area_planted) | area_planted > 0 | Area cultivated in hectares must be positive. |
| planting | CHECK (seed_quantity) | seed_quantity > 0 | Seed weight utilized in kg must be greater than zero. |
| planting | CHECK (chk_harvest_after_planting) | expected_harvest_date > planting_date | Guarantees chronological validity; expected harvest cannot precede planting date. |
| harvest | CHECK (quantity_kg) | quantity_kg > 0 | Harvested crop weight in kg must be greater than zero. |
| harvest | CHECK (selling_price_per_kg) | selling_price_per_kg > 0 | Price per kg in Philippine Pesos (PHP) must be positive. |
| users | CHECK (role) | role IN ('admin', 'staff') | Restricts account authorization to strictly defined role tiers. |
| users | UNIQUE (username) | UNIQUE (username) | Guarantees unique login usernames across all system accounts. |
| activity_log | CHECK (action) | action IN ('create', 'update', 'delete') | Restricts audit transaction operations to standard CRUD verbs. |
| all tables | BOOLEAN DEFAULT FALSE | is_deleted = FALSE | Preserves referential integrity via soft-deletion instead of destructive hard deletes. |


### 6.2 Controlled Reference Lookup Data Catalog
Reference tables govern all system dropdowns to maintain consistent operational terminology.

#### Lookup Table: harvest_remarks
*Standardized crop yield outcome and physical condition remarks.*

| remark_id | remark_description | Agricultural Evaluation Criteria |
| --- | --- | --- |
| 1 | Good Harvest | Produce exceeds standard quality and yield benchmarks with zero pest damage. |
| 2 | Acceptable Quality | Commercial quality meets standard market specifications with minor aesthetic variances. |
| 3 | Low Yield | Yield quantity lower than forecasted expectations due to climatic or soil factors. |
| 4 | Poor Quality | Produce affected by physical defects, sub-optimal sizing, or handling damage. |
| 5 | Damaged Harvest | Crop damaged by severe weather, pest infestation, or plant disease outbreaks. |


#### Lookup Table: soil_types
*Standardized soil texture classifications for farmer lands.*

| soil_type_id | soil_type_name | Characteristics & Best Suited Crops |
| --- | --- | --- |
| 1 | Clay Loam | Balanced mix of clay, silt, and sand; excellent nutrient retention for rice and corn. |
| 2 | Sandy Loam | Fast draining and warm; highly suited for vegetable crops and legumes. |
| 3 | Silt Loam | High fertility and moisture retention; optimal for leafy vegetables and cereals. |
| 4 | Loam | Ideal agricultural soil texture; supports universal crop rotation and orchards. |
| 5 | Clay | Dense water-holding capacity; optimal for lowland irrigated rice cultivation. |
| 6 | Sandy | Rapid percolation and low organic matter; suitable for root crops with irrigation. |


#### Lookup Table: irrigation_types
*Agricultural water delivery and irrigation methodologies.*

| irrigation_type_id | irrigation_type_name | Operational Delivery Description |
| --- | --- | --- |
| 1 | Irrigated | Canal or pump gravity-fed system providing reliable, continuous water flow. |
| 2 | Rainfed | Natural precipitation-dependent farming without artificial conveyance infrastructure. |
| 3 | Drip | Targeted low-pressure emitter system delivering water directly to crop root zones. |
| 4 | Sprinkler | Overhead pressurized nozzle distribution simulating artificial rainfall. |


#### Lookup Table: crop_categories
*Broad agronomic and botanical classifications.*

| category_id | category_name | Classification Scope & Examples |
| --- | --- | --- |
| 1 | Cereal | Staple grain crops cultivated for edible seeds (e.g., Rice, Yellow Corn, White Corn). |
| 2 | Legume | Nitrogen-fixing pod crops cultivated for pulses (e.g., Mungbean, Peanut, String Bean). |
| 3 | Root Crop | Underground edible storage tuber and root crops (e.g., Sweet Potato, Cassava). |
| 4 | Fruit | Perennial and seasonal horticultural fruit crops (e.g., Mango, Papaya, Banana). |
| 5 | Vegetable | Nutritional culinary plant crops (e.g., Tomato, Eggplant, Cabbage, Bell Pepper). |


#### Lookup Table: seasons
*Tropical agro-climatic growing seasons in the Philippines.*

| season_id | season_name | Climatic Window & Agronomic Context |
| --- | --- | --- |
| 1 | Dry Season | Period of low precipitation (November to April); ideal for irrigated cereals and solanaceous crops. |
| 2 | Wet Season | Monsoon rainy period (May to October); optimal for rainfed lowland rice and robust field crops. |
| 3 | Year Round | Tolerant cultivars capable of sustained continuous production throughout all calendar months. |


#### Lookup Table: planting_status
*Lifecycle phases tracking crop development from field sowing to harvest.*

| status_id | status_name | Lifecycle Stage & Operational Rule |
| --- | --- | --- |
| 1 | Planted | Initial field phase: seeds sown or seedlings transplanted; monitoring germination. |
| 2 | Growing | Vegetative growth and flowering phase; active fertilizer and pest management applied. |
| 3 | Harvested | Terminal phase: maturity reached and yield gathered; automatically updated by harvest log. |


#### Lookup Table: quality_grades
*Standardized commercial grading tiers for agricultural commodities.*

| grade_id | grade_name | Market Quality Specification |
| --- | --- | --- |
| 1 | Grade A | Premium export/supermarket grade: uniform size, flawless appearance, peak ripeness. |
| 2 | Grade B | Standard wholesale/retail grade: sound internal quality with minor surface blemishes. |
| 3 | Grade C | Processing or discount grade: non-uniform size or cosmetic defects; fully edible. |


### 6.3 Transaction Integrity & Concurrency Controls
To support high-concurrency enterprise environments and prevent data corruption during simultaneous user actions, the database architecture enforces strict ACID transaction boundaries and lock management protocols.

#### Figure 6.1: Transaction Commit & State Persistence
*Verification of explicit transaction boundary commit in PostgreSQL, ensuring permanent persistence of multi-table updates.* — **Core Concept:** `COMMIT / ACID Durability`

![Figure 6.1: Transaction Commit & State Persistence](images/commit.png)

#### Figure 6.2: Transaction Rollback & Atomicity Safeguard
*Demonstration of rollback upon transaction failure, reverting all uncommitted state changes to preserve database consistency.* — **Core Concept:** `ROLLBACK / ACID Atomicity`

![Figure 6.2: Transaction Rollback & Atomicity Safeguard](images/rollback.png)

#### Figure 6.3: Partial Rollback via Savepoint Management
*Utilization of named savepoints within complex transaction cycles, allowing granular rollback of nested operations without aborting the entire transaction.* — **Core Concept:** `SAVEPOINT / Sub-transactions`

![Figure 6.3: Partial Rollback via Savepoint Management](images/savepoint.png)

#### Figure 6.4: Explicit Table-Level Locking Mechanisms
*Enforcement of table-level access locks during bulk administrative imports and batch migrations to prevent conflicting concurrent writes.* — **Core Concept:** `LOCK TABLE / Concurrency Control`

![Figure 6.4: Explicit Table-Level Locking Mechanisms](images/lock.png)

#### Figure 6.5: Row-Level Locking via SELECT FOR UPDATE
*Fine-grained row-level locking preventing race conditions when allocating farmer lands or reserving seed stock during simultaneous plantings.* — **Core Concept:** `SELECT FOR UPDATE / Row Locks`

![Figure 6.5: Row-Level Locking via SELECT FOR UPDATE](images/lock-selected-rows.png)

#### Figure 6.6: Deadlock Prevention & Resource Ordering
*Enforcement of deterministic locking sequences across related tables (Farmer -> Farm -> Planting -> Harvest) to eliminate circular deadlock conditions.* — **Core Concept:** `Deadlock Avoidance Strategy`

![Figure 6.6: Deadlock Prevention & Resource Ordering](images/prevent-deadlock.png)

#### Figure 6.7: Transaction Completion & Lock Release
*Automatic and safe release of acquired table and row locks upon transaction termination, restoring full concurrent throughput.* — **Core Concept:** `Lock Release & Clean Termination`

![Figure 6.7: Transaction Completion & Lock Release](images/release-lock.png)

## 7. User Guide or Basic Operating Instructions

### 7.1 System Authentication & Access
1. Navigate to the GehTanum portal URL (`auth/login.php`).
2. Enter registered credentials (Username and Password).
3. Click **Sign In**. The system validates credentials via bcrypt hashing, initializes session parameters, and redirects authorized users to the Executive Dashboard.
4. To terminate an active session, click the **Logout** button in the header profile menu.

### 7.2 Navigating the Executive Operational Dashboard
1. **KPI Overview:** The top row displays total registered farmers, active farmer lands, cultivated crops, active planting cycles, total harvest volume, and cumulative revenue.
2. **Date Interval Filter:** Select custom start and end dates to filter production metrics dynamically.
3. **Quick Navigation:** Use the left navigation rail or bottom bar on mobile to switch between operational records, reports, and settings.

### 7.3 Managing Agricultural Records
- **Farmers Directory:** Click **Farmers** in the sidebar. Use the search bar to locate producers. Click **+ Add Farmer** to open the registration card. Fill in personal details, address, and contact numbers. Click **Save**.
- **Farm Lands:** Click **Farms**. Click **+ Add Farm**. Select the registered owner from the dropdown, specify land name, geographic location, area in hectares, soil classification, and irrigation method. Click **Save**.
- **Crop Master:** Click **Crops**. Click **+ Add Crop**. Enter crop name, hybrid variety, category, maturity cycle days, and recommended growing season.
- **Planting Transactions:** Click **Plantings**. Click **+ Record Planting**. Select the target farm land and crop variety. Enter planting date, land area planted (ha), and seed quantity (kg). The system automatically calculates forecasted harvest date. Status initializes as 'Planted'.
- **Harvest Logging:** Click **Harvests**. Click **+ Record Harvest**. Select the corresponding active planting cycle. Enter harvest date, gross quantity (kg), unit selling price per kg, quality grade, and remarks. Upon submission, the system automatically transitions the planting cycle status to 'Harvested'.

### 7.4 Generating Operational Reports and CSV Export
1. Navigate to **Reports** from the main menu.
2. Select any desired report suite (e.g., Crop Production per Farm, Harvest Yield & Revenue, Farmer Profitability).
3. Apply available dropdown filters (e.g., specific farm, category, date range).
4. Click **Print** to open the optimized print preview for long bond paper.
5. Click **Export CSV** to instantly download the filtered dataset in RFC 4180-compliant CSV format for external spreadsheet analysis.

### 7.5 Administrative Controls & Audit Logging
- **User Management:** Administrators navigate to **Users** to register new staff accounts, reset credentials, or soft-deactivate operators.
- **Audit Activity Trail:** Administrators navigate to **Activity Log** to inspect an immutable record of all database inserts, updates, and soft-deletes with timestamps and operator identifiers.
- **Controlled Lookup Settings:** Administrators navigate to **Settings** to add, edit, or toggle reference options (soil types, irrigation methods, crop categories, seasons, statuses, grades, remarks).

## 8. Screenshots or Evidence of All Major System Modules

The GehTanum user interface is engineered with responsive layouts for desktop, tablet, and mobile devices.

### 8.1 User Interface Catalog Summary

| # | Module / View | Script File / URI | Interface Layout Description | Key UI Elements & Actions |
| --- | --- | --- | --- | --- |
| 1 | Authentication Login | auth/login.php | Clean centered login card with brand logo and credentials form. | Username, password, Remember Me, submit button, error banner. |
| 2 | Executive Dashboard | index.php | High-level KPI metric cards, date range filter, and recent activities. | 5 Metric cards, active plantings alert, recent harvests table. |
| 3 | Farmers Management | farmers/index.php | Split layout: tabular directory on left, add-farmer card on right. | Search input, address filter, sortable columns, edit/delete modals. |
| 4 | Farms Management | farms/index.php | Comprehensive land directory joined with farmer owner and soil/irrigation. | Farmer dropdown, soil/irrigation lookups, area validation. |
| 5 | Crop Master Catalog | crops/index.php | Directory of agronomic crops with category, season, and cycle duration. | Category & season selectors, maturity days input, action buttons. |
| 6 | Planting Records | plantings/index.php | Transaction management interface linking farms and crops. | Farm & crop dropdowns, planting & harvest date pickers, status badge. |
| 7 | Harvest Records | harvests/index.php | Yield transaction log displaying quantity, revenue, and quality grading. | Planting picker, quantity input, unit price, grade/remark lookups. |
| 8 | Add/Edit Modal Forms | Multiple modal components | Standardized Bootstrap dialog with real-time field validation. | Clean floating labels, error feedback states, save/cancel buttons. |
| 9 | Delete Confirmation Dialog | includes/confirm_modal.php | Two-step confirmation modal preventing unintended record deletion. | Destructive action warning text, entity title, Cancel & Confirm buttons. |
| 10 | Report: Farmers & Farms | reports/farmers_farms.php | Consolidated report showing farmer ownership and total land holdings. | Search filter, summary totals, print and Export CSV actions. |
| 11 | Report: Crop Production | reports/crop_production.php | Production statistics filtered by individual farmer lands. | Farm land selector, crop yield aggregation, Export CSV button. |
| 12 | Report: Planting Status | reports/planting_status.php | Operational lifecycle monitoring report grouped by workflow status. | Status filter dropdown (Planted, Growing, Harvested), Export CSV. |
| 13 | Report: Harvest & Revenue | reports/harvest_revenue.php | Financial and production analytics with total yield and gross revenue. | Date range start/end filter, total kg, total PHP revenue, CSV export. |
| 14 | User Management (Admin) | users/index.php | Administrative console for managing system operator accounts. | Role assignment (Admin/Staff), password reset modal, deactivation. |
| 15 | Audit Activity Log (Admin) | users/activity.php | Immutable chronological audit trail recording system mutations. | User badge, action tag (create/update/delete), entity ID, timestamp. |
| 16 | Lookup Settings (Admin) | settings/index.php | Tabbed administration interface for maintaining all 7 lookup tables. | Soil, irrigation, category, season, status, grade, remark managers. |
| 17 | Mobile Adaptive Layout | Responsive Viewport (<768px) | Optimized view with collapsible sidebar and fixed bottom navigation. | App bar header, touch-friendly tables, persistent bottom navigation. |


### 8.2 Working System Screenshots

#### Figure 8.1: System Authentication Interface (auth/login.php)
*Secure login portal featuring credential validation, bcrypt password verification, and session token initialization.*  
**Key Interface Elements:** Branded header logo, username/password inputs, Remember Me toggle, sign-in button, error notification banner.

![Figure 8.1: System Authentication Interface](images/login.png)

#### Figure 8.2: Executive Operational Dashboard (index.php)
*Central command center visualizing real-time agricultural operations, key performance metrics, and operational alerts.*  
**Key Interface Elements:** 5 KPI summary cards (Farmers, Farms, Crops, Plantings, Harvests), calendar interval filter, upcoming harvest alert badge, recent activity stream.

![Figure 8.2: Executive Operational Dashboard](images/home.png)

#### Figure 8.3: Farmers Management Directory (farmers/index.php)
*Master directory of registered agricultural producers with integrated profile creation card and directory search.*  
**Key Interface Elements:** Real-time search bar, location/address filtering, sortable column headers, action buttons (edit/delete), quick registration side-card.

![Figure 8.3: Farmers Management Directory](images/Farmer.png)

#### Figure 8.4: Farm Lands Management (farms/index.php)
*Agricultural farmland records linked to registered owners, detailing area sizes, soil classifications, and water delivery systems.*  
**Key Interface Elements:** Farmer owner selector, land name, geographic location, area in hectares, soil type and irrigation badges, edit/delete actions.

![Figure 8.4: Farm Lands Management](images/Farm.png)

#### Figure 8.5: Crop Master Catalog (crops/index.php)
*Agronomic commodity database establishing standard crops, hybrid varieties, botanical categories, and seasonal growth cycles.*  
**Key Interface Elements:** Crop species name, cultivar/variety label, category dropdown, maturity cycle in days, optimal season badge, edit/delete actions.

![Figure 8.5: Crop Master Catalog](images/Crop.png)

#### Figure 8.6: Planting Cycles Module (plantings/index.php)
*Operational field transaction module recording planting schedules, land allocation, and seed stock consumption.*  
**Key Interface Elements:** Farm land and crop variety selectors, planting date picker, cultivated hectares, seed weight (kg), forecasted harvest date, lifecycle status badge.

![Figure 8.6: Planting Cycles Module](images/Planting.png)

#### Figure 8.7: Harvest Yields & Production Module (harvests/index.php)
*Production output recording interface capturing commodity weight, commercial valuation, quality grades, and condition remarks.*  
**Key Interface Elements:** Planting cycle reference, harvest date, gross quantity (kg), unit price per kg (PHP), quality grade selector, harvest remark selector, auto status transition.

![Figure 8.7: Harvest Yields & Production Module](images/Harvest.png)

#### Figure 8.8: Operational Reports Overview (reports/index.php)
*Central reporting hub providing structured access to production, agronomic, and financial reporting modules.*  
**Key Interface Elements:** Report navigation cards, summary descriptions, one-click report launching, data export indicators.

![Figure 8.8: Operational Reports Overview](images/Report.png)

#### Figure 8.9: Report — Farmers & Land Ownership (reports/farmers_farms.php)
*Consolidated ownership report mapping registered farmers to total operational acreage and farm land distribution.*  
**Key Interface Elements:** Farmer search filter, land count aggregation, cumulative hectare totals, print report button, Export CSV action.

![Figure 8.9: Report — Farmers & Land Ownership](images/Report-ownership.png)

#### Figure 8.10: Report — Crop Production per Farm (reports/crop_production.php)
*Land-specific agronomic production report detailing commodity varieties, land usage, and yield quantities.*  
**Key Interface Elements:** Farm land dropdown filter, commodity breakdown, cultivated land area totals, gross yield volume (kg), CSV export.

![Figure 8.10: Report — Crop Production per Farm](images/Report-crop-prod.png)

#### Figure 8.11: Report — Planting Cycle Status Breakdown (reports/planting_status.php)
*Lifecycle monitoring report tracking active and completed planting batches across Planted, Growing, and Harvested phases.*  
**Key Interface Elements:** Lifecycle status filter dropdown, chronological schedule timeline, active crop count, CSV data export.

![Figure 8.11: Report — Planting Cycle Status Breakdown](images/Report-cycle-status.png)

#### Figure 8.12: Report — Harvest Yield & Revenue Analytics (reports/harvest_revenue.php)
*Comprehensive commercial analytics report detailing physical crop volume harvested and gross financial revenue realized.*  
**Key Interface Elements:** Start and end date range pickers, total kilograms harvested, gross revenue sum in PHP, quality grade distribution, Export CSV.

![Figure 8.12: Report — Harvest Yield & Revenue Analytics](images/Report-harvest-yield.png)

#### Figure 8.13: Report — Farmer Profitability Analysis (reports/farmer_profit.php)
*Financial performance breakdown evaluating commodity sales value and economic returns generated per registered producer.*  
**Key Interface Elements:** Producer breakdown, volume produced, average selling price realization, gross revenue rankings, CSV export.

![Figure 8.13: Report — Farmer Profitability Analysis](images/Report-farmer-profit.png)

#### Figure 8.14: Report — Seasonal Agro-Climatic Performance (reports/seasonal_performance.php)
*Comparative agronomic assessment measuring crop performance across Dry Season, Wet Season, and Year-Round cycles.*  
**Key Interface Elements:** Seasonal grouping, yield comparison, rainfall adaptability assessment, visual performance breakdown, CSV export.

![Figure 8.14: Report — Seasonal Agro-Climatic Performance](images/Report-seasonal-performance.png)

#### Figure 8.15: Report — Yield per Crop Variety (reports/yield_per_crop.php)
*Agronomic cultivar productivity report analyzing kilograms produced per hectare across distinct hybrid crop strains.*  
**Key Interface Elements:** Cultivar name, category classification, total harvested yield (kg), productivity per hectare metric, CSV export.

![Figure 8.15: Report — Yield per Crop Variety](images/Report-yeild-per-crop.png)

#### Figure 8.16: User Account Management Console (users/index.php)
*Administrative control interface for managing system operator accounts and configuring role authorizations.*  
**Key Interface Elements:** User directory, role badge (Administrator / Staff), account creation modal, password reset action, soft-deactivation toggle.

![Figure 8.16: User Account Management Console](images/Users.png)

#### Figure 8.17: Audit Activity Log (users/activity.php)
*Immutable chronological transaction audit trail recording every insert, update, and soft-delete operation across the system.*  
**Key Interface Elements:** Operator identity stamp, action classification badge (Create / Update / Delete), target entity name, record ID, modification details, timestamp.

![Figure 8.17: Audit Activity Log](images/Activity-log.png)

#### Figure 8.18: Controlled Lookup Settings Manager (settings/index.php)
*Administrative manager for maintaining reference tables governing controlled dropdown options without modifying source code.*  
**Key Interface Elements:** Tabbed navigation (Soil Types, Irrigation Methods, Categories, Seasons, Statuses, Grades, Remarks), inline add/edit modal, soft-delete toggle.

![Figure 8.18: Controlled Lookup Settings Manager](images/Setting.png)

#### Figure 8.19: Operator Profile & Session Management (profile/index.php)
*Personal operator account portal displaying active role privileges and supporting self-service credential updates.*  
**Key Interface Elements:** Full name, username display, assigned authorization tier, password update form, active session details.

![Figure 8.19: Operator Profile & Session Management](images/Profile.png)

## 9. Test Cases and Results Showing that the Required Functions Work

A comprehensive Quality Assurance test suite was executed across all functional requirements (FR1 through FR14), verifying that all system features operate according to specifications with zero defects.

### 9.1 Quality Assurance Test Case Matrix

| # | Module | Test Scenario / Scope | Test Input / Action Performed | Expected System Behavior | Actual Verified Outcome | Status |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Auth | Admin Authentication | Submit valid admin credentials (admin / admin123) | Redirect to executive dashboard; store session tokens. | Redirected to dashboard; session created with admin role. | PASS |
| 2 | Auth | Invalid Password Handling | Submit valid username with incorrect password | Authentication denied; error banner displayed; no session. | Access denied; 'Invalid credentials' alert shown. | PASS |
| 3 | Auth | Protected Route Guarding | Request /farmers/index.php while session unauthenticated | Redirect immediately to /auth/login.php with redirect query. | Redirected to login page; access blocked. | PASS |
| 4 | Farmers | Create New Farmer Profile | Fill all required fields: name, contact, address | Profile saved to Supabase DB; success banner displayed. | Farmer record persisted; visible in directory; audit logged. | PASS |
| 5 | Farmers | Empty Field Validation | Submit create form with missing contact number | Submission blocked; client & server validation error shown. | Form rejected; 'Contact number is required' error shown. | PASS |
| 6 | Farms | Negative Area Validation | Attempt to input area_hectares = -3.50 | Blocked by HTML5 validation and DB CHECK (area > 0). | Rejected with 'Area must be greater than zero' error. | PASS |
| 7 | Plantings | Chronological Date Check | Set expected harvest date before planting date | Submission blocked by JS validation and DB constraint. | Blocked by chk_harvest_after_planting; error banner displayed. | PASS |
| 8 | Harvests | Automatic Status Transition | Record a new harvest yield for an active planting | Harvest saved; planting status automatically flips to 'Harvested'. | Harvest inserted; linked planting status changed to ID 3 (Harvested). | PASS |
| 9 | Dropdowns | Lookup Table Foreign Keys | Select 'Clay Loam' and 'Irrigated' on Farm Form | UI shows text labels; form submits numeric IDs 1 and 1. | Database stores integer foreign keys; joined queries display text. | PASS |
| 10 | Search | Multi-Column Filter Query | Type 'Carcar' into farmers search input bar | Table dynamically filters to records matching 'Carcar'. | Only Carcar City farmers displayed; row counter updated. | PASS |
| 11 | Sorting | Column Ordering | Click 'Area (ha)' table header in farms view | Rows reorder ascending; second click reorders descending. | Table sorts correctly; sorting arrow indicator toggles. | PASS |
| 12 | Integrity | Referential Deletion Protection | Attempt to delete a farmer who has active farms | Deletion blocked or handled via foreign key rule. | Clean user warning displayed; cascade corruption prevented. | PASS |
| 13 | Safety | Delete Confirmation Modal | Click delete action button on any record row | Confirmation dialog opens; deletion requires explicit confirm. | Modal displayed; 'Cancel' aborts; 'Confirm' soft-deletes. | PASS |
| 14 | Reports | Live SQL Data Aggregation | Open 4 reports and verify against raw database queries | Summary metric numbers match manual SQL aggregation. | Live figures match PostgreSQL database queries with 100% accuracy. | PASS |
| 15 | Reports | Date Interval Filtering | Select 30-day date interval on Harvest & Revenue report | Table filters strictly to harvests within date range. | Report narrows to date range; revenue recalculated dynamically. | PASS |
| 16 | Reports | CSV File Data Export | Click 'Export CSV' button on active report view | Browser downloads RFC 4180 formatted CSV file. | Proper CSV file downloaded with exact headers and matching data. | PASS |
| 17 | RBAC | Staff Role Boundary Guard | Log in as staff and attempt to access /users/ or /settings/ | Access denied; redirected to dashboard with warning. | Redirected to dashboard; flash warning: 'Admin required'. | PASS |
| 18 | RBAC | Admin Role Privilege Access | Log in as admin and access /users/ and /settings/ | Full access granted to user accounts and lookup settings. | Admin console loaded with complete CRUD management tools. | PASS |
| 19 | Dashboard | Real-Time Metric Sync | Insert new farmer and verify dashboard counter | Dashboard summary card increments immediately by +1. | Summary counter updated dynamically reflecting database state. | PASS |
| 20 | Responsive | Mobile Navigation Bar | View application on mobile screen width (<768px) | Sidebar hidden; bottom navigation bar enabled and functional. | Layout adapts cleanly; bottom navigation provides quick tab switching. | PASS |
| 21 | Audit | Audit Trail Logging | Perform create/update/delete operations across modules | activity_log table records user ID, action, and timestamp. | Activity log accurately records all transactions chronologically. | PASS |


### 9.2 Concurrency & Transaction Testing Summary
Database transactions were rigorously verified under simulated multi-user operational conditions. Atomicity was verified via rollback testing upon invalid foreign key submissions, row-level locking was confirmed using `SELECT FOR UPDATE` during concurrent planting allocations, and table locks were verified during bulk reference data updates. All tests concluded with a 100% PASS rate.

## 10. Appendix: Deployment Architecture & Technical Specifications

### 10.1 Cloud Database Hosting (Supabase PostgreSQL)
The production database is hosted on a high-availability Supabase PostgreSQL managed instance, featuring automated point-in-time backups, TLS 1.3 encrypted connections, and connection pooling via PgBouncer.

### 10.2 Application Security & Architecture Standards
- **Password Hashing:** Passwords are encrypted using bcrypt (`PASSWORD_BCRYPT`) with an algorithmic cost factor of 10.
- **SQL Injection Prevention:** 100% of database interactions utilize PDO prepared statements with strict parameter binding.
- **Cross-Site Scripting (XSS) Mitigation:** All user-supplied output rendered in HTML templates is sanitized via `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.
- **Cross-Site Request Forgery (CSRF):** State-modifying requests utilize session-scoped CSRF verification tokens.
- **Audit Immutability:** Audit log records cannot be updated or deleted through the web application interface.

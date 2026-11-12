# Agriculture Information System — System Documentation

**Course requirement:** AP3 Midterm Project — Agriculture Information Systems
**Database:** Supabase (PostgreSQL)
**Application:** Web-based Information System (PHP)

---

## Table of Contents

1. [Project Title and Description](#1-project-title-and-description)
2. [Project Objectives](#2-project-objectives)
3. [Functional Requirements](#3-functional-requirements)
4. [Entity-Relationship Diagram (ERD)](#4-entity-relationship-diagram-erd)
5. [Database Normalization (UNF → 1NF → 2NF → 3NF)](#5-database-normalization-unf--1nf--2nf--3nf)
6. [Data Dictionary](#6-data-dictionary)
7. [Table Relationships and Constraints](#7-table-relationships-and-constraints)
8. [Screenshots of the Working System](#8-screenshots-of-the-working-system)
9. [Testing Results](#9-testing-results)
10. [User Guide / Operating Instructions](#10-user-guide--operating-instructions)

---

## 1. Project Title and Description

**Title:** Agriculture Information System (AIS)

**Description:** AIS is a web-based information system that manages the core records of a
small-to-medium agriculture operation: farmers, the farms they own, the crops grown,
the planting activities carried out on each farm, and the resulting harvests. The
system centralizes these records in a normalized relational database (Supabase
PostgreSQL), replacing manual/paper-based tracking with searchable, reportable,
role-gated digital records.

## 2. Project Objectives

- Design a properly normalized (3NF) relational database for an agriculture
  operation covering farmers, farms, crops, plantings, and harvests.
- Implement the database on Supabase PostgreSQL with correct keys, constraints,
  and relationships.
- Build a working CRUD web application over that database for all five core
  modules.
- Provide a dashboard that summarizes operational data in real time.
- Enforce authenticated, role-based access to the system.
- Validate user input at the point of entry before it reaches the database.
- Provide search, filtering, and sorting across all major modules.
- Generate operational reports (farmer/farm, crop production, planting status,
  and harvest/revenue) sourced live from the database.
- Record who changed what and when, for accountability.

## 3. Functional Requirements

| # | Requirement | Covered by |
|---|---|---|
| FR1 | Register, view, update, and soft-delete farmers | `farmers/*.php`, `models/Farmer.php` |
| FR2 | Register, view, update, and soft-delete farms, linked to a farmer | `farms/*.php`, `models/Farm.php` |
| FR3 | Maintain a crop master list (name, variety, category, season) | `crops/*.php`, `models/Crop.php` |
| FR4 | Record planting transactions linking a farm to a crop | `plantings/*.php`, `models/Planting.php` |
| FR5 | Record harvest transactions linking back to a planting | `harvests/*.php`, `models/Harvest.php` |
| FR6 | Dashboard with live summary counts and recent activity | `index.php` |
| FR7 | User login/logout with session-based authentication | `auth/login.php`, `auth/logout.php` |
| FR8 | Role-based access (Administrator vs. Staff) | `includes/auth_check.php`, role checks in `users/*.php`, `settings/*.php` |
| FR9 | Search, filter, and sort records per module | `includes/table_helpers.php`, module `index.php` files |
| FR10 | Generate operational reports with filters and CSV export | `reports/*.php`, `includes/report_helpers.php` |
| FR11 | Controlled dropdown values backed by lookup tables | `models/Lookup.php`, all create/edit forms |
| FR12 | Confirm before destructive delete actions | `includes/confirm_modal.php` |
| FR13 | Audit trail of create/update/delete actions | `includes/activity_log.php`, `activity_log` table, `users/activity.php` |

## 4. Entity-Relationship Diagram (ERD)

### 4.1 Core entity relationships

```
FARMER (1) ──────< (M) FARM (1) ──────< (M) PLANTING (1) ──────< (M) HARVEST
                        │                      │
                        │                      │
                 soil_types (M:1)        crop (M:1) ──< category_id → crop_categories
            irrigation_types (M:1)                 └──< season_id   → seasons
                                              status_id (M:1) → planting_status
                                         HARVEST.grade_id  (M:1) → quality_grades
                                         HARVEST.remark_id (M:1) → harvest_remarks
```

### 4.2 Relationship summary

| Parent | Child | Cardinality | FK column |
|---|---|---|---|
| farmer | farm | 1 : M | `farm.farmer_id` |
| farm | planting | 1 : M | `planting.farm_id` |
| crop | planting | 1 : M | `planting.crop_id` |
| planting | harvest | 1 : M | `harvest.planting_id` |
| soil_types | farm | 1 : M | `farm.soil_type_id` |
| irrigation_types | farm | 1 : M | `farm.irrigation_type_id` |
| crop_categories | crop | 1 : M | `crop.category_id` |
| seasons | crop | 1 : M | `crop.season_id` |
| planting_status | planting | 1 : M | `planting.status_id` |
| quality_grades | harvest | 1 : M | `harvest.grade_id` |
| harvest_remarks | harvest | 1 : M | `harvest.remark_id` |
| users | activity_log | 1 : M | `activity_log.user_id` |

> **📌 IMAGE: `docs/images/04-erd-diagram.png`**
> *Place here a generated ERD diagram (e.g., exported from Supabase's Database
> → Schema Visualizer, or drawn in dbdiagram.io / Lucidchart / draw.io) showing
> all 13 tables and their relationships.*

## 5. Database Normalization (UNF → 1NF → 2NF → 3NF)

### 5.1 Unnormalized Form (UNF)

Before normalization, a single flat "harvest report" record would have looked
like this — one row mixing farmer, farm, crop, planting, and harvest data
together, with repeating crop/harvest groups per farm:

| farmer_name | farmer_contact | farm_name | farm_location | soil_type | irrigation | crops_planted (repeating) | harvests (repeating) |
|---|---|---|---|---|---|---|---|
| Juan Dela Cruz | 09171234567 | Green Valley Rice Farm | Carcar City | Loam | Irrigated | Rice (RC-222): planted 2025-06-10 … | 8200 kg @ ₱24.50, Good Harvest … |

Problems: repeating groups (multiple crops/harvests crammed into one row),
non-atomic fields (a single "crops_planted" field holding a list), and farmer
data duplicated on every row for every crop/harvest they have.

### 5.2 First Normal Form (1NF)

- Split the repeating "crops_planted" and "harvests" groups into their own rows.
- Every column now holds a single atomic value (no lists inside a cell).
- Result: one wide table per transaction, e.g. a flat `planting_harvest` row with
  farmer, farm, crop, and harvest columns all repeated per transaction — atomic,
  but still redundant.

### 5.3 Second Normal Form (2NF)

- The flat table's key is effectively `(farm, crop, harvest_date)`.
- Attributes that depend only on part of that key were pulled out:
  - Farmer details depend only on the farm's owner → moved to **FARMER**.
  - Farm details (location, soil, irrigation) depend only on the farm → moved
    to **FARM**.
  - Crop details (variety, growing period) depend only on the crop → moved to
    **CROP**.
- Result: FARMER, FARM, and CROP become independent tables; PLANTING and
  HARVEST reference them by foreign key instead of repeating their data.

### 5.4 Third Normal Form (3NF)

- Removed transitive dependencies: descriptive text that depended on a *code*
  rather than directly on the record's primary key was extracted into its own
  lookup table.
  - `soil_type_name` depended on a soil type code, not directly on the farm →
    extracted to **soil_types**.
  - `irrigation_type_name` → extracted to **irrigation_types**.
  - `category_name` (crop category) → extracted to **crop_categories**.
  - `season_name` → extracted to **seasons**.
  - `status_name` (planting status) → extracted to **planting_status**.
  - `grade_name` (quality grade) → extracted to **quality_grades**.
  - `remark_description` (harvest remark) → extracted to **harvest_remarks**.
- Every non-key column in every table now depends only on that table's primary
  key — no partial or transitive dependencies remain.
- Final structure: **6 core entity tables** (farmer, farm, crop, planting,
  harvest, users) + **1 audit table** (activity_log) + **7 lookup tables**
  (soil_types, irrigation_types, crop_categories, seasons, planting_status,
  quality_grades, harvest_remarks) = **13 tables**, matching `database/schema.sql`.

### 5.5 Controlled-vocabulary example (Harvest Remarks)

| remark_id | remark_description |
|---|---|
| 1 | Good Harvest |
| 2 | Acceptable Quality |
| 3 | Low Yield |
| 4 | Poor Quality |
| 5 | Damaged Harvest |

The harvest form's dropdown displays the description; only `remark_id` (the
numeric code) is stored on the `harvest` row.

## 6. Data Dictionary

### 6.1 Lookup / reference tables

**soil_types**
| Column | Type | Constraints |
|---|---|---|
| soil_type_id | SERIAL | PRIMARY KEY |
| soil_type_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**irrigation_types**
| Column | Type | Constraints |
|---|---|---|
| irrigation_type_id | SERIAL | PRIMARY KEY |
| irrigation_type_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**crop_categories**
| Column | Type | Constraints |
|---|---|---|
| category_id | SERIAL | PRIMARY KEY |
| category_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**seasons**
| Column | Type | Constraints |
|---|---|---|
| season_id | SERIAL | PRIMARY KEY |
| season_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**planting_status**
| Column | Type | Constraints |
|---|---|---|
| status_id | SERIAL | PRIMARY KEY |
| status_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**quality_grades**
| Column | Type | Constraints |
|---|---|---|
| grade_id | SERIAL | PRIMARY KEY |
| grade_name | VARCHAR(50) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

**harvest_remarks**
| Column | Type | Constraints |
|---|---|---|
| remark_id | SERIAL | PRIMARY KEY |
| remark_description | VARCHAR(100) | NOT NULL |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |

### 6.2 Core entity tables

**farmer**
| Column | Type | Constraints |
|---|---|---|
| farmer_id | SERIAL | PRIMARY KEY |
| first_name | VARCHAR(50) | NOT NULL |
| last_name | VARCHAR(50) | NOT NULL |
| contact_number | VARCHAR(20) | NOT NULL |
| address | VARCHAR(150) | NOT NULL |
| date_registered | DATE | NOT NULL, DEFAULT CURRENT_DATE |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

**farm**
| Column | Type | Constraints |
|---|---|---|
| farm_id | SERIAL | PRIMARY KEY |
| farmer_id | INT | NOT NULL, FK → farmer.farmer_id |
| farm_name | VARCHAR(100) | NOT NULL |
| location | VARCHAR(150) | NOT NULL |
| area_hectares | NUMERIC(10,2) | NOT NULL, CHECK > 0 |
| soil_type_id | INT | NOT NULL, FK → soil_types.soil_type_id |
| irrigation_type_id | INT | NOT NULL, FK → irrigation_types.irrigation_type_id |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at / updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

**crop**
| Column | Type | Constraints |
|---|---|---|
| crop_id | SERIAL | PRIMARY KEY |
| crop_name | VARCHAR(100) | NOT NULL |
| variety | VARCHAR(100) | NOT NULL |
| category_id | INT | NOT NULL, FK → crop_categories.category_id |
| growing_period_days | INT | NOT NULL, CHECK > 0 |
| season_id | INT | NOT NULL, FK → seasons.season_id |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at / updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

**planting**
| Column | Type | Constraints |
|---|---|---|
| planting_id | SERIAL | PRIMARY KEY |
| farm_id | INT | NOT NULL, FK → farm.farm_id |
| crop_id | INT | NOT NULL, FK → crop.crop_id |
| planting_date | DATE | NOT NULL |
| area_planted | NUMERIC(10,2) | NOT NULL, CHECK > 0 |
| seed_quantity | NUMERIC(10,2) | NOT NULL, CHECK > 0 |
| expected_harvest_date | DATE | NOT NULL |
| status_id | INT | NOT NULL, FK → planting_status.status_id |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at / updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |
| *(table check)* | — | `CHECK (expected_harvest_date > planting_date)` |

**harvest**
| Column | Type | Constraints |
|---|---|---|
| harvest_id | SERIAL | PRIMARY KEY |
| planting_id | INT | NOT NULL, FK → planting.planting_id |
| harvest_date | DATE | NOT NULL |
| quantity_kg | NUMERIC(10,2) | NOT NULL, CHECK > 0 |
| selling_price_per_kg | NUMERIC(10,2) | NOT NULL, CHECK > 0 |
| grade_id | INT | NOT NULL, FK → quality_grades.grade_id |
| remark_id | INT | NOT NULL, FK → harvest_remarks.remark_id |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at / updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

**users**
| Column | Type | Constraints |
|---|---|---|
| user_id | SERIAL | PRIMARY KEY |
| username | VARCHAR(50) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL (bcrypt via PHP `password_hash`) |
| full_name | VARCHAR(100) | NOT NULL |
| role | VARCHAR(20) | NOT NULL, DEFAULT 'staff', CHECK IN ('admin','staff') |
| is_deleted | BOOLEAN | NOT NULL, DEFAULT FALSE |
| created_at / updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

**activity_log** (audit trail)
| Column | Type | Constraints |
|---|---|---|
| log_id | SERIAL | PRIMARY KEY |
| user_id | INT | NOT NULL, FK → users.user_id |
| action | VARCHAR(20) | NOT NULL, CHECK IN ('create','update','delete') |
| entity | VARCHAR(50) | NOT NULL (e.g. "farmer", "harvest") |
| entity_id | INT | NOT NULL |
| details | VARCHAR(200) | NOT NULL, DEFAULT '' |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() |

## 7. Table Relationships and Constraints

- **Primary keys:** every table uses a `SERIAL` surrogate primary key
  (`farmer_id`, `farm_id`, `crop_id`, `planting_id`, `harvest_id`, `user_id`,
  `log_id`, and one `_id` per lookup table).
- **Foreign keys:** all parent/child relationships listed in §4.2 are enforced
  with `REFERENCES` foreign keys at the database level.
- **NOT NULL:** every required field (names, dates, quantities, FK columns) is
  declared `NOT NULL`.
- **CHECK constraints:**
  - `farm.area_hectares > 0`
  - `crop.growing_period_days > 0`
  - `planting.area_planted > 0`, `planting.seed_quantity > 0`
  - `planting.expected_harvest_date > planting.planting_date`
  - `harvest.quantity_kg > 0`, `harvest.selling_price_per_kg > 0`
  - `users.role IN ('admin', 'staff')`
  - `activity_log.action IN ('create', 'update', 'delete')`
- **UNIQUE:** `users.username`.
- **Soft deletes:** every table carries `is_deleted` — records are never hard
  deleted, so historical FK references always resolve; "delete" in the UI sets
  this flag instead of removing the row.
- **Audit columns:** `created_at` / `updated_at` on every core table, auto-set
  by the database (`DEFAULT NOW()`) and refreshed on update by the model layer.

## 8. Screenshots of the Working System

> Replace each placeholder below with the actual screenshot, keeping the same
> filename so the links keep working. Save images under `docs/images/`.

| # | Screenshot | Image file | What it should show |
|---|---|---|---|
| 1 | Login page | `docs/images/01-login.png` | The login form (`auth/login.php`) |
| 2 | Dashboard | `docs/images/02-dashboard.png` | `index.php` with summary cards, date filter, recent activity |
| 3 | Farmers module | `docs/images/03-farmers.png` | `farmers/index.php` — list, search/filter, add-farmer side card |
| 4 | Farms module | `docs/images/04-farms.png` | `farms/index.php` — list with farmer/soil/irrigation columns |
| 5 | Crops module | `docs/images/05-crops.png` | `crops/index.php` — crop master list |
| 6 | Planting records module | `docs/images/06-plantings.png` | `plantings/index.php` — joined farm/crop/status display |
| 7 | Harvest records module | `docs/images/07-harvests.png` | `harvests/index.php` — joined planting/grade/remark display |
| 8 | Add/Edit form example | `docs/images/08-edit-form.png` | Any module's edit form showing dropdown-driven fields |
| 9 | Delete confirmation | `docs/images/09-delete-confirm.png` | The confirmation modal before a delete action |
| 10 | Report — Farmers & Farms | `docs/images/10-report-farmers-farms.png` | `reports/farmers_farms.php` |
| 11 | Report — Crop Production | `docs/images/11-report-crop-production.png` | `reports/crop_production.php` with a farm filter applied |
| 12 | Report — Planting Status | `docs/images/12-report-planting-status.png` | `reports/planting_status.php` with a status filter applied |
| 13 | Report — Harvest & Revenue | `docs/images/13-report-harvest-revenue.png` | `reports/harvest_revenue.php` with a date-range filter applied |
| 14 | Users module (admin only) | `docs/images/14-users.png` | `users/index.php` showing admin/staff roles |
| 15 | Activity log (admin only) | `docs/images/15-activity-log.png` | `users/activity.php` audit trail |
| 16 | Settings (admin only) | `docs/images/16-settings.png` | `settings/index.php` lookup-table management |
| 17 | Mobile view | `docs/images/17-mobile-view.png` | Any page at mobile width, showing the bottom navigation bar |

## 9. Testing Results

> Fill in the **Result** and **Notes** columns after running each test against
> the live system with real Supabase data.

| # | Module | Test case | Expected result | Result (Pass/Fail) | Notes |
|---|---|---|---|---|---|
| 1 | Auth | Log in with valid admin credentials | Redirected to dashboard | | |
| 2 | Auth | Log in with wrong password | Error shown, access denied | | |
| 3 | Auth | Access any module URL while logged out | Redirected to login page | | |
| 4 | Farmers | Create a farmer with all fields filled | Record saved, success message shown | | |
| 5 | Farmers | Submit the create form with a required field empty | Validation error shown, no record saved | | |
| 6 | Farms | Create a farm with area_hectares = 0 or negative | Rejected by validation | | |
| 7 | Planting | Set expected harvest date before the planting date | Rejected by validation | | |
| 8 | Harvest | Add a harvest for a planting; verify planting status flips to "Harvested" | Status updates automatically | | |
| 9 | Dropdowns | Add a crop and confirm category/season display as text, store as ID | Label shown in UI, numeric ID stored in DB | | |
| 10 | Search/Filter | Filter farmers by address, farms by irrigation type, etc. | Filtered list matches criteria | | |
| 11 | Sorting | Sort a module's table by a sortable column | Rows reorder correctly | | |
| 12 | Delete | Delete a record referenced elsewhere (e.g. a farm with plantings) | Deletion blocked or handled per FK rule | | |
| 13 | Delete | Delete confirmation dialog appears before any delete | Dialog shown, cancel aborts the action | | |
| 14 | Reports | Open each of the 4 report pages and confirm data matches the DB | Figures match manual DB query | | |
| 15 | Reports | Apply a report filter (date range / farm / status) | Result set narrows correctly | | |
| 16 | Reports | Export a report to CSV | File downloads with matching rows | | |
| 17 | Roles | Log in as Staff and attempt to open Users or Settings | Access denied / redirected | | |
| 18 | Roles | Log in as Admin and open Users and Settings | Access granted | | |
| 19 | Dashboard | Confirm all summary cards match live counts in the database | Numbers match | | |
| 20 | Responsiveness | Load the app on a mobile-width viewport | Bottom nav appears, layout remains usable | | |
| 21 | Audit | Perform a create/update/delete and check `users/activity.php` | New log entry recorded with correct user/action | | |

## 10. User Guide / Operating Instructions

### 10.1 Logging in

1. Navigate to the application URL; you will land on `auth/login.php`.
2. Enter your username and password, then submit.
3. On success you are redirected to the dashboard (`index.php`).

### 10.2 Dashboard

- The dashboard is the landing page after login.
- Summary cards show live totals (farmers, farms, crops, plantings, harvests)
  and revenue for the selected date range.
- Use the date range control (desktop: inline form; mobile: tap the date chip
  to open the date sheet) to change the reporting period.
- Use the quick-link cards to jump to Active Plantings or Harvest Due Soon.

### 10.3 Managing records (Farmers / Farms / Crops / Plantings / Harvests)

Each module follows the same pattern:

1. Open the module from the sidebar (desktop) or bottom navigation (mobile).
2. **Add** a record using the side card form; all dropdown fields (soil type,
   irrigation type, category, season, status, grade, remark) must be selected
   from the list — free-text is not accepted for these fields.
3. **Search/filter** the list using the controls above the table.
4. **Sort** by clicking a sortable column header.
5. **Edit** a record via its row action; the form pre-fills with existing
   values.
6. **Delete** a record via its row action; confirm in the dialog that appears.
   Deleting a record that is still referenced by another table (e.g. a farm
   with existing plantings) is handled according to the defined foreign-key
   rule.

### 10.4 Reports

1. Open **Reports** from the navigation.
2. Choose a report: Farmers & Farms, Crop Production, Planting Status, or
   Harvest & Revenue.
3. Apply the available filter for that report (farm, status, or date range).
4. Use the **Export CSV** action to download the currently filtered result set.

### 10.5 User & Settings management (Administrator only)

- **Users** (`users/index.php`): create/edit/deactivate user accounts and
  assign the Administrator or Staff role. Only accessible to Administrators.
- **Settings** (`settings/index.php`): maintain the lookup/reference values
  (soil types, irrigation types, crop categories, seasons, planting statuses,
  quality grades, harvest remarks) that populate every dropdown in the system.
- **Activity Log** (`users/activity.php`): review the audit trail of who
  created, updated, or deleted which record and when.

### 10.6 Logging out

Use the account menu in the header/sidebar and select **Logout**
(`auth/logout.php`) to end the session.

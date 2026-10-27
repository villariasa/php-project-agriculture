# <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/map.svg" width="32" height="32" align="center" /> AIS Development Phase Plan
### Agriculture Information System — Module-by-Module Build Roadmap (White Theme / Clean Design)

> **How to use this file:** Build the system strictly in phase order. Each phase produces working, testable pages before moving on. Check off tasks as they are completed. All code must follow [`CODING_STANDARDS.md`](CODING_STANDARDS.md) — native PHP + PDO + Tailwind CDN only.

---

## <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/paint-brush.svg" width="22" height="22" align="center" /> Global Design System — "Clean White" Theme

Every page in the system uses the same visual language. **No dark mode, no gradients, no heavy shadows.**

### Color Palette

| Role | Tailwind Class | Usage |
| :--- | :--- | :--- |
| Page background | `bg-gray-50` | Body background on every page |
| Surface / Cards | `bg-white` | Cards, tables, forms, navbar |
| Borders | `border-gray-200` | Card edges, table dividers, inputs |
| Primary text | `text-gray-800` | Headings and body copy |
| Secondary text | `text-gray-500` | Labels, hints, table headers |
| Primary accent | `green-600` / `green-700` | Buttons, active nav, links (agriculture identity) |
| Info accent | `blue-600` | Edit actions, informational badges |
| Danger accent | `red-600` | Delete actions, error alerts |
| Success feedback | `green-50` bg + `green-700` text | Success alert banners |
| Error feedback | `red-50` bg + `red-700` text | Error alert banners |

### Component Standards

- **Page shell:** `<body class="bg-gray-50 text-gray-800 min-h-screen">` with content inside `<main class="max-w-7xl mx-auto px-4 py-6">`.
- **Navbar:** White (`bg-white border-b border-gray-200`), left logo + system name, module links, right user name + role badge + logout. Active link: `text-green-700 font-semibold border-b-2 border-green-600`.
- **Cards:** `bg-white rounded-lg border border-gray-200 shadow-sm p-6`.
- **Primary button:** `bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg inline-flex items-center gap-2` (with inline Heroicon SVG).
- **Secondary button:** `bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg`.
- **Tables:** `w-full text-sm` inside a card; header `bg-gray-50 text-gray-500 uppercase text-xs tracking-wide`; rows `border-b border-gray-100 hover:bg-gray-50`.
- **Inputs / Selects:** `w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none`.
- **Status badges:** pill style `px-2 py-1 rounded-full text-xs font-medium` —
  `Planted` = `bg-blue-50 text-blue-700`, `Growing` = `bg-yellow-50 text-yellow-700`, `Harvested` = `bg-green-50 text-green-700`.
- **Icons:** Inline Heroicons SVG only (`w-5 h-5`), never emoji in the UI.
- **Forms:** Two-column grid on desktop (`grid grid-cols-1 md:grid-cols-2 gap-4`), single column on mobile.

---

## <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/queue-list.svg" width="22" height="22" align="center" /> Phase Overview

| Phase | Module | Depends On | Deliverable |
| :---: | :--- | :--- | :--- |
| 0 | Foundation & Database | — | Schema live on Supabase, PDO connects |
| 1 | Layout & Includes | 0 | Reusable header/footer/navbar shell |
| 2 | Authentication | 1 | Login/logout + session protection + roles |
| 3 | Farmers Module | 2 | Full CRUD for farmers |
| 4 | Farms Module | 3 | Full CRUD for farms (FK → farmer) |
| 5 | Crops Module | 2 | Full CRUD for crop catalog |
| 6 | Plantings Module | 4, 5 | CRUD with JOINs + date validation |
| 7 | Harvests Module | 6 | CRUD with lookup dropdown + revenue calc |
| 8 | Dashboard | 3–7 | Summary cards + recent activity |
| 9 | Reports Module | 3–7 | Four filterable reports |
| 10 | Polish & QA | All | Validation, access control audit, final testing |

---

## Phase 0 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/circle-stack.svg" width="18" height="18" align="center" /> Foundation & Database Setup

**Goal:** Working database connection and complete schema before any UI exists.

- [ ] Create Supabase project and obtain PostgreSQL credentials.
- [ ] Write the full SQL schema (run in Supabase SQL Editor):
  - [ ] `farmer` (farmer_id PK, first_name, last_name, contact_number, address, date_registered)
  - [ ] `farm` (farm_id PK, farmer_id FK, farm_name, location, area_hectares, soil_type, irrigation_type)
  - [ ] `crop` (crop_id PK, crop_name, variety, crop_category, growing_period_days, recommended_season)
  - [ ] `planting` (planting_id PK, farm_id FK, crop_id FK, planting_date, area_planted, seed_quantity, expected_harvest_date, status)
  - [ ] `harvest_remarks` (remark_id PK, remark_description) + seed the 5 standard remarks
  - [ ] `harvest` (harvest_id PK, planting_id FK, harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remark_id FK)
  - [ ] `users` (user_id PK, username UNIQUE, password_hash, full_name, role, created_at)
- [ ] Add `ON DELETE` rules on all FKs (restrict to protect referential integrity).
- [ ] Create `config/Database.php` exactly per the teacher's PDO standard.
- [ ] Write a throwaway `test_connection.php`, verify `SELECT 1` works, then delete it.
- [ ] Insert 3–5 sample rows per table for development.

**Exit criteria:** `new Database()->connect()` returns a live PDO; all 7 tables visible in Supabase.

---

## Phase 1 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/rectangle-group.svg" width="18" height="18" align="center" /> Layout & Shared Includes

**Goal:** The white-theme page shell every module will reuse.

- [ ] `includes/header.php`:
  - [ ] HTML head with Tailwind CDN `<script src="https://cdn.tailwindcss.com"></script>`.
    > Note: the Tailwind Play CDN serves a dynamically generated script, so a fixed `integrity` (SRI) hash cannot be used with it. This is acceptable for this classroom project per the teacher's standard; a production app would use a compiled Tailwind build instead.
  - [ ] Dynamic `<title>` via a `$page_title` variable.
  - [ ] White navbar with Heroicon logo + "AIS" name, links to all modules, active-link highlighting via current script name.
  - [ ] User greeting + role badge + logout button (rendered only when session exists).
- [ ] `includes/footer.php` — closing tags + small gray footer line.
- [ ] `includes/auth_check.php` — redirect to `auth/login.php` when `$_SESSION['user_id']` is not set.
- [ ] Reusable alert snippet pattern (success = green-50 banner, error = red-50 banner) driven by `$_GET['msg']` / `$_GET['error']`.

**Exit criteria:** A blank test page including header + footer renders the clean white shell on desktop and mobile.

---

## Phase 2 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/lock-closed.svg" width="18" height="18" align="center" /> Authentication & Roles

**Goal:** Nobody reaches any module page without logging in.

- [ ] `models/User.php` — `findByUsername()`, `create()` (with `password_hash`), `getAll()`.
- [ ] `auth/login.php`:
  - [ ] Centered white card on `bg-gray-50`, system logo, username + password inputs.
  - [ ] `password_verify()` check; on success set `$_SESSION['user_id']`, `$_SESSION['full_name']`, `$_SESSION['role']`.
  - [ ] Red-50 error banner on wrong credentials. Redirect to dashboard on success.
- [ ] `auth/logout.php` — destroy session, redirect to login.
- [ ] Seed one `admin` and one `staff` user (hashed passwords).
- [ ] Add `require_once auth_check.php` to every protected page from here on.

**Exit criteria:** Login/logout cycle works; visiting any module URL while logged out redirects to login; role stored in session.

---

## Phase 3 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/users.svg" width="18" height="18" align="center" /> Farmers Module (CRUD Template)

**Goal:** First full CRUD module — this becomes the copy-paste pattern for all others.

- [ ] `models/Farmer.php` — `getAll()`, `getById()`, `create()` (RETURNING id), `update()`, `delete()`, plus `search(string $term)`.
- [ ] `farmers/index.php` — list page:
  - [ ] Page heading + "Add Farmer" primary button (plus-icon).
  - [ ] Search bar (filter by name/address via GET).
  - [ ] White card table: ID, Full Name, Contact, Address, Date Registered, Actions.
  - [ ] Edit (blue, pencil icon) for all; Delete (red, trash icon) **admin only** with `confirm()` dialog.
- [ ] `farmers/create.php` — two-column white card form, `(string) trim()` casting, redirect with success message.
- [ ] `farmers/edit.php` — pre-filled form via `getById((int) $_GET['id'])`.
- [ ] `farmers/delete.php` — admin check + delete + redirect; friendly error if farmer still owns farms (FK restrict).

**Exit criteria:** Full create → list → search → edit → delete cycle works; staff cannot see/use delete.

---

## Phase 4 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/home-modern.svg" width="18" height="18" align="center" /> Farms Module (First Foreign Key)

**Goal:** Reuse the Phase 3 pattern, now with an FK dropdown.

- [ ] `models/Farm.php` — standard 5 methods; `getAll()` JOINs farmer to show owner name (`fm.first_name || ' ' || fm.last_name AS owner_name`).
- [ ] `farms/index.php` — table: Farm Name, Owner, Location, Area (ha), Soil Type, Irrigation, Actions.
- [ ] `farms/create.php` / `edit.php`:
  - [ ] Owner `<select>` populated from `Farmer->getAll()` (value = `farmer_id`, label = full name).
  - [ ] `(float)` casting for `area_hectares`; soil/irrigation as controlled `<select>` options.
- [ ] `farms/delete.php` — admin only; friendly error when plantings reference the farm.

**Exit criteria:** Farms list shows real owner names (no raw IDs); FK dropdown works on create/edit.

---

## Phase 5 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/tag.svg" width="18" height="18" align="center" /> Crops Catalog Module

**Goal:** Standalone catalog with controlled category/season dropdowns.

- [ ] `models/Crop.php` — standard 5 methods.
- [ ] `crops/index.php` — table: Crop Name, Variety, Category (gray pill badge), Growing Period (days), Season, Actions.
- [ ] `crops/create.php` / `edit.php`:
  - [ ] Category `<select>`: Cereal, Legume, Root Crop, Fruit, Vegetable.
  - [ ] Season `<select>`: Dry Season, Wet Season, Year Round.
  - [ ] `(int)` casting for `growing_period_days`.
- [ ] `crops/delete.php` — admin only.

**Exit criteria:** Crop catalog CRUD complete with dropdown-controlled category and season.

---

## Phase 6 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/calendar-days.svg" width="18" height="18" align="center" /> Plantings Module (Multi-Table JOINs)

**Goal:** The transactional core — JOINs across farm, farmer, and crop.

- [ ] `models/Planting.php`:
  - [ ] `getAll()` using the teacher's 3-table JOIN standard (planting → farm → farmer, planting → crop), newest first.
  - [ ] Standard `getById`, `create`, `update`, `delete`.
- [ ] `plantings/index.php` — table: Farm, Crop (name + variety), Farmer, Planting Date, Area (ha), Status badge, Actions.
  - [ ] Status filter dropdown (All / Planted / Growing / Harvested) via GET.
- [ ] `plantings/create.php` / `edit.php`:
  - [ ] Farm and Crop `<select>` dropdowns from their models.
  - [ ] Date inputs (`type="date"`); **server-side rule: expected_harvest_date > planting_date**, red banner on violation.
  - [ ] `(float)` for area_planted and seed_quantity; Status `<select>` (Planted / Growing / Harvested).
- [ ] `plantings/delete.php` — admin only; friendly error when harvests reference the planting.

**Exit criteria:** List shows human-readable farm/crop/farmer names; invalid date ranges are rejected server-side; status badges render in theme colors.

---

## Phase 7 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/archive-box.svg" width="18" height="18" align="center" /> Harvests Module (Lookup Table Requirement)

**Goal:** Fulfills the official `ap3-project.docx` controlled-dropdown requirement.

- [ ] `models/Lookup.php` — `getRemarks()` returning all rows from `harvest_remarks`.
- [ ] `models/Harvest.php`:
  - [ ] `getAll()` JOINing planting → farm → crop and `harvest_remarks` (show `remark_description`).
  - [ ] Standard 5 methods; `(float)` quantity_kg and selling_price_per_kg.
- [ ] `harvests/index.php` — table: Farm, Crop, Harvest Date, Quantity (kg), Price/kg, **Total Value** (computed `quantity × price`, formatted `number_format(..., 2)`), Grade badge, Remark, Actions.
- [ ] `harvests/create.php` / `edit.php`:
  - [ ] Planting `<select>` showing "Farm — Crop (Planting Date)".
  - [ ] Quality Grade `<select>`: Grade A / Grade B / Grade C.
  - [ ] **Remark `<select>` from `harvest_remarks` lookup** (value = `remark_id`, label = description) — per teacher spec.
- [ ] On harvest creation, optionally set the related planting status to `Harvested`.
- [ ] `harvests/delete.php` — admin only.

**Exit criteria:** Remarks dropdown is DB-driven (not hardcoded); total value computes correctly; list is fully human-readable.

---

## Phase 8 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/chart-bar.svg" width="18" height="18" align="center" /> Dashboard (index.php)

**Goal:** The landing page after login — clean white summary view.

- [ ] Count queries (one per entity) for the 5 summary stats.
- [ ] Summary card row (`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4`):
  - [ ] Each card: white, bordered, Heroicon in a `bg-green-50 text-green-600 rounded-lg p-2` square, big number, gray label.
  - [ ] Cards: Total Farmers, Active Farms, Cataloged Crops, Active Plantings, Total Harvests.
- [ ] "Recent Plantings" card — last 5 via JOIN (farm, crop, date, status badge).
- [ ] "Recent Harvests" card — last 5 via JOIN (crop, date, quantity, total value).
- [ ] Each card header links to its module ("View all →").

**Exit criteria:** Dashboard loads with live counts and recent activity; layout stacks cleanly on mobile.

---

## Phase 9 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/document-chart-bar.svg" width="18" height="18" align="center" /> Reports Module

**Goal:** Four filterable, printable reports.

- [ ] `reports/index.php` — hub page with 4 white report cards (icon + title + description + "Open" button).
- [ ] `reports/farmers_farms.php` — Farmers & Farm Ownership (farmer, farm count, total hectares).
- [ ] `reports/crop_production.php` — Crop Production per Farm (farm × crop totals), filter by farm.
- [ ] `reports/planting_status.php` — Planting Cycle Status, filter by status.
- [ ] `reports/harvest_revenue.php` — Harvest Yield & Revenue, **date-range filter** (`date_from` / `date_to` via GET), footer row with SUM of quantity and revenue.
- [ ] Shared pattern: filter form card on top, results table below, "Print" button (`window.print()`), print-friendly (navbar hidden via `print:hidden`).

**Exit criteria:** All four reports return correct filtered data; harvest report totals match manual SUM; pages print cleanly.

---

## Phase 10 — <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/check-badge.svg" width="18" height="18" align="center" /> Polish & Final QA

**Goal:** Defense-ready quality pass across the whole system.

- [ ] **Security audit:**
  - [ ] Every page behind `auth_check.php` (except login).
  - [ ] Every delete endpoint re-checks `$_SESSION['role'] === 'admin'` server-side (not just hidden links).
  - [ ] All SQL uses prepared statements; all output uses `htmlspecialchars()`.
  - [ ] No credentials committed — confirm `.gitignore` covers `config/Database.php` (or use a committed template file).
- [ ] **Validation sweep:** required fields, correct `(int)`/`(float)`/`trim()` casting on every form handler.
- [ ] **UX consistency sweep:** same card/button/table/badge classes on every page; active nav highlighting correct everywhere.
- [ ] **Data integrity tests:** try deleting a farmer with farms, a farm with plantings, a planting with harvests — each should show a friendly red banner, never a raw PDO error.
- [ ] **Responsive check:** every table scrolls horizontally on mobile (`overflow-x-auto` wrapper).
- [ ] Final walkthrough as `staff` (no deletes visible) and as `admin` (full access).
- [ ] Update README screenshots / finalize docs for submission.

**Exit criteria:** Full demo path — login → add farmer → farm → crop → planting → harvest → view dashboard → run all reports — completes without a single error page.

---

## <img src="https://raw.githubusercontent.com/tailwindlabs/heroicons/master/optimized/24/outline/light-bulb.svg" width="22" height="22" align="center" /> Working Rules Reminder

1. **One phase at a time** — never start a module before its dependencies pass exit criteria.
2. **Farmers module is the template** — Phases 4–7 copy its file structure and adapt.
3. **Stay native** — no Composer, no frameworks, no over-engineering (see CODING_STANDARDS.md §9).
4. **White theme always** — `bg-gray-50` page, white cards, green-600 accents, Heroicons.

# Project Status Tracker — Agriculture Information System (AIS)

> **Purpose:** Session continuity log. Read this at the start of every session to know exactly where the project stands. Update it at the end of every working session.

**Last updated:** 2026-10-28

---

## Current State: ALL PHASES CODE-COMPLETE (0–11) — awaiting user testing

### Completed
- [x] **Phase 0–10** — Full system built: schema, layout includes, auth (admin/staff roles), Farmers / Farms / Crops / Plantings / Harvests CRUD modules, dashboard, 4 reports, QA sweep.
- [x] **Delete confirmation modal** — `includes/confirm_modal.php` replaces native `confirm()` on all 5 modules + settings (spec req. 11 allows "dialog or message").
- [x] **Phase 11 — 13-table migration** (teacher requirement):
  - 6 core entities + 7 lookup tables (`soil_types`, `irrigation_types`, `crop_categories`, `seasons`, `planting_status`, `quality_grades`, `harvest_remarks`).
  - All dropdowns DB-driven; zero hardcoded `<select>` options.
  - **Soft delete system-wide** (teacher requirement): `is_deleted` flag on all 13 tables, zero `DELETE FROM` statements; deletion blocked while active child records exist.
  - `created_at` / `updated_at` audit columns on core entities.
  - **Settings module** (`settings/`, admin-only): CRUD for all 7 lookup tables; in-use values cannot be deleted.
- [x] README.md rewritten for the 13-table build (ERD, structure, setup, seeded accounts).
- [x] UI_STYLE_GUIDE.md created (avoid AI-generated look; white theme spec).
- [x] **Facebook-inspired layout redesign (2026-10-28)** — layout-only restyle, zero logic/route/DB changes:
  - Sticky full-bleed top header with centered underline-tab navigation; PNG logo (`assets/image-cutout.png`) in nav + login.
  - Workspace widened to `max-w-screen-2xl`; shared feed + right-rail pattern (`flex flex-col xl:flex-row`, `xl:w-80` aside) on all list pages, filterable reports, and settings. Rails hold relocated existing content only (search/filter forms, Add buttons) and are `print:hidden`.
  - Form pages widened to `max-w-3xl` with a "Back to list" link.
  - **Dashboard upgrade:** compact 6-tile KPI strip (new: Harvested kg total), two Chart.js v4 charts (CDN + SRI hash) — Harvest Volume by month (12 mo) and Plantings by Status — fed by two new read-only soft-delete-filtered queries via `json_encode()`; recent-activity panels moved to the right rail; empty-data states included. This covers the pending spec items "plantings by status breakdown" and "total harvested quantity".
  - `tailwind.config` inline block (system font stack) added in header.php and login.php.

### Environment Facts
- **No PHP binary on the dev machine** — the user runs the server and all tests themselves (`php -S localhost:8000`). Claude writes code + does static verification (grep sweeps) only.
- `config/Database.php` is **gitignored** (real credentials, local defaults + env-var override); `config/Database.example.php` is the committed template. User modified Database.php for local PostgreSQL (`agriculture_system_db`).
- Users seeded with pre-hashed bcrypt: `admin`/`admin123`, `staff`/`staff123`.
- Schema is re-runnable: `database/schema.sql` drops and recreates all 13 tables + seed data.

### Pending / Next Steps
- [ ] **User to smoke-test the redesigned UI** (`php -S localhost:8000`): login → dashboard (charts render? empty states?) → each module list (rail search/filter/Add work) → create/edit forms → delete modal → reports incl. Print preview → settings tabs. Check desktop / ~768px / ~375px widths.
- [ ] **User to re-run `database/schema.sql`** (v2, 13 tables) and smoke-test the full demo path: login → farmer → farm → crop → planting → harvest → dashboard → reports → settings.
- [ ] Fix any first-run errors reported from testing (code was never executed — static checks only).
- [ ] Spec items not yet implemented:
  - [ ] **Pagination** for large record lists (spec: Data Tables and Pagination).
  - [ ] **Sorting** on table columns (spec: Search, Filter, and Sorting — search/filter done, sorting not).
  - [ ] Additional filters: farms by irrigation type, crops by category, harvests by quality grade (spec examples; plantings-by-status done).
  - [x] Dashboard "number of plantings by status" breakdown + total harvested quantity card (done in the 2026-10-28 redesign: status chart + Harvested-kg KPI tile).
- [ ] Documentation deliverables for submission: screenshots, data dictionary, UNF→1NF→2NF→3NF writeup, test results, user guide (see spec in `ap3-project.docx`).

### Key Conventions (do not break)
- Teacher's standards in `CODING_STANDARDS.md`: native PHP + PDO only, no Composer/frameworks, `htmlspecialchars()` on all output, `(int)`/`(float)`/`trim()` casting, prepared statements, Tailwind CDN, Heroicons.
- Soft delete only — never write a `DELETE FROM`.
- Lookup values managed ONLY through the Settings module; never hardcode dropdown options.
- UI must follow `UI_STYLE_GUIDE.md` (white theme, no AI-template look).

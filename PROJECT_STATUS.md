# Project Status Tracker — Agriculture Information System (AIS)

> **Purpose:** Session continuity log. Read this at the start of every session to know exactly where the project stands. Update it at the end of every working session.

**Last updated:** 2026-11-04

---

## Current State: ALL PHASES CODE-COMPLETE (0–16) — Phases 12–16 implemented 2026-11-04, awaiting user testing

### ▶ RESUME HERE — prompt for the next session
> **"Phases 12–16 are code-complete but never executed (static checks only). Fix the bugs I report from smoke-testing. The full test checklist is under 'Pending / Next Steps'. Remember: `database/schema.sql` must be re-run first — it now has 14 tables (new `activity_log`)."**

### Reports mobile polish (2026-11-04, latest)
- **Analytics overview:** date-range form wraps as full-width pill inputs + pill Apply/Print on phones; **Revenue KPI is a hero tile spanning both columns**; all KPI/summary numbers `text-lg sm:text-xl`; chart/detail cards `p-4 sm:p-6`; trend chart `h-56 sm:h-72`.
- **All 7 detail reports (via report_helpers):** summary-strip values downsized one notch on phones; **Export CSV + Print buttons split the full row 50/50 on mobile** (`flex-1 sm:flex-initial justify-center`), shrink to content on sm+. Scroll-viewport tables with sticky headers already handle narrow screens.

### Dashboard mobile layout polish (2026-11-04)
- Date-range filter wraps as full-width **pill inputs** on phones (matches toolbar style); Apply is a pill button.
- KPI strip on mobile: **Revenue is a hero tile spanning both columns** (peso amount never clips), remaining 5 tiles in the 2-col grid with `text-xl` numbers (`sm:text-2xl` upward).
- Density: all dashboard cards `p-4 sm:p-6`; chart heights stepped down one notch on phones (`h-56 sm:h-64`, crops `h-52 sm:h-56`).

### Rainbow background app-wide (2026-11-04) — user request
- The login's pastel radial "rainbow" wash now backs ALL app pages via `includes/header.php`: same four glows at slightly lower alpha (.14–.22) over `#fafafa`, `background-attachment: fixed` so it stays steady while scrolling; `bg-gray-50` removed from the body tag; `@media print` forces a plain white background so reports print clean. White sidebar/topbar/cards sit on top unchanged.

### Login restyle per sample.webp reference (2026-11-04, latest)
- Background: near-white `#fafafa` with four soft pastel radial glows (violet/green/pink/amber, ≤.26 alpha) — the reference's subtle "rainbow" wash.
- Card per reference: `rounded-2xl`, big soft shadow (`0 8px 30px rgb(0,0,0,.08)`), split into a **white header** (centered logo, "Sign In", subtitle, bottom hairline) and a **gray-50 form body** with small UPPERCASE labels and white shadowed inputs. Username + password only (per user). All life features kept (fade-up stagger, logo drop, shake, eye toggle, Caps Lock, spinner, NProgress).

### Context-aware empty states (2026-11-04) — user bug: "Add the first farmer" shown on empty SEARCH results
- All 5 modules now set `$is_filtered` (search term or active filter) and branch both empty states (desktop table + mobile cards): filtered-but-empty → "No X match your search or filter." + **Clear filters** link (plain `index.php` href, resets everything); truly empty table → "No X yet." + "Add the first X" button.

### Custom dropdowns + combobox, login reverted to original layout (2026-11-04)
- **Custom dropdown component** (vanilla JS in `includes/footer.php`, syntax-checked): every `<select>` app-wide is replaced by a styled trigger (inherits the select's classes — pills stay pills) + white rounded options panel with hover rows, green check on the selected item, rotating chevron. The native select stays hidden in the form → values submit normally, inline `onchange` fires via dispatched change events, no-JS falls back to native. Esc/click-outside close; Arrow keys change value on the trigger.
- **Search combobox (user rule):** selects with **>10 options** automatically get a search box pinned at the top of the panel (type-to-filter, Enter picks the first match, "No matches found." empty state). Both variants show **~8 rows (`max-h-72`), the rest scroll**.
- Edit sliders re-sync trigger labels after JS prefill via `window.syncCustomSelects()` (wired in sidecard.php + users page).
- Hardened per user report ("selects on the data tables too"): enhancer now runs on every select at load AND via a MutationObserver for anything added later (already-enhanced skipped via `data-enhanced`); `window.enhanceSelects()` exposed. Verified every page containing a `<select>` includes footer.php. If toolbar selects still look native in testing → hard refresh (Ctrl+Shift+R), old cached JS.
- **Login reverted** to the ORIGINAL centered-card layout at the user's demand ("the previous layout is what I want, just add life") — v2 split-screen and v3 scene are gone. Same markup as the original + life only: staggered fade-up, logo drop-bounce, error shake, eye toggle, Caps Lock hint, submit spinner, NProgress kept.

### NProgress top loading bar (2026-11-04) — user-supplied snippet applied app-wide
- NProgress 0.2.0 (cdnjs, **SRI sha384 hashes computed from the actual files** + crossorigin) added to `includes/header.php` and `auth/login.php`; bar restyled green `#22a447`, 3px, spinner disabled.
- Triggers: `beforeunload` → every full navigation (nav links, create/delete posts, logout, login); plus explicit start/done around `loadRecords()` (sort/filter/pagination/panel refresh) and the edit-slider fetch save in sidecard.php. All calls guarded with `if (window.NProgress)` so a blocked CDN degrades silently.

### Uniform table row limiting (2026-11-04) — every table bounded
- **Modules + Activity:** already paginated at PER_PAGE=10 (unchanged).
- **Users + Settings:** now paginated too — `array_slice` over the full list + the same `paginationBar()` (no model changes; settings keeps `?type=` in page links via `buildQuery`); header count uses the true total.
- **All 7 detail reports:** table card is now a `max-h-[70vh]` scroll viewport with a **sticky header** — visible rows limited, rest scroll; `print:max-h-none print:overflow-visible` so printing/CSV still covers every row and tfoot totals stay correct.
- Dashboard/analytics tables were already capped at top-5.

### Login v3 (2026-11-04) — user not satisfied with v2; now immersive
- Full-screen deep-green scene (`bg-green-950`): three blurred drifting blobs + faint field-line SVG at the bottom; **pointer parallax** — layers follow the mouse via CSS vars (`--px/--py`), disabled on reduced-motion.
- Centered white card (`rounded-3xl shadow-2xl`) pops in; **logo badge drops in with overshoot bounce**, half-overlapping the card top; **time-based greeting** (Good morning/afternoon/evening, server time).
- **Floating labels**: placeholders rise into small green uppercase labels on focus/filled (pure CSS, `:placeholder-shown`).
- Carried over from v2: error shake, red banner, eye toggle, Caps Lock hint, spinner + "Signing in...", green focus icons, autocomplete attrs. Server logic unchanged.
- Note: deliberately more decorative than UI_STYLE_GUIDE baseline at the user's explicit insistence (blur blobs ≈ soft glow); the inner app stays strictly clean-white.

### Interactivity pass (2026-11-04) — user: "it's not interactive"
- **Sliders actually slide** now: side cards animate in from the bottom (mobile) / right (desktop) with cubic-bezier easing; overlays fade in; the delete confirmation pops in (scale .95→1); toasts drop down from the top.
- **Lists respond to actions:** during a sort/filter/pagination/save fetch the records panel dims (`.loading`), then settles in with a soft rise (`.swapped`, animation restarted via reflow). Table rows ease into hover tint; sidebar links nudge right on hover; clickable white tiles (KPIs, report cards, browse shortcuts) lift with a soft shadow on hover.
- All new animations disabled under `prefers-reduced-motion`.

### Component upgrade pass (2026-11-04) — no default browser controls
- Global component stylesheet in `includes/header.php` `<style>` upgrades EVERY control app-wide with zero markup churn: selects lose the native arrow (custom gray chevron that turns green on focus, pointer cursor), inputs/selects get smooth focus/hover transitions + hover border, number fields lose default spinners, date-picker icons softened, all buttons get press-down feedback (`scale(.98)`), side cards/sheets get a slide transition, slim rounded custom scrollbars (WebKit), green text selection. `prefers-reduced-motion` disables transitions.

### Login UI v2 (2026-11-04) — "give it life": animated split-screen sign-in
- `auth/login.php` view fully rebuilt (server logic untouched): **split-screen** — green-800 brand panel (logo, "Track every harvest, from seed to sale", 3 real feature rows with icons) + white form panel; stacks on mobile with compact brand header.
- **Motion (CSS keyframes, `prefers-reduced-motion` respected):** staggered fade-up entrance (brand → card → fields → button); error banner triggers a card **shake**; button has hover lift / active press; submit shows a **spinner + "Signing in..."** and disables the button.
- **Micro-interactions:** leading user/lock icons turn green on focus; **show/hide password** eye toggle; **Caps Lock warning** under the password field; autocomplete attributes set.
- Smoke test: load animation plays once; wrong password → shake + red banner; eye toggle; Caps Lock hint; submit spinner; mobile stacking; reduced-motion OS setting disables animations.

### No-reload everything + top-center toasts (2026-11-04) — user complaints: update/sort/filter reloads
- **Edit saves without reload:** new `includes/form_response.php` → `respondAndExit()`; all 7 `edit.php` handlers return JSON when called with the `X-Requested-With: fetch` header (classic redirect otherwise). The edit slider submits via `fetch()`: success → slider closes, success toast, list panel refreshed in the background; validation error → error toast, slider stays open with values. `.catch` falls back to the classic form post.
- **Sort / pagination / filters without reload (PJAX-style):** module list containers got `id="records-panel"`, header counts `id="records-count"`, toolbars `id="filter-toolbar"`. `loadRecords()` in sidecard.php fetches the same URL and swaps the server-rendered panel + count in place, with pushState (Back/Forward re-render via popstate). Clicks on sort-header/pagination links inside the panel are intercepted; toolbar selects/clear now call `submitToolbar()` (`requestSubmit`) so the intercepted submit event fires. Users + settings pages have their own edit-save interceptors with panel refresh.
- **Toasts moved to top-center** (`top-4 left-1/2 -translate-x-1/2`) on all screens per user request.
- **Smoke test:** click column headers / page numbers / change filters / pick a sort preset — table updates with NO page flash, URL updates, Back works; edit a record → Save → slider closes + toast + row shows new values without reload; invalid edit (e.g., bad dates) → error toast, slider stays open; with JS disabled everything still works via classic redirects.

### Pill-style toolbars (2026-11-04) — per user's THIS.png reference
- All filter toolbars (5 modules, activity log, 5 detail reports, search page, topbar search) restyled to **pills**: `rounded-full` search fields and dropdowns. Module search fields gained an inline **× clear button** (visible only while a search term is active; clears and resubmits preserving filter/sort).

### Clean-minimal desktop relayout + instant edit + sticky sidebar (2026-11-04) — per user's sample.png
- **Right rails are gone everywhere.** Every filtered page now uses one **inline toolbar above a full-width table card**: modules get [search input (icon) | filter select | Sort-by select]; activity gets its two selects; detail reports get their select or date-range + Apply/Reset inline. Settings' Add button moved into the page header; values list is a clean single column.
- **Search added to all modules** (sample shows it): farms (name/location), crops (name/variety), plantings (farm/crop/farmer), harvests (farm/crop) — models' `countAll`/`getPage` gained a `$term` param (prepared ILIKE); farmers already had it. Search + filter + sort + pagination all compose.
- **Instant edit — NO page reload (user complaint fixed):** rows/cards carry a `data-edit` JSON (form-field ⇒ raw value; harvest query now also selects grade_id/remark_id). `fillAndOpenEditCard()` in sidecard.php fills the always-rendered edit slider and opens it when you hit Edit in the details card or the pencil icon. Users module has its own `openUserEdit()`. The `?edit=<id>` server-prefill remains ONLY as deep-link fallback (search results) — edit sliders render unconditionally with `?? ''` guards.
- **Sticky sidebar fix (user complaint):** desktop sidebar is `lg:sticky lg:top-0 lg:h-screen` — logout/user panel always visible without scrolling; nav scrolls internally.
- **Smoke test:** desktop — toolbar above each table, search works on all 5 modules, Edit opens instantly with values filled (no reload), sidebar logout visible on long pages; reports — inline filters still rescope + CSV/print fine; settings — Add in header, sliders work.

### Mobile inline filter bar + card avatars (2026-11-04) — per user's mobile.png sample
- On mobile (<768px) every filtered list (farmers/farms/crops/plantings/harvests/activity) now shows a **clean inline bar under the Add button**: flexible filter control (farmers: icon search input; others: filter select; activity: two selects) + a compact single **"Sort by" select** (combined presets like Newest / Name A–Z / Highest kg that set hidden `sort`+`dir` inputs via 2-line JS and submit together with the filter). `rounded-xl` inputs per the sample.
- The **right rail is now desktop-only** (`hidden md:block`); the old two-select mobile sort block inside the rail was removed (superseded).
- Mobile record cards gained a **round green icon avatar** per module (user/farm/tag/calendar/tray icons) matching the sample's card look — green/white theme kept, not the sample's purple.
- **Add to smoke test (~375px):** filter bar sits under Add button; search/filter and Sort-by preserve each other when either changes; avatar circles on all 5 modules' cards; rail no longer renders on phones; desktop unchanged.
- **Security fix:** `csvDownload()` in `includes/report_helpers.php` now runs every cell through `csvEscape()` (quote-prefixes values starting with `= + - @` tab/CR) — blocks CSV formula injection in all 7 report exports.

### Bottom sliders everywhere + Edit moved into sliders, all modules (2026-11-04)
- **Every overlay is a bottom slider on mobile (<768px), a right slide-over / centered modal on desktop:** details card, all Add forms, all Edit forms, settings Add/Edit, and the delete confirmation (now `items-end` + `rounded-t-2xl` on mobile). Shared responsive class on all `<aside>` cards (`inset-x-0 bottom-0 max-h-[85vh] rounded-t-2xl` → `md:` right panel).
- **Edit pages are gone as pages — Edit is a slider on every module index** (farmers/farms/crops/plantings/harvests/users, matching settings): Edit buttons and the details card link to `index.php?edit=<id>`; the page server-side prefills an `#edit-card` slider (inputs filled, selects `selected`) and auto-opens it on load (same mechanism as `?add=1`). All 7 `edit.php` files are now **POST-only handlers** (hidden `id` field; GET redirects to `index.php?edit=<id>`; validation failure redirects back with `?edit=<id>&error=` so the slider reopens with a toast; success logs activity + toasts).
- `includes/sidecard.php` now closes/opens `edit-card` too; `buildQuery()` also strips `edit` so sort/page links don't reopen the slider; search result links now deep-link to `module/index.php?edit=<id>` (record opens in its slider).
- **Add to smoke test:** on ~375px every panel (details / add / edit / settings / delete confirm) slides from the BOTTOM with rounded top; on desktop edit opens as a right panel prefilled; invalid edit submit bounces back with slider reopened + error toast; search result tap lands on the module with the edit slider open; Esc/overlay close everything.

### Mobile cards + live search + settings side cards (2026-11-04)
- **Tables → cards on mobile (<768px), all modules:** every list page (farmers/farms/crops/plantings/harvests/users/activity) keeps its table `hidden md:block` and gains a `md:hidden` tappable card list — title, sub-lines, status/grade/role pills, kg/₱ right-aligned, chevron. Record cards open the existing **details side card** (Edit + admin Delete) via the same `data-detail` attributes; users cards keep inline edit/deactivate icons; one pagination bar serves both views. Column headers vanish with the table, so each filter rail gains **mobile-only Sort by / Order selects** (same whitelisted keys, submit with the filters). Detail *reports* intentionally stay as scrollable tables (printable documents).
- **Live search:** `search.php` now serves a shared results fragment at `?ajax=1` (links prefixed with the app root so they work when injected anywhere). Topbar input shows a **debounced dropdown** (2+ chars, grouped results, "View all", Esc/click-away closes). The search page itself is app-like: big autofocused input, **results render live as you type** (no Search button), and an empty box shows a Browse grid of module shortcuts.
- **Settings uses side cards now (user request):** inline rail Add form → **Add side card**; separate edit page → **Edit side card** (prefilled via JS, posts type/id/name). `settings/edit.php` converted to a POST-only handler (GET redirects to index); validation failures toast. Soft-delete/in-use rules unchanged.
- **Add to smoke test (~375px):** each module shows cards not tables; tap card → details card → Edit/Delete work; mobile Sort by/Order selects re-sort and keep filters; pagination under the cards; topbar live dropdown on desktop; search page live typing + Browse grid; settings Add/Edit side cards save and toast.

### Reports hub → one-page Analytics Overview (2026-11-04) — user request: reports "all in 1 page" with KPIs/charts
- `reports/index.php` is no longer a card menu; it is a live **analytics report**: date-range filter (default last 12 months) + Print; 6-tile KPI strip (Revenue, Harvested kg, Harvests, Avg price/kg, Avg yield/ha, Active plantings); full-width **combo chart** (bars = kg left axis, line = revenue right axis); three breakdown charts (Plantings-by-status doughnut, Harvests-by-grade doughnut, Top-crops horizontal bar); two leaderboards (Top farms / Top farmers by revenue); compact "Detailed Reports" link grid to the 7 record-level pages at the bottom (those keep their filters/CSV/print).
- All numbers live queries, soft-delete filtered; charts gated behind `$has_data` with a dashed empty-state when the range has no harvests; print header included; template if/endif + foreach balance verified.
- **Add to smoke test:** reports page charts render; date range rescopes everything; doughnut colors match badge colors (Planted blue / Growing yellow / Harvested green; Grade A/B/C green/yellow/red); combo chart dual axes; Print preview; all 7 detail links still work.

### Mobile navigation rework (2026-11-04, later same session) — layout-only, zero logic changes
- Below `lg` (1024px) the hamburger **drawer is gone**; mobile now uses a fixed **bottom navigation bar** (5 items: Dashboard · Records · Search · Reports · Menu) with `env(safe-area-inset-bottom)` padding.
- **Records** opens a slide-up **bottom sheet** listing the 5 record modules (user request: record navs live in a burger at the bottom); **Menu** opens a sheet with My Profile, admin links (Settings/Users/Activity, admin-only), and Logout with the user identity at top. Overlay tap / ✕ / Esc close; active item + sheet rows highlight green.
- Desktop sidebar unchanged (`hidden lg:flex`); mobile top bar now shows logo + breadcrumbs + search. `<main>` gets `pb-24 lg:pb-6`, footer `mb-16 lg:mb-0`, toasts moved to `bottom-20 lg:bottom-4` — all so nothing hides behind the bar.
- **Add to the smoke test (~375px/768px):** bottom bar visible with correct active state; Records/Menu sheets open-close (overlay, ✕, Esc); staff sees no admin rows in Menu; side cards and delete modal still cover the bottom bar; toasts appear above it; print preview hides it.

### Phases 12–16 implemented (2026-11-04 session) — all static-verified, zero runtime testing
- **App shell v3 (Phase 16, built first):** `includes/header.php`/`footer.php` fully rewritten — fixed white left **sidebar** (grouped nav: Overview / Records / Insights / Administration, admin links admin-only) with mobile slide-in drawer + overlay; sticky **top bar** with hamburger, **breadcrumbs** (`$breadcrumbs` array per page, falls back to `$page_title`), **global quick search** box; user panel at sidebar bottom (avatar initial, profile + logout icons). New `search.php` (root): grouped prepared-ILIKE search across farmers/farms/crops. Standard page-header pattern (title + count subtitle left, primary action right) on all list pages; dashed-border empty states everywhere.
- **Data tables v2 (Phase 12):** new `includes/table_helpers.php` — `PER_PAGE=10`, `resolveSort()` (whitelist-only ORDER BY), `currentPage()/pageOffset()`, `buildQuery()` (preserves filters, strips one-shot `msg/error/add` params), `sortableTh()` (clickable headers with direction arrow), `paginationBar()` ("Showing X–Y of Z" + windowed page links). All 5 models gained `countAll($filter)` + `getPage($filter, $order, $dir, $limit, $offset)`. All 5 module index pages rebuilt: pagination + column sorting + filters compose in the query string. New filters: **farms by irrigation type, crops by category, harvests by quality grade** (spec items now closed, incl. sorting + pagination).
- **Dashboard v2 (Phase 13):** `index.php` rewritten — date-range selector (`?from/&to`, default last 12 months) scoping charts/range-KPIs; KPI strip: Revenue this month + %-vs-last-month, Harvested kg this month + %, Avg yield/ha (range), Active plantings, Harvest due ≤14 days, Total farmers; charts: harvest volume (bar), revenue (line), top-5 crops (horizontal bar); top-5 farms revenue table; right rail: Upcoming harvests (days-left pills, red ≤3d), recent plantings/harvests.
- **Reports v2 (Phase 14):** new `includes/report_helpers.php` (`csvDownload()`, `reportPrintHeader()` print-only header with filters+timestamp, `summaryStrip()`, `reportActions()` Export CSV + Print buttons). All 4 existing reports upgraded with CSV (`?export=csv`, `fputcsv` on `php://output`), summary strip, tfoot totals, print header. **3 new reports:** `farmer_profitability.php` (date-range, ranked by revenue), `seasonal_performance.php` (revenue share bars per season), `yield_efficiency.php` (kg/ha per crop, category filter). Hub lists all 7.
- **Administration (Phase 15):** schema adds **`activity_log`** table (now 14 tables; drop+create included, re-runnable). `includes/activity_log.php` → `logActivity()` wired into **all 22 write handlers** (5×create/edit/delete + settings add/edit/delete + users + profile). New `users/` module (admin-only, server-side guarded): list + Add side card, edit (username immutable, blank password = keep, last-admin demotion blocked), deactivate (soft delete; self + last-admin blocked; login already rejects `is_deleted` users). `users/activity.php`: paginated audit trail, filter by user + entity. `auth/profile.php`: change own name/password, current password verified.
- Role dropdowns in `users/` are intentionally hardcoded admin/staff (matches the `users.role` CHECK constraint — roles are not one of the 7 lookup tables).
- Static verification passed: zero `DELETE FROM`; template foreach/if balanced; every logActivity caller requires the helper; auth_check + admin guards on all new pages; no raw GET in ORDER BY (whitelists only); all module indexes keep sidecard/confirm_modal/alert; only hardcoded selects are the role dropdowns.

### Completed
- [x] **Side cards + toasts UX rework (2026-10-28, latest session)** — no more separate pages for viewing/adding; all feedback is toasts:
  - `includes/sidecard.php` (new): shared right slide-over — clicking any table row on the 5 entity modules opens a **details side card** (fields from the row's `data-detail` JSON, Edit link + admin-only Delete reusing `confirm_modal.php`). Esc / overlay click / ✕ closes.
  - **Add side card** on each module's `index.php` (farmers/farms/crops/plantings/harvests): the Add button opens a slide-over containing the create form (posts to `create.php`); index pages now load the lookups/parent lists the form needs. Action icons use `event.stopPropagation()` so they don't trigger the row click.
  - `create.php` ×5 reduced to **POST-only handlers**: GET → `index.php?add=1` (auto-reopens the Add card), validation failure → `index.php?add=1&error=...` (toast + card reopens; HTML `required`/`min` still the first line of defense), success → `index.php?msg=...`. Edit pages unchanged (still separate pages — not requested).
  - `includes/alert.php` rewritten as **toast notifications** (bottom-right, white theme, green/red accent border, 4s auto-dismiss + manual close). Same inputs as before: `?msg=` / `?error=` redirect params, plus page-local `$msg` / `$error` vars — so all existing redirects toast automatically. Inline error banners on the 6 edit pages + settings replaced with the alert include. Login page error intentionally left inline (pre-auth standalone page).
  - Static verification only (no PHP binary): 1 add-card per index, sidecard include + data-detail rows on all 5 modules, no `href="create.php"` left, create.php files contain no HTML, foreach/endforeach + if/endif balanced. **Needs user smoke test**: row click → details card, Edit/Delete from card, Add card save + cancel + validation bounce (`?add=1` reopen), toasts on create/edit/delete/settings, Esc/overlay close, ~375px width (card is full-width there).
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

### Pending / Next Steps — USER SMOKE TEST (run `php -S localhost:8000`)
- [ ] **Re-run `database/schema.sql` first** — it now creates **14 tables** (new: `activity_log`). Old DB will 500 on any write (logActivity insert) until re-run.
- [ ] **Shell:** sidebar renders on desktop; hamburger drawer + overlay at ~768px/375px; active nav highlighting; breadcrumbs on every page; admin sees Users/Activity Log/Settings, staff does not; profile + logout icons in sidebar footer work.
- [ ] **Quick search:** topbar search → `search.php` grouped results (farmers/farms/crops), result rows link to edit pages; empty-term and no-result states.
- [ ] **Tables v2 (each of the 5 modules):** pagination bar math ("Showing X–Y of Z", Prev/Next, page numbers), column-header sorting toggles asc/desc with arrow, filters (farmers search, farms irrigation, crops category, plantings status, harvests grade) combine with sort + page in the URL; sort/page links must NOT re-toast or reopen the Add card.
- [ ] **Side cards still work after rewrite:** row click → details card, Add card save/cancel/validation bounce (`?add=1` reopen), Esc/overlay close, toasts on create/edit/delete.
- [ ] **Dashboard v2:** KPI numbers correct (verify revenue-this-month by hand), %-vs-last-month line, date-range Apply/Reset rescopes the 3 charts + top farms, upcoming-harvests pills, empty states with no data in range.
- [ ] **Reports (all 7):** open, filter, tfoot totals = summary strip; **Export CSV** downloads and matches screen; **Print preview** shows the print header (title/filters/date) and hides nav/rails. New: farmer_profitability, seasonal_performance, yield_efficiency.
- [ ] **Users module (as admin):** add user (duplicate username rejected, short password rejected) → log in as them; edit (blank password keeps old); deactivate (self blocked, last admin blocked, deactivated user cannot log in). As staff: `users/` URLs redirect away.
- [ ] **Profile:** change name (sidebar updates), change password (wrong current password rejected, mismatch rejected), re-login with new password.
- [ ] **Activity log:** every create/edit/delete from testing appears; user + entity filters; pagination.
- [ ] Fix any first-run errors reported from testing (code was never executed — static checks only).
- [ ] Documentation deliverables for submission: screenshots, data dictionary, UNF→1NF→2NF→3NF writeup, test results, user guide (see spec in `ap3-project.docx`). Note for docs: schema is now 14 tables (13 + activity_log audit table).

### Key Conventions (do not break)
- Teacher's standards in `CODING_STANDARDS.md`: native PHP + PDO only, no Composer/frameworks, `htmlspecialchars()` on all output, `(int)`/`(float)`/`trim()` casting, prepared statements, Tailwind CDN, Heroicons.
- Soft delete only — never write a `DELETE FROM`.
- Lookup values managed ONLY through the Settings module; never hardcode dropdown options.
- UI must follow `UI_STYLE_GUIDE.md` (white theme, no AI-template look).

# CLAUDE.md — Agriculture Information System (AIS)

**MANDATORY at the start of EVERY session — read these files BEFORE doing any work:**

1. `PROJECT_STATUS.md` — where the project currently stands and what is pending. **Update it at the end of every working session.**
2. `CODING_STANDARDS.md` — the teacher's coding conventions. All code must follow it exactly.
3. `UI_STYLE_GUIDE.md` — UI rules. The interface must look human-designed (white theme), never like an AI-generated template.
4. `DEVELOPMENT_PHASES.md` — the phase roadmap (reference when planning new work).

## Hard Rules
- Native PHP + PDO + Tailwind CDN only. No Composer, no frameworks, no over-engineering.
- **Soft delete only** — never write a `DELETE FROM` statement; set `is_deleted = TRUE` and filter it everywhere.
- 13-table schema (6 entities + 7 lookups). All dropdowns are DB-driven via the Settings module — never hardcode `<select>` options.
- `htmlspecialchars()` on all output; prepared statements on all queries; `(int)`/`(float)`/`(string) trim()` casting on all inputs.
- Server-side role checks: deletes and Settings are admin-only.
- The user runs the PHP server and all tests themselves (no PHP binary on this machine) — deliver code + static verification, then ask them to test.

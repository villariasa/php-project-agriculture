# UI Style Guide — Agriculture Information System (AIS)

### UI STYLE NOTE — AVOID AI-GENERATED LOOK

When implementing or modifying the UI, keep the interface **human-designed and natural-looking**.

Do not intentionally make the UI look like an AI-generated template or generic modern SaaS dashboard.

Follow these principles:

- Prefer simple, intentional layouts.
- Use consistent spacing and typography.
- Use borders and subtle contrast instead of excessive shadows.
- Keep border radius moderate.
- Avoid excessive rounded cards.
- Avoid excessive gradients.
- Avoid glassmorphism.
- Avoid glowing effects.
- Avoid unnecessary animations.
- Avoid decorative elements that do not serve a purpose.
- Avoid excessive icons.
- Avoid giant headings.
- Avoid excessive empty space.
- Avoid putting every piece of content inside a card.
- Avoid fake statistics, charts, activity feeds, or decorative data.
- Avoid generic purple/blue AI-style color schemes unless the existing project already uses them.
- Do not add "AI-powered", "smart", or similar labels unless they are actually part of the functionality.

The UI should feel like a **real application designed by a developer for actual users**, not a generated concept or Dribbble-style mockup.

Prioritize:

**clarity → usability → consistency → practicality → polish**

Do not change the application's functionality just to make the UI look impressive.

---

## Project-Specific Theme (established)

The AIS uses a **clean white theme** consistent with the principles above:

- Page background `bg-gray-50`, white surfaces with `border-gray-200` and `shadow-sm` only.
- Primary accent: `green-600` / `green-700` (agriculture identity). Blue for edit actions, red for delete/danger.
- Moderate radius (`rounded-lg`), no gradients, no glow, no animations.
- Inline Heroicons SVG (`w-5 h-5`) only where they serve a purpose (action buttons, nav logo, summary cards).
- Status/grade pills: soft background + dark text (e.g. `bg-green-50 text-green-700`).
- Tables inside a single bordered white container with `overflow-x-auto`; header `bg-gray-50 text-gray-500 uppercase text-xs`.
- Real data only — all dashboard numbers and feeds come from live database queries.

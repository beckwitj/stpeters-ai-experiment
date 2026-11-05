# St. Peter's Anglican Church, Erindale — WordPress Prototype

A local, non-production experiment (built with **WordPress Studio**) exploring a modern,
accessible, FSE-based redesign for St. Peter's Anglican Church, Mississauga.

## Status

This is a **working events prototype** (Stage 2). The homepage and broader content are
**not yet built** — they follow only after the events workflow is reviewed.

## Architecture

| Concern | Implementation |
|---|---|
| Base theme | **Twenty Twenty-Five** (parent, updateable via core) |
| Custom theme | **`stpeters-anglican`** — a *thin* child theme. Inherits templates/parts/patterns/theme.json from TT5; only deltas live here. |
| Event data + dates | **`stpeters-events`** site plugin — registers post meta, date logic, Query Loop variations. Independent of theme so swapping themes can't disable event management. |
| Design tokens, templates | Child theme `theme.json` + `templates`/`parts` (under Git) |
| Content (Stories/News/Events as Posts), categories | WordPress **database** (Post editor) |
| Homepage / landing pages | Pages built from core blocks + Query Loop blocks (auto-populated) |

### Event lifecycle — the three-date model
Events are normal **Posts** in the `events` category with two registered date fields:

- **Event date/time** — `event_date` meta (ISO `YYYY-MM-DD`). Drives chronological ordering.
- **Display until** — `display_until` meta. Editorial control over how long an item stays
  in "current/recent" surfaces. **Never unpublishes or deletes** the post.

An event flows through three states, each a separate Query Loop surface:

1. **Upcoming** — `event_date >= today` (ascending).
2. **Recent Parish Activity** — completed events (`event_date < today`) still within
   `display_until` (descending).
3. **Archive** — the native `events` category archive; shows everything (no date filter).

**Default display window:** if `display_until` is empty, it is calculated as
`event_date + 7 days` *at save time* (constant `STP_EVENTS_DEFAULT_DISPLAY_WINDOW`,
editable via `wp-config`). Editors can override by setting a value.

### Front-end date filtering
The standard core **Query Loop block** is used everywhere (via registered block
**variations** that carry the field/operator/sort/sentinel). Because "today" can't be
expressed as a static block attribute, a server-side hook on
**`query_loop_block_query_vars`** injects the live value at render time.

### Division of labour — theme vs. plugin
| In the **plugin** (`stpeters-events`) | In the **theme** (`stpeters-anglican`) |
|---|---|
| Post-meta registration | design tokens, `theme.json` |
| Query Loop variations + date filter | templates, parts, patterns |
| Editor date fields | homepage + landing page presentation |

> Rule for volunteers: **content changes are made in the WordPress editor only** —
> publishing/editing a Post with the right category updates every Query Loop surface
> automatically. No one edits layouts, templates, or theme files to publish content.

## Custom code footprint
- `stpeters-events` plugin: the only non-trivial custom code (~meta registration + the
  date-filter hook + block variations + save-time default).
- Child theme `functions.php`: parent/child stylesheet enqueue (verified necessary —
  without it no theme CSS loads in child context).

## Running in WordPress Studio
1. Site runs in **Studio** (SQLite backing store at `wp-content/database/`).
2. Activate the child theme (already scaffolded/active). The `stpeters-events` plugin is
   activated after Stage 2 once its logic is verified.
3. Administer via WP-CLI (Studio's SQLite stores make `wp db query` unavailable; use
   `wp post list`, `option get`, etc.).

## Version control scope
`.gitignore` whitelists only **this theme**, **this plugin**, and **`README.md`**.
WordPress core, wp-config.php (credentials), the SQLite database, uploads, and Studio
runtime files (`mu-plugins`, `db.php`, `STUDIO.md`, `CLAUDE.md`, `AGENTS.md`) are excluded.

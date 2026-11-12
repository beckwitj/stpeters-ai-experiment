# St. Peter's Anglican Church, Erindale — WordPress Prototype

A local, non-production experiment (built with **WordPress Studio**) exploring a modern,
accessible, FSE-based redesign for St. Peter's Anglican Church, Mississauga.

## Status

This is a **working events prototype (Stage 2 complete)**. The homepage and broader content are
**not yet built** — they follow only after this events workflow is reviewed.

Implemented and verified in Stage 2:

- `event_date` and `display_until` post meta (editor date fields + save-time default).
- Front-end date filtering for **Upcoming Events** and **Recent Parish Activity** via
  `query_loop_block_query_vars`, exercised against this WordPress install.
- Ordering by ISO dates uses **string** ordering (`orderby = meta_value`), **not**
  `meta_value_num` — verified here that `meta_value_num` numeric-casts `2026-11-30` to
  `2026` and mis-sorts within a year.
- A test display page (`/events-test/`, page "Events Prototype Test") with two Query Loop
  blocks and seven fictional events demonstrating the full Upcoming → Recent → Archive
  lifecycle (dates were anchored to the site's "today", 2026-11-05).

### Known limitation — editor preview vs. front end
Per core's own documentation, `query_loop_block_query_vars` only affects the **front-end**
render; the **block-editor preview uses the REST API** and is not filtered. In the editor, the
"Upcoming Events" variation therefore previews **all** events (expired ones included), while
on the front end only today-or-later events show. This is expected; the front end is
authoritative. A future improvement could replicate the date filter on the REST/editor side.

### Caching implications (no cache code added)
Upcoming/Recent membership is evaluated at render time with `current_time()` in the site
timezone, so it changes at the site's local midnight. Stock WordPress does not persist
Query-Loop results in cache, so there is no staleness now. **If a full-page cache plugin is
added later**, a page cached "yesterday" could show a just-expired/became-recent event until
the cache clears; a page-cache TTL of 24h or a purge on post save (`clean_post_cache` already
fires) would be the fix. Deliberately no cache handling is added at this stage.

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

**Default display window & automatic/manual distinction:** Display until is either
**Automatic** (the plugin computes `event_date + 7 days`, constant
`STP_EVENTS_DEFAULT_DISPLAY_WINDOW`, readable via `define()`/`wp-config`) or **Manual**
(an editor picks a fixed date). A post-meta flag (`_stp_events_display_auto`) records which
mode is in effect:

- **Automatic** — Display until always **follows the event date**: if the event is moved,
  the display-until follows to the new date + 7 days. This is the default for any event with
  a date and no fixed value.
- **Manual** — Display until is **frozen**: moving the event date leaves it untouched.
  Set by unchecking "Automatic" in the editor and picking a date.
- **Editor**: in the "Event dates" box, checking *Automatic* (default) clears the until field
  so it follows the date; unchecking it enables a date picker and freezes the value. To move
  a custom date back to automatic, check Automatic again.
- **Legacy**: posts that already carry `display_until` = the computed default are adopted as
  Automatic on their next save; anything else becomes Manual.

All three save channels (editor form, REST, WP-CLI) run the same reconcile on save so the
automatic/manual behaviour holds regardless of how the event is updated. One note: with the
REST API, explicitly making an event Manual requires setting both `display_until` and
`_stp_events_display_auto` to `"0"`; the editor GUI remains the primary, simplest way to
switch between Automatic and Manual.

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

## Service Times roster

The Sunday service roster is a **single source of truth** maintained on one screen and
displayed on every page that carries the **Worship Times** block (Home and
the Plan your visit page). Saving there updates both pages — no page editing needed.

### Staff guide (whoever edits times)
- **Where to edit:** in the WordPress admin, open the **Service Times** menu item. It is
a dedicated form, not the post/page editor.
- **Each service** is one row with three fields: **Time**, **Service** (BCP Service /
  BCP Communion / BCP Morning Prayer / BCP Evening Prayer / BAS Eucharist), and
  **How to attend** (Online / In-person / Online and In-person). Use *Add service*
for more rows and *Remove* to drop one — the list is revised by hand when it
changes; nothing is auto-scheduled. A Sunday can have any number of services;
saving always stores exactly the rows shown, so a 4-service Sunday followed by a
2-service one leaves nothing behind.
- **The first-Sunday exception:** there is no scheduling engine. Instead, describe a
  recurring variation in the optional **Visitor note** box on the same screen, e.g.
  *"On the first Sunday of the month the 8 am service is also held in person."* The note
  renders directly under the times with the rest of the roster.
- **After saving, check both pages:** the Home page and the *Plan your visit* page — then
  you are done. Every other surface uses those same pages, so no other page needs editing.

### Maintainer notes
- **Data location:** the saved roster lives in the WordPress database, two options under
  `wp_options`: **`stp_service_times`** (the rows) and **`stp_service_times_note`** (the
  visitor note). Git stores only the plugin code and the built-in example defaults; a
  saved roster is not in Git.
- **Back up / restore:** back up the site database (`sqls` side of a Studio push/export),
  which carries the two options. Restoring a backup restores the roster. To reset to the
  example defaults, delete `stp_service_times` from the database.
- **Block placements:** the `stpeters/worship-times` block appears once on each of two
  pages (Home = page 55, Plan your visit = page 82 on the local prototype). Both render
  from the same roster, so there is only one set of times to maintain.
- **Editing permission:** Editors and Administrators can edit by default
  (capability `edit_pages`); Authors and below cannot. This is a provisional
  default — reviewed again later. To change who maintains times, define
  `STP_SERVICE_TIMES_CAPABILITY` (e.g. `'edit_posts'` for Authors too, or
  `'manage_options'` for admins only) in `wp-config.php` before the plugin loads.
- **Customizing the selectable values:** the five service types and three ways to
  attend are plain maps of *slug → display label* (so a rename or addition never breaks
  saved data). They can be changed without editing the plugin, via the filters
  `stp_service_times_service_types` and `stp_service_times_attendance_modes` in a small
  snippet — e.g. in the child theme's `functions.php` or an mu-plugin:

  ```php
  add_filter( 'stp_service_times_service_types', function ( $types ) {
      $types['bas-communion'] = 'BAS Communion'; // add a type
      unset( $types['bcp-service'] );            // retire one
      return $types;
  } );
  ```

  Anything added this way immediately appears in the admin dropdown and on the site.
- **If the plugin were removed:** the block renders no fallback HTML, so the times would
disappear from both pages; the underlying options and saved roster would remain intact.

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

## Page layout checkpoints

Approved page layouts can be saved as block-content snapshots under
`wp-content/themes/stpeters-anglican/content/`. These are recovery sources only:
WordPress still renders and edits the page stored in its database, and changing a
snapshot does not automatically update the page. Git does not back up the database
or media uploads.

- `content/plan-your-visit.html`: approved full-gutter-width card grid, with
  4 / 2 / 1 columns at 1186px / 600px breakpoints (shared child-theme CSS).

To restore a checkpoint, first validate its blocks on the running site, then use
Studio's WP-CLI tool to run `post update <page-id> <snapshot-path>`. The local
Plan your visit page ID at this checkpoint is 82; confirm the ID on other sites.
Keep its `page-no-title` page template and the custom service-times plugin active.
Images referenced by a snapshot must already exist in that site's media library.

## Version control scope
`.gitignore` whitelists only **this theme**, **this plugin**, and **`README.md`**.
WordPress core, wp-config.php (credentials), the SQLite database, uploads, and Studio
runtime files (`mu-plugins`, `db.php`, `STUDIO.md`, `CLAUDE.md`, `AGENTS.md`) are excluded.

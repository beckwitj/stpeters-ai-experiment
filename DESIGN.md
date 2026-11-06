---
name: St. Peter's Anglican Church, Erindale
description: A welcoming, active Anglican parish in Mississauga.
colors:
  paper: "#F8F3EA"
  surface: "#EFE6D4"
  stone: "#E4D9C3"
  ink: "#282219"
  evergreen: "#2F4A3E"
  amber: "#A9652F"
typography:
  display:
    fontFamily: "'Fraunces', serif"
    fontWeight: 600
    lineHeight: 1.08
    letterSpacing: "-0.015em"
  body:
    fontFamily: "'Source Sans 3', sans-serif"
    fontWeight: 400
    lineHeight: 1.6
    letterSpacing: "0"
spacing:
  xs: 8px
  sm: 16px
  md: 32px
  lg: 64px
  xl: 96px
rounded:
  sm: 8px
  md: 16px
  full: 999px
components:
  button-primary:
    backgroundColor: "{colors.evergreen}"
    textColor: "#FFFFFF"
    rounded: "{rounded.full}"
    padding: "12px 24px"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.evergreen}"
    border: "2px solid {colors.evergreen}"
    rounded: "{rounded.full}"
    padding: "10px 22px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "28px"
imagery:
  overlay: "#2F4A3E"
---

# Design Direction — St. Peter's Anglican Church, Erindale

## Overview

A warm, welcoming, quietly reverent site for an active suburban parish. The palette is warm
ivory and stone with a deep evergreen — a nod to the church's heritage and the surrounding
nature of the Credit Valley — and one candle-amber accent used sparingly. Serif display type
(Fraunces) gives the church voice and character; a humanist sans (Source Sans 3) keeps long
text comfortably readable. The layout is calm and roomy, never a cluttered bulletin board:
welcome first, worship up front, then clear paths in, and a modest glimpse of parish life.

## Colors

- **Paper** — the home ground: warm ivory behind almost everything.
- **Surface** — a soft, deeper ivory used for cards and tinted bands.
- **Stone** — a muted taupe for thin rules, dividers, and quiet fills.
- **Ink** — a warm near-black for body text (high contrast on Paper).
- **Evergreen** — the primary: buttons, key headings, the hero band, the footer. Conveys
  stability, welcome, and heritage. Paired with white/ivory text it meets AA.
- **Amber** — a single warm accent for eyebrows, small labels, and highlights. Used sparingly.

## Typography

- **Display — Fraunces** (serif): headlines. Warm, characterful, welcoming. Set large and
  comfortable; the hero headline is the page's largest type.
- **Body — Source Sans 3** (humanist sans): paragraphs, labels, navigation. Highly legible for
  all readers; 16-18px body on mobile, generous line height.

### Responsive type scale (all tiers fluid)

Every preset declares an explicit `fluid: { min, max }` in the child theme.json. This is
intentional, for three reasons: (a) the editor typography panel shows every size as fluid
(an implicit `fluid: true` on a static size is ambiguous in the UI); (b) the floors keep
text comfortably large on small phones; (c) the ceilings stop headlines growing unwieldy on
wide desktops. `rem` stays anchored to the 16px root, so these sizes do not reflow the
layout's rem-based spacing.

| Tier | rem (min → max) | Phone ≈ | Desktop ≈ | Used by |
|---|---|---|---|---|
| small | 0.9 → 1.0 | 14–15px | 16px | eyebrows, meta, event dates |
| medium | 1.125 → 1.25 | 18px | 20px | body, supporting descriptions |
| large | 1.375 → 1.625 | 22px | 26px | worship times, card titles, activity titles |
| x-large | 1.75 → 2.375 | 28px | 38px | section headings (h2) |
| huge | 2.5 → 4.0 | 40px | 64px | hero h1 |

Rules of use:
- **Large > medium > body**: a heading, or anything that must lead (like the Sunday service
  times), always sits at or above the Large tier so it reads clearly against plain body
element text. Body stays in Medium; meta/eyebrows in Small.
- Nothing renders below 18px for body-tier content on a standard 16px root.
- The huge floor (2.5rem / 40px) was chosen so the hero headline wraps to two comfortable
  lines inside the cover's horizontal padding even at a 320px viewport, and its ceiling
  (4rem / 64px) keeps it from becoming oversized on wide desktops.

## Layout

Content column ~760px for text; wide rows up to ~1180px for card grids and image bands.
Sections are calm stacked bands; full-bleed evergreeen bands break the ivory rhythm and give
the page its structure. Generous vertical padding (spacing.lg/xl) so no section feels crowded.

## Elevation & Depth

Low and calm. Cards sit on Surface with a soft shadow; the hero uses an evergreen overlay over
the image for text contrast. No bright, noisy depth.

## Shapes

Full-bleed sections stay square at the viewport edges. Rounded corners appear on inner
elements only — buttons are full pill; cards and small frames use rounded.md.

## Components

- **button-primary** — evergreen fill, white text, pill, the page's principal action
  (Plan your visit, Worship).
- **button-secondary** — outline in evergreen, for secondary actions.
- **card** — Surface background, rounded corners, generous padding; used for the nav cards.

## Do's and Don'ts

- Do keep the hero welcoming and spare; one message, one path in.
- Do put worship times where a first-time visitor finds them in seconds.
- Do keep the parish-life preview modest — three recent items at most, not a news feed.
- Don't turn the homepage into a bulletin board of notices.
- Don't rely on color alone; pair every color signal with text or shape.
- Don't add decorative clutter; restraint is the welcome.

## Imagery

Warm, atmospheric, non-figurative placeholder imagery only — light through windows, quiet
architecture, candle glow. No identifiable people, no claims of real parish activities.
Temporary content is clearly labelled as sample. Dark evergreen overlay under any text for AA
contrast.

## Motion

Gentle, if any. Respect `prefers-reduced-motion`; no autoplay or flashing.

## Voice

Warm, plain, inviting. Short sentences. "You" and "we" — direct and human. Words that belong:
welcome, come as you are, worship, parish, community, Mississauga, Erindale. Avoid: jargon,
formality, and any sense of being an announcements board.

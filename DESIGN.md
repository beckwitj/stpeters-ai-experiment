---
name: St. Peter's Anglican Church, Erindale
description: A welcoming, active Anglican parish in Mississauga.
colors:
  paper: "#FBF7F0"
  surface: "#F4ECE0"
  stone: "#E5DAC6"
  ink: "#282219"
  evergreen: "#1F4E79"
  amber: "#A9652F"
  brand: "#3E8DDD"
  ivory: "#FDFBF4"
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
  overlay: "#1F4E79"
---

# Design Direction — St. Peter's Anglican Church, Erindale

## Overview

A warm, welcoming, quietly reverent site for an active suburban parish. The palette is warm
ivory and stone with a deep parish blue — a nod to the church's heritage and its place in
Erindale — and one candle-amber accent used sparingly. Serif display type (Fraunces) gives
the church voice and character; a humanist sans (Source Sans 3) keeps long text comfortably
readable. The layout is calm and roomy, never a cluttered bulletin board: welcome first,
worship up front, then clear paths in, and a modest glimpse of parish life.

## Palette

This file tracks **Palette 2 — Warm & Traditional** as the current theme. **Palette 1 —
Contemporary & Forest** (warm ivory + deep evergreen) is retained below as the historical
alternative that preceded it; the theme.json and homepage currently ship Palette 2. Git
already tracks this file (`!/DESIGN.md` in `.gitignore`).

### Palette 2 (current) — Warm & Traditional, deep parish blue

- **Paper `#FBF7F0`** — the home ground: warm cream behind almost everything (body text
  ink-on-paper = 14.75:1).
- **Surface `#F4ECE0`** — a deeper warm cream for cards and the footer band (ink-on-surface
  = 13.44:1; deep blue on surface = 7.39:1).
- **Stone `#E5DAC6`** — muted taupe for thin rules and card borders (decorative only, ~1.2:1).
- **Ink `#282219`** — warm near-black body text.
- **Evergreen `#1F4E79`** — the slug now holds a **deep parish blue** (kept for continuity of
  the token name). It is the primary: buttons, the hero overlay, the worship band, footer
  headings and links. Ivory/white on it = 8.37:1 (AA).
- **Brand `#3E8DDD`** — St. Peter's own blue, kept as an **accent only**: it passes 3:1
  non-text contrast on light/Paper surfaces (3.25:1) but fails on warm Surface (2.96:1).
  Use it for large decorative details and focus indicators on light backgrounds only;
  never for normal-size text or on the warm Surface band.
- **Ivory `#FDFBF4`** — near-white for text on the deep-blue bands.
- **Amber `#A9652F`** — candle accent for eyebrows and small labels (darkened from the
  original for AA).

### Palette 1 (historical) — Contemporary & Forest

Predecessor of the current palette: same warm ivory ground, but with a **deep evergreen
`#2F4A3E`** in place of deep blue, `paper #F8F3EA / surface #EFE6D4 / stone #E4D9C3`,
overlay `#2F4A3E`, and no separate Brand token. Superseded by Palette 2; kept for reference
and easy reversion.

## Colors

- **Paper** — the home ground: warm cream behind almost everything.
- **Surface** — a deeper warm cream used for cards and the footer band.
- **Stone** — a muted taupe for thin rules, dividers, and quiet fills.
- **Ink** — a warm near-black for body text (high contrast on Paper).
- **Evergreen (deep parish blue)** — the primary: buttons, key bands, footer headings/links.
  With ivory/white text it meets AA.
- **Brand (St. Peter's blue)** — accent and accessible UI detail on light surfaces only.
- **Amber** — a single warm accent for eyebrows, small labels, and highlights, used sparingly.

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

Horizontally, full-bleed sections keep their tint (or image) to the viewport edge while the
content inside is inset with the **root gutter `clamp(1.25rem, 4vw, 2.5rem)`** — 20px at a
390px phone, ~31px at a tablet. Two full-bleed bands get this treatment in CSS:

- **Parish-life activity list** — the Query Loop is a centered `66rem` (1056px) content
  column with the root-gutter padding, and each card fills it (no separate narrow cap), so
  titles and dates never touch the edge on mobile and cards unlock a comfortable ~976px on
  wide desktops instead of hovering in a narrow centered bubble.
- **Footer** — the three column groups share the root gutter on mobile (`@media` up to
  781px) so contact, the flag/"All are welcome", and the social/connect links are all inset
  consistently; on desktop the columns center within the wide 1180px row.

The **hero** uses a **content-aware minimum height, not a rigid value**: `56vh` on mobile
(kept), and `clamp(400px, 40vh, 480px)` from 900px up — so it lands in the 400-480px band on
a typical 1366px-wide desktop, never clips the heading + intro + CTA when text scales up or
zooms in, and grows freely with its content.

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

## Footer

The default Twenty Twenty-Five footer is replaced by a custom accessible block pattern in the
child theme: `parts/footer.html`, rendered on the warm **Surface** ground with deep-blue
(slug `evergreen`) headings and links.

- **Statement** — the parish identity line, "Serving God together, Acting with compassion,
  Proclaiming hope," centered in Fraunces at the top of the footer.
- **Three columns, balanced on desktop, stacked on mobile**:
  1. **Parish & contact** — name, address (3041 Mississauga Rd, Mississauga ON L5L 0B7),
     telephone (`tel:`), directions link, and office/connect detail.
  2. **Inclusion** — the **Progress Pride flag** and "All are welcome." The flag is a real
     SVG asset, not emoji or AI-generated.
  3. **Visit & connect** — visitor actions (Newsletter, Contact us) and social links
     (Facebook, Instagram, YouTube), then a brand/copyright line "© 2026".

### Supplying a flag asset

The prototype ships a genuine Wikimedia intersex-inclusive **Progress Pride flag SVG** at
`wp-content/themes/stpeters-anglican/assets/images/progress-pride-flag.svg` with a
meaningful `alt="Progress Pride flag"`. To use the parish's own official flag instead,
supply an **SVG or high-resolution PNG** (ideally a vector for crisp scaling; ~150px display
width), commit it to the same `assets/images/` path, and reference it from the footer's
`wp-block-image` block — swap the `src` URL in `parts/footer.html`. Keep the descriptive `alt`
text. A flat-design progress flag is preferred over a photo so it reads clearly at small size.

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

## Future Architectural Requirements

### Single-source content and editorial syndication

WordPress will be the authoritative source for parish stories, events and announcements. Content should be reusable across the website, weekly email and social media, with editorial discretion over what is distributed, when, and through which channels.

Newsletter and social media integration will be considered after the core website and post-authoring workflow are established. Avoid unnecessary dependence on any particular commercial service.
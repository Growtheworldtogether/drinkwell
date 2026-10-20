# DRinkWeLL DESIGN.md

Single source of truth for visual design. If a spec, prompt or mockup disagrees with this file, this file wins.

Two surfaces, one product:
- **Public pages** (Home, How it works, Territories, Terms, auth screens): Uber's design system. Black and white, confident, large type, pill buttons.
- **Portal and admin** (investor dashboard, application flow, wallet, withdrawals, admin): Tesla's design system. Quiet, instrument-panel, data first, small radius.

Shared tokens (color, type, spacing, money formatting) are identical on both. Only shape, density and scale differ.

Academic project. Earnings are simulated. No securities are offered.

---

## 1. Visual Theme and Atmosphere

Industrial, utilitarian, data-dense. It should read like a fleet operations console that happens to be open to the public, not a fintech landing page.

- Black and white first. Color is rare and means something.
- Hairline borders (1px) instead of shadows or filled cards.
- Full-width bands instead of floating boxes. Content sits in horizontal bands separated by hairlines or a black/white swap.
- Every number that matters is set in mono with tabular figures: currency, VINs, dates, times, coordinates, telemetry.
- Real content only. No lorem ipsum, no demo inbox, no read-only screens, no view-as-user mode.
- Motion exists only to show state changing (a vehicle moving, a balance settling, a bar being earned). Never for decoration.

Public mood: Uber. Large headlines, generous white space, black bands, pill buttons.
Portal mood: Tesla. Dense panels, small radius, muted grays, one blue accent for the single primary action per view.

---

## 2. Color Palette and Roles

Accent is Tesla blue. Orange is retired everywhere.

| Token | Hex | Role |
|---|---|---|
| `--black` | `#000000` | Public nav, footer, live ledger band, primary button on public pages, route line |
| `--ink` | `#111111` | Body text on white |
| `--gray-700` | `#3A3A3A` | Secondary text, dark-band hairlines |
| `--gray-500` | `#6B6B6B` | Labels, captions, disabled text |
| `--gray-300` | `#C9C9C9` | Input borders, dashed alternate route |
| `--gray-200` | `#E2E2E2` | Hairline borders on white |
| `--gray-100` | `#F2F2F2` | Subtle band background, table zebra |
| `--white` | `#FFFFFF` | Page background |
| `--accent` | `#3E6AE1` | Primary action in portal and admin, focus ring, active nav item, links in portal, the "you" marker on charts |
| `--accent-ink` | `#2F55BF` | Accent hover and pressed |
| `--ok` | `#1F7A3D` | Confirmed, settled, funded, approved |
| `--warn` | `#9A6700` | Pending, low battery, awaiting review |
| `--danger` | `#B3261E` | Rejected, failed, critical battery, disabled withdrawals |

Rules:
- Text on `--accent` is white. Minimum contrast 4.5:1 for body text, 3:1 for large text and UI borders. Verify `--warn` and `--ok` on `--gray-100` too.
- Status colors are used for text and 1px borders on a tint of the same hue at 8% at most. Never a colored left-border card.
- One primary accent action per view. Accent may also mark the active nav item, focus rings, links in the portal, and the earned bars or "you" marker on a chart. Nothing else is accent.
- Dark surfaces (black bands, portal dark theme if offered) use `#000` and `#0B0B0B`, text `#FFFFFF` and `#B5B5B5`. No mid-gray-on-gray. Body text on dark must clear 7:1.
- No gradients anywhere. No purple-to-blue, no gradient text, no gradient buttons, no gradient backgrounds.

---

## 3. Typography Rules

Fonts: **Geist** for text, **Geist Mono** for figures. Geist is the free stand-in for Tesla's proprietary Universal Sans, used on both surfaces for consistency. Self-host the font files; no third-party font CDN.

```
--font-sans: "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
--font-mono: "Geist Mono", ui-monospace, "SF Mono", Menlo, Consolas, monospace;
```

Scale (public / portal):

| Style | Public | Portal | Weight | Tracking |
|---|---|---|---|---|
| Display | 64 / 68 | not used | 600 | -0.02em |
| H1 | 44 / 48 | 28 / 32 | 600 | -0.015em |
| H2 | 32 / 38 | 20 / 26 | 600 | -0.01em |
| H3 | 20 / 26 | 16 / 22 | 600 | 0 |
| Body | 17 / 26 | 14 / 21 | 400 | 0 |
| Label | 12 / 16 | 11 / 14 | 500, uppercase, +0.06em | |
| Figure | Geist Mono, `font-variant-numeric: tabular-nums` | same | 500 | 0 |

Rules:
- Currency, VINs, dates, times, coordinates, battery %, speeds and IDs are always Geist Mono with tabular numerals.
- Currency format: `$1,055.00`, `$333.33`. Always two decimals. Right-aligned in tables.
- Payback progress: "Day N of 15". Never "Day N of 14".
- No emojis in headings or labels. No decorative serif italics. Do not pair Space Grotesk with Instrument Serif. Do not use Inter as the everywhere font.
- No em dashes in UI copy. Use a period, a comma, a colon or a plain hyphen with spaces.
- No buzzword copy. Say what the thing does in plain words.

---

## 4. Component Stylings

### Radius
- Public: **pill** (`9999px`) for buttons and chips. Inputs 8px. Cards and bands 0 (bands are full-width, no rounding).
- Portal and admin: **4px** for buttons, inputs, chips, panels. No pills.

### Buttons
| Variant | Public | Portal |
|---|---|---|
| Primary | Black fill, white text, pill, 48px high | Accent fill, white text, 4px, 40px high |
| Secondary | White fill, 1px black border, black text | White fill, 1px `--gray-300` border, ink text |
| Tertiary | Text with underline on hover | Text in accent, underline on hover |
| Destructive | n/a | White fill, 1px `--danger` border, `--danger` text |

Hover changes color or border, never opacity. Focus ring: 2px `--accent` outline with 2px offset, on every interactive element, on both surfaces.

### Inputs
1px `--gray-300` border, white fill, 44px high, label above in Label style. Error state: 1px `--danger` border plus text under the field. OTP entry is six separate mono boxes with auto-advance and paste support.

### Tables
Hairline row separators, no zebra unless density demands it. Header row in Label style. Currency and telemetry columns mono and right-aligned. Sticky header on long lists. Every row action is a real button that does a real thing.

### Panels (portal)
1px `--gray-200` border, 4px radius, white fill, 16px padding. Panel title in Label style. No shadow.

### Status chips
Text plus 1px border in the status color, 4px radius (portal) or pill (public). Text only, no icon.

### Charts (Chart.js)
Flat fills, no gradients, no shadows. Bars in `--black` on public, earned bars in `--accent` on portal with unearned bars in `--gray-200`. Axis labels in Geist Mono 11px. Draw once on first view, no looping animation.

### Icons
Only functional icons that carry meaning a label alone would not (map controls, sort direction, expand/collapse). No decorative icons. No three-icon feature boxes.

### Forbidden components
Colored left-border cards, glassmorphism or blur surfaces, pill above the headline, stock shadcn-looking cards, cursor glow beams, grain or noise overlays.

---

## 5. Layout Principles

- 12-column grid. Public max width 1200px, portal max width 1440px, admin fluid.
- Spacing scale (px): 4, 8, 12, 16, 24, 32, 48, 64, 96. Nothing else. Inconsistent spacing is a lint failure.
- Public pages are stacks of **full-width bands**. Each band owns its background (white, `--gray-100` or black) and its own vertical padding (96 desktop, 48 mobile).
- No terms block on the front page. Terms live on the Terms page, the footer carries the one-line disclaimer.
- No dollar amount on the Fund step of the public how-it-works road.

### Home layout (v9, approved)
1. **Black nav.** Brand logo (dark-background variant, or the text wordmark) left, links center or right, pill "Apply" button in white.
2. **Hero.** Headline and one sentence on the left, primary and secondary pill buttons. Right side is a live Leaflet map with a floating trip card (pickup, drop-off, fare, ETA) driven by the same animation state as the vehicle.
3. **Black live ledger band.** Streaming rows of simulated trips and settlements in Geist Mono. White text on black.
4. **Charging photo slot.** Full-width image slot for a real photo of the inductive charging pad. Labeled placeholder until supplied.
5. **Payback bar chart.** Bars for days 1 to 15, "Day N of 15", the 15th bar is the one that crosses $5,000.
6. **Apply / Review / Fund / Earn.** Two-lane road with U-turns between lanes. Four steps, no dollar amounts.
7. **Black footer.** Links, territories, and `Academic project. Earnings are simulated. No securities are offered.`

### Public pages
Home, How it works, Territories, Specs, Terms, Privacy, Sign in, Register, Verify email, Forgot password. Terms and Privacy are single-column, max width 768px, numbered sections.

### Admin layout (mocked, approved)
Fluid width. Left rail of 13 destinations in five groups: Review (Overview, Applications, Investors, Withdrawals), Money (Earnings), Fleet (Vehicles, Simulation), Platform (Automation, Content, Settings), Records (Audit log, Email log, Integrity). Two-pane screens (Withdrawals, Applications) use list on the left and detail on the right; mutations open an inline confirm panel, never a modal. Settings has General and Branding tabs.

### Branding (white-label)
The product name, logos, favicon, tagline and support email are admin-editable (Settings, Branding) and are never typed into a view, email or PDF. Use the brand components. With no logo, the name renders as a Geist 600 text wordmark. Accent, fonts, radius and layout are fixed and not editable. Dark surfaces use the dark-background logo, or the wordmark in white.

### Portal dashboard layout (mocked, approved)
Left rail nav (Overview, Rides, Earnings, Wallet, Certificate, Profile; withdrawals live inside Wallet) at 208px, top bar with balance and clock mode, main area of 12-column panels. Overview panels: payback progress ("Day N of 15"), today's gross and payout, vehicle state and battery, live map, last 10 ledger entries.

---

## 6. Depth and Elevation

Flat. Depth comes from borders and band color, not shadow.

- No box-shadow on cards, panels, buttons or nav.
- Allowed shadows: (a) the vehicle marker drop shadow on the map, (b) the menu/popover shadow `0 4px 16px rgba(0,0,0,0.12)`, (c) modal scrim `rgba(0,0,0,0.6)`.
- Layering order: page, band, panel, popover, modal. Nothing else.
- Sticky nav has a 1px bottom hairline once scrolled, no blur, no translucency.

---

## 7. Do's and Don'ts

### Do
- Use black bands to create rhythm on public pages.
- Set every figure in Geist Mono with tabular numerals.
- Keep one primary action per view.
- Show real state: real buttons, real data, real errors.
- Use `Day N of 15` wherever payback is shown.
- Write copy in plain words with no em dashes.

### Don't
- No purple-to-blue gradients, gradient hero text, or any gradient.
- No emojis in headings or labels.
- No Inter as the everywhere font. No Space Grotesk with Instrument Serif. No decorative serif italics.
- No colored left-border cards.
- No glassmorphism, blur or translucent surfaces.
- No low-contrast dark mode.
- No three generic icon feature boxes. No decorative Lucide icons. No stock shadcn clones.
- No pill above the headline.
- No scroll fade-ins or scroll-jacking. No cursor glow beams. No opacity-fade hover effects.
- No grain or noise overlays.
- No inconsistent spacing.
- No buzzword copy.
- No stock photos and no AI-generated placeholder art.

### Purposeful motion
Allowed:
- Vehicle movement on the map (Leaflet marker, see below).
- HUD values updating in the same frame as the marker.
- A balance or ledger row appearing when a settlement posts (150 ms, position only, no fade).
- Payback bars drawing once when the chart first enters view. Draw is a height change, not a fade.
- Focus rings and pressed states.

Not allowed: anything that runs on scroll to reveal content, loops without a state change behind it, or fades opacity on hover.

Respect `prefers-reduced-motion: reduce`: the marker jumps between positions once per second, bars render at final height, no row animation.

### Real image rules
- Photos are supplied by the project owner: charging pad, pickup point, portal dashboard screenshot.
- Until supplied, render a labeled slot: 1px hairline box at the final aspect ratio with a mono caption such as `IMAGE SLOT 16:9 / charging pad photo`. No gradient placeholder, no stock image.
- Every image has real alt text. Images never carry text that is not also in the HTML.

---

## 8. Responsive Behavior

Every page works from 320px to 1920px, portrait and landscape, with no horizontal page scroll at any width. This covers public, auth, applicant, portal and admin screens. Breakpoints are 640, 1024 and 1280 only (Tailwind `sm`, `lg`, `xl`; no `md`, no `2xl`). Design and test at 320, 360, 390, 768, 1024, 1280, 1440, 1920.

- **Under 640:** one column. Nav (public) or top bar (portal, admin) with a Menu button opening a solid full-screen sheet, tap targets 48px, closes on Escape and returns focus. Hero map goes below the headline at 4:3, the trip card docks under the map. Tables become stacked cards of label/value pairs, figures in mono and right-aligned. Inputs are 16px so iOS does not zoom. Wizard footer (Back, Continue) is pinned to the bottom.
- **640 to 1023:** two columns where content allows. Tables scroll horizontally inside their own container with a sticky first column. Portal and admin keep the top bar and sheet. Summary stats in a 2 by 2 grid.
- **1024 and up:** full layouts from section 5. The portal and admin left rail is persistent. Two-pane admin screens show list and detail side by side; below 1024 the detail is its own page with a Back link.
- Minimum tap target 44px below 1024. No hover-only interactions.
- Use `min-w-0` on flex and grid children, `overflow-wrap:anywhere` on emails, VINs and references, `min-h-dvh` (never `h-screen`), and safe-area insets on fixed bars.
- Images keep an aspect ratio and `max-w-full`. Charts fill a sized container. The payback chart keeps all 15 bars on every screen and labels every 5th day; other charts thin their labels below 640.
- The map never captures page scroll on touch: two-finger pan, one-finger scrolls the page, with a visible hint.
- Enforced by lint bans (`w-screen`, `h-screen`, `md:`, `2xl:`, `user-scalable`) and by `npm run check:responsive`, which asserts no horizontal overflow on every route at all eight widths.

---

## 9. Agent Prompt Guide

### Leaflet vehicle motion and marker spec (v9, approved)
Use exactly this behavior when building any live map.

- **Position:** arc-length position along a road-snapped polyline. The vehicle's location is a pure function of the simulated minute, computed from the persisted day timeline. Never tween between lat/lng pairs.
- **Loop:** one `requestAnimationFrame` loop. Marker and HUD read the same frame state, so they cannot drift.
- **Heading:** eased toward the target bearing using shortest-angle math (wrap to -180..180 before easing).
- **Speed profile:** acceleration constant A = 0.8 m/s^2 with a smooth ramp at stops and turns.
- **Marker:** `L.divIcon` with a centered anchor (half of width and height). Inside it, a wrapper that rotates with heading, containing a top-down car SVG. Drop shadow on the inner wrapper, not on the icon container, so rotation does not skew the shadow.
- **Route drawing:** three layers. A white casing (wider), the black route line on top, and a dashed `--gray-300` alternate route. Route line weight 5, casing weight 9.
- **Labels:** street and place labels must not overlap. Place them by priority and hide the loser.
- **Fallback:** if tiles fail, show the baked road grid on `--gray-100`. Routes are baked once from the OSRM demo server or hand-recorded, and stored. No runtime calls to paid APIs.

### Prompt 0 (paste at the top of every design-related Cursor prompt)
```
Read DESIGN.md first and follow it exactly. It overrides any general design skill or default.

Surfaces: public pages follow the Uber rules (black and white, pill buttons, full-width bands).
Portal and admin follow the Tesla rules (4px radius, dense panels, one accent action per view).
Fonts: Geist and Geist Mono, self-hosted. All figures in Geist Mono with tabular numerals.
Accent: #3E6AE1 (portal and admin primary action, focus ring, active nav). No orange.
No gradients, no emojis in headings or labels, no em dashes in UI copy, no glassmorphism,
no colored left-border cards, no scroll animations, no opacity-fade hovers, no decorative icons.
Payback is always "Day N of 15". Money is integer cents in code, "$1,055.00" in UI.
Footer and Terms carry: Academic project. Earnings are simulated. No securities are offered.
Never type the product name in a view, mail, PDF or JS string: use the brand components (<x-brand.name />, <x-brand.logo>).
Every screen works from 320px to 1920px with no horizontal page scroll: mobile first, breakpoints 640, 1024, 1280 only,
44px touch targets, 16px inputs on phones, tables stack as cards below 640, rail becomes a top bar and full-screen sheet below 1024.
If a Taste Skill or other style skill conflicts with DESIGN.md, DESIGN.md wins.
```

### Design lint (run before every commit)
Fail the build if any of these match in `resources/`, `public/` or `app/` view code.

| Check | Pattern or rule |
|---|---|
| Gradients | `linear-gradient`, `radial-gradient`, `conic-gradient`, `bg-gradient-`, `from-`/`via-`/`to-` Tailwind gradient stops |
| Gradient text | `bg-clip-text` with `text-transparent` |
| Em dashes in UI | `—` in any `.blade.php`, Livewire view or JS string shown to users |
| Emojis | Unicode emoji ranges in headings, labels, buttons |
| Glass/blur | `backdrop-blur`, `backdrop-filter` |
| Grain/noise | `noise`, `grain`, feTurbulence in shipped CSS or SVG |
| Colored left border | `border-l-` with a color class on a card container |
| Opacity hovers | `hover:opacity-`, `transition-opacity` on interactive elements |
| Scroll animation | `IntersectionObserver` used to add reveal classes, `animate-on-scroll`, AOS, scroll-jacking libraries |
| Cursor glow | mousemove handlers that set CSS variables for a spotlight |
| Old accent | `orange`, `#F97316`, `#FF6900` or any hex used as the accent other than `#3E6AE1` |
| Wrong payback text | `of 14` next to "Day" |
| Off-scale spacing | Tailwind spacing outside the scale 1, 2, 3, 4, 6, 8, 12, 16, 24 (4px units) |
| Missing mono | Currency rendered outside an element with `font-mono tabular-nums` |
| Missing disclaimer | Footer or Terms without the exact disclaimer string |
| Radius drift | Pill radius inside portal or admin layouts, non-4px radius on portal buttons and inputs |
| Hard-coded brand name | The product name typed in any view, mail, notification or JS string (use the brand components) |
| Non-responsive units | `w-screen`, `h-screen`, `md:`, `2xl:`, `user-scalable=no`, `maximum-scale` |

### Prompt template for new screens
```
Build [screen] for [public | portal | admin] following DESIGN.md.
Use only tokens from section 2 and the spacing scale from section 5.
Include: empty, loading, error and success states. Every control must perform a real action.
Return the file list changed and confirm the design lint passes.
```

---

## Public home page (supersedes the Uber-style public rules above)

The home page follows the Coca-Cola Company homepage: a full-screen animated gradient behind everything that changes with the section in view (coral, grey, amber, cream, white), a header that is transparent over the hero and a glass bar once scrolled, staggered headlines, photo cards with chips, black pill buttons, two rows of round thumbnails with pale names, colored tabs on cards, a help panel, a plain white footer.

- All of it lives in `resources/css/public.css` and `resources/js/public-fx.js`. Those two files alone may use gradients, blur, translucency, shadow and motion. The design lint excludes only them.
- Portal and admin keep the flat rules in the sections above.
- Type is Montserrat on the public pages. Money and tables on the home page keep tabular figures.
- No logo, photo, wording or font file of the reference site is used. Photo slots stay grey/colored until real photos are supplied.

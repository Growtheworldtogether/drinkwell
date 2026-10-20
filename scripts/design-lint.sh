#!/usr/bin/env bash
export LC_ALL=C.UTF-8
fail=0
XARGS=()
check() {  # $1 label, $2 regex, $3.. paths (missing paths are skipped; a match always fails the build)
  label="$1"; re="$2"; shift 2
  paths=(); for p in "$@"; do [ -e "$p" ] && paths+=("$p"); done
  [ "${#paths[@]}" -eq 0 ] && return
  out=$(grep -RInP --include='*.php' --include='*.js' --include='*.css' --include='*.html' "${XARGS[@]}" -e "$re" "${paths[@]}" 2>/dev/null || true)
  if [ -n "$out" ]; then echo "FAIL: $label"; echo "$out"; fail=1; fi
}
UI="resources app"
# Only the public home stylesheet may use gradients, blur, translucency, shadow and motion. Everything else keeps the flat rules.
PUBX=(--exclude=public.css --exclude=public-fx.js)
PORTAL="resources/views/portal resources/views/admin resources/views/apply resources/views/layouts/app.blade.php resources/views/layouts/admin.blade.php resources/views/livewire"
PUBLIC="resources/views/marketing resources/views/auth resources/views/layouts/marketing.blade.php"

XARGS=("${PUBX[@]}")
check "gradients"             '\bbg-gradient|\b(from|via|to)-(af-[a-z0-9-]+|[a-z]+-\d{2,3})\b|bg-clip-text|text-transparent|linear-gradient|radial-gradient|conic-gradient' $UI
XARGS=("${PUBX[@]}")
check "blur / glass / alpha"  'backdrop-blur|backdrop-filter|\bblur-(sm|md|lg|xl)|\bbg-[a-z0-9-]+/\d+' $UI
XARGS=()
# CSS filter is banned except the v9 grayscale tiles on .leaflet-tile-pane (and the allowed marker drop-shadow).
XARGS=(--exclude=app.css)
check "css filter"            '(^|[^-])filter:' resources/views resources/js app
XARGS=()
check "left-border accents"   '\bborder-l(-\d+)?\b|\bborder-s\b' $UI
check "default radius"        '\brounded(?![-\w])|\brounded-(?!pill\b|input\b|panel\b)' $UI
check "pill in portal"        '\brounded-(pill|input)\b' $PORTAL
check "panel radius on public" '\brounded-panel\b' $PUBLIC
XARGS=("${PUBX[@]}")
check "shadows"               '(?<!drop-)\bshadow(?![-\w])|\bshadow-(?!popover\b)[a-z0-9]+|\bdrop-shadow-' $UI
XARGS=()
check "opacity hover / fade"  'hover:opacity|group-hover:opacity|transition-opacity|\bfade|\banimate-' $UI
XARGS=(--exclude=payback-chart.js --exclude=public-fx.js)
check "scroll effects"        'IntersectionObserver|data-aos|\baos\b|scroll-smooth|snap-y|parallax' $UI
XARGS=()
XARGS=(--exclude=public-fx.js)
check "cursor glow"           'mousemove.*setProperty|--mouse-[xy]|spotlight' $UI
XARGS=()
check "dark variants"         '\bdark:' $UI
check "arbitrary values"      '\b(?:[pm][trblxy]?|gap(?:-[xy])?|space-[xy]|rounded|text|bg|border)-\[' $UI
check "off-scale spacing"     '\b(?:[pm][trblxy]?|gap(?:-[xy])?|space-[xy])-(?:0\.5|1\.5|2\.5|3\.5|5|7|9|10|11|14|15|1[89]|2[0-3]|2[5-9]|[3-9]\d)\b' $UI
check "default palette"       '\b(?:bg|text|border|ring|outline|divide|fill|stroke|from|via|to)-(?:zinc|gray|slate|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d' $UI
check "old accent"            '(?i)orange|#F97316|#FF6900' $UI
XARGS=(--exclude=tokens.js)
check "raw hex outside tokens" '#[0-9A-Fa-f]{6}\b' resources/views resources/js
XARGS=()
check "hard-coded brand name" "(?i)(?<!config\(')drinkwell" resources/views app/Mail app/Notifications resources/js
check "non-responsive units"  '\bw-screen\b|\bh-screen\b|\bmd:|\b2xl:|user-scalable|maximum-scale|\bmin-w-\[|overflow-x-scroll' $UI
check "banned fonts"          'Inter\b|Space Grotesk|Instrument Serif|font-serif|\bitalic\b|IBM Plex|Barlow' $UI
check "icon libraries"        'lucide|heroicons|fontawesome' $UI
check "wrong payback text"    '(?i)day\s*(\{\{[^}]*\}\}|\d+|N)?\s*of\s*14\b' resources
check "em dash"               '\x{2014}' resources
check "emoji"                 '[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]' resources
check "buzzwords"             '(?i)revolutioniz|seamless|cutting-edge|decentraliz|paradigm|next-gen|unlock' resources

# The footer component must carry the Demo mark.
if ! grep -qF ">Demo</p>" resources/views/components/footer-disclaimer.blade.php; then echo "FAIL: the footer component must say Demo"; fail=1; fi

[ "$fail" -eq 0 ] && echo "design-lint: OK" || exit 1

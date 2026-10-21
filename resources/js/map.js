// Leaflet maps. Tiles come from this server's own cache; there is no paid map service.
import { createMap } from './leaflet-setup.js';
import { addTiles } from './tiles.js';
import { colors, mesh } from './tokens.js';

// Map pin: a teardrop with a small drink vending machine inside (glass front with rows of bottles, keypad, pickup slot).
// Coral while the machine still has an open half for a co-partner, black once it is shared.
const bottleRow = (y, cols) => cols.map((c, i) => `<rect x="${14 + i * 3.2}" y="${y}" width="2.2" height="3" rx="0.6" fill="${c}"/>`).join('');

export const pin = (open) => {
    const body = open ? colors.accent : colors.black;
    const glass = colors.ink;
    const rows = [mesh.mint[0], mesh.amber[0], mesh.coral[2]];
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="52" viewBox="0 0 40 52" aria-hidden="true">
        <path d="M20 50.5C20 50.5 3.5 31.5 3.5 19a16.5 16.5 0 0 1 33 0C36.5 31.5 20 50.5 20 50.5Z" fill="${body}" stroke="${colors.white}" stroke-width="2.5"/>
        <rect x="11" y="7" width="18" height="24" rx="2.6" fill="${colors.white}"/>
        <rect x="13" y="9.2" width="10.6" height="14.6" rx="1.2" fill="${glass}"/>
        ${bottleRow(10.6, rows)}${bottleRow(15.2, [rows[1], rows[2], rows[0]])}${bottleRow(19.8, [rows[2], rows[0], rows[1]])}
        <rect x="25" y="9.2" width="2.4" height="2.4" rx="0.6" fill="${body}"/>
        <rect x="25" y="12.6" width="2.4" height="5.4" rx="0.6" fill="${colors[300]}"/>
        <rect x="13" y="25.6" width="14.4" height="3" rx="1.2" fill="${body}"/>
    </svg>`;
    return window.L.divIcon({ className: 'cc-vpin', html: svg, iconSize: [40, 52], iconAnchor: [20, 50], popupAnchor: [0, -46] });
};

/** Landing page: every facility with a machine. */
export async function initPublicMap(el) {
    const map = createMap(el, { zoomControl: true, scrollWheelZoom: false });
    addTiles(map);
    map.setView([39.5, -98.35], 4);
    try {
        const res = await fetch(el.dataset.src, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        const pts = data.machines.map((m) => {
            window.L.marker([m.lat, m.lng], { icon: pin(m.open), title: m.facility })
                .addTo(map)
                .bindPopup(`<b>${m.facility}</b><br>${m.city}<br><span style="font-family:monospace">${m.serial}</span>`);
            return [m.lat, m.lng];
        });
        if (pts.length) map.fitBounds(pts, { padding: [30, 30], maxZoom: 9 });
    } catch (e) {
        el.dataset.failed = '1';
    }
    return map;
}

/** Dashboard: one pin at the machine's facility, zoomed to street level. */
export function initFacilityMap(el) {
    const lat = Number(el.dataset.lat);
    const lng = Number(el.dataset.lng);
    const map = createMap(el, { zoomControl: true, scrollWheelZoom: false });
    addTiles(map);
    map.setView([lat, lng], 16);
    window.L.marker([lat, lng], { icon: pin(false), title: el.dataset.name }).addTo(map);
    return map;
}

// Leaflet maps. Tiles come from this server's own cache; there is no paid map service.
import { createMap } from './leaflet-setup.js';
import { addTiles } from './tiles.js';
import { colors } from './tokens.js';

const pin = (open) => window.L.divIcon({
    className: '',
    html: `<span style="display:block;width:14px;height:14px;border:2px solid ${colors.white};background:${open ? colors.accent : colors.black}"></span>`,
    iconSize: [14, 14],
    iconAnchor: [7, 7],
});

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

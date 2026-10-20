// Map tiles come from this server's own cache (/map/tiles/...), which fetches each tile once and keeps it on disk.
// If the server cannot supply one, that tile alone is tried straight from the public tile server before giving up.
const OWN = '/map/tiles/{z}/{x}/{y}.png';
const DIRECT = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

export function addTiles(map, { onFail } = {}) {
    const L = window.L;
    const layer = L.tileLayer(OWN, {
        maxZoom: 17,
        // Keep a ring of tiles around the view and load while panning, so moves feel instant.
        keepBuffer: 4,
        updateWhenIdle: false,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(map);

    layer.on('tileerror', (event) => {
        const tile = event.tile;
        if (!tile.dataset.direct) {
            tile.dataset.direct = '1';
            tile.src = DIRECT.replace('{z}', event.coords.z).replace('{x}', event.coords.x).replace('{y}', event.coords.y);
            return;
        }
        onFail?.();
    });

    return layer;
}

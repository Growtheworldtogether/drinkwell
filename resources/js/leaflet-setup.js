// Shared Leaflet setup. Every map in the app is created through createMap().
const L = window.L;

/**
 * Creates a map with the app defaults:
 * - `leaflet-touch` is always set, so controls render flat (Leaflet removes its control shade under that
 *   class) and resources/css/app.css sizes them for fingers.
 * - On touch screens one finger scrolls the page and two fingers move the map (DESIGN.md section 8).
 * - No animation between positions.
 */
export function createMap(element, options = {}) {
    const map = L.map(element, {
        dragging: !L.Browser.touch,
        tap: false,
        zoomSnap: 0.5,
        zoomAnimation: false,
        markerZoomAnimation: false,
        ...options,
    });
    map.getContainer().classList.add('leaflet-touch');

    return map;
}

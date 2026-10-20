import '@fontsource/geist-sans/400.css';
import '@fontsource/geist-sans/500.css';
import '@fontsource/geist-sans/600.css';
import '@fontsource/geist-mono/400.css';
import '@fontsource/geist-mono/500.css';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
window.Chart = Chart;
window.L = L;
import brandingForm from './branding-form.js';
import yieldCalculator from './calculator.js';
import { initHub } from './hub.js';
import { initFacilityMap, initPublicMap } from './map.js';

// Alpine components are registered before Livewire starts Alpine (Livewire 4 bundles it).
Alpine.data('brandingForm', brandingForm);
Alpine.data('yieldCalculator', yieldCalculator);

const publicMap = document.querySelector('[data-public-map]');
if (publicMap) initPublicMap(publicMap);
const facility = document.querySelector('[data-facility-map]');
if (facility) initFacilityMap(facility);
const hub = document.querySelector('[data-hub]');
if (hub) initHub(hub);

Livewire.start();

'use strict';


const EF_DB = {

    // ── BAHAN BAKAR STASIONER & BERGERAK (kgCO₂e/Liter kecuali disebutkan) ──
    fuel: {
        solar_cn53:   { ef: 72.85, unit: 'Ton CO2/TJ',  ref: '' },
        solar_cn51:   { ef: 72.93, unit: 'Ton CO2/TJ',  ref: '' },
        solar_cn48:   { ef: 73.28, unit: 'Ton CO2/TJ',  ref: '' },
        diesel:       { ef: 74.52, unit: 'Ton CO2/TJ',  ref: '' },
        ron98:        { ef: 68.91, unit: 'Ton CO2/TJ',  ref: '' },
        ron92:        { ef: 69.04, unit: 'Ton CO2/TJ',  ref: '' },
        ron90:        { ef: 69.29 , unit: 'Ton CO2/TJ', ref: '' },
        ron88:        { ef: 69.67, unit: 'Ton CO2/TJ',  ref: '' },
        lpg:          { ef: 65.41, unit: 'kgCO₂e/kg', ref: '' },
        cng:          { ef: 56.1,  unit: 'kgCO₂e/kg', ref: '' },
        avtur:        { ef: 72.36, unit: 'Ton CO2/TJ',  ref: '' },
        kerosene:     { ef: 72.43, unit: 'Ton CO2/TJ',  ref: '' },
        coal:         { ef: 99.718, unit: 'kgCO₂e/GJ', ref: '' },
        naturalgas:   { ef: 57.600, unit: 'kgCO₂e/GJ', ref: '' },
        minyakbakar:   { ef: 77.90, unit: 'Ton CO2/TJ', ref: '' },
        // Distance-based (kgCO₂e/km) — IPCC 2006 Vol.2 T3.2.2
        car_petrol:   { ef: 0.210, unit: 'kgCO₂e/km', ref: '' },
        car_diesel:   { ef: 0.270, unit: 'kgCO₂e/km', ref: '' },
        motorcycle:   { ef: 0.113, unit: 'kgCO₂e/km', ref: '' },
    },

    // ── LISTRIK PLN (kgCO₂e/kWh) ─────────────────────────────────────────────
    // Sumber: MEMR Permen 10/2022 
    grid: {
        jawa_bali:   { ef: 0.80, display: '0,80', unit: 'kgCO₂e/kWh', ref: 'Grid Jawa-Bali' },
        // Household (per kWh)
        household:   { ef: 0.80, display: '0,80', unit: 'kgCO₂e/kWh', ref: 'Grid Jawa-Bali' },
    },

    // ── TRANSPORTASI PENUMPANG (kgCO₂e/pax·km) ───────────────────────────────
    transit: {
        flight_dom:       { ef: 0.1330, unit: 'kgCO₂e/pax·km', ref: 'Domestik' },
        flight_int_short: { ef: 0.1530, unit: 'kgCO₂e/pax·km', ref: 'Intl <3700km' },
        flight_int_long:  { ef: 0.1950, unit: 'kgCO₂e/pax·km', ref: 'Intl >3700km' },
        train:            { ef: 0.0370, unit: 'kgCO₂e/pax·km', ref: '' },
        bus:              { ef: 0.0890, unit: 'kgCO₂e/pax·km', ref: '' },
        // Flight class multipliers for company (base = economy pax·km)
        economy:  { ef: 1, unit: 'kgCO₂e/pax·km', ref: 'Economy class' },
        business: { ef: 1.5, unit: 'kgCO₂e/pax·km', ref: ' Business class (2× economy)' },
        first:    { ef: 2, unit: 'kgCO₂e/pax·km', ref: 'First class (3× economy)' },
    },

    // ── KERETA API (kgCO₂e/pax·km) ───────────────────────────────────────────
    train: {
        ekonomi:     { ef: 0.01219, unit: 'kgCO₂e/pax·km', ref: 'KAI' },
        bisnis:      { ef: 0.01308, unit: 'kgCO₂e/pax·km', ref: 'KAI' },
    },

    // ── HOTEL (kgCO₂e/kamar·malam) ───────────────────────────────────────────
    hotel: { ef: 49.37, unit: '' },

    // ── PANGAN (kgCO₂e/kg) ───────────────────────────────────────────────────
    food: {
        beef:    { ef: 209.9, unit: 'kgCO₂e/kg', ref: '' },
        poultry: { ef:  20.1, unit: 'kgCO₂e/kg', ref: '' },
        fish:    { ef:  26.5, unit: 'kgCO₂e/kg', ref: '' },
        veg:     { ef:  1, unit: 'kgCO₂e/kg', ref: '' },
        rice:    { ef:  8.8, unit: 'kgCO₂e/kg', ref: '' },
        egg:     { ef:  8.4, unit: 'kgCO₂e/kg', ref: '' },
        fruits:  { ef:  2.9, unit: 'kgCO₂e/kg', ref: '' },
        milk:    { ef:  4.8, unit: 'kgCO₂e/kg', ref: '' },
    },

    // ── SAMPAH & AIR ──────────────────────────────────────────────────────────
    waste: { ef: 0.4,  unit: 'kgCO₂e/kg', ref: '' },
    water: { ef: 0.149, unit: 'kgCO₂e/m³', ref: '' },
    limbah: { ef: 0.272, unit: 'kgCO₂e/m³', ref: '' },

    // ── KENDARAAN PRIBADI (distance-based, kgCO₂e/km) ────────────────────────
    vehicle: {
        car_petrol: { ron98: 68.91, ron92: 69.04, ron90: 69.29, ron88: 69.67},
        car_diesel: { ron98: 68.91, ron92: 69.04, ron90: 69.29, ron88: 69.67, diesel: 74.52 },
        motorcycle: { ron98: 68.91, ron92: 69.04, ron90: 69.29, ron88: 69.67,  },
    },
};

// ── EF lookup helpers ──────────────────────────────────────────────────────────
function getFuelEF(key) { return EF_DB.fuel[key] || null; }
function getGridEF(key) { return EF_DB.grid[key] || null; }
function getTransitEF(key) { return EF_DB.transit[key] || null; }
function getTrainEF(key)   { return EF_DB.train[key] || null; }
function getFoodEF(key)    { return EF_DB.food[key] || null; }

const AIRPORT_API_URL = '/api/airports';
const AIRPORT_LOOKUP = {
    byCode: new Map(),
    byName: new Map(),
    byDisplay: new Map(),
};
const AIRPORT_QUERY_CACHE = new Map();

function rememberAirport(airport) {
    const code = String(airport.iata_code || '').trim().toUpperCase();
    const name = String(airport.name || '').trim();
    if (!code || !name) return null;

    const normalisedAirport = {
        ...airport,
        iata_code: code,
        name,
        latitude_deg: parseFloat(airport.latitude_deg),
        longitude_deg: parseFloat(airport.longitude_deg),
    };
    const display = `${code} - ${name}`;

    AIRPORT_LOOKUP.byCode.set(code, normalisedAirport);
    AIRPORT_LOOKUP.byName.set(name.toLowerCase(), normalisedAirport);
    AIRPORT_LOOKUP.byDisplay.set(display.toLowerCase(), normalisedAirport);

    return normalisedAirport;
}

function renderAirportOptions(airports) {
    const datalist = document.getElementById('airport-options');
    if (!datalist) return;

    datalist.innerHTML = airports
        .map((airport) => {
            const display = airport.display || `${airport.iata_code} - ${airport.name}`;
            return `<option value="${escapeHtml(display)}"></option>`;
        })
        .join('');
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

async function fetchAirports(search) {
    const query = String(search || '').trim();
    if (query.length < 2) return [];

    const cacheKey = query.toLowerCase();
    if (AIRPORT_QUERY_CACHE.has(cacheKey)) {
        return AIRPORT_QUERY_CACHE.get(cacheKey);
    }

    const url = `${AIRPORT_API_URL}?search=${encodeURIComponent(query)}&limit=12`;
    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!response.ok) return [];

    const airports = await response.json();
    const normalisedAirports = Array.isArray(airports)
        ? airports.map(rememberAirport).filter(Boolean)
        : [];

    AIRPORT_QUERY_CACHE.set(cacheKey, normalisedAirports);
    return normalisedAirports;
}

function scheduleAirportSearch(input) {
    if (!input) return;

    const query = String(input.value || '').trim();
    if (query.length < 2) return;
    if (input.dataset.airportSearchQuery === query) return;

    clearTimeout(input._airportSearchTimer);
    input._airportSearchTimer = setTimeout(() => {
        fetchAirports(query)
            .then((airports) => {
                input.dataset.airportSearchQuery = query;
                renderAirportOptions(airports);
                updateFlightRoute(input);
                calcLive();
            })
            .catch(() => {
                input.dataset.airportSearchQuery = '';
            });
    }, 250);
}

function findAirport(value) {
    const raw = String(value || '').trim();
    if (!raw) return null;

    const code = raw.toUpperCase();
    if (AIRPORT_LOOKUP.byCode.has(code)) return AIRPORT_LOOKUP.byCode.get(code);

    const lower = raw.toLowerCase();
    if (AIRPORT_LOOKUP.byName.has(lower)) return AIRPORT_LOOKUP.byName.get(lower);
    if (AIRPORT_LOOKUP.byDisplay.has(lower)) return AIRPORT_LOOKUP.byDisplay.get(lower);

    const codePrefix = raw.split('-')[0]?.trim().toUpperCase();
    if (AIRPORT_LOOKUP.byCode.has(codePrefix)) return AIRPORT_LOOKUP.byCode.get(codePrefix);

    return null;
}

async function normaliseAirportField(input) {
    if (!input) return null;

    let airport = findAirport(input.value);
    if (!airport) {
        await fetchAirports(input.value).catch(() => []);
        airport = findAirport(input.value);
    }

    if (airport) {
        input.value = airport.iata_code;
        updateFlightRoute(input);
        calcLive();
    }

    return airport;
}

function normaliseAllAirportFields() {
    return Promise.all([...document.querySelectorAll('.airport-input')].map(normaliseAirportField));
}

function haversineKm(origin, destination) {
    const toRad = (deg) => deg * Math.PI / 180;
    const lat1 = toRad(origin.latitude_deg);
    const lat2 = toRad(destination.latitude_deg);
    const dLat = toRad(destination.latitude_deg - origin.latitude_deg);
    const dLon = toRad(destination.longitude_deg - origin.longitude_deg);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;

    return 6371.0088 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function updateFlightRoute(inputOrRow) {
    const row = inputOrRow?.classList?.contains('entry-row')
        ? inputOrRow
        : inputOrRow?.closest?.('.entry-row');
    if (!row) return 0;

    const group = row.dataset.group;
    const isTransitFlight = group === 'p_transit'
        && row.querySelector('[name="p_transit_mode[]"]')?.value === 'plane';
    const originInput = row.querySelector(`[name="${group}_origin[]"]`);
    const destinationInput = row.querySelector(`[name="${group}_destination[]"]`);
    const distanceInput = row.querySelector(`[name="${group}_km[]"]`);
    const hint = row.querySelector('.flight-distance-hint');
    const origin = findAirport(originInput?.value);
    const destination = findAirport(destinationInput?.value);

    if (!distanceInput) return 0;
    if (group === 'p_transit' && !isTransitFlight) return parseFloat(distanceInput.value) || 0;

    if (!origin || !destination) {
        scheduleAirportSearch(originInput);
        scheduleAirportSearch(destinationInput);
        distanceInput.value = '';
        if (hint) hint.textContent = 'Pilih asal dan tujuan bandara';
        return 0;
    }

    if (origin.iata_code === destination.iata_code) {
        distanceInput.value = '0';
        if (hint) hint.textContent = `${origin.iata_code} ke ${destination.iata_code}`;
        return 0;
    }

    const distance = haversineKm(origin, destination);
    distanceInput.value = distance.toFixed(2);
    if (hint) hint.textContent = `${origin.iata_code} ke ${destination.iata_code}`;

    return distance;
}

function updateAllFlightRoutes() {
    document.querySelectorAll('[data-group="p_transit"], [data-group="p_flight"], [data-group="c_flight"]').forEach(updateFlightRoute);
}

function updateTransitModeFields(modeSelectOrRow) {
    const row = modeSelectOrRow?.classList?.contains('entry-row')
        ? modeSelectOrRow
        : modeSelectOrRow?.closest?.('.entry-row');
    if (!row || row.dataset.group !== 'p_transit') return;

    const isPlane = row.querySelector('[name="p_transit_mode[]"]')?.value === 'plane';
    const distanceInput = row.querySelector('[name="p_transit_km[]"]');
    const label = row.querySelector('.transit-distance-label');
    const unit = row.querySelector('.transit-distance-unit');
    const hint = row.querySelector('.flight-distance-hint');

    row.querySelectorAll('.transit-flight-only').forEach((field) => {
        field.hidden = !isPlane;
    });

    if (distanceInput) {
        distanceInput.readOnly = isPlane;
        distanceInput.classList.toggle('flight-distance', isPlane);
        if (!isPlane && distanceInput.value === '0') distanceInput.value = '';
    }
    if (label) label.textContent = isPlane ? 'Jarak Penerbangan' : 'Jarak Bulanan';
    if (unit) unit.textContent = isPlane ? 'km' : 'km/bln';
    if (hint && !isPlane) hint.textContent = 'Pilih asal dan tujuan bandara';

    if (isPlane) updateFlightRoute(row);
}

function updateAllTransitModeFields() {
    document.querySelectorAll('[data-group="p_transit"]').forEach(updateTransitModeFields);
}


function switchTab(prefix, step, total) {
    for (let i = 1; i <= total; i++) {
        const pane = document.getElementById(`${prefix}-step-${i}`);
        if (pane) {
            const isTarget = i === step;
            pane.classList.toggle('active', isTarget);
            pane.hidden = !isTarget;
        }
    }

    for (let i = 1; i <= total; i++) {
        const isTarget = i === step;
        document.querySelectorAll(`[data-step="${prefix}${i}"]`).forEach(b => {
            b.classList.toggle('active', isTarget);
            b.setAttribute('aria-selected', isTarget ? 'true' : 'false');
            b.tabIndex = isTarget ? 0 : -1;
        });
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function switchSubTab(group, key) {
    document.querySelectorAll(`.sub-tab-pane[id^="${group}-"]`).forEach(p => {
        const isTarget = p.id === `${group}-${key}`;
        p.classList.toggle('active', isTarget);
        p.hidden = !isTarget;
    });
    const target = document.getElementById(`${group}-${key}`);
    if (target) {
        target.classList.add('active');
        target.hidden = false;
    }

    document.querySelectorAll(`.sub-tab-btn[data-sub-tab-group="${group}"]`).forEach((button) => {
        const isTarget = button.dataset.subTabKey === key;
        button.classList.toggle('active', isTarget);
        button.setAttribute('aria-selected', isTarget ? 'true' : 'false');
        button.tabIndex = isTarget ? 0 : -1;
    });
}

function switchMethod(method) {
    document.getElementById('method-fuel')?.classList.remove('active');
    document.getElementById('method-dist')?.classList.remove('active');
    document.getElementById(`method-${method}`)?.classList.add('active');
    document.getElementById('method-fuel')?.toggleAttribute('hidden', method !== 'fuel');
    document.getElementById('method-dist')?.toggleAttribute('hidden', method !== 'dist');

    document.getElementById('methodFuelBtn')?.classList.remove('active');
    document.getElementById('methodDistBtn')?.classList.remove('active');
    const fuelBtn = document.getElementById('methodFuelBtn');
    const distBtn = document.getElementById('methodDistBtn');
    const activeBtn = method === 'fuel' ? fuelBtn : distBtn;

    [fuelBtn, distBtn].forEach((btn) => {
        if (!btn) return;
        const isActive = btn === activeBtn;
        btn.classList.toggle('active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        btn.tabIndex = isActive ? 0 : -1;
    });
}

// ═══════════════════════════════════════════════════════════════════════════════
// MODULE ACCORDION
// ═══════════════════════════════════════════════════════════════════════════════

function toggleModule(id) {
    const content = document.getElementById(`${id}-content`);
    const header  = content?.previousElementSibling;
    const icon    = header?.querySelector('.module-toggle');
    if (!content) return;

    const isOpen = content.classList.contains('open');
    content.classList.toggle('open', !isOpen);
    content.hidden = isOpen;
    header?.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
    icon?.classList.toggle('open', !isOpen);
}

function enhanceCalculatorAccessibility() {
    document.querySelectorAll('.module-header[onclick]').forEach((header) => {
        const match = header.getAttribute('onclick')?.match(/toggleModule\('([^']+)'\)/);
        if (!match) return;

        const moduleId = match[1];
        const button = document.createElement('button');
        button.type = 'button';
        button.className = header.className;
        button.dataset.moduleToggle = moduleId;
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', `${moduleId}-content`);
        button.innerHTML = header.innerHTML;
        header.replaceWith(button);
    });

    document.querySelectorAll('[data-module-toggle]').forEach((button) => {
        button.addEventListener('click', () => toggleModule(button.dataset.moduleToggle));
    });

    document.querySelectorAll('.module-content[id$="-content"]').forEach((content) => {
        content.hidden = !content.classList.contains('open');
    });

    document.querySelectorAll('.step-tab-nav').forEach((nav) => {
        nav.setAttribute('role', 'tablist');
        nav.querySelectorAll('.step-tab-btn').forEach((button) => {
            const step = button.dataset.step || '';
            const prefix = step.charAt(0);
            const number = step.slice(1);
            const pane = document.getElementById(`${prefix}-step-${number}`);

            button.type = 'button';
            button.setAttribute('role', 'tab');
            button.id ||= `${step}-tab`;
            if (pane) {
                button.setAttribute('aria-controls', pane.id);
                pane.setAttribute('role', 'tabpanel');
                pane.setAttribute('aria-labelledby', button.id);
                pane.hidden = !pane.classList.contains('active');
            }
            button.setAttribute('aria-selected', button.classList.contains('active') ? 'true' : 'false');
            button.tabIndex = button.classList.contains('active') ? 0 : -1;
            button.addEventListener('click', () => switchTab(prefix, Number(number), nav.querySelectorAll('.step-tab-btn').length));
        });

        nav.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            const tabs = [...nav.querySelectorAll('.step-tab-btn')];
            const current = tabs.indexOf(document.activeElement);
            if (current === -1) return;

            event.preventDefault();
            const nextIndex = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? tabs.length - 1
                    : (current + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            tabs[nextIndex].click();
        });
    });

    document.querySelectorAll('.sub-step-tabs').forEach((nav) => {
        nav.setAttribute('role', 'tablist');
        nav.querySelectorAll('.sub-tab-btn').forEach((button) => {
            const group = button.dataset.subTabGroup;
            const key = button.dataset.subTabKey;
            if (!group || !key) return;

            const panelId = `${group}-${key}`;
            const panel = document.getElementById(panelId);
            button.type = 'button';
            button.setAttribute('role', 'tab');
            button.id ||= `tab-${panelId}`;
            button.setAttribute('aria-controls', panelId);
            button.setAttribute('aria-selected', button.classList.contains('active') ? 'true' : 'false');
            button.tabIndex = button.classList.contains('active') ? 0 : -1;
            if (panel) {
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', button.id);
                panel.hidden = !panel.classList.contains('active');
            }
            button.addEventListener('click', () => switchSubTab(group, key));
        });

        nav.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            const tabs = [...nav.querySelectorAll('.sub-tab-btn')];
            const current = tabs.indexOf(document.activeElement);
            if (current === -1) return;

            event.preventDefault();
            const nextIndex = event.key === 'Home'
                ? 0
                : event.key === 'End'
                    ? tabs.length - 1
                    : (current + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            tabs[nextIndex].click();
        });
    });

    const methodButtons = [
        ['methodFuelBtn', 'method-fuel'],
        ['methodDistBtn', 'method-dist'],
    ];
    methodButtons.forEach(([buttonId, panelId]) => {
        const button = document.getElementById(buttonId);
        const panel = document.getElementById(panelId);
        if (!button || !panel) return;

        button.type = 'button';
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-controls', panelId);
        button.setAttribute('aria-selected', button.classList.contains('active') ? 'true' : 'false');
        button.tabIndex = button.classList.contains('active') ? 0 : -1;
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', buttonId);
        panel.hidden = !panel.classList.contains('active');
    });

    document.querySelectorAll('[data-method]').forEach((button) => {
        button.addEventListener('click', () => switchMethod(button.dataset.method));
    });
}


const ROW_TEMPLATES = {};

document.addEventListener('DOMContentLoaded', () => {
    organisePersonalCalculator();

    document.querySelectorAll('[data-group]').forEach(row => {
        const g = row.dataset.group;
        if (!ROW_TEMPLATES[g]) ROW_TEMPLATES[g] = row.outerHTML;
    });
    initAll();
});

function organisePersonalCalculator() {
    const scope2Slot = document.getElementById('personal-scope2-modules');
    const scope3Slot = document.getElementById('personal-scope3-modules');
    const electricityModule = document.getElementById('p_electricity-content')?.closest('.calc-module');
    const transitModule = document.getElementById('p_transit-content')?.closest('.calc-module');
    const personalFlightModule = document.getElementById('p_flight-content')?.closest('.calc-module');

    if (scope2Slot && electricityModule) {
        scope2Slot.appendChild(electricityModule);
    }
    if (scope3Slot && transitModule) {
        scope3Slot.appendChild(transitModule);
    }
    if (personalFlightModule) {
        personalFlightModule.remove();
    }
}

function addRow(group) {
    const container = document.getElementById(`rows_${group}`);
    if (!container || !ROW_TEMPLATES[group]) return;

    const tmp  = document.createElement('div');
    tmp.innerHTML = ROW_TEMPLATES[group];
    const newRow = tmp.firstElementChild;

    newRow.querySelectorAll('input').forEach(i => i.value = '');
    newRow.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    newRow.querySelectorAll('.flight-distance-hint').forEach(h => h.textContent = 'Pilih asal dan tujuan bandara');
    newRow.querySelectorAll('.ef-chip[data-ef-group]').forEach(c => c.textContent = '—');

    container.appendChild(newRow);
    updateTransitModeFields(newRow);
    newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function removeRow(btn) {
    const row = btn.closest('.entry-row');
    const container = row?.parentElement;
    if (!container) return;
    if (container.querySelectorAll('.entry-row').length <= 1) {
        row.querySelectorAll('input').forEach(i => i.value = '');
        row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        row.querySelectorAll('.flight-distance-hint').forEach(h => h.textContent = 'Pilih asal dan tujuan bandara');
        row.querySelectorAll('.ef-chip[data-ef-group]').forEach(c => c.textContent = '—');
        updateTransitModeFields(row);
    } else {
        row.remove();
    }
    calcLive();
}

// ═══════════════════════════════════════════════════════════════════════════════
// EF CHIP UPDATER
// ═══════════════════════════════════════════════════════════════════════════════

function updateEfChip(selectEl, group) {
    const val  = selectEl.value;
    const row  = selectEl.closest('.entry-row');
    const chip = row?.querySelector(`.ef-chip[data-ef-group="${group}"]`);
    if (!chip) return;

    let efData = null;

    if (['c_stat','c_mobile_fuel'].includes(group)) efData = getFuelEF(val);
    else if (group === 'c_mobile_dist')              efData = getFuelEF(val);
    else if (group === 'c_elec')                     efData = getGridEF(val);
    else if (group === 'c_flight')                   efData = EF_DB.transit[val] || null;
    else if (group === 'c_train')                    efData = getTrainEF(val);
    else if (group === 'p_energy')                   efData = getFuelEF(val);
    else if (group === 'p_food')                     efData = getFoodEF(val);
    else if (group === 'p_flight')                   efData = getTransitEF(val);
    else if (group === 'p_transit') {
        const mode = row?.querySelector('[name="p_transit_mode[]"]')?.value;
        const cabinClass = row?.querySelector('[name="p_transit_class[]"]')?.value || 'economy';
        efData = getTransitEF(mode === 'plane' ? cabinClass : mode);
    }

    if (efData) {
        chip.innerHTML = `${efData.display || efData.ef} ${efData.unit}<br><small>${efData.ref}</small>`;
    } else {
        chip.textContent = '—';
    }
}

function updateVehicleFuel(typeSelect) {
    const fuelSel = typeSelect.closest('.entry-fields')?.querySelector('[name="p_vehicle_fuel[]"]');
    if (!fuelSel) return;
    const t = typeSelect.value;
    if (t === 'car_diesel') {
        fuelSel.querySelector('option[value="ron98"]') && (fuelSel.value = 'ron88');
    }
}

function calcLive() {
    updateAllFlightRoutes();

    const r = calcPersonalAll();
    setPreview('p_energy',      r.energy_rt);
    setPreview('p_vehicle',     r.vehicle);
    setPreview('p_electricity', r.electricity);
    setPreview('p_transit',     r.transit);
    setPreview('p_flight',      r.flight);
    setPreview('p_food',        r.food);
    setPreview('p_water',       r.water);
    setPreview('p_waste',       r.waste);
    setSub('p_energy',      r.energy_rt);
    setSub('p_electricity', r.electricity);
    setSub('p_vehicle',     r.vehicle);
    setSub('p_transit',     r.transit);
    setSub('p_flight',      r.flight);
    setSub('p_food',        r.food);
    setSub('p_water',       r.water);
    setSub('p_waste',       r.waste);

    const c = calcCompanyAll();
    setPreview('c_stat',   c.stat);
    setPreview('c_mobile', c.mobile);
    setPreview('c_elec',   c.elec);
    setPreview('c_flight', c.flight);
    setPreview('c_hotel',  c.hotel);
    setPreview('c_train',  c.train);
    setSub('c_stat',   c.stat);
    setSub('c_mobile', c.mobile);
    setSub('c_elec',   c.elec);
    setSub('c_flight', c.flight);
    setSub('c_hotel',  c.hotel);
    setSub('c_train',  c.train);
}

function setPreview(id, val) {
    const el = document.getElementById(`prev_${id}`);
    if (!el) return;
    const num  = val >= 1000
        ? (val / 1000).toFixed(2) + ' ton'
        : val.toFixed(1) + ' kg';
    el.childNodes[0].textContent = num + ' ';
}

function setSub(id, val) {
    const el = document.getElementById(`sub_${id}`);
    if (!el) return;
    el.textContent = val >= 1000
        ? (val / 1000).toFixed(3) + ' ton'
        : val.toFixed(2);
}

function v(id) { return parseFloat(document.getElementById(id)?.value) || 0; }
function rows(name) {
    return [...document.querySelectorAll(`[name="${name}[]"]`)];
}
function rowPairs(nameA, nameB) {
    const a = rows(nameA), b = rows(nameB);
    return a.map((el, i) => [el.value, parseFloat(b[i]?.value) || 0]);
}
function rowTriples(nameA, nameB, nameC) {
    const a = rows(nameA), b = rows(nameB), c = rows(nameC);
    return a.map((el, i) => [el.value, parseFloat(b[i]?.value)||0, parseFloat(c[i]?.value)||0]);
}

function calcPersonalAll() {
    let energy_rt = 0;
    rowPairs('p_energy_fuel','p_energy_qty').forEach(([fuel, qty]) => {
        const ef = getFuelEF(fuel);
        if (ef) energy_rt += qty * ef.ef;
    });

    let vehicle = 0;
    rowTriples('p_vehicle_type','p_vehicle_fuel','p_vehicle_km').forEach(([type, fuel, km]) => {
        const vEF = EF_DB.vehicle[type];
        if (vEF) {
            const efVal = vEF[fuel] !== undefined ? vEF[fuel] : (vEF.ron88 || 0);
            vehicle += km * efVal;
        }
    });

    const electricity = v('p_elec_kwh') * EF_DB.grid.household.ef;

    let transit = 0;
    document.querySelectorAll('[data-group="p_transit"]').forEach((row) => {
        const mode = row.querySelector('[name="p_transit_mode[]"]')?.value;
        const pax = parseFloat(row.querySelector('[name="p_transit_pax[]"]')?.value) || 0;
        const km = parseFloat(row.querySelector('[name="p_transit_km[]"]')?.value) || 0;
        const cabinClass = row.querySelector('[name="p_transit_class[]"]')?.value || 'economy';
        const ef = getTransitEF(mode === 'plane' ? cabinClass : mode);
        if (!ef) return;

        transit += mode === 'plane'
            ? pax * km * ef.ef
            : pax * km * 12 * ef.ef;
    });

    const flight = 0;

    let food = 0;
    rowPairs('p_food_type','p_food_kg').forEach(([type, kg]) => {
        const ef = getFoodEF(type);
        if (ef) food += kg * 12 * ef.ef;
    });

    const water = v('p_water_m3') * 12 * EF_DB.water.ef;
    const waste = v('p_waste_kg') * 12 * EF_DB.waste.ef;

    return { energy_rt, vehicle, electricity, transit, flight, food, water, waste };
}

function calcCompanyAll() {
    let stat = 0;
    rowPairs('c_stat_fuel','c_stat_qty').forEach(([fuel, qty]) => {
        const ef = getFuelEF(fuel);
        if (ef) stat += qty * ef.ef;
    });

    let mobileFuel = 0;
    rowPairs('c_mobile_fuel_type','c_mobile_fuel_qty').forEach(([fuel, qty]) => {
        const ef = getFuelEF(fuel);
        if (ef) mobileFuel += qty * ef.ef;
    });

    let mobileDist = 0;
    rowPairs('c_mobile_dist_fuel','c_mobile_dist_km').forEach(([fuel, km]) => {
        let ef_km = 0;
        if (['solar_cn53','solar_cn51','solar_cn48','diesel'].includes(fuel)) ef_km = 74.52;
        else if (['ron98','ron92','ron90','ron88'].includes(fuel)) ef_km = 0.210;
        mobileDist += km * ef_km;
    });

    const mobile = mobileFuel + mobileDist;

    let elec = 0;
    rowPairs('c_elec_src','c_elec_kwh').forEach(([src, kwh]) => {
        const ef = getGridEF(src);
        if (ef) elec += kwh * ef.ef;
    });

    let flight = 0;
    rowTriples('c_flight_class','c_flight_pax','c_flight_km').forEach(([cls, pax, km]) => {
        const ef = EF_DB.transit[cls];
        if (ef) flight += pax * km * ef.ef;
    });

    let hotel = 0;
    rowPairs('c_hotel_nights','c_hotel_rooms').forEach(([nights, rooms]) => {
        hotel += (parseFloat(nights)||0) * rooms * EF_DB.hotel.ef;
    });

    let train = 0;
    rowPairs('c_train_class','c_train_km').forEach(([cls, km]) => {
        const ef = getTrainEF(cls);
        if (ef) train += km * ef.ef;
    });

    return { stat, mobile, elec, flight, hotel, train };
}

function buildScopeDetails(mode) {
    const scopes = { scope1: [], scope2: [], scope3: [] };
    const add = (scope, source, activity, quantity, unit, factor, emission, attributes = {}) => {
        if (!(quantity > 0) || !(emission >= 0)) return;
        scopes[scope].push({
            source,
            activity,
            quantity,
            unit,
            emission_factor: factor,
            value_kg: emission,
            ...attributes
        });
    };
    const num = (el) => parseFloat(el?.value) || 0;
    const selectText = (el) => el?.selectedOptions?.[0]?.textContent?.trim() || el?.value || '';

    if (mode === 'personal') {
        document.querySelectorAll('[data-group="p_energy"]').forEach(row => {
            const fuel = row.querySelector('[name="p_energy_fuel[]"]')?.value;
            const qty = num(row.querySelector('[name="p_energy_qty[]"]'));
            const ef = getFuelEF(fuel);
            if (ef) add('scope1', 'Energi rumah tangga', selectText(row.querySelector('[name="p_energy_fuel[]"]')), qty, `${ef.unit.split('/').pop()}`, ef.ef, qty * ef.ef);
        });
        document.querySelectorAll('[data-group="p_vehicle"]').forEach(row => {
            const typeEl = row.querySelector('[name="p_vehicle_type[]"]');
            const fuelEl = row.querySelector('[name="p_vehicle_fuel[]"]');
            const type = typeEl?.value, fuel = fuelEl?.value, km = num(row.querySelector('[name="p_vehicle_km[]"]')) ;
            const factor = EF_DB.vehicle[type]?.[fuel];
            if (factor !== undefined) add('scope1', selectText(typeEl), `Bahan bakar: ${selectText(fuelEl)}`, km, 'km / bulan', factor, km * factor);
        });
        const kwh = num(document.getElementById('p_elec_kwh'));
        add('scope2', 'Listrik rumah tangga (Grid Jawa-Bali)', 'Listrik', kwh, 'kWh/bulan', EF_DB.grid.household.ef, kwh * EF_DB.grid.household.ef);
        document.querySelectorAll('[data-group="p_transit"]').forEach(row => {
            const modeEl = row.querySelector('[name="p_transit_mode[]"]');
            const modeValue = modeEl?.value;
            const pax = num(row.querySelector('[name="p_transit_pax[]"]'));
            const km = num(row.querySelector('[name="p_transit_km[]"]'));
            const classEl = row.querySelector('[name="p_transit_class[]"]');
            const ef = getTransitEF(modeValue === 'plane' ? (classEl?.value || 'economy') : modeValue);
            const annualKm = km * pax * (modeValue === 'plane' ? 1 : 12);
            if (ef) add('scope3', 'Transportasi', `${selectText(modeEl)}${modeValue === 'plane' ? ` (${selectText(classEl)})` : ''}`, annualKm, 'penumpang-km / tahun', ef.ef, annualKm * ef.ef, {
                input_type: modeValue,
                transport_mode: modeValue,
                transport_class: modeValue === 'plane' ? classEl?.value || null : null,
                distance_km: km,
                origin: row.querySelector('[name="p_transit_origin[]"]')?.value || null,
                destination: row.querySelector('[name="p_transit_destination[]"]')?.value || null,
                passengers: pax
            });
        });
        document.querySelectorAll('[data-group="p_food"]').forEach(row => {
            const typeEl = row.querySelector('[name="p_food_type[]"]'), qty = num(row.querySelector('[name="p_food_kg[]"]')) * 12;
            const ef = getFoodEF(typeEl?.value);
            if (ef) add('scope3', 'Konsumsi makanan', selectText(typeEl), qty, 'kg / bulan', ef.ef, qty * ef.ef);
        });
        const water = num(document.getElementById('p_water_m3'));
        add('scope3', 'Penggunaan air', 'Air', water, 'm³ / bulan', EF_DB.water.ef, water * EF_DB.water.ef);
        const waste = num(document.getElementById('p_waste_kg'));
        add('scope3', 'Pengelolaan limbah', 'Sampah', waste, 'kg / bulan', EF_DB.waste.ef, waste * EF_DB.waste.ef);
    } else {
        document.querySelectorAll('[data-group="c_stat"]').forEach(row => {
            const fuelEl = row.querySelector('[name="c_stat_fuel[]"]'), qty = num(row.querySelector('[name="c_stat_qty[]"]'));
            const ef = getFuelEF(fuelEl?.value);
            if (ef) add('scope1', 'Pembakaran stasioner', selectText(fuelEl), qty, `${ef.unit.split('/').pop()} / bulan`, ef.ef, qty * ef.ef);
        });
        document.querySelectorAll('[data-group="c_mobile_fuel"]').forEach(row => {
            const fuelEl = row.querySelector('[name="c_mobile_fuel_type[]"]'), qty = num(row.querySelector('[name="c_mobile_fuel_qty[]"]'));
            const ef = getFuelEF(fuelEl?.value);
            if (ef) add('scope1', 'Kendaraan operasional (berdasarkan BBM)', selectText(fuelEl), qty, `${ef.unit.split('/').pop()} / bulan`, ef.ef, qty * ef.ef);
        });
        document.querySelectorAll('[data-group="c_mobile_dist"]').forEach(row => {
            const fuelEl = row.querySelector('[name="c_mobile_dist_fuel[]"]'), km = num(row.querySelector('[name="c_mobile_dist_km[]"]'));
            const fuel = fuelEl?.value;
            const ef = ['solar_cn53','solar_cn51','solar_cn48','diesel'].includes(fuel) ? .270 : (['ron98','ron92','ron90','ron88'].includes(fuel) ? .210 : 0);
            if (ef) add('scope1', 'Kendaraan operasional (berdasarkan jarak)', selectText(fuelEl), km, 'km / bulan', ef, km * ef);
        });
        document.querySelectorAll('[data-group="c_elec"]').forEach(row => {
            const srcEl = row.querySelector('[name="c_elec_src[]"]'), qty = num(row.querySelector('[name="c_elec_kwh[]"]'));
            const ef = getGridEF(srcEl?.value);
            if (ef) add('scope2', 'Konsumsi listrik', selectText(srcEl), qty, 'kWh / bulan', ef.ef, qty * ef.ef);
        });
        document.querySelectorAll('[data-group="c_flight"]').forEach(row => {
            const clsEl = row.querySelector('[name="c_flight_class[]"]'), pax = num(row.querySelector('[name="c_flight_pax[]"]')), km = num(row.querySelector('[name="c_flight_km[]"]'));
            const ef = EF_DB.transit[clsEl?.value];
            if (ef) add('scope3', 'Penerbangan dinas', `${selectText(clsEl)}; ${pax} penumpang`, km * pax, 'penumpang-km / bulan', ef.ef, km * pax * ef.ef);
        });
        document.querySelectorAll('[data-group="c_hotel"]').forEach(row => {
            const nights = num(row.querySelector('[name="c_hotel_nights[]"]')), rooms = num(row.querySelector('[name="c_hotel_rooms[]"]')), qty = nights * rooms;
            add('scope3', 'Akomodasi hotel', `${rooms} kamar × ${nights} malam`, qty, 'kamar-malam / bulan', EF_DB.hotel.ef, qty * EF_DB.hotel.ef);
        });
        document.querySelectorAll('[data-group="c_train"]').forEach(row => {
            const clsEl = row.querySelector('[name="c_train_class[]"]'), km = num(row.querySelector('[name="c_train_km[]"]'));
            const ef = getTrainEF(clsEl?.value);
            if (ef) add('scope3', 'Perjalanan kereta', selectText(clsEl), km, 'penumpang-km / bulan', ef.ef, km * ef.ef);
        });
    }
    return scopes;
}

async function calculateAll(mode) {
    await normaliseAllAirportFields();
    updateAllFlightRoutes();
    
    let totalKg = 0;
    let details = {};

    if (mode === 'personal') {
        details = calcPersonalAll();
        totalKg = Object.values(details).reduce((s, v) => s + v, 0);
    } else {
        const c = calcCompanyAll();
        totalKg = c.stat + c.mobile + c.elec + c.flight + c.hotel + c.train;
        details = c;
    }

    if (totalKg <= 0) {
        alert('Silakan masukkan data konsumsi Anda terlebih dahulu sebelum menghitung!');
        return;
    }

    // ── HITUNG MASING-MASING SCOPE SECARA AKURAT ─────────────────
    const scope1Kg = mode === 'personal' ? (details.energy_rt + details.vehicle) : (details.stat + details.mobile);
    const scope2Kg = mode === 'personal' ? details.electricity : details.elec;
    const scope3Kg = mode === 'personal' ? ((details.transit || 0) + (details.flight || 0) + details.food + details.water + details.waste) : (details.flight + details.hotel + details.train);

    window.currentCalculationData = {
        mode: mode,
        total_kg: totalKg,
        scope1_kg: scope1Kg,
        scope2_kg: scope2Kg,
        scope3_kg: scope3Kg,
        details: details,
        scope_details: buildScopeDetails(mode)
    };

    // ── KIRIM DATA INI OTOMATIS KE CHATBOT CAMI ──────────────────
    if (window.CAMI_CHAT_ENDPOINT) {
        fetch(window.CAMI_CHAT_ENDPOINT, {
            method: 'POST', 
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.CAMI_CSRF_TOKEN,
            },
            body: JSON.stringify({
                message: 'hitung emisi',
                custom: {
                    type: 'calculation_request',
                    data: window.currentCalculationData
                }
            })
        }).catch(() => {}); 
    }

    if (mode === 'company') {
        renderCompanyResult(details, totalKg);
        switchTab('c', 4, 4);
        document.getElementById('result-box-company')?.scrollIntoView({ behavior: 'smooth' });
        return;
    }

    renderPersonalResult(details, totalKg);
    switchTab('p', 4, 4);
    document.getElementById('result-box')?.scrollIntoView({ behavior: 'smooth' });
}

function renderPersonalResult(cats, totalKg) {
    const box = document.getElementById('result-box');
    if (!box) return;
    box.style.display = 'block';

    const totalTon = totalKg / 1000;
    const scope1 = cats.energy_rt + cats.vehicle;
    const scope2 = cats.electricity;
    const transport = (cats.transit || 0) + (cats.flight || 0);
    const scope3 = transport + cats.food + cats.water + cats.waste;
    const items = [
        { label: 'Scope 1 — Energi Rumah Tangga', kg: cats.energy_rt },
        { label: 'Scope 1 — Kendaraan Pribadi', kg: cats.vehicle },
        { label: 'Scope 2 — Listrik', kg: cats.electricity },
        { label: 'Scope 3 - Transportasi Umum', kg: transport },
        { label: 'Scope 3 — Pangan', kg: cats.food },
        { label: 'Scope 3 — Air Bersih', kg: cats.water },
        { label: 'Scope 3 — Limbah', kg: cats.waste },
    ];

    let gridHTML = items.map((item) => {
        const ton = item.kg / 1000;
        return `<div class="result-item">
            <div class="result-label">${item.label}</div>
            <div class="result-value">${ton < 1 ? item.kg.toFixed(1) : ton.toFixed(3)}</div>
            <div class="result-unit">${ton < 1 ? 'kg CO₂e' : 'ton CO₂e'}</div>
        </div>`;
    }).join('');

    gridHTML += `<div class="result-item result-item-total">
        <div class="result-label">Total Emisi</div>
        <div class="result-value result-value-total">${totalTon.toFixed(3)}</div>
        <div class="result-unit">ton CO₂e/tahun</div>
    </div>`;

    const scope1Pct = totalKg > 0 ? ((scope1 / totalKg) * 100).toFixed(1) : 0;
    const scope2Pct = totalKg > 0 ? ((scope2 / totalKg) * 100).toFixed(1) : 0;
    const scope3Pct = totalKg > 0 ? ((scope3 / totalKg) * 100).toFixed(1) : 0;
    const trees = Math.round(totalTon / 0.022);
    const saveButton = window.CARBON_STORAGE_KEY
        ? `<button type="button" class="btn-calculate" data-save-calculation>
                <i class="fas fa-cloud-upload-alt"></i> Simpan Hasil ke Dashboard
           </button>`
        : `<a href="/register" class="btn-calculate result-register-link">
                <i class="fas fa-user-plus"></i> Masuk untuk Simpan Hasil
           </a>`;

    box.innerHTML = `
        <h3 class="result-title"><i class="fas fa-chart-bar"></i> Hasil Kalkulasi Emisi Individu</h3>
        <div class="result-grid">${gridHTML}</div>

        <div class="distribution-section">
            <div class="distribution-title">Distribusi Emisi per Scope (GHG Protocol)</div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 1</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope1" id="pbar_s1" style="width:0%"></div></div>
                <span class="distribution-pct">${scope1Pct}%</span>
            </div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 2</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope2" id="pbar_s2" style="width:0%"></div></div>
                <span class="distribution-pct">${scope2Pct}%</span>
            </div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 3</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope3" id="pbar_s3" style="width:0%"></div></div>
                <span class="distribution-pct">${scope3Pct}%</span>
            </div>
        </div>

        <div class="result-footer">
            <p class="result-info">
                Setara dengan <strong>${trees.toLocaleString('id-ID')} pohon</strong> yang harus ditanam untuk offset emisi ini selama 30 tahun.
            </p>
        </div>

        <div class="save-action">
            ${saveButton}
            <a href="/proyek" class="btn-calculate result-project-link">
                <i class="fas fa-seedling"></i> Cari Proyek Offset
            </a>
            <button class="btn-reset" type="button" data-reset-calculator>
                <i class="fas fa-redo"></i> Hitung Ulang
            </button>
        </div>`;

    requestAnimationFrame(() => {
        [
            ['pbar_s1', scope1Pct],
            ['pbar_s2', scope2Pct],
            ['pbar_s3', scope3Pct],
        ].forEach(([id, width], index) => {
            setTimeout(() => {
                const bar = document.getElementById(id);
                if (bar) bar.style.width = width + '%';
            }, 80 * (index + 1));
        });
    });
}

function renderCompanyResult(c, totalKg) {
    const box = document.getElementById('result-box-company');
    if (!box) return;
    const totalTon = totalKg / 1000;
    const scope1 = c.stat + c.mobile;
    const scope2 = c.elec;
    const scope3 = c.flight + c.hotel + c.train;

    const items = [
        { label: 'Scope 1 — Stasioner', kg: c.stat, cls: 'fill-scope1' },
        { label: 'Scope 1 — Bergerak',  kg: c.mobile, cls: 'fill-scope1' },
        { label: 'Scope 2 — Listrik',   kg: c.elec,   cls: 'fill-scope2' },
        { label: 'Scope 3 — Penerbangan Dinas', kg: c.flight, cls: 'fill-scope3' },
        { label: 'Scope 3 — Hotel',     kg: c.hotel,  cls: 'fill-scope3' },
        { label: 'Scope 3 — Kereta',    kg: c.train,  cls: 'fill-scope3' },
    ];

    let gridHTML = items.map(it => {
        const ton = it.kg / 1000;
        return `<div class="result-item">
            <div class="result-label">${it.label}</div>
            <div class="result-value">${ton < 1 ? it.kg.toFixed(1) : ton.toFixed(3)}</div>
            <div class="result-unit">${ton < 1 ? 'kg CO₂e' : 'ton CO₂e'}</div>
        </div>`;
    }).join('');
    gridHTML += `<div class="result-item result-item-total">
        <div class="result-label">Total Emisi</div>
        <div class="result-value result-value-total">${totalTon.toFixed(3)}</div>
        <div class="result-unit">ton CO₂e/tahun</div>
    </div>`;

    const s1pct = totalKg > 0 ? ((scope1 / totalKg) * 100).toFixed(1) : 0;
    const s2pct = totalKg > 0 ? ((scope2 / totalKg) * 100).toFixed(1) : 0;
    const s3pct = totalKg > 0 ? ((scope3 / totalKg) * 100).toFixed(1) : 0;
    const trees = Math.round(totalTon / 0.022);
    const saveButton = window.CARBON_STORAGE_KEY
        ? `<button type="button" class="btn-calculate" data-save-calculation>
                <i class="fas fa-cloud-upload-alt"></i> Simpan Hasil ke Dashboard
           </button>`
        : '';

    box.innerHTML = `
        <h3 class="result-title"><i class="fas fa-chart-bar"></i> Hasil Kalkulasi Emisi Perusahaan</h3>
        <div class="result-grid">${gridHTML}</div>

        <div class="distribution-section">
            <div class="distribution-title">Distribusi Emisi per Scope (GHG Protocol)</div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 1</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope1" id="cbar_s1" style="width:0%"></div></div>
                <span class="distribution-pct">${s1pct}%</span>
            </div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 2</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope2" id="cbar_s2" style="width:0%"></div></div>
                <span class="distribution-pct">${s2pct}%</span>
            </div>
            <div class="distribution-row">
                <span class="distribution-label">Scope 3</span>
                <div class="distribution-track"><div class="distribution-fill fill-scope3" id="cbar_s3" style="width:0%"></div></div>
                <span class="distribution-pct">${s3pct}%</span>
            </div>
        </div>

        <div class="result-footer">
            <p class="result-info">
                Setara dengan <strong>${trees.toLocaleString('id-ID')} pohon</strong> yang harus ditanam untuk offset emisi ini selama 30 tahun.
            </p>
        </div>

        <div class="save-action">
            ${saveButton}
            <a href="/proyek" class="btn-calculate" style="text-decoration:none; display:inline-flex; align-items:center; gap:.5rem;">
                <i class="fas fa-seedling"></i> Cari Proyek Offset
            </a>
            <button class="btn-reset" type="button" data-reset-calculator>
                <i class="fas fa-redo"></i> Hitung Ulang
            </button>
        </div>`;

    requestAnimationFrame(() => {
        setTimeout(() => {
            const bar = document.getElementById('cbar_s1');
            if (bar) bar.style.width = s1pct + '%';
        }, 80);
        setTimeout(() => {
            const bar = document.getElementById('cbar_s2');
            if (bar) bar.style.width = s2pct + '%';
        }, 160);
        setTimeout(() => {
            const bar = document.getElementById('cbar_s3');
            if (bar) bar.style.width = s3pct + '%';
        }, 240);
    });
}

function resetCalculator() {
    document.querySelectorAll('.input-field').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
    document.querySelectorAll('.flight-distance-hint').forEach(h => h.textContent = 'Pilih asal dan tujuan bandara');
    document.querySelectorAll('.ef-chip[data-ef-group]').forEach(c => c.textContent = '—');
    document.querySelectorAll('.preview-value').forEach(el => el.textContent = '0');
    updateAllTransitModeFields();

    const rBox = document.getElementById('result-box');
    if (rBox) rBox.style.display = 'none';

    const rComp = document.getElementById('result-box-company');
    if (rComp) rComp.innerHTML = '';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function initAll() {
    enhanceCalculatorAccessibility();

    document.querySelectorAll('select[name$="_fuel[]"], select[name$="_src[]"], select[name$="_mode[]"], select[name$="_type[]"], select[name$="_class[]"], select[name$="_fuel_type[]"], select[name$="_dist_fuel[]"]').forEach(sel => {
        sel.addEventListener('change', () => {
            updateTransitModeFields(sel);
            const grpAttr = sel.closest('.entry-row')?.querySelector('.ef-chip[data-ef-group]');
            if (grpAttr) {
                const g = grpAttr.dataset.efGroup;
                updateEfChip(sel, g);
            }
            calcLive();
        });
    });
    document.querySelectorAll('input[type=number]').forEach(inp => {
        inp.addEventListener('input', calcLive);
    });
    updateAllTransitModeFields();
}

window.saveCalculationToDatabase = function(e) {
    if (!window.currentCalculationData) {
        let rawEmisiText = document.getElementById('hasil-emisi')?.textContent?.trim() || '';
        
        if (!rawEmisiText || rawEmisiText.includes('0 ton CO₂e') || rawEmisiText === '0') {
            alert('Tidak ada data hasil perhitungan yang bisa disimpan. Mohon lakukan kalkulasi terlebih dahulu.');
            return;
        }

        let cleanValue = rawEmisiText.replace(/ton\sCO₂e/gi, '').replace(/kg\sCO₂e/gi, '').trim();
        let totalTon = parseFloat(cleanValue);
        let totalKg = totalTon * 1000;

        window.currentCalculationData = {
            mode: 'personal',
            total_kg: totalKg,
            details: {
                electricity: v('p_elec_kwh') * EF_DB.grid.household.ef,
                water: v('p_water_m3') * EF_DB.water.ef,
                waste: v('p_waste_kg') * EF_DB.waste.ef
            }
        };
    }

    const currentEvent = e || window.event;
    const btn = currentEvent?.target
        ? (currentEvent.target.closest('[data-save-calculation]') || currentEvent.target)
        : document.querySelector('[data-save-calculation]');
    
    if (btn && btn.style) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan ke Dashboard...';
    }

    fetch('/calculator/store', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(window.currentCalculationData)
    })
    .then(res => {
        if (!res.ok) throw new Error('Server bermasalah (' + res.status + ')');
        return res.json();
    })
    .then(res => {
        if (res.success) {
            const recommendationEl = document.getElementById('rekomendasi-proyek');
            if (recommendationEl && res.data && res.data.proyek) {
                const scoreLabel = (res.data.skor_rekomendasi !== null && res.data.skor_rekomendasi !== undefined)
                    ? ` (${Number(res.data.skor_rekomendasi).toFixed(0)}% cocok)`
                    : '';
                recommendationEl.textContent = res.data.proyek + scoreLabel;
                recommendationEl.title = Array.isArray(res.data.alasan_rekomendasi) ? res.data.alasan_rekomendasi.join(' ') : '';
            }
            
            if (btn && btn.style) {
                btn.innerHTML = '<i class="fas fa-check"></i> Berhasil Disimpan';
                btn.style.background = '#67C090';
                btn.style.borderColor = '#67C090';
            }

            const storageKey = window.CARBON_STORAGE_KEY || 'carbon_history_guest';
            let historyList = [];
            try {
                historyList = JSON.parse(localStorage.getItem(storageKey)) || [];
            } catch (err) {
                historyList = [];
            }

            const newHistoryItem = {
                id: Date.now(),
                date: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' }),
                total_kg: window.currentCalculationData ? window.currentCalculationData.total_kg : 0,
                biaya: res.data ? res.data.biaya : '-',
                proyek: res.data ? res.data.proyek : '-'
            };

            historyList.unshift(newHistoryItem);

            try {
                localStorage.setItem(storageKey, JSON.stringify(historyList));
            } catch (storageErr) {
                console.warn('LocalStorage error:', storageErr);
            }
            
            if (typeof renderHistoryContainer === 'function') {
                renderHistoryContainer();
            }

            alert('Selamat! Hasil kalkulasi karbon Anda berhasil disimpan ke database dashboard.');
        } else {
            alert('Gagal menyimpan ke database: ' + (res.message || 'Terjadi kesalahan saat memproses data.'));
            resetButton(btn);
        }
    })
    .catch((err) => {
        console.error('Error saat menyimpan kalkulasi:', err);
        alert('Terjadi kendala saat mencoba menghubungi server.');
        resetButton(btn);
    });
};

function resetButton(btn) {
    if (btn && btn.style) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Simpan Hasil ke Dashboard';
    }
}

function renderHistoryContainer() {
    let historyWrapper = document.getElementById('history-log-wrapper');
    const resultBox = document.getElementById('result-box');
    
    if (!resultBox) return;

    if (!historyWrapper) {
        historyWrapper = document.createElement('div');
        historyWrapper.id = 'history-log-wrapper';
        historyWrapper.style.cssText = 'margin-top: 25px; padding: 20px; background: #fff; border-radius: 16px; border: 2px solid #e9ecef; box-shadow: 0 4px 20px rgba(0,0,0,.08);';
        resultBox.parentNode.insertBefore(historyWrapper, resultBox.nextSibling);
    }

    const currentKey = window.CARBON_STORAGE_KEY || 'carbon_history_guest';
    let historyList = [];
    try {
        historyList = JSON.parse(localStorage.getItem(currentKey)) || [];
    } catch (e) {
        historyList = [];
    }

    if (historyList.length === 0) {
        historyWrapper.style.display = 'none';
        return;
    }

    historyWrapper.style.display = 'block';
    
    let htmlContent = `
        <h4 style="font-family: 'Manrope', sans-serif; font-weight: 800; color: #124170; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-history" style="color: #67C090;"></i> Riwayat Perhitungan Kalkulator Anda
        </h4>
        <div style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 5px;">
    `;

    historyList.forEach((item, index) => {
        const ton = ((item.total_kg || 0) / 1000).toFixed(3);
        htmlContent += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; border-radius: 10px; border: 1px solid #dee2e6; font-size: .85rem;">
                <div>
                    <span style="color: #6c757d; font-size: .72rem; display: block; font-weight: 600;">📋 Perhitungan #${historyList.length - index} — ${item.date}</span>
                    <strong style="color: #124170; font-size: .95rem;">${ton} ton CO₂e</strong> 
                    <span style="color: #495057; font-size: .8rem; margin-left: 10px;">(Estimasi: ${item.biaya || '-'})</span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: .75rem; background: rgba(38,102,127,.1); color: #26667F; padding: 3px 8px; border-radius: 20px; font-weight: 700;">
                        📍 ${item.proyek || '-'}
                    </span>
                </div>
            </div>
        `;
    });

    htmlContent += `</div>`;
    
    htmlContent += `
        <button type="button" data-clear-carbon-history style="background: none; border: none; color: #e74c3c; font-size: .75rem; font-weight: 700; cursor: pointer; margin-top: 12px; padding: 0; display: flex; align-items: center; gap: 4px;">
            Hapus Semua Riwayat Lokal
        </button>
    `;

    historyWrapper.innerHTML = htmlContent;
}

window.clearCarbonHistory = function() {
    if (confirm('Apakah Anda yakin ingin menghapus seluruh daftar riwayat kalkulasi, baik di browser ini maupun di database dashboard?')) {
        
        fetch('/calculator/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const currentKey = window.CARBON_STORAGE_KEY || 'carbon_history_guest';
                localStorage.removeItem(currentKey);
                localStorage.removeItem('carbon_history_list');
                
                const wrapper = document.getElementById('history-log-wrapper');
                if (wrapper) wrapper.style.display = 'none';

                const box = document.getElementById('result-box');
                if (box) box.style.display = 'none';

                alert('Sukses! Semua riwayat kalkulasi Anda telah bersih terhapus dari server dan browser.');
            } else {
                alert('Gagal menghapus riwayat di server: ' + res.message);
            }
        })
        .catch(() => {
            alert('Terjadi kendala jaringan saat mencoba menghapus riwayat di server.');
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    if (!window.CARBON_STORAGE_KEY) {
        const historyContainer = document.getElementById('history-container');
        if (historyContainer) historyContainer.innerHTML = '';
        
        const box = document.getElementById('result-box');
        if (box) box.style.display = 'none';
        
        return;
    }

    renderHistoryContainer();
});

document.addEventListener('click', function(event) {
    const removeButton = event.target.closest('[data-remove-row]');
    if (removeButton) {
        window.removeRow(removeButton);
        return;
    }

    const addButton = event.target.closest('[data-add-row]');
    if (addButton) {
        window.addRow(addButton.dataset.addRow);
        return;
    }

    const switchButton = event.target.closest('[data-switch-tab-prefix]');
    if (switchButton) {
        window.switchTab(
            switchButton.dataset.switchTabPrefix,
            Number(switchButton.dataset.switchTabStep),
            Number(switchButton.dataset.switchTabTotal)
        );
        return;
    }

    const calculateButton = event.target.closest('[data-calculate-mode]');
    if (calculateButton) {
        window.calculateAll(calculateButton.dataset.calculateMode);
        return;
    }

    const saveButton = event.target.closest('[data-save-calculation]');
    if (saveButton) {
        window.saveCalculationToDatabase(event);
        return;
    }

    if (event.target.closest('[data-reset-calculator]')) {
        window.resetCalculator();
        return;
    }

    if (event.target.closest('[data-clear-carbon-history]')) {
        window.clearCarbonHistory();
    }
});

window.switchTab      = switchTab;
window.switchSubTab   = switchSubTab;
window.switchMethod   = switchMethod;
window.toggleModule   = toggleModule;
window.addRow         = addRow;
window.removeRow      = removeRow;
window.updateEfChip   = updateEfChip;
window.updateVehicleFuel = updateVehicleFuel;
window.updateTransitModeFields = updateTransitModeFields;
window.normaliseAirportField = normaliseAirportField;
window.updateFlightRoute = updateFlightRoute;
window.calcLive       = calcLive;
window.calculateAll   = calculateAll;
window.resetCalculator = resetCalculator;
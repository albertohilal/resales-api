// assets/js/filters.js
document.addEventListener('DOMContentLoaded', function() {
    const REST_BASE = (window.wpApiSettings?.root ? window.wpApiSettings.root + 'resales/v1/filters' : '/wp-json/resales/v1/filters');
    const $form = document.querySelector('.lusso-filters-v6');
    if (!$form) return;
    const $area = $form.querySelector('select[name="area"]');
    const $location = $form.querySelector('select[name="location"]');
    const $type = $form.querySelector('select[name="type"]');
    const $bedrooms = $form.querySelector('select[name="bedrooms"]');
    let areaList = [];
    let areaLocations = {};

    // --- Helpers ---
    function setSelectOptions($select, options, opts = {}) {
        $select.innerHTML = '';
        if (opts.placeholder) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = opts.placeholder;
            if (opts.disabled) opt.disabled = true;
            $select.appendChild(opt);
        }
        options.forEach(opt => {
            const o = document.createElement('option');
            if (typeof opt === 'object') {
                o.value = opt.value ?? opt.id ?? '';
                o.textContent = opt.label ?? opt.name ?? opt.value ?? '';
            } else {
                o.value = opt;
                o.textContent = opt;
            }
            $select.appendChild(o);
        });
<<<<<<< HEAD
    }

    function updateURL() {
        const params = new URLSearchParams();
        if ($area.value) params.set('area', $area.value);
        if ($location.value) params.set('location', $location.value);
        if ($type.value) params.set('type', $type.value);
        if ($bedrooms.value) params.set('bedrooms', $bedrooms.value);
        const qs = params.toString();
        const url = window.location.pathname + (qs ? '?' + qs : '');
        window.history.replaceState({}, '', url);
    }

    function getURLParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            area: params.get('area') || '',
            location: params.get('location') || '',
            type: params.get('type') || '',
            bedrooms: params.get('bedrooms') || ''
        };
    }

    // --- Poblar selects desde la API ---
    async function fetchAreasAndLocations(selectedArea, selectedLocation) {
        try {
            const res = await fetch(REST_BASE + '/locations');
            const data = await res.json();
            areaList = Object.keys(data.areas || {});
            areaLocations = data.areas || {};
            setSelectOptions($area, areaList, { placeholder: 'Área' });
            if (selectedArea && areaList.includes(selectedArea)) {
                $area.value = selectedArea;
                await fetchLocationsForArea(selectedArea, selectedLocation);
            } else {
                setSelectOptions($location, [], { placeholder: 'Localidad', disabled: true });
            }
        } catch (e) {
            setSelectOptions($area, [], { placeholder: 'Áreas no disponibles', disabled: true });
            setSelectOptions($location, [], { placeholder: 'Localidad', disabled: true });
        }
    }

    async function fetchLocationsForArea(area, selectedLocation) {
        if (!area) {
            setSelectOptions($location, [], { placeholder: 'Localidad', disabled: true });
            return;
        }
        try {
            const res = await fetch(REST_BASE + '/locations?area=' + encodeURIComponent(area));
            const data = await res.json();
            const locs = Object.values(data.areas || {})[0] || [];
            if (locs.length) {
                setSelectOptions($location, locs, { placeholder: 'Localidad' });
                if (selectedLocation && locs.includes(selectedLocation)) {
                    $location.value = selectedLocation;
                }
            } else {
                setSelectOptions($location, [], { placeholder: 'Sin localidades disponibles', disabled: true });
            }
        } catch (e) {
            setSelectOptions($location, [], { placeholder: 'Localidad', disabled: true });
        }
    }

    async function fetchTypes(selectedType) {
        try {
            const res = await fetch(REST_BASE + '/types');
            const data = await res.json();
            setSelectOptions($type, (data || []).map(t => ({ value: t.id, label: t.label })), { placeholder: 'Tipo' });
            if (selectedType) $type.value = selectedType;
        } catch (e) {
            setSelectOptions($type, [], { placeholder: 'Tipos no disponibles', disabled: true });
        }
    }

    async function fetchBedrooms(selectedBedrooms) {
        try {
            const res = await fetch(REST_BASE + '/bedrooms');
            const data = await res.json();
            setSelectOptions($bedrooms, data || [], { placeholder: 'Dormitorios' });
            if (selectedBedrooms) $bedrooms.value = selectedBedrooms;
        } catch (e) {
            setSelectOptions($bedrooms, [], { placeholder: 'Dormitorios no disponibles', disabled: true });
        }
    }

    // --- Eventos de selects ---
    $area.addEventListener('change', async function() {
        await fetchLocationsForArea($area.value, '');
        $location.value = '';
        updateURL();
    });
    $location.addEventListener('change', updateURL);
    $type.addEventListener('change', updateURL);
    $bedrooms.addEventListener('change', updateURL);

    // --- Inicialización: poblar y restaurar estado desde URL ---
    (async function init() {
        const params = getURLParams();
        await fetchAreasAndLocations(params.area, params.location);
        await fetchTypes(params.type);
        await fetchBedrooms(params.bedrooms);
        // Restaurar selects si hay params
        if (params.area) $area.value = params.area;
        if (params.location) $location.value = params.location;
        if (params.type) $type.value = params.type;
        if (params.bedrooms) $bedrooms.value = params.bedrooms;
    })();
});
        formData.push({name:'nonce', value:LUSSO_NEWDEVS.nonce});
        formData.push({name:'lang', value:2});
        formData.push({name:'page_size', value:20});
        fetch(LUSSO_NEWDEVS.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: $.param(formData)
=======
        $location.html(options);
        $location.val('');
        $location.trigger('change');
    });

    (function ($) {
        if (typeof RESALES_FILTERS === 'undefined') return;

        const post = (action) =>
            $.post(RESALES_FILTERS.ajaxUrl, { action, nonce: RESALES_FILTERS.nonce });

        const $area     = $('#lusso-filter-area');
        const $location = $('#lusso-filter-location');
        const $types    = $('#lusso-filter-types');

        function setOptions($sel, items, valueKey, textKey, placeholder) {
            $sel.empty().append(new Option(placeholder, ''));
            items.forEach(it => $sel.append(new Option(it[textKey], it[valueKey])));
        }
        function dedupBy(arr, key) {
            const m = new Map();
            arr.forEach(o => m.set(o[key], o));
            return Array.from(m.values());
        }

        // LOCATIONS
        post('resales_v6_locations').done((res) => {
            if (!res || !res.success) { console.error('locations error', res); return; }
            const all = res.data || [];

            // Areas únicas
            const areas = dedupBy(all.filter(i => i.area).map(i => ({ k: i.area, v: i.area })), 'k')
                .map(i => ({ value: i.v, text: i.v }));
            setOptions($area, areas, 'value', 'text', 'Area');

            // Todas las locations inicialmente
            const locs = all.map(i => ({ value: i.name, text: i.name }));
            setOptions($location, locs, 'value', 'text', 'Location');

            // Filtrar locations por área
            $area.on('change', function () {
                const sel = this.value;
                const filtered = all
                    .filter(i => !sel || i.area === sel)
                    .map(i => ({ value: i.name, text: i.name }));
                setOptions($location, filtered, 'value', 'text', 'Location');
            });
        });

        // TYPES
        post('resales_v6_types').done((res) => {
            if (!res || !res.success) { console.error('types error', res); return; }
            const items = res.data || [];
            setOptions($types, items, 'value', 'text', 'All types');
        });

    })(jQuery);
>>>>>>> e6c05ce (feat(filters-v6): enqueue JS y endpoints AJAX para selects dinámicos desde WebAPI V6)
        })
        .then(r => r.json())
        .then(renderResults)
        .catch(function(){
            $results.html('<div class="lusso-error">Error loading results.</div>');
        });
    });

    // Paginación
    $results.on('click', '.lusso-load-more', function(e){
        e.preventDefault();
        setLoading(true);
        var queryId = $(this).data('queryid');
        var pageNo = $(this).data('pageno');
        var formData = $form.serializeArray();
        formData.push({name:'nonce', value:LUSSO_NEWDEVS.nonce});
        formData.push({name:'lang', value:2});
        formData.push({name:'query_id', value:queryId});
        formData.push({name:'page_no', value:pageNo});
        formData.push({name:'page_size', value:20});
        fetch(LUSSO_NEWDEVS.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: $.param(formData)
        })
        .then(r => r.json())
        .then(renderResults)
        .catch(function(){
            $results.html('<div class="lusso-error">Error loading results.</div>');
        });
    });

    // Lusso Filters V6 - Poblar selects dinámicamente
    (function(){
        // Configuración: whitelist y endpoints
        const AREA_WHITELIST = [
            'Benahavís','Benalmádena','Casares','Estepona','Fuengirola',
            'Manilva','Marbella','Mijas','Torremolinos','Malaga','Sotogrande'
        ];
        const ENDPOINTS = {
            locations: '/wp-json/resales/v1/filters/locations',
            types: '/wp-json/resales/v1/filters/types',
            bedrooms: '/wp-json/resales/v1/filters/bedrooms'
        };
        // Helpers
        function getQS() {
            return new URLSearchParams(window.location.search);
        }
        function setQS(params) {
            const url = new URL(window.location);
            Object.keys(params).forEach(k => {
                if (params[k] !== '' && params[k] !== null && typeof params[k] !== 'undefined') {
                    url.searchParams.set(k, params[k]);
                } else {
                    url.searchParams.delete(k);
                }
            });
            window.history.replaceState({}, '', url);
        }
        function fetchJSON(url) {
            return fetch(url).then(r => r.ok ? r.json() : Promise.resolve(null)).catch(() => null);
        }
        // DOM Ready
        document.addEventListener('DOMContentLoaded', function(){
            const areaSel = document.querySelector('select[name="area"]');
            const locSel  = document.querySelector('select[name="location"]');
            const typeSel = document.querySelector('select[name="type"]');
            const bedSel  = document.querySelector('select[name="bedrooms"]');
            if (!areaSel || !locSel || !typeSel || !bedSel) return;
            // Poblar Area
            areaSel.innerHTML = '<option value="">Área</option>' + AREA_WHITELIST.map(a => `<option value="${a}">${a}</option>`).join('');
            // Poblar Bedrooms
            if (window.LUSSO_BEDROOMS && Array.isArray(window.LUSSO_BEDROOMS)) {
                bedSel.innerHTML = '<option value="">Dormitorios</option>' + window.LUSSO_BEDROOMS.map(b => `<option value="${b}">${b}</option>`).join('');
            } else {
                fetchJSON(ENDPOINTS.bedrooms).then(arr => {
                    bedSel.innerHTML = '<option value="">Dormitorios</option>' + (arr||[]).map(b => `<option value="${b}">${b}</option>`).join('');
                });
            }
            // Poblar Types
            fetchJSON(ENDPOINTS.types).then(arr => {
                typeSel.innerHTML = '<option value="">Tipo</option>' + (arr||[]).map(t => `<option value="${t.id}">${t.label}</option>`).join('');
            });
            // Area→Location dependiente
            function updateLocations(area) {
                if (!area) {
                    locSel.innerHTML = '<option value="">Localidad</option>';
                    return;
                }
                fetchJSON(ENDPOINTS.locations + '?area=' + encodeURIComponent(area)).then(obj => {
                    let opts = '<option value="">Localidad</option>';
                    const areas = obj && obj.areas ? obj.areas : {};
                    const locs = areas[area] || [];
                    if (locs.length) {
                        opts += locs.map(l => `<option value="${l.name}">${l.name}</option>`).join('');
                    } else {
                        opts += '<option disabled>Sin localidades disponibles</option>';
                    }
                    locSel.innerHTML = opts;
                });
            }
            // Persistir estado en URL y reconstruir selects
            function restoreFromQS() {
                const qs = getQS();
                if (qs.has('area')) {
                    areaSel.value = qs.get('area');
                    updateLocations(qs.get('area'));
                }
                if (qs.has('location')) locSel.value = qs.get('location');
                if (qs.has('type')) typeSel.value = qs.get('type');
                if (qs.has('bedrooms')) bedSel.value = qs.get('bedrooms');
            }
            areaSel.addEventListener('change', function(){
                setQS({area: areaSel.value, location: ''});
                updateLocations(areaSel.value);
            });
            locSel.addEventListener('change', function(){
                setQS({location: locSel.value});
            });
            typeSel.addEventListener('change', function(){
                setQS({type: typeSel.value});
            });
            bedSel.addEventListener('change', function(){
                setQS({bedrooms: bedSel.value});
            });
            restoreFromQS();
        });
    })();
});

// assets/js/filters.js
jQuery(document).ready(function($){
    var $form = $('.lusso-filters');
    var $results = $('#lusso-search-results');
    var $area = $('#lusso-filter-area');
    var $location = $('#lusso-filter-location');
    var $searchBtn = $form.find('.lusso-filters__submit');
    var areaLocations = $form.data('area-locations') || {};
    if (typeof areaLocations === 'string') {
        try { areaLocations = JSON.parse(areaLocations); } catch(e) { areaLocations = {}; }
    }

    // UX: Prevenir submit por Enter accidental
    $form.on('keydown', function(e){
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !$(e.target).is($searchBtn)) {
            e.preventDefault();
            return false;
        }
    });

    // Poblar locations dependientes
    $area.on('change', function(){
        var area = $(this).val();
        var locs = area && areaLocations[area] ? areaLocations[area] : [];
        var allLocs = [];
        $.each(areaLocations, function(_, arr){ allLocs = allLocs.concat(arr); });
        allLocs = Array.from(new Set(allLocs));
        var options = '<option value="">'+($location.data('all-label')||'All Locations')+'</option>';
        (area ? locs : allLocs).forEach(function(loc){
            options += '<option value="'+loc.replace(/"/g,'&quot;')+'">'+loc+'</option>';
        });
        $location.html(options);
        $location.val('');
        $location.trigger('change');
    });

    // Focus management
    function focusResults(){
        if ($results.length) $results.attr('tabindex', -1).focus();
    }

    // Render results
    function renderResults(data) {
        if (!$results.length) return;
        $results.removeAttr('aria-busy');
        if (!data || !data.success || !data.data || !data.data.data) {
            $results.html('<div class="lusso-error">No results found.</div>');
            focusResults();
            return;
        }
        var html = '';
        data.data.data.forEach(function(item){
            html += '<div class="lusso-result-item">';
            html += '<strong>' + (item.Title || item.Reference || 'Property') + '</strong><br>';
            html += (item.Location ? '<span>' + item.Location + '</span><br>' : '');
            html += (item.Price ? '<span>' + item.Price + '</span>' : '');
            html += '</div>';
        });
        // Paginación
        if (data.data.QueryId && data.data.PageNo < data.data.PageCount) {
            html += '<button class="lusso-load-more" data-queryid="'+data.data.QueryId+'" data-pageno="'+(data.data.PageNo+1)+'">'+LUSSO_NEWDEVS.loadMore+'</button>';
        }
        $results.html(html);
        focusResults();
    }

    // Loading state
    function setLoading(on) {
        if (on) {
            $results.attr('aria-busy', 'true').html('<div class="lusso-loading">'+(LUSSO_NEWDEVS.loading||'Loading...')+'</div>');
        } else {
            $results.removeAttr('aria-busy');
        }
    }

    // Submit handler
    $form.on('submit', function(e){
        e.preventDefault();
        setLoading(true);
        var formData = $form.serializeArray();
        formData.push({name:'nonce', value:LUSSO_NEWDEVS.nonce});
        formData.push({name:'lang', value:2});
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

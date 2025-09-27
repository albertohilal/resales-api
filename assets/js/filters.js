// assets/js/filters.js
(function($){
  // Inyectar la config PHP como window.LUSSO_FILTERS (esto lo hará wp_localize_script en prod)
  // window.LUSSO_FILTERS = {...}

  // --- Filtros clásicos con jQuery (si existen en el DOM) ---
  $(function(){
    var $form = $('.lusso-filters');
    var $results = $('#lusso-search-results');
    var $area = $('#lusso-filter-area');
    var $location = $('#lusso-filter-location');
    var $searchBtn = $form.find('.lusso-filters__submit');
    var areaLocations = $form.data('area-locations') || {};
    if (typeof areaLocations === 'string') {
      try { areaLocations = JSON.parse(areaLocations); } catch(e) { areaLocations = {}; }
    }

    $form.on('keydown', function(e){
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !$(e.target).is($searchBtn)) {
        e.preventDefault();
        return false;
      }
    });

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

    function focusResults(){
      if ($results.length) $results.attr('tabindex', -1).focus();
    }

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
      if (data.data.QueryId && data.data.PageNo < data.data.PageCount) {
        html += '<button class="lusso-load-more" data-queryid="'+data.data.QueryId+'" data-pageno="'+(data.data.PageNo+1)+'">'+LUSSO_NEWDEVS.loadMore+'</button>';
      }
      $results.html(html);
      focusResults();
    }

    function setLoading(on) {
      if (on) {
        $results.attr('aria-busy', 'true').html('<div class="lusso-loading">'+(LUSSO_NEWDEVS.loading||'Loading...')+'</div>');
      } else {
        $results.removeAttr('aria-busy');
      }
    }

    $form.on('submit', function(e){
      e.preventDefault();
      setLoading(true);
      // Allow-list de parámetros
      const allowed = [
        'province', 'location', 'subarea', 'property_types',
        'beds', 'baths', 'price_min', 'price_max', 'sort', 'new_devs_mode'
      ];
      const formData = {};
      allowed.forEach(function(key){
        const el = $form.find('[name="'+key+'"]');
        if (el.length) {
          const val = el.val();
          if (val !== undefined && val !== null && val !== '') {
            formData[key] = val;
          }
        }
      });
      formData['nonce'] = LUSSO_NEWDEVS.nonce;
      formData['lang'] = 2;
      formData['page_size'] = 20;
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
  });

  // --- Poblar selects solo con window.LUSSO_FILTERS ---
  function populateSelect(sel, options, placeholder) {
    sel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    sel.appendChild(opt);
    if (Array.isArray(options) && options.length) {
      options.forEach(val => {
        const o = document.createElement('option');
        o.value = val;
        o.textContent = val;
        sel.appendChild(o);
      });
      sel.disabled = false;
    } else {
      sel.disabled = true;
    }
  }

  function initLussoFiltersConfig() {
    const config = window.LUSSO_FILTERS || {};
    const provinceSel = document.querySelector('select[name="province"]');
    const locationSel = document.querySelector('select[name="location"]');
    const subareaSel  = document.querySelector('select[name="subarea"]');
    if (!provinceSel || !locationSel || !subareaSel) return;

    // Poblar provincias
    populateSelect(provinceSel, config.provinces, 'Provincia');

    provinceSel.addEventListener('change', function() {
      const pv = this.value;
      const locs = (pv && config.locationsByProvince[pv]) ? config.locationsByProvince[pv] : [];
      populateSelect(locationSel, locs, 'Localidad');
      populateSelect(subareaSel, [], 'Subárea');
    });

    locationSel.addEventListener('change', function() {
      const lv = this.value;
      const subas = (lv && config.subareasByLocation[lv]) ? config.subareasByLocation[lv] : [];
      populateSelect(subareaSel, subas, 'Subárea');
    });

    // Inicializar selects vacíos
    populateSelect(locationSel, [], 'Localidad');
    populateSelect(subareaSel, [], 'Subárea');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLussoFiltersConfig);
  } else {
    initLussoFiltersConfig();
  }
    initDynamicFilters();
  }

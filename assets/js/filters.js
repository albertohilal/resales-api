// assets/js/filters.js
(function($){
  // Inyectar la config PHP como window.LUSSO_FILTERS (esto lo hará wp_localize_script en prod)
  // window.LUSSO_FILTERS = {...}

  // --- Filtros clásicos con jQuery (si existen en el DOM) ---
  $(function(){
    var $form = $('.lusso-filters');
    var $results = $('#lusso-search-results');

    var $location = $('#lusso-filter-location');
    var $subarea = $('#lusso-filter-subarea');
    var $searchBtn = $form.find('.lusso-filters__submit');

    $form.on('keydown', function(e){
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !$(e.target).is($searchBtn)) {
        e.preventDefault();
        return false;
      }
    });

    // Al cambiar provincia, poblar location y subarea usando SearchLocations (window.LUSSO_FILTERS)
    var config = window.LUSSO_FILTERS || {};
    var $province = $form.find('select[name="province"]');
    $province.on('change', function(){
      var pv = $(this).val();
      var locs = (pv && config.locationsByProvince && config.locationsByProvince[pv]) ? config.locationsByProvince[pv] : [];
      $location.html('<option value="">Localidad</option>');
      locs.forEach(function(loc){
        $location.append('<option value="'+loc.replace(/"/g,'&quot;')+'">'+loc+'</option>');
      });
      $location.val('');
      $location.trigger('change');
      $subarea.html('<option value="">Subárea</option>');
      $subarea.val('');
    });

    $location.on('change', function(){
      var lv = $(this).val();
      var subas = (lv && config.subareasByLocation && config.subareasByLocation[lv]) ? config.subareasByLocation[lv] : [];
      $subarea.html('<option value="">Subárea</option>');
      subas.forEach(function(sa){
        $subarea.append('<option value="'+sa.replace(/"/g,'&quot;')+'">'+sa+'</option>');
      });
      $subarea.val('');
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
      // Solo enviar location, type, bedrooms en la URL
      const location  = document.querySelector('select[name="location"]')?.value?.trim();
      const type      = document.querySelector('select[name="type"]')?.value?.trim();
      const bedrooms  = document.querySelector('select[name="bedrooms"]')?.value?.trim();
      const qs = new URLSearchParams();
      if (location) qs.set('location', location);
      if (type)     qs.set('type', type);
      if (bedrooms) qs.set('bedrooms', bedrooms);
      const url = window.location.pathname + (qs.toString() ? '?' + qs.toString() : '');
      window.location.assign(url);
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



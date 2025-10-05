jQuery(function($){
  var $form = $('#lusso-filters');
  var $results = $('#lusso-search-results');

  // Función auxiliar para renderizar el listado de propiedades
  function renderProperties(properties) {
    if (!properties || !properties.length) {
      $results.html('<div class="lusso-error">No se encontraron propiedades.</div>');
      return;
    }
    var html = '';
    properties.forEach(function(p){
      html += '<div class="property-card">';
      html += '<strong>' + (p.Title || p.Reference || 'Property') + '</strong><br>';
      html += (p.Location ? '<span>' + p.Location + '</span><br>' : '');
      html += (p.Price ? '<span>' + p.Price + '</span>' : '');
      html += '</div>';
    });
    $results.html(html);
  }

  // Función auxiliar para mostrar errores de filtro/AJAX
  function showFilterError(msg) {
    $results.html('<div class="lusso-error">' + msg + '</div>');
  }

  // Intercepta el submit del formulario de filtros
  $form.on('submit', function(e){
    e.preventDefault();

    // Leer valores visibles de los filtros
    var type     = $form.find('select[name="type"]').val();
    var location = $form.find('select[name="location"]').val();
    var bedrooms = $form.find('select[name="bedrooms"]').val();

    // Normalizar el tipo para el backend (case-insensitive)
    var typeNormalized = type ? type.toLowerCase().trim() : '';

    // Validar solo tipos permitidos
    var allowedTypes = { apartment: true, house: true, plot: true };
    if (typeNormalized && !allowedTypes[typeNormalized]) {
      showFilterError('Tipo de propiedad no válido.');
      console.log('Tipo no permitido:', typeNormalized);
      return;
    }

    // Mostrar loading
    $results.html('<div class="lusso-loading">Cargando...</div>');

    // Construir datos para AJAX
    var ajaxData = {
      action: 'lusso_search_properties_type',
      type: typeNormalized,
      location: location,
      bedrooms: bedrooms
    };

    console.log('Enviando AJAX:', ajaxData);

    // Enviar petición AJAX al endpoint PHP usando la URL localizada
    $.ajax({
      url: (typeof myAjax !== 'undefined' ? myAjax.ajaxurl : '/wp-admin/admin-ajax.php'),
      method: 'POST',
      dataType: 'json',
      data: ajaxData
    }).done(function(response){
      console.log('Respuesta AJAX:', response);
      if (response.success && response.data && response.data.properties) {
        renderProperties(response.data.properties);
      } else if (response.data && response.data.error) {
        showFilterError(response.data.error);
      } else {
        showFilterError('No se encontraron propiedades.');
      }
    }).fail(function(xhr){
      var msg = 'Error al cargar resultados.';
      if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.error) {
        msg = xhr.responseJSON.data.error;
      }
      showFilterError(msg);
      console.log('AJAX error:', xhr);
    });
  });
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

    // Intercepta el submit del formulario de filtros
    // Intercepta el submit del formulario de filtros
    $form.on('submit', function(e){
      e.preventDefault();

      // Leer valores de los filtros
      var type     = $form.find('select[name="type"]').val();
      var location = $form.find('select[name="location"]').val();
      var bedrooms = $form.find('select[name="bedrooms"]').val();

      // Normalizar el tipo para el backend (case-insensitive)
      var typeNormalized = type ? type.toLowerCase().trim() : '';

      // Validar solo tipos permitidos
      var allowedTypes = { apartment: true, house: true, plot: true };
      if (typeNormalized && !allowedTypes[typeNormalized]) {
        $results.html('<div class="lusso-error">Tipo de propiedad no válido.</div>');
        return;
      }

      // Mostrar loading
      $results.html('<div class="lusso-loading">Cargando...</div>');

      // Construir datos para AJAX
      var ajaxData = {
        action: 'lusso_search_properties_type',
        type: typeNormalized,
        location: location,
        bedrooms: bedrooms
      };

      // Enviar petición AJAX al endpoint PHP
      $.ajax({
        url: window.ajaxurl || '/wp-admin/admin-ajax.php',
        method: 'POST',
        dataType: 'json',
        data: ajaxData
      }).done(function(resp){
        // Manejo de respuesta y renderizado de resultados
        if (resp.success && resp.data && resp.data.properties && resp.data.properties.length) {
          var html = '';
          resp.data.properties.forEach(function(p){
            html += '<div class="property-card">';
            html += '<strong>' + (p.Title || p.Reference || 'Property') + '</strong><br>';
            html += (p.Location ? '<span>' + p.Location + '</span><br>' : '');
            html += (p.Price ? '<span>' + p.Price + '</span>' : '');
            html += '</div>';
          });
          $results.html(html);
        } else if (resp.data && resp.data.error) {
          $results.html('<div class="lusso-error">' + resp.data.error + '</div>');
        } else {
          $results.html('<div class="lusso-error">No se encontraron propiedades.</div>');
        }
      }).fail(function(xhr){
        // Mostrar error de red o servidor
        var msg = 'Error al cargar resultados.';
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.error) {
          msg = xhr.responseJSON.data.error;
        }
        $results.html('<div class="lusso-error">' + msg + '</div>');
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



/*! Lusso Filters – JSON version v2.2 */
/* Carga dinámicamente las subáreas desde includes/data/locations-custom.json */
(function ($) {
  'use strict';

  // === Funciones utilitarias ===
  function qs(name) {
    const m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function normalizeArray(arr) {
    const seen = Object.create(null), out = [];
    (arr || []).forEach(x => {
      if (!x) return;
      if (!seen[x]) { seen[x] = 1; out.push(x); }
    });
    return out;
  }

  // === DOM cache ===
  const dom = {
    $form: $('.lusso-filters'),
    $location: null,
    $subarea: null
  };

  // === Inicialización ===
  $(function () {
    if (!dom.$form.length) {
      dom.$form = $('form').has('select[name="location"]');
    }

    dom.$location = dom.$form.find('select[name="location"]');
    dom.$subarea  = dom.$form.find('select[name="area"], select[name="subarea"], select[name="zona"]');

    // placeholders
    if (dom.$location.find('option[value=""]').length === 0)
      dom.$location.prepend('<option value="">Location</option>');
    if (dom.$subarea.find('option[value=""]').length === 0)
      dom.$subarea.prepend('<option value="">Subarea</option>');

    // === Cargar datos desde el JSON dinámico ===
    const jsonUrl = (typeof lussoFiltersData !== 'undefined' && lussoFiltersData.jsonUrl)
      ? lussoFiltersData.jsonUrl
      : '/wp-content/plugins/resales-api/includes/data/locations-custom.json';

    fetch(jsonUrl)
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(data => {
        console.log('[Lusso Filters] JSON cargado correctamente:', jsonUrl);

        // --- Construir subáreas según el área seleccionada ---
        function updateSubareaOptions(areaName) {
          const areaData = data[areaName];
          dom.$subarea.empty().append('<option value="">Subarea</option>');

          if (areaData && areaData.subareas && areaData.subareas.length) {
            const list = normalizeArray(areaData.subareas);
            list.forEach(label => {
              dom.$subarea.append('<option value="' + label + '">' + label + '</option>');
            });
            dom.$subarea.prop('disabled', false);

            // Si existe subárea por defecto, seleccionarla automáticamente
            if (areaData.defaultSubarea) {
              dom.$subarea.val(areaData.defaultSubarea);
              console.log('[Lusso Filters] Subárea por defecto seleccionada:', areaData.defaultSubarea);
            }
          } else {
            dom.$subarea.prop('disabled', true);
          }
        }

        // --- Restaurar valores desde la URL ---
        const initialLoc = qs('location') || dom.$location.val() || '';
        if (initialLoc) {
          dom.$location.val(initialLoc);
          updateSubareaOptions(initialLoc);

          const subQS = qs('zona') || qs('area') || qs('subarea') || '';
          if (subQS) {
            setTimeout(() => {
              let matched = false;
              dom.$subarea.find('option').each(function () {
                if ($(this).val().trim().toLowerCase() === subQS.trim().toLowerCase()) {
                  dom.$subarea.val($(this).val());
                  console.log('[DEBUG] Subárea retenida en carga inicial:', $(this).val());
                  matched = true;
                  return false;
                }
              });
              if (!matched) console.warn('[DEBUG] Subárea no encontrada al cargar:', subQS);
            }, 150);
          }
        }

        // --- Al cambiar Location, actualizar subáreas ---
        dom.$location.on('change', function (e) {
          e.preventDefault();
          const locQS = dom.$location.val();
          updateSubareaOptions(locQS);
        });

        // --- Envío del formulario (solo subárea a la API) ---
        dom.$form.on('submit', function (e) {
          e.preventDefault();

          const subareaVal = dom.$subarea.val();

          // 🔸 Solo enviar si hay subárea seleccionada
          if (!subareaVal) {
            alert('Please select a Subarea before searching.');
            return;
          }

          // 🔸 Siempre usar la subárea como location
          const params = ['location=' + encodeURIComponent(subareaVal)];

          // agregar otros filtros (bedrooms, type, etc.)
          dom.$form.find('select, input').each(function () {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (
              name &&
              value &&
              name !== 'location' &&
              name !== 'zona' &&
              name !== 'subarea' &&
              name !== 'area'
            ) {
              params.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
            }
          });

          const baseUrl = window.location.pathname;
          const newUrl = baseUrl + (params.length ? '?' + params.join('&') : '');
          console.log('[Lusso Filters] Redirigiendo a:', newUrl);
          window.location.href = newUrl;
        });
      })
      .catch(err => {
        console.error('[Lusso Filters] Error al cargar JSON:', err);
      });
  });
})(jQuery);

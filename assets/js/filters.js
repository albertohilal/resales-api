/*! Lusso Filters – JSON version v2.3 */
/* Carga dinámicamente las subáreas desde includes/data/locations-custom.json */
(function ($) {
  'use strict';

  // === Utilidades ===
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

  // normaliza texto: minúsculas + sin acentos + trim
  function norm(s) {
    return String(s || '')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  // === DOM cache ===
  const dom = {
    $form: $('.lusso-filters'),
    $location: null,
    $subarea: null
  };

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

    // URL del JSON (con fallback)
    const jsonUrl = (typeof lussoFiltersData !== 'undefined' && lussoFiltersData.jsonUrl)
      ? lussoFiltersData.jsonUrl
      : '/wp-content/plugins/resales-api/includes/data/locations-custom.json';

    fetch(jsonUrl)
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(rawData => {
        console.log('[Lusso Filters] JSON cargado correctamente:', jsonUrl);

        // Índice tolerante a acentos/mayúsculas
        const DATA_IDX = {};
        Object.keys(rawData || {}).forEach(key => {
          DATA_IDX[norm(key)] = rawData[key];
        });

        function getAreaData(areaName) {
          return DATA_IDX[norm(areaName)];
        }

        // Rellena opciones de subárea para una location dada
        function updateSubareaOptions(areaName) {
          const areaData = getAreaData(areaName);
          dom.$subarea.empty().append('<option value="">Subarea</option>');

          if (areaData && Array.isArray(areaData.subareas) && areaData.subareas.length) {
            const list = normalizeArray(areaData.subareas);
            list.forEach(label => {
              dom.$subarea.append('<option value="' + label + '">' + label + '</option>');
            });
            dom.$subarea.prop('disabled', false);

            if (areaData.defaultSubarea) {
              dom.$subarea.val(areaData.defaultSubarea);
              console.log('[Lusso Filters] Subárea por defecto seleccionada:', areaData.defaultSubarea);
            }
          } else {
            // No bloqueamos el control para evitar estados "muertos"
            dom.$subarea.prop('disabled', false);
            console.warn('[Lusso Filters] No se encontraron subáreas para:', areaName);
          }
        }

        // --- Estado inicial desde URL ---
        const initialLoc = qs('location') || dom.$location.val() || '';
        if (initialLoc) {
          dom.$location.val(initialLoc);
          updateSubareaOptions(initialLoc);

          const subQS = qs('zona') || qs('area') || qs('subarea') || '';
          if (subQS) {
            setTimeout(() => {
              let matched = false;
              dom.$subarea.find('option').each(function () {
                if (norm($(this).val()) === norm(subQS)) {
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

        // --- Cambiar Location => refrescar subáreas ---
        dom.$location.on('change', function (e) {
          e.preventDefault();
          const loc = dom.$location.val();
          updateSubareaOptions(loc);
        });

        // --- Envío del formulario (enviar SOLO subárea a la URL pública/shortcode) ---
        dom.$form.on('submit', function (e) {
          e.preventDefault();

          let subareaVal = dom.$subarea.val();
          const locationVal = dom.$location.val();

          // Si no eligió subárea pero la location tiene defaultSubarea, usarla
          if (!subareaVal && locationVal) {
            const areaData = getAreaData(locationVal);
            if (areaData && areaData.defaultSubarea) {
              subareaVal = areaData.defaultSubarea;
              dom.$subarea.val(subareaVal); // reflejarlo en UI
              console.log('[Lusso Filters] Usando defaultSubarea en submit:', subareaVal);
            }
          }

          // Si seguimos sin subárea, no enviamos nada a la API (no tiene sentido)
          if (!subareaVal) {
            alert('Please select a Subarea before searching.');
            return;
          }

          // Siempre usar la subárea como "location" en la URL pública
          const params = ['location=' + encodeURIComponent(subareaVal)];

          // Mantener otros filtros (nunca enviamos location/zona/area)
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
        // En caso de error de carga, nunca bloquear el selector de subárea
        dom.$subarea.prop('disabled', false);
      });
  });
})(jQuery);

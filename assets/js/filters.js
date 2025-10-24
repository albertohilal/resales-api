/*! Lusso Filters – JSON version v2.5 (auto defaultSubarea + & fix) */
(function ($) {
  'use strict';

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

  function norm(s) {
    return String(s || '')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  const dom = {
    $form: $('.lusso-filters'),
    $location: null,
    $subarea: null
  };

  $(function () {
    if (!dom.$form.length) dom.$form = $('form').has('select[name="location"]');

    dom.$location = dom.$form.find('select[name="location"]');
    dom.$subarea  = dom.$form.find('select[name="area"], select[name="subarea"], select[name="zona"]');

    if (dom.$location.find('option[value=""]').length === 0)
      dom.$location.prepend('<option value="">Location</option>');
    if (dom.$subarea.find('option[value=""]').length === 0)
      dom.$subarea.prepend('<option value="">Subarea</option>');

    const jsonUrl = (typeof lussoFiltersData !== 'undefined' && lussoFiltersData.jsonUrl)
      ? lussoFiltersData.jsonUrl
      : '/wp-content/plugins/resales-api/includes/data/locations-custom.json';

    fetch(jsonUrl)
      .then(res => {
        console.log('[Lusso Filters] Fetching subareas JSON:', jsonUrl);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(rawData => {
        console.log('[Lusso Filters] JSON loaded:', rawData);
        const DATA_IDX = {};

        // === PATCH: soporte para "&" y "and" ===
        Object.keys(rawData || {}).forEach(key => {
          const nk = norm(key);
          DATA_IDX[nk] = rawData[key];
          DATA_IDX[nk.replace(/&/g, 'and')] = rawData[key];
        });

        function getAreaData(areaName) {
          if (!areaName) return null;
          const n = norm(areaName);
          console.log('[Lusso Filters] getAreaData:', { areaName, n, idx: DATA_IDX[n], idxAnd: DATA_IDX[n.replace(/&/g, 'and')] });
          return DATA_IDX[n] || DATA_IDX[n.replace(/&/g, 'and')] || null;
        }

        // === PATCH: fuerza la subárea por defecto ===
        // BEGIN multi-subarea support
        function renderSubareaMultiselect(subareas) {
          const $select = $('#subarea-multiselect');
          $select.empty();
          console.log('[Lusso Filters] Render subarea multiselect:', subareas);
          // Insertar opción "All" como primer elemento
          $select.append($('<option>').val('All').text('All'));
          if (!subareas || subareas.length === 0) {
            $select.append('<option disabled>No sub-areas available</option>');
            return;
          }
          subareas.forEach(function(label) {
            $select.append($('<option>').val(label).text(label));
          });
          // Activar Select2 si está disponible
          if ($select.length) {
            if ($select.hasClass('select2-hidden-accessible')) {
              $select.select2('destroy');
            }
            if (window.jQuery && $select.select2) {
              $select.select2({
                placeholder: 'Select subareas',
                allowClear: true,
                width: '100%',
                closeOnSelect: false,
                templateResult: function (data) {
                  if (!data.id) return data.text;
                  const selected = $select.val() || [];
                  const checked = selected.includes(data.id) ? 'checked="checked"' : '';
                  return $(
                    '<span style="display:flex;align-items:center;">' +
                      '<input type="checkbox" ' + checked + ' style="margin-right:8px;">' +
                      '<span>' + data.text + '</span>' +
                    '</span>'
                  );
                },
                  templateSelection: function (data, container) {
                    const selected = $select.val() || [];
                    // If no selection or "All" selected → placeholder
                    if (selected.length === 0 || selected.includes('All')) {
                      return 'Select subareas';
                    }
                    // Show number of selected items
                    const count = selected.length;
                    return `${count} Selected`;
                  }
              });

                // Listener to update label dynamically
                $select.on('change.select2', function () {
                  const selected = $select.val() || [];
                  const container = $(this).siblings('.select2').find('.select2-selection__rendered');
                  if (selected.length === 0 || selected.includes('All')) {
                    container.text('Select subareas');
                  } else {
                    container.text(`${selected.length} Selected`);
                  }
                });

                // Initial update to sync label
                $select.trigger('change.select2');
              // Lógica de selección exclusiva para "All"
              $select.on('change', function() {
                const selected = $select.val() || [];
                // Si se selecciona "All", desmarcar el resto
                if (selected && selected.includes('All')) {
                  $select.val(['All']).trigger('change.select2');
                } else {
                  // Si se selecciona cualquier otra, desmarcar "All"
                  const filtered = selected.filter(v => v !== 'All');
                  if (filtered.length !== selected.length) {
                    $select.val(filtered).trigger('change.select2');
                  }
                }
              });
            }
          }
          // Estilo compacto y scroll
          $('.select2-subarea-dropdown').css({
            'max-height': '220px',
            'overflow-y': 'auto',
            'font-size': '15px',
            'padding': '4px 0',
            'background': '#fff',
            'border-radius': '4px',
            'box-shadow': '0 2px 8px rgba(0,0,0,0.08)'
          });
          $('.select2-subarea-selection').css({
            'min-height': '38px',
            'font-size': '15px',
            'padding': '2px 6px',
            'background': '#fff',
            'border-radius': '4px',
            'border': '1px solid #ccc'
          });
          $select.css({
            'width': '220px',
            'max-width': '100%',
            'font-size': '15px',
            'background': '#fff',
            'border-radius': '4px',
            'border': '1px solid #ccc'
          });
        }

  function updateSubareaOptionsMulti(areaName) {
          console.log('[Lusso Filters] updateSubareaOptionsMulti called with:', areaName);
          const areaData = getAreaData(areaName);
          if (areaData && areaData.subareas && areaData.subareas.length) {
            const list = normalizeArray(areaData.subareas);
            renderSubareaMultiselect(list);
          } else {
            renderSubareaMultiselect([]);
          }
        }
        // END multi-subarea support

  const initialLoc = qs('location') || dom.$location.val() || '';
        if (initialLoc) {
          dom.$location.val(initialLoc);
          updateSubareaOptions(initialLoc);
          const subQS = qs('zona') || qs('area') || qs('subarea') || '';
          if (subQS) {
            setTimeout(() => {
              dom.$subarea.find('option').each(function () {
                if (norm($(this).val()) === norm(subQS)) dom.$subarea.val($(this).val());
              });
            }, 150);
          }
        }

        // BEGIN multi-subarea support
        dom.$location.on('change', e => {
          e.preventDefault();
          updateSubareaOptionsMulti(dom.$location.val());
        });
        // END multi-subarea support

        // BEGIN multi-subarea support
        dom.$form.on('submit', function(e) {
          const selected = $('#subarea-multiselect').val() || [];
          let literalString = '';
          if (selected.length === 0 || selected.includes('All')) {
            // Si no hay selección o se selecciona "All", enviar vacío (todas las subáreas)
            literalString = '';
          } else {
            literalString = selected.join(',');
          }
          $('#sublocation_literal').val(literalString);
          // Los demás filtros se envían como siempre
        });
        // END multi-subarea support
      })
      .catch(err => {
        console.error('[Lusso Filters] Error al cargar JSON:', err);
        dom.$subarea.prop('disabled', false);
      });
  });
})(jQuery);

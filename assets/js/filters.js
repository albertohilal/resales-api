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
      .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
      .then(rawData => {
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
          console.log('getAreaData:', { areaName, n, idx: DATA_IDX[n], idxAnd: DATA_IDX[n.replace(/&/g, 'and')] });
          return DATA_IDX[n] || DATA_IDX[n.replace(/&/g, 'and')] || null;
        }

        // === PATCH: fuerza la subárea por defecto ===
        // BEGIN multi-subarea support
        function renderSubareaCheckboxes(subareas) {
          const container = $('#subarea-checkboxes');
          container.empty();
          if (!subareas || subareas.length === 0) {
            container.append('<div>No sub-areas available.</div>');
            return;
          }
          subareas.forEach(function(label) {
            const literal = label;
            const checkbox = $('<input>')
              .attr('type', 'checkbox')
              .attr('name', 'sublocation[]')
              .attr('value', literal)
              .addClass('subarea-checkbox');
            const labelEl = $('<label>').text(literal);
            container.append($('<div>').append(checkbox).append(labelEl));
          });
        }

        function updateSubareaOptionsMulti(areaName) {
          const areaData = getAreaData(areaName);
          if (areaData && areaData.subareas && areaData.subareas.length) {
            const list = normalizeArray(areaData.subareas);
            renderSubareaCheckboxes(list);
          } else {
            renderSubareaCheckboxes([]);
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
          const checked = $('.subarea-checkbox:checked').map(function() {
            return $(this).val();
          }).get();

          if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one sub-area.');
            return false;
          }

          const literalString = checked.join(',');
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

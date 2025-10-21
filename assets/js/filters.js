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
        function updateSubareaOptions(areaName) {
          const areaData = getAreaData(areaName);
          dom.$subarea.empty().append('<option value="">Subarea</option>');
          if (areaData && areaData.subareas && areaData.subareas.length) {
            const list = normalizeArray(areaData.subareas);
            list.forEach(label => {
              dom.$subarea.append('<option value="' + label + '">' + label + '</option>');
            });
            dom.$subarea.prop('disabled', false);
            if (areaData.defaultSubarea) {
              dom.$subarea.val(areaData.defaultSubarea);
              dom.$subarea.trigger('change');
            }
          } else {
            dom.$subarea.prop('disabled', false);
          }
        }

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

        dom.$location.on('change', e => {
          e.preventDefault();
          updateSubareaOptions(dom.$location.val());
        });

        dom.$form.on('submit', e => {
          e.preventDefault();
          let subareaVal = dom.$subarea.val();
          const locationVal = dom.$location.val();
          if (!subareaVal && locationVal) {
            const areaData = getAreaData(locationVal);
            if (areaData && areaData.defaultSubarea) {
              subareaVal = areaData.defaultSubarea;
              dom.$subarea.val(subareaVal);
            }
          }
          if (!subareaVal) {
            alert('Please select a Subarea before searching.');
            return;
          }
          const params = ['location=' + encodeURIComponent(subareaVal)];
          dom.$form.find('select, input').each(function () {
            const name = $(this).attr('name'), value = $(this).val();
            if (name && value && !['location','zona','subarea','area'].includes(name))
              params.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
          });
          const baseUrl = window.location.pathname;
          window.location.href = baseUrl + (params.length ? '?' + params.join('&') : '');
        });
      })
      .catch(err => {
        console.error('[Lusso Filters] Error al cargar JSON:', err);
        dom.$subarea.prop('disabled', false);
      });
  });
})(jQuery);

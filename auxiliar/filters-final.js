
/*! Lusso Filters Final – v1.1 */
/* Enlaza Location con Subarea, mantiene selección desde URL y evita autosubmit innecesario. */
(function ($) {
  'use strict';

  var SUBAREAS_MAP = {
    "Marbella": [
      "Aloha","Altos de los Monteros","Artola","Atalaya","Bahía de Marbella",
      "Cabopino","El Rosario","Elviria","Guadalmina Alta","Guadalmina Baja",
      "La Campana","Las Chapas","Los Monteros","Nagüeles","Nueva Andalucía",
      "Río Real","Sierra Blanca"
    ],
    "Benalmadena": [
      "Arroyo de la Miel","Benalmadena","Benalmadena Costa","Benalmadena Pueblo",
      "Carvajal","Higueron","Torremuelle","Torrequebrada"
    ],
    "Benahavís": [
      "Benahavís","El Madroñal","La Heredia","La Quinta","Los Almendros",
      "Los Arqueros","Monte Halcones","Zagaleta"
    ],
    "Mijas": [
      "Calahonda","Campo Mijas","La Cala de Mijas","La Cala Hills",
      "Mijas Costa","Mijas Golf","Riviera del Sol","Sierrezuela"
    ],
    "Fuengirola": [
      "Centro","Los Boliches","Los Pacos","Carvajal"
    ],
    "Estepona": [
      "Bel Air","Cancelada","Diana Park","El Padrón","El Paraíso",
      "Estepona","New Golden Mile","Selwo","Costalita","Atalaya"
    ],
    "Manilva": ["La Duquesa","Manilva","San Luis de Sabinillas","Chullera"],
    "Sotogrande": [
      "Sotogrande","Sotogrande Alto","Sotogrande Costa","Sotogrande Marina",
      "Sotogrande Puerto","Sotogrande Playa"
    ],
    "Torremolinos": ["Bajondillo","La Carihuela","Playamar","El Pinillo","Montemar"],
    "Málaga": ["Málaga Centro","Málaga Este","Puerto de la Torre"]
  };

  function qs(name) {
    var m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function normalizeArray(arr) {
    var seen = Object.create(null), out = [];
    (arr || []).forEach(function (x) {
      if (!x) return;
      if (!seen[x]) { seen[x] = 1; out.push(x); }
    });
    return out;
  }

  var dom = {
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

    if (dom.$location.find('option[value=""]').length === 0) {
      dom.$location.prepend('<option value="">Location</option>');
    }
    if (dom.$subarea.find('option[value=""]').length === 0) {
      dom.$subarea.prepend('<option value="">Subarea</option>');
    }

    function updateSubareaOptions(locationVal) {
      var list = normalizeArray(SUBAREAS_MAP[locationVal] || []);
      dom.$subarea.empty().append('<option value="">Subarea</option>');
      if (list.length) {
        list.forEach(function (label) {
          dom.$subarea.append('<option value="' + label + '">' + label + '</option>');
        });
        dom.$subarea.prop('disabled', false);
      } else {
        dom.$subarea.prop('disabled', true);
      }
    }

    dom.$location.on('change', function (e) {
      e.preventDefault();
      updateSubareaOptions($(this).val());
      dom.$subarea.focus();
    });

    // Para activar autosubmit al cambiar Subarea, descomentá la siguiente línea:
    // dom.$subarea.on('change', function (e) { e.preventDefault(); dom.$form.trigger('submit'); });

    var locQS = qs('location') || dom.$location.val() || '';
    if (locQS) {
      dom.$location.val(locQS);
    }

    updateSubareaOptions(locQS);

    var subQS = qs('zona') || qs('area') || qs('subarea') || '';
    if (subQS && locQS) {
      setTimeout(function () {
        var matched = false;
        dom.$subarea.find('option').each(function () {
          if ($(this).val().trim().toLowerCase() === subQS.trim().toLowerCase()) {
            dom.$subarea.val($(this).val());
            console.log('[DEBUG] Subarea seleccionada:', $(this).val());
            matched = true;
            return false;
          }
        });
        if (!matched) {
          console.warn('[DEBUG] Subarea NO encontrada:', subQS);
        }
      }, 100);
    }
  });
})(jQuery);

// JS para manejar el submit AJAX del formulario de filtros y renderizar resultados sin recargar

/*! Migración lógica de filtros: Location/Subarea dinámico y persistencia */
(function ($) {
    'use strict';

    // ====== CONFIG: Mapa Location -> Subareas ======
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

    // ====== Helpers ======
    function qs(name) {
        var m = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.search);
        return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
    }

    function serializeNonEmpty($form) {
        var params = [];
        $form.find('select, input').each(function () {
            var $el = $(this);
            var n = $el.attr('name');
            if (!n) return;
            var v = ($el.val() || '').toString().trim();
            if (v !== '' && v !== '0' && v !== 'Subarea' && v !== 'Location') {
                params.push(encodeURIComponent(n) + '=' + encodeURIComponent(v));
            }
        });
        return params.join('&');
    }

    function safeSubmit(e) {
        e && e.preventDefault && e.preventDefault();
        var q = serializeNonEmpty(dom.$form);
        var base = window.location.pathname.replace(/\/+$/, '') + '/';
        window.location.href = q ? (base + '?' + q) : base;
    }

    function normalizeArray(arr) {
        var seen = Object.create(null), out = [];
        (arr || []).forEach(function (x) {
            if (!x) return;
            if (!seen[x]) {
                seen[x] = 1;
                out.push(x);
            }
        });
        return out;
    }

    // ====== DOM cache (por name=, robusto ante cambios de IDs) ======
    var dom = {
        $form: $('.lusso-filters'),
        $location: null,
        $subarea: null
    };

    $(function () {
        // Si no encontró .lusso-filters, buscar el form que contiene location
        if (!dom.$form.length) {
            dom.$form = $('form').has('select[name="location"]');
        }

        dom.$location = dom.$form.find('select[name="location"]');
        dom.$subarea  = dom.$form.find('select[name="area"], select[name="subarea"], select[name="zona"]');

        // Placeholders (opcion vacía) si no existen
        if (dom.$location.find('option[value=""]').length === 0) {
            dom.$location.prepend('<option value="">Location</option>');
        }
        if (dom.$subarea.find('option[value=""]').length === 0) {
            dom.$subarea.prepend('<option value="">Subarea</option>');
        }

        // ====== Función para poblar subárea según la ubicación seleccionada ======
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

        // Al cambiar Location -> repoblar Subarea (pero no enviar aún)
        dom.$location.on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();
            updateSubareaOptions($(this).val());
            dom.$subarea.focus();
        });

        // Auto-submit para otros selects (no location ni subarea)
        dom.$form.on('change', 'select', function (e) {
            var name = ($(this).attr('name') || '').toLowerCase();
            if (name === 'location' || name === 'subarea' || name === 'zona' || name === 'area') {
                return;
            }
            safeSubmit(e);
        });

        // ====== Inicialización desde querystring o valores default ======
        var locQS = qs('location') || dom.$location.val() || '';
        if (locQS) {
            dom.$location.val(locQS);
        }

        updateSubareaOptions(locQS);

        // Lógica para seleccionar la subárea si viene en la URL
        var subQS = qs('zona') || qs('area') || '';
        if (subQS) {
            // Dar tiempo al DOM para construir las opciones
            setTimeout(function () {
                var $opt = dom.$subarea.find('option[value="' + subQS + '"]');
                if ($opt.length) {
                    dom.$subarea.val(subQS);
                }
            }, 0);
        }

        // ---- si quisieras que al cambiar subarea se envíe el formulario ----
      //  dom.$subarea.on('change', safeSubmit);
    });
})(jQuery);

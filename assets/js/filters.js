/**
 * LUSSO Filters (GET-only, sin AJAX)
 * - Envía el formulario por GET al cambiar filtros.
 * - Limpia parámetros vacíos y valida valores básicos.
 * - Preserva parámetros ajenos al form (ej: utm_*).
 * - Evita doble envío y hace scroll al grid de resultados.
 *
 * Requisitos:
 * - Form con class="lusso-filters"
 * - Selects: name="location" | name="bedrooms" | name="type"
 * - input[type=hidden] name="newdevs" (opcional, lo respeta)
 * - El shortcode imprime el grid con class="lusso-grid"
 */
jQuery(function ($) {
  'use strict';

  /** -----------------------------------------
  // Mapeo Location -> Subareas
  const SUBAREAS_MAP = {
    "Benahavís": ["El Madroñal","La Heredia","La Quinta","La Zagaleta","Los Almendros","Los Arqueros","Monte Halcones"],
    "Benalmádena": ["Arroyo de la Miel","Benalmádena Costa","Benalmádena Pueblo","La Capellanía","Torremar","Torremuelle","Torrequebrada"],
    "Casares": ["Casares Playa","Casares Pueblo","Doña Julia"],
    "Estepona": ["Atalaya","Bel Air","Benamara","Benavista","Cancelada","Costalita","Diana Park","El Padrón","El Paraíso","El Presidente","Estepona","Hacienda del Sol","Los Flamingos","New Golden Mile","Selwo","Valle Romano"],
    "Fuengirola": ["Carvajal","Fuengirola","Los Boliches","Los Pacos","Torreblanca"],
    "Málaga": ["Higuerón","Málaga Centro","Málaga Este","Puerto de la Torre"],
    "Manilva": ["Manilva Pueblo","Puerto de la Duquesa","Punta Chullera","San Diego","San Luis de Sabinillas"],
    "Marbella": ["Aloha","Cortijo Blanco","Golden Mile - Nagüeles","Golden Mile - Sierra Blanca","Guadalmina Alta","Guadalmina Baja","La Campana","Las Brisas","Linda Vista","Marbella Centro","Nueva Andalucía","Puerto Banús","San Pedro de Alcántara","Marbella Este - Altos de los Monteros","Marbella Este - Artola","Marbella Este - Bahía de Marbella","Marbella Este - Cabopino","Marbella Este - Carib Playa","Marbella Este - Costabella","Marbella Este - El Rosario","Marbella Este - Elviria","Marbella Este - Hacienda Las Chapas","Marbella Este - La Mairena","Marbella Este - Las Chapas","Marbella Este - Los Monteros","Marbella Este - Marbesa","Marbella Este - Reserva de Marbella","Marbella Este - Río Real","Marbella Este - Santa Clara","Marbella Este - Torre Real"],
    "Mijas": ["Calahonda","Calanova Golf","Calypso","Campo Mijas","Cerros del Águila","El Chaparral","El Coto","El Faro","La Cala de Mijas","La Cala Golf","La Cala Hills","Las Lagunas","Mijas Costa","Miraflores","Riviera del Sol","Sierrezuela","Torrenueva","Valtocado"],
    "Torremolinos": ["Bajondillo","El Calvario","El Pinillo","La Carihuela","La Colina","Los Álamos","Montemar","Playamar","Torremolinos Centro"],
    "Sotogrande": ["Guadiaro","La Alcaidesa","Los Barrios","Pueblo Nuevo de Guadiaro","San Diego","San Enrique","San Martín de Tesorillo","San Roque","San Roque Club","Sotogrande Alto","Sotogrande Costa","Sotogrande Marina","Sotogrande Playa","Sotogrande Puerto","Torreguadiaro"]
  };

  // Actualiza el select de Subarea según Location
  function updateSubareaOptions(location) {
    var $zona = $('#resales-filter-zona');
    $zona.empty();
    $zona.append('<option value="">Subarea</option>');
    if (location && SUBAREAS_MAP[location]) {
      SUBAREAS_MAP[location].forEach(function(sub){
        $zona.append('<option value="'+sub+'">'+sub+'</option>');
      });
    }
  }

  // Al cambiar Location, actualizar Subarea
  $('#resales-filter-location').on('change', function(){
    updateSubareaOptions($(this).val());
  });

  // Inicializar Subarea según Location seleccionada al cargar
  updateSubareaOptions($('#resales-filter-location').val());
   * Utilidades
   * ----------------------------------------- */
  const qs = new URLSearchParams(window.location.search);

    'use strict';

    // -----------------------------------------
    // Mapeo Location → Subareas
    // -----------------------------------------
    const SUBAREAS_MAP = {
      "Benahavís": ["El Madroñal", "La Heredia", "La Quinta", "La Zagaleta", "Los Almendros", "Los Arqueros", "Monte Halcones"],
      "Benalmádena": ["Arroyo de la Miel", "Benalmádena Costa", "Benalmádena Pueblo", "La Capellanía", "Torremar", "Torremuelle", "Torrequebrada"],
      "Casares": ["Casares Playa", "Casares Pueblo", "Doña Julia"],
      "Estepona": ["Atalaya", "Bel Air", "Benamara", "Cancelada", "Costalita", "Diana Park", "El Paraíso", "Estepona", "New Golden Mile", "Selwo", "Valle Romano"],
      "Fuengirola": ["Carvajal", "Fuengirola", "Los Boliches", "Los Pacos", "Torreblanca"],
      "Málaga": ["Higuerón", "Málaga Centro", "Málaga Este", "Puerto de la Torre"],
      "Manilva": ["Manilva Pueblo", "Puerto de la Duquesa", "Punta Chullera", "San Diego", "San Luis de Sabinillas"],
      "Marbella": ["Aloha", "Guadalmina Alta", "Guadalmina Baja", "Nueva Andalucía", "Puerto Banús", "San Pedro de Alcántara", "Golden Mile - Nagüeles", "Golden Mile - Sierra Blanca", "Marbella Este - Elviria", "Marbella Este - Los Monteros", "Marbella Este - Río Real"],
      "Mijas": ["La Cala de Mijas", "Mijas Costa", "Calahonda", "Riviera del Sol"],
      "Torremolinos": ["Bajondillo", "Playamar", "La Carihuela", "Montemar"],
      "Sotogrande": ["Sotogrande Alto", "Sotogrande Costa", "Sotogrande Marina", "Torreguadiaro"]
    };

    // -----------------------------------------
    // Actualiza el select de Subarea según Location
    // -----------------------------------------
    function updateSubareaOptions(location) {
      const $zona = $('#resales-filter-zona');
      $zona.empty();
      $zona.append('<option value="">Subarea</option>');
      if (location && SUBAREAS_MAP[location]) {
        SUBAREAS_MAP[location].forEach(sub => {
          $zona.append(`<option value="${sub}">${sub}</option>`);
        });
      }
    }

    // Al cambiar Location, actualizar Subarea
    $('#resales-filter-location').on('change', function () {
      updateSubareaOptions($(this).val());
  });

    // Inicializar Subarea al cargar la página si hay Location seleccionada
    updateSubareaOptions($('#resales-filter-location').val());

    // -----------------------------------------
    // Funcionalidad del formulario (GET)
    // -----------------------------------------
    const dom = {
      $form: $('.lusso-filters'),
    };
    if (!dom.$form.length) return;

    const $location = dom.$form.find('select[name="location"]');
    const $beds     = dom.$form.find('select[name="bedrooms"]');
    const $type     = dom.$form.find('select[name="type"]');

    function valOf($el) {
      if (!$el.length) return '';
      return ($el.val() || '').toString().trim();
    }

    function safeSubmit() {
      dom.$form.find('select').each(function () {
        if (!$(this).val()) $(this).prop('disabled', true);
      });
      dom.$form.trigger('submit');
    }

    dom.$form.on('change', 'select', function () {
      safeSubmit();
    });

    $(window).on('pageshow', function () {
      dom.$form.find('select:disabled,input:disabled').prop('disabled', false);
    });

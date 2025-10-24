<?php
/**
 * Resales API – Filters (New Developments)
 * Archivo: includes/class-resales-filters.php
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Filters')):

class Resales_Filters {

    /**
     * Shortcode principal: [lusso_filters]
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_select2_assets']);
        add_shortcode('lusso_filters', [$this, 'render_shortcode']);
    }

    /**
     * Enqueue Select2 assets from CDN
     */
    public function enqueue_select2_assets() {
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js', ['jquery'], null, true);
    }

    /**
     * Render del formulario + listado.
     * El filtrado dinámico (subáreas) se gestiona con filters.js + JSON.
     */
    public function render_shortcode($atts = []) {

    ob_start();

        $current_location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $current_beds     = isset($_GET['bedrooms']) ? sanitize_text_field($_GET['bedrooms']) : '';
        $current_type     = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $current_newdevs  = isset($_GET['newdevs']) ? sanitize_text_field($_GET['newdevs']) : '';

        ?>
        <div class="lusso-filters-wrap">
            <div class="lusso-filters-form-bg">
                <form class="lusso-filters" method="get" action="<?php echo esc_url(get_permalink()); ?>" style="margin:0">
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">

                        <!-- Location principal -->
                        <div>
                            <select id="resales-filter-location" name="location" style="min-width:220px;padding:6px 8px;">
                                <option value="">Location</option>
                                <option value="Sotogrande" <?php selected($current_location, 'Sotogrande'); ?>>Sotogrande</option>
                                <option value="Manilva" <?php selected($current_location, 'Manilva'); ?>>Manilva</option>
                                <option value="Casares" <?php selected($current_location, 'Casares'); ?>>Casares</option>
                                <option value="Estepona & New Golden Mile" <?php selected($current_location, 'Estepona & New Golden Mile'); ?>>Estepona &amp; New Golden Mile</option>
                                <option value="Benahavís" <?php selected($current_location, 'Benahavís'); ?>>Benahavís</option>
                                <option value="Marbella" <?php selected($current_location, 'Marbella'); ?>>Marbella</option>
                                <option value="Marbella Este" <?php selected($current_location, 'Marbella Este'); ?>>Marbella Este</option>
                                <option value="Golden Mile / Milla de Oro" <?php selected($current_location, 'Golden Mile / Milla de Oro'); ?>>Golden Mile / Milla de Oro</option>
                                <option value="Mijas" <?php selected($current_location, 'Mijas'); ?>>Mijas</option>
                                <option value="Fuengirola" <?php selected($current_location, 'Fuengirola'); ?>>Fuengirola</option>
                                <option value="Benalmadena" <?php selected($current_location, 'Benalmadena'); ?>>Benalmadena</option>
                                <option value="Torremolinos" <?php selected($current_location, 'Torremolinos'); ?>>Torremolinos</option>
                                <option value="Malaga Costa" <?php selected($current_location, 'Malaga Costa'); ?>>Malaga Costa</option>
                            </select>
                        </div>

                        <!-- Subarea (relleno dinámicamente vía filters.js) -->
                        <div>
                            <!-- BEGIN multi-subarea support -->
                            <select id="subarea-multiselect" name="sublocation_multi[]" multiple="multiple" style="min-width:220px;">
                                <option value="" disabled selected>Subarea</option>
                                <!-- Las opciones se renderizan dinámicamente vía JS -->
                            </select>
                            <input type="hidden" name="sublocation_literal" id="sublocation_literal" value="">
                            <!-- END multi-subarea support -->
                        </div>

                        <!-- Bedrooms -->
                        <div>
                            <select id="resales-filter-bedrooms" name="bedrooms" style="min-width:150px;padding:6px 8px;">
                                <option value="">Bedrooms</option>
                                <?php foreach (self::bedroom_options() as $val => $label): ?>
                                    <option value="<?php echo esc_attr($val); ?>" <?php selected($current_beds, (string)$val); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Type -->
                        <div>
                            <select id="resales-filter-type" name="type" style="min-width:200px;padding:6px 8px;">
                                <option value="">Type</option>
                                <?php foreach (self::property_types_static() as $group => $subtypes): ?>
                                    <optgroup label="<?php echo esc_attr($group); ?>">
                                        <?php foreach ($subtypes as $t): ?>
                                            <option value="<?php echo esc_attr($t['value']); ?>" <?php selected($current_type, $t['value']); ?>>
                                                <?php echo esc_html($t['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Hidden: new developments -->
                        <?php
                        $default_newdevs = get_option('resales_api_newdevs', 'only');
                        $hidden_newdevs  = $current_newdevs !== '' ? $current_newdevs : $default_newdevs;
                        ?>
                        <input type="hidden" name="newdevs" value="<?php echo esc_attr($hidden_newdevs); ?>" />

                        <!-- Botón Search -->
                        <div>
                            <button type="submit" class="lusso-search-button">
                                <?php esc_html_e('Search', 'resales-api'); ?>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
        <?php

        // --- Listado de propiedades (usa Swiper original) ---
        echo do_shortcode('[lusso_properties]');
        return ob_get_clean();
    }

    /**
     * Construye parámetros para la URL pública.
     * Si el usuario elige subárea, se usa como location.
     */
    public function build_public_query_args($args = []) {
        $location = isset($args['location']) ? trim($args['location']) : '';
        $zona     = isset($args['zona']) ? trim($args['zona']) : '';

        $query = [];

        if (!empty($zona)) {
            $query['location'] = $zona;
        } elseif (!empty($location)) {
            $query['location'] = $location;
        }

        if (isset($args['bedrooms'])) $query['bedrooms'] = $args['bedrooms'];
        if (isset($args['type'])) $query['type'] = $args['type'];
        if (isset($args['newdevs'])) $query['newdevs'] = $args['newdevs'];
        if (isset($args['page'])) $query['page'] = $args['page'];
        if (isset($args['qid'])) $query['qid'] = $args['qid'];

        return $query;
    }

    /**
     * Tipos de propiedad (V6 oficial)
     */
    public static function property_types_static(): array {
        return [
            'Apartamento' => [
                [ 'value' => '1-2', 'label' => 'Apartamento Planta Baja' ],
                [ 'value' => '1-4', 'label' => 'Apartamento Planta Media' ],
                [ 'value' => '1-5', 'label' => 'Apartamento en Planta Última' ],
                [ 'value' => '1-6', 'label' => 'Ático' ],
                [ 'value' => '1-7', 'label' => 'Ático Dúplex' ],
                [ 'value' => '1-8', 'label' => 'Dúplex' ]
            ],
            'Casa' => [
                [ 'value' => '2-2', 'label' => 'Villa - Chalet' ],
                [ 'value' => '2-4', 'label' => 'Pareada' ],
                [ 'value' => '2-5', 'label' => 'Adosada' ],
                [ 'value' => '2-6', 'label' => 'Finca - Cortijo' ],
                [ 'value' => '2-7', 'label' => 'Bungalow' ]
            ],
            'Terreno' => [
                [ 'value' => '4-1', 'label' => 'Terreno' ]
            ]
        ];
    }

    /**
     * Opciones de dormitorios
     */
    public static function bedroom_options(): array {
        return [
            '1' => '1+',
            '2' => '2+',
            '3' => '3+',
            '4' => '4+',
            '5' => '5+'
        ];
    }
}

// Bootstrap
new Resales_Filters();

endif;


// Incluir Select2 solo cuando se renderiza el shortcode (dentro del output buffer)

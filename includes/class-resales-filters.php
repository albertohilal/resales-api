<?php
/**
 * Resales Filters (Location estático, Type dinámico con fallback, sin AJAX)
 * Archivo: includes/class-resales-filters.php
 */
if (!defined('ABSPATH')) exit;

/* ============================================================
 * HELPERS GLOBALES (fuera de la clase) - NO TOCAR DENTRO DE LA CLASE
 * ============================================================ */

/** Lee credenciales/base desde wp_options */
if (!function_exists('resales_get_settings')) {
    function resales_get_settings(): array {
        $p1   = (string) get_option('resales_api_p1', '');
        $p2   = (string) get_option('resales_api_p2', '');
        $fid  = (string) get_option('resales_api_agency_filterid', ''); // P_Agency_FilterId
        $aid  = (string) get_option('resales_api_apiid', '');           // P_ApiId (alternativo)
        $lang = (int)    get_option('resales_api_lang', 1);

        $out = [
            'p1'     => $p1,
            'p2'     => $p2,
            'P_Lang' => $lang ?: 1,
        ];
        if (!empty($fid)) {
            $out['P_Agency_FilterId'] = $fid;
        } elseif (!empty($aid)) {
            $out['P_ApiId'] = $aid;
        }
        return $out;
    }
}

/** Construye parámetros base para cualquier endpoint V6 con settings */
if (!function_exists('resales_api_base_params')) {
    function resales_api_base_params(array $extra = []): array {
        $s = resales_get_settings();
        $base = [
            'p1'     => $s['p1'] ?? '',
            'p2'     => $s['p2'] ?? '',
            'P_Lang' => $s['P_Lang'] ?? 1,
        ];
        if (!empty($s['P_Agency_FilterId'])) {
            $base['P_Agency_FilterId'] = $s['P_Agency_FilterId'];
        } elseif (!empty($s['P_ApiId'])) {
            $base['P_ApiId'] = $s['P_ApiId'];
        }
        return array_filter($base) + $extra;
    }
}

/** Fallback estático para tipos (pensado para New Developments) */
if (!function_exists('resales_property_types_static')) {
    function resales_property_types_static(): array {
        return [
            ['value' => '2-2', 'label' => 'Apartment'],
            ['value' => '2-6', 'label' => 'Penthouse'],
            ['value' => '2-5', 'label' => 'Ground Floor Apartment'],
            ['value' => '2-4', 'label' => 'Middle Floor Apartment'],
            ['value' => '5-1', 'label' => 'Townhouse'],
            ['value' => '4-1', 'label' => 'Villa'],
            ['value' => '3-1', 'label' => 'Duplex'],
        ];
    }
}

/** Carga tipos desde SearchPropertyTypes (cache 24h) */
if (!function_exists('resales_property_types_dynamic')) {
    function resales_property_types_dynamic(): array {
        $lang = (int) (resales_get_settings()['P_Lang'] ?? 1);
        $cache_key = 'resales_prop_types_' . $lang;

        $cached = get_transient($cache_key);
        if (is_array($cached)) return $cached;

        $url = 'https://webapi.resales-online.com/V6/SearchPropertyTypes?' . http_build_query(resales_api_base_params());
        $resp = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($resp)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[resales-api][ERR] PropertyTypes: ' . $resp->get_error_message());
            }
            return [];
        }

        $json = json_decode(wp_remote_retrieve_body($resp), true);
        $out  = [];
        if (!empty($json['PropertyTypes']['PropertyType']) && is_array($json['PropertyTypes']['PropertyType'])) {
            foreach ($json['PropertyTypes']['PropertyType'] as $opt) {
                $text = isset($opt['OptionText']) ? (string)$opt['OptionText'] : '';
                $val  = isset($opt['OptionValue']) ? (string)$opt['OptionValue'] : '';
                if ($text !== '' && $val !== '') $out[] = ['value' => $val, 'label' => $text];
            }
        }

        set_transient($cache_key, $out, DAY_IN_SECONDS);
        return $out;
    }
}

/** Selector final: intenta dinámico; si falla, usa fallback estático */
if (!function_exists('resales_property_types')) {
    function resales_property_types(): array {
        $types = resales_property_types_dynamic();
        return !empty($types) ? $types : resales_property_types_static();
    }
}

/* ============================================================
 * CLASE DEL FORMULARIO (sin AJAX) + LISTADO ESTÁTICO DE LOCATIONS
 * ============================================================ */

if (!class_exists('Resales_Filters')) {
    class Resales_Filters {

        /** Listado estático de localidades por provincia (optgroups) */
        public static $LOCATIONS = [
            'Málaga' => [
                [ 'value' => 'Benahavís',    'label' => 'Benahavís'    ],
                [ 'value' => 'Benalmadena',  'label' => 'Benalmádena'  ],
                [ 'value' => 'Casares',      'label' => 'Casares'      ],
                [ 'value' => 'Estepona',     'label' => 'Estepona'     ],
                [ 'value' => 'Fuengirola',   'label' => 'Fuengirola'   ],
                [ 'value' => 'Málaga',       'label' => 'Málaga'       ],
                [ 'value' => 'Manilva',      'label' => 'Manilva'      ],
                [ 'value' => 'Marbella',     'label' => 'Marbella'     ],
                [ 'value' => 'Mijas',        'label' => 'Mijas'        ],
                [ 'value' => 'Torremolinos', 'label' => 'Torremolinos' ],
            ],
            'Cádiz' => [
                [ 'value' => 'Sotogrande',   'label' => 'Sotogrande'   ],
            ],
        ];

        public function __construct() {
            // Compatibilidad total con ambos shortcodes
            add_shortcode('resales_filters', [$this, 'shortcode']);
            add_shortcode('lusso_filters',   [$this, 'shortcode']);
        }

        /** Shortcode principal */
        public function shortcode($atts = [], $content = null) {
            ob_start();
            $this->render_filters_form();
            return ob_get_clean();
        }

        /** Render del formulario (sin AJAX) */
        public function render_filters_form() {
            // Valores actuales desde la URL
            $current_location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
            $current_type     = isset($_GET['type'])     ? sanitize_text_field($_GET['type'])     : '';
            $current_beds     = isset($_GET['bedrooms']) ? intval($_GET['bedrooms'])               : 0;

            // Forzar/Respetar New Developments
            $opt_newdevs = get_option('resales_api_newdevs', 'only'); // include | only | exclude
            if (empty($opt_newdevs) || !in_array($opt_newdevs, ['include','only','exclude'], true)) {
                $opt_newdevs = 'only';
            }

            // Tipos desde API con fallback
            $types  = resales_property_types();

            // Action del formulario: misma página sin duplicar query args
            $action = home_url(add_query_arg([], remove_query_arg(array_keys($_GET))));
            ?>
            <form class="resales-filters lusso-filters" method="get" action="<?php echo esc_url($action); ?>">
                <div class="lusso-filters__row" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="filter-group">
                        <select id="resales-filter-location" name="location" style="min-width:120px;">
                            <option value=""><?php esc_html_e('Location', 'resales-api'); ?></option>
                            <?php
                            foreach (self::$LOCATIONS as $province => $cities) {
                                echo '<optgroup label="' . esc_attr($province) . '">';
                                foreach ($cities as $city) {
                                    $val = esc_attr($city['value']);
                                    $lab = esc_html($city['label']);
                                    $sel = ($current_location === $val) ? ' selected' : '';
                                    echo '<option value="' . $val . '"' . $sel . '>' . $lab . '</option>';
                                }
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select id="resales-filter-bedrooms" name="bedrooms" style="min-width:80px;">
                            <option value=""><?php esc_html_e('Bedrooms', 'resales-api'); ?></option>
                            <?php
                            $beds_options = [1=>1, 2=>2, 3=>3, 4=>4, 5=>5];
                            foreach ($beds_options as $val => $label) {
                                $sel = (string)$current_beds === (string)$val ? ' selected' : '';
                                echo '<option value="'.esc_attr($val).'"'.$sel.'>'.esc_html($label).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select id="resales-filter-type" name="type" style="min-width:120px;">
                            <option value=""><?php echo esc_html__('Type', 'resales-api'); ?></option>
                            <?php
                            foreach ($types as $t) {
                                $val = esc_attr($t['value']);  // OptionValue (2-2, 4-1, ...)
                                $lab = esc_html($t['label']);  // OptionText
                                $sel = ($current_type === $val) ? ' selected' : '';
                                echo '<option value="'.$val.'"'.$sel.'>'.$lab.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group filter-actions">
                        <button type="submit" class="btn btn-primary" style="height:38px;min-width:100px;">
                            <?php echo esc_html__('Search', 'resales-api'); ?>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="newdevs" value="<?php echo esc_attr($opt_newdevs); ?>" />
            </form>
            <?php
        }

        /* =================================================================
         * (Opcional) utilidades si esta clase también arma la request
         * ================================================================= */

        /** Construye parámetros para SearchProperties desde $_GET */
        private function build_search_params_from_get(): array {
            $p = [];
            if (!empty($_GET['location'])) $p['P_Location']      = sanitize_text_field($_GET['location']);
            if (!empty($_GET['bedrooms'])) $p['P_Beds']          = (int) $_GET['bedrooms'];
            if (!empty($_GET['type']))     $p['P_PropertyTypes'] = sanitize_text_field($_GET['type']); // OptionValue
            $newdevs = !empty($_GET['newdevs']) ? sanitize_text_field($_GET['newdevs']) : get_option('resales_api_newdevs','only');
            if (!in_array($newdevs, ['only','include','exclude'], true)) $newdevs = 'only';
            $p['p_new_devs'] = $newdevs;

            if (!empty($_GET['page']) && ctype_digit((string)$_GET['page'])) {
                $p['P_PageNo'] = (int) $_GET['page'];
            }
            if (!empty($_GET['lang']) && ctype_digit((string)$_GET['lang'])) {
                $p['P_Lang'] = (int) $_GET['lang'];
            }

            $p = resales_api_base_params($p);
            return $p;
        }

        /** Ejecuta SearchProperties (wp_remote_get) */
        private function run_search(array $params): array {
            $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($params);
            $r   = wp_remote_get($url, ['timeout' => 25]);
            if (is_wp_error($r)) return ['ok' => false, 'error' => $r->get_error_message()];
            $code = (int) wp_remote_retrieve_response_code($r);
            $body = wp_remote_retrieve_body($r);
            $json = json_decode($body, true);
            if ($code !== 200 || !is_array($json)) return ['ok' => false, 'http' => $code, 'raw' => $body];
            return ['ok' => true, 'data' => $json];
        }
    }
}

/* ============================================================
 * Instancia única
 * ============================================================ */
if (class_exists('Resales_Filters')) {
    new Resales_Filters();
}

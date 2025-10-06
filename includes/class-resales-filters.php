<?php
/**
 * Resales API – Filters (New Developments)
 * Archivo: includes/class-resales-filters.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Resales_Filters' ) ):

class Resales_Filters {

    /**
     * Shortcode principal: [lusso_filters]
     */
    public function __construct() {
        add_shortcode( 'lusso_filters', [ $this, 'render_shortcode' ] );
    }

    /**
     * Render del formulario + listado.
     */
    public function render_shortcode( $atts = [] ) {

        // --- FORMULARIO ---
        ob_start();

        $current_location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $current_beds     = isset($_GET['bedrooms']) ? sanitize_text_field($_GET['bedrooms']) : '';
        $current_type     = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $current_newdevs  = isset($_GET['newdevs']) ? sanitize_text_field($_GET['newdevs']) : '';

        ?>
        <form class="lusso-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>" style="margin:16px 0 24px">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
                <!-- Location -->
                <div>
                    <label for="resales-filter-location" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Location', 'resales-api'); ?></label>
                    <select id="resales-filter-location" name="location" style="min-width:220px;padding:6px 8px;">
                        <option value=""><?php esc_html_e('Any', 'resales-api'); ?></option>
                        <?php foreach ( self::locations_static() as $province => $cities ): ?>
                            <optgroup label="<?php echo esc_attr( $province ); ?>">
                                <?php foreach ( $cities as $c ): ?>
                                    <option value="<?php echo esc_attr( $c['value'] ); ?>" <?php selected( $current_location, $c['value'] ); ?>>
                                        <?php echo esc_html( $c['label'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bedrooms -->
                <div>
                    <label for="resales-filter-bedrooms" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Bedrooms', 'resales-api'); ?></label>
                    <select id="resales-filter-bedrooms" name="bedrooms" style="min-width:150px;padding:6px 8px;">
                        <option value=""><?php esc_html_e('Any', 'resales-api'); ?></option>
                        <?php foreach ( self::bedroom_options() as $val => $label ): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected( $current_beds, (string)$val ); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Type (estático de 5 opciones) -->
                <div>
                    <label for="resales-filter-type" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Type', 'resales-api'); ?></label>
                    <select id="resales-filter-type" name="type" style="min-width:200px;padding:6px 8px;">
                        <option value=""><?php esc_html_e('All Types', 'resales-api'); ?></option>
                        <?php foreach ( self::property_types_static() as $t ): ?>
                            <option value="<?php echo esc_attr($t['value']); ?>" <?php selected( $current_type, $t['value'] ); ?>>
                                <?php echo esc_html($t['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- newdevs (oculto si querés fijarlo a only) -->
                <?php
                // Si querés exponerlo, cambiá a un <select>. Por ahora, fijamos / respetamos valor.
                $default_newdevs = get_option('resales_api_newdevs', 'only'); // tu preferencia
                $hidden_newdevs  = $current_newdevs !== '' ? $current_newdevs : $default_newdevs;
                ?>
                <input type="hidden" name="newdevs" value="<?php echo esc_attr($hidden_newdevs); ?>" />

                <!-- Search -->
                <div>
                    <button type="submit" style="padding:8px 14px;font-weight:600;cursor:pointer;">
                        <?php esc_html_e('Search', 'resales-api'); ?>
                    </button>
                </div>
            </div>
        </form>
        <?php

        // --- LISTADO (llamada a API) ---
        $params = $this->build_search_params_from_get();
        $params = $this->resales_api_base_params( $params );

        // Log de GET "seguro"
        $safe_get = $_GET;
        if ( isset($safe_get['p2']) ) { $safe_get['p2'] = substr($safe_get['p2'], 0, 6) . '…'; }
        error_log('[Resales API][LOG] GET params: ' . wp_json_encode($safe_get));

        // Aviso si type no se mapeó
        if ( !empty($_GET['type']) && empty($params['P_PropertyTypes']) ) {
            error_log('[Resales API][WARN] type recibido ('.sanitize_text_field($_GET['type']).') pero P_PropertyTypes no se añadió a $params');
        }

        $res = $this->run_search( $params );
        echo $res; // HTML del grid (o mensaje de error)

        return ob_get_clean();
    }

    /**
     * Construye parámetros desde $_GET
     */
    private function build_search_params_from_get(): array {
        $p = [];

        // Location
        if ( isset($_GET['location']) && $_GET['location'] !== '' ) {
            $p['P_Location'] = sanitize_text_field( $_GET['location'] );
        }

        // Bedrooms
        if ( isset($_GET['bedrooms']) && $_GET['bedrooms'] !== '' ) {
            $p['P_Beds'] = (int) $_GET['bedrooms'];
        }

        // TYPE -> P_PropertyTypes (lista blanca)
        if ( isset($_GET['type']) && $_GET['type'] !== '' ) {
            $allowed = wp_list_pluck( self::property_types_static(), 'value' ); // ['1-1','2-2','5-1','1-6','4-1']
            $val = sanitize_text_field( $_GET['type'] );
            if ( in_array( $val, $allowed, true ) ) {
                $p['P_PropertyTypes'] = $val;
            }
        }

        // newdevs (respetar GET; por defecto "only")
        if ( isset($_GET['newdevs']) && $_GET['newdevs'] !== '' ) {
            $opt = sanitize_text_field($_GET['newdevs']);
            if ( in_array( $opt, ['only','include','exclude'], true ) ) {
                $p['p_new_devs'] = $opt;
            }
        }

        // Log corto para validar
        error_log('[resales-api][SAFELOG] SHORTCODE ARGS | ' . wp_json_encode([
            'location_get'        => isset($_GET['location']) && $_GET['location'] !== '' ? 'yes' : 'no',
            'args_has_P_Location' => isset($p['P_Location']) ? 'yes' : 'no',
            'P_Location_val'      => $p['P_Location'] ?? 'empty',
            'beds_get'            => isset($_GET['bedrooms']) && $_GET['bedrooms'] !== '' ? 'yes' : 'no',
            'type_get'            => isset($_GET['type']) && $_GET['type'] !== '' ? 'yes' : 'no',
            'P_PropertyTypes'     => $p['P_PropertyTypes'] ?? 'empty',
            'newdevs'             => $p['p_new_devs'] ?? '(default later)',
        ]));

        return $p;
    }

    /**
     * Agrega credenciales y parámetros base (sin pisar lo ya definido)
     */
    private function resales_api_base_params( array $p ): array {
        $base = [
            'p1'         => get_option('resales_api_p1'),
            'p2'         => get_option('resales_api_p2'),
            'p_output'   => 'JSON',
            'P_ApiId'    => (string) get_option('resales_api_apiid'),
            'P_Lang'     => (int) get_option('resales_api_lang', 1),
            'P_PageNo'   => 1,
            'P_PageSize' => (int) get_option('resales_api_pagesize', 12),
            'P_SortType' => 3,
        ];

        // Sólo fijar por defecto si NO vino en $p
        if ( empty( $p['p_new_devs'] ) ) {
            $p['p_new_devs'] = get_option('resales_api_newdevs', 'only'); // tu caso: ONLY (New Developments)
        }

        return array_merge( $base, $p );
    }

    /**
     * Llama a la API (Search Properties) y pinta un grid básico.
     */
    private function run_search( array $params ): string {

        // Log antes de llamar
        $safe = $params;
        if ( ! empty( $safe['p2'] ) ) $safe['p2'] = substr( $safe['p2'], 0, 6 ) . '…';
        error_log('[Resales API][LOG] Params enviados a API: ' . wp_json_encode( $safe ));

        // Endpoint oficial V6 (ForSale -> Search Properties)
        $endpoint = 'https://webapi-v6.learning.resales-online.com/ForSale';

        $url = add_query_arg( $params, $endpoint );

        $resp = wp_remote_get( $url, [
            'timeout' => (int) get_option('resales_api_timeout', 20),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ( is_wp_error( $resp ) ) {
            return '<p>' . esc_html__( 'Error contacting the API', 'resales-api' ) . '</p>';
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        error_log('[Resales API][LOG] Respuesta API: ' . $body);

        if ( $code !== 200 || empty( $body ) ) {
            return '<p>' . esc_html__( 'Empty or invalid response', 'resales-api' ) . '</p>';
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return '<p>' . esc_html__( 'Unexpected API response', 'resales-api' ) . '</p>';
        }

        // Pinta un grid simple de resultados
        $html = '<div class="lusso-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">';

        $props = $data['Property'] ?? [];
        $count = is_array($props) ? count($props) : 0;

        error_log('[Resales API][LOG] Total propiedades renderizadas: ' . $count);

        if ( $count === 0 ) {
            $html .= '<p>' . esc_html__( 'No results found', 'resales-api' ) . '</p>';
            $html .= '</div>';
            return $html;
        }

        foreach ( $props as $p ) {
            $ref   = $p['Reference'] ?? '';
            $loc   = $p['Location'] ?? '';
            $beds  = $p['Bedrooms'] ?? '';
            $typeN = $p['PropertyType']['Subtype1'] ?? ($p['PropertyType']['Type'] ?? '');
            $price = $p['Price'] ?? '';
            $pic   = '';
            if ( ! empty($p['Pictures']['Picture'][0]['PictureURL']) ) {
                $pic = esc_url( $p['Pictures']['Picture'][0]['PictureURL'] );
            }

            error_log('[Resales API][LOG] Propiedad: Ref=' . $ref . ' | Dormitorios=' . $beds);

            $html .= '<article style="border:1px solid #eee;border-radius:10px;overflow:hidden;background:#fff">';
            if ( $pic ) {
                $html .= '<div style="aspect-ratio:16/9;overflow:hidden;"><img src="'. $pic .'" alt="" style="width:100%;height:100%;object-fit:cover;"></div>';
            }
            $html .= '<div style="padding:12px 14px">';
            $html .= '<div style="font-weight:700;margin-bottom:4px;">' . esc_html( $typeN ) . '</div>';
            $html .= '<div style="opacity:.8;margin-bottom:6px;">' . esc_html( $loc ) . '</div>';
            $html .= '<div style="display:flex;justify-content:space-between;align-items:center">';
            $html .= '<span>' . esc_html__( 'Ref', 'resales-api' ) . ': ' . esc_html( $ref ) . '</span>';
            if ( $price ) {
                $html .= '<span style="font-weight:700;">€ ' . esc_html( number_format( (float)$price, 0, ',', '.' ) ) . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</article>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * LISTA ESTÁTICA: 5 tipos (con códigos V6 correctos)
     */
    public static function property_types_static(): array {
        return [
            [ 'value' => '1-1', 'label' => 'Apartment' ],
            [ 'value' => '2-2', 'label' => 'Villa' ],
            [ 'value' => '5-1', 'label' => 'Townhouse' ],
            [ 'value' => '1-6', 'label' => 'Penthouse' ],
            [ 'value' => '4-1', 'label' => 'Plot' ],
        ];
    }

    /**
     * Ubicaciones estáticas (corto, editable)
     */
    public static function locations_static(): array {
        return [
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
            '5' => '5+',
        ];
    }

}

// Bootstrap de la clase
new Resales_Filters();

endif;

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
        <div class="lusso-filters-wrap">
        <form class="lusso-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>" style="margin:16px 0 24px">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
                <!-- Location (estático) -->
                <div>
                    <label for="resales-filter-location" style="display:block;font-weight:600;margin-bottom:6px;">Location</label>
                    <select id="resales-filter-location" name="location" style="min-width:220px;padding:6px 8px;">
                        <option value="">Any</option>
                        <option value="Benahavís" <?php selected( $current_location, 'Benahavís' ); ?>>Benahavís</option>
                        <option value="Benalmadena" <?php selected( $current_location, 'Benalmadena' ); ?>>Benalmadena</option>
                        <option value="Casares" <?php selected( $current_location, 'Casares' ); ?>>Casares</option>
                        <option value="Estepona" <?php selected( $current_location, 'Estepona' ); ?>>Estepona</option>
                        <option value="Fuengirola" <?php selected( $current_location, 'Fuengirola' ); ?>>Fuengirola</option>
                        <option value="Málaga" <?php selected( $current_location, 'Málaga' ); ?>>Málaga</option>
                        <option value="Manilva" <?php selected( $current_location, 'Manilva' ); ?>>Manilva</option>
                        <option value="Marbella" <?php selected( $current_location, 'Marbella' ); ?>>Marbella</option>
                        <option value="Mijas" <?php selected( $current_location, 'Mijas' ); ?>>Mijas</option>
                        <option value="Torremolinos" <?php selected( $current_location, 'Torremolinos' ); ?>>Torremolinos</option>
                        <option value="Sotogrande" <?php selected( $current_location, 'Sotogrande' ); ?>>Sotogrande</option>
                    </select>
                </div>

                <!-- Zona (desplegable estático) -->
                <div>
                    <label for="resales-filter-zona" style="display:block;font-weight:600;margin-bottom:6px;">Zona</label>
                    <select id="resales-filter-zona" name="zona" style="min-width:220px;padding:6px 8px;">
                        <option value="">Any</option>
                        <option value="Aloha">Aloha</option>
                        <option value="Altos de los Monteros">Altos de los Monteros</option>
                        <option value="Arroyo de la Miel">Arroyo de la Miel</option>
                        <option value="Artola">Artola</option>
                        <option value="Atalaya">Atalaya</option>
                        <option value="Bahía de Marbella">Bahía de Marbella</option>
                        <option value="Bajondillo">Bajondillo</option>
                        <option value="Bel Air">Bel Air</option>
                        <option value="Benahavís">Benahavís</option>
                        <option value="Benalmadena">Benalmadena</option>
                        <option value="Benalmadena Costa">Benalmadena Costa</option>
                        <option value="Benalmadena Pueblo">Benalmadena Pueblo</option>
                        <option value="Benamara">Benamara</option>
                        <option value="Benavista">Benavista</option>
                        <option value="Cabopino">Cabopino</option>
                        <option value="Calahonda">Calahonda</option>
                        <option value="Calanova Golf">Calanova Golf</option>
                        <option value="Calypso">Calypso</option>
                        <option value="Campo Mijas">Campo Mijas</option>
                        <option value="Cancelada">Cancelada</option>
                        <option value="Carib Playa">Carib Playa</option>
                        <option value="Carvajal">Carvajal</option>
                        <option value="Casares">Casares</option>
                        <option value="Casares Playa">Casares Playa</option>
                        <option value="Casares Pueblo">Casares Pueblo</option>
                        <option value="Cerros del Aguila">Cerros del Aguila</option>
                        <option value="Cortijo Blanco">Cortijo Blanco</option>
                        <option value="Costabella">Costabella</option>
                        <option value="Costalita">Costalita</option>
                        <option value="Diana Park">Diana Park</option>
                        <option value="Doña Julia">Doña Julia</option>
                        <option value="El Calvario">El Calvario</option>
                        <option value="El Chaparral">El Chaparral</option>
                        <option value="El Coto">El Coto</option>
                        <option value="El Faro">El Faro</option>
                        <option value="El Madroñal">El Madroñal</option>
                        <option value="El Padron">El Padron</option>
                        <option value="El Paraiso">El Paraiso</option>
                        <option value="El Pinillo">El Pinillo</option>
                        <option value="El Presidente">El Presidente</option>
                        <option value="El Rosario">El Rosario</option>
                        <option value="Elviria">Elviria</option>
                        <option value="Estepona">Estepona</option>
                        <option value="Fuengirola">Fuengirola</option>
                        <option value="Guadalmina Alta">Guadalmina Alta</option>
                        <option value="Guadalmina Baja">Guadalmina Baja</option>
                        <option value="Guadiaro">Guadiaro</option>
                        <option value="Hacienda del Sol">Hacienda del Sol</option>
                        <option value="Hacienda Las Chapas">Hacienda Las Chapas</option>
                        <option value="Higueron">Higueron</option>
                        <option value="La Alcaidesa">La Alcaidesa</option>
                        <option value="La Cala de Mijas">La Cala de Mijas</option>
                        <option value="La Cala Golf">La Cala Golf</option>
                        <option value="La Cala Hills">La Cala Hills</option>
                        <option value="La Campana">La Campana</option>
                        <option value="La Capellania">La Capellania</option>
                        <option value="La Carihuela">La Carihuela</option>
                        <option value="La Colina">La Colina</option>
                        <option value="La Duquesa">La Duquesa</option>
                        <option value="La Heredia">La Heredia</option>
                        <option value="La Mairena">La Mairena</option>
                        <option value="La Quinta">La Quinta</option>
                        <option value="La Zagaleta">La Zagaleta</option>
                        <option value="Las Brisas">Las Brisas</option>
                        <option value="Las Chapas">Las Chapas</option>
                        <option value="Las Lagunas">Las Lagunas</option>
                        <option value="Los Alamos">Los Alamos</option>
                        <option value="Los Almendros">Los Almendros</option>
                        <option value="Los Arqueros">Los Arqueros</option>
                        <option value="Los Boliches">Los Boliches</option>
                        <option value="Los Flamingos">Los Flamingos</option>
                        <option value="Los Monteros">Los Monteros</option>
                        <option value="Los Pacos">Los Pacos</option>
                        <option value="Málaga">Málaga</option>
                        <option value="Málaga Centro">Málaga Centro</option>
                        <option value="Málaga Este">Málaga Este</option>
                        <option value="Manilva">Manilva</option>
                        <option value="Marbella">Marbella</option>
                        <option value="Marbesa">Marbesa</option>
                        <option value="Mijas">Mijas</option>
                        <option value="Mijas Costa">Mijas Costa</option>
                        <option value="Mijas Golf">Mijas Golf</option>
                        <option value="Miraflores">Miraflores</option>
                        <option value="Monte Halcones">Monte Halcones</option>
                        <option value="Montemar">Montemar</option>
                        <option value="Nagüeles">Nagüeles</option>
                        <option value="New Golden Mile">New Golden Mile</option>
                        <option value="Nueva Andalucía">Nueva Andalucía</option>
                        <option value="Ojén">Ojén</option>
                        <option value="Playamar">Playamar</option>
                        <option value="Pueblo Nuevo de Guadiaro">Pueblo Nuevo de Guadiaro</option>
                        <option value="Puerto Banús">Puerto Banús</option>
                        <option value="Puerto de Cabopino">Puerto de Cabopino</option>
                        <option value="Puerto de la Torre">Puerto de la Torre</option>
                        <option value="Punta Chullera">Punta Chullera</option>
                        <option value="Reserva de Marbella">Reserva de Marbella</option>
                        <option value="Río Real">Río Real</option>
                        <option value="Riviera del Sol">Riviera del Sol</option>
                        <option value="San Diego">San Diego</option>
                        <option value="San Enrique">San Enrique</option>
                        <option value="San Luis de Sabinillas">San Luis de Sabinillas</option>
                        <option value="San Martín de Tesorillo">San Martín de Tesorillo</option>
                        <option value="San Pedro de Alcántara">San Pedro de Alcántara</option>
                        <option value="San Roque">San Roque</option>
                        <option value="San Roque Club">San Roque Club</option>
                        <option value="Santa Clara">Santa Clara</option>
                        <option value="Selwo">Selwo</option>
                        <option value="Sierra Blanca">Sierra Blanca</option>
                        <option value="Sierrezuela">Sierrezuela</option>
                        <option value="Sotogrande">Sotogrande</option>
                        <option value="Sotogrande Alto">Sotogrande Alto</option>
                        <option value="Sotogrande Costa">Sotogrande Costa</option>
                        <option value="Sotogrande Marina">Sotogrande Marina</option>
                        <option value="Sotogrande Playa">Sotogrande Playa</option>
                        <option value="Sotogrande Puerto">Sotogrande Puerto</option>
                        <option value="Torre Real">Torre Real</option>
                        <option value="Torreblanca">Torreblanca</option>
                        <option value="Torreguadiaro">Torreguadiaro</option>
                        <option value="Torremar">Torremar</option>
                        <option value="Torremolinos">Torremolinos</option>
                        <option value="Torremolinos Centro">Torremolinos Centro</option>
                        <option value="Torremuelle">Torremuelle</option>
                        <option value="Torrenueva">Torrenueva</option>
                        <option value="Torrequebrada">Torrequebrada</option>
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

                <!-- Type (agrupado por tipo principal y subtipos) -->
                <div>
                    <label for="resales-filter-type" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Type', 'resales-api'); ?></label>
                    <select id="resales-filter-type" name="type" style="min-width:200px;padding:6px 8px;">
                        <option value=""><?php esc_html_e('All Types', 'resales-api'); ?></option>
                        <?php foreach ( self::property_types_static() as $group => $subtypes ): ?>
                            <optgroup label="<?php echo esc_attr($group); ?>">
                                <?php foreach ($subtypes as $t): ?>
                                    <option value="<?php echo esc_attr($t['value']); ?>" <?php selected( $current_type, $t['value'] ); ?> >
                                        <?php echo esc_html($t['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- newdevs (oculto si querés fijarlo a only) -->
                <?php
                $default_newdevs = get_option('resales_api_newdevs', 'only'); // preferencia
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

        // --- Integración con [lusso_properties] ---
        echo do_shortcode('[lusso_properties]');
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

        // TYPE -> P_PropertyTypes (lista blanca, agrupado)
        if ( isset($_GET['type']) && $_GET['type'] !== '' ) {
            $all_types = [];
            foreach ( self::property_types_static() as $group => $subtypes ) {
                foreach ( $subtypes as $t ) {
                    $all_types[] = $t['value'];
                }
            }
            $val = sanitize_text_field( $_GET['type'] );
            if ( in_array( $val, $all_types, true ) ) {
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
            $p['p_new_devs'] = get_option('resales_api_newdevs', 'only'); // ONLY (New Developments) por defecto
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

        // ✅ PRODUCCIÓN (no learning)
        $endpoint = 'https://webapi.resales-online.com/V6/SearchProperties';

        $url = add_query_arg( $params, $endpoint );
        error_log('[Resales API][LOG] API URL: ' . $url);

        $resp = wp_remote_get( $url, [
            'timeout' => (int) get_option('resales_api_timeout', 20),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ( is_wp_error( $resp ) ) {
            error_log('[Resales API][HTTP ERROR] ' . $resp->get_error_message());
            return '<p>' . esc_html__( 'Error contacting the API', 'resales-api' ) . '</p>';
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        error_log('[Resales API][LOG] Respuesta API: ' . substr($body, 0, 300)); // recorta para el log

        if ( $code !== 200 || empty( $body ) ) {
            return '<p>' . esc_html__( 'Empty or invalid response', 'resales-api' ) . '</p>';
        }

        // Si por algún motivo el servidor devolviera HTML (credenciales/endpoint incorrecto), lo detectamos
        if ( stripos($body, '<!DOCTYPE html') !== false || stripos($body, '<html') !== false ) {
            error_log('[Resales API][INVALID OUTPUT] Se recibió HTML. Revisa dominio/credenciales.');
            return '<p>' . esc_html__( 'Empty or invalid response', 'resales-api' ) . '</p>';
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            error_log('[Resales API][JSON ERROR] ' . json_last_error_msg());
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
        // Agrupado por tipo principal y subtipos, con OptionValue oficial
        return [
            'Apartamento' => [
                [ 'value' => '1-2', 'label' => 'Apartamento Planta Baja' ],
                [ 'value' => '1-4', 'label' => 'Apartamento Planta Media' ],
                [ 'value' => '1-5', 'label' => 'Apartamento en Planta Última' ],
                [ 'value' => '1-6', 'label' => 'Ático' ],
                [ 'value' => '1-7', 'label' => 'Ático Dúplex' ],
                [ 'value' => '1-8', 'label' => 'Dúplex' ],
                [ 'value' => '1-9', 'label' => 'Estudio en Planta Baja' ],
                [ 'value' => '1-10', 'label' => 'Estudio Planta Media' ],
                [ 'value' => '1-11', 'label' => 'Estudio Planta Superior' ],
            ],
            'Casa' => [
                [ 'value' => '2-2', 'label' => 'Villa - Chalet' ],
                [ 'value' => '2-4', 'label' => 'Pareada' ],
                [ 'value' => '2-5', 'label' => 'Adosada' ],
                [ 'value' => '2-6', 'label' => 'Finca - Cortijo' ],
                [ 'value' => '2-7', 'label' => 'Bungalow' ],
                [ 'value' => '2-8', 'label' => 'Cabaña de Madera' ],
                [ 'value' => '2-9', 'label' => 'Quad' ],
                [ 'value' => '2-10', 'label' => 'Casa de Madera' ],
                [ 'value' => '2-11', 'label' => 'Castillo' ],
                [ 'value' => '2-12', 'label' => 'Autocaravana' ],
                [ 'value' => '2-13', 'label' => 'Palacete de Ciudad' ],
                [ 'value' => '2-14', 'label' => 'Casa Cueva' ],
            ],
            'Terreno' => [
                [ 'value' => '4-1', 'label' => 'Terreno' ],
            ],
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

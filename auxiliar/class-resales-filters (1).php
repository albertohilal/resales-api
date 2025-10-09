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
        // Subarea (nuevo filtro)
        $current_subarea  = isset($_GET['subarea']) ? sanitize_text_field($_GET['subarea']) : '';
        $current_newdevs  = isset($_GET['newdevs']) ? sanitize_text_field($_GET['newdevs']) : '';

        ?>
        <div class="lusso-filters-wrap">
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

                <!-- Subarea (solo se rellena si se seleccionó una ubicación) -->
                <div>
                    <label for="resales-filter-subarea" style="display:block;font-weight:600;margin-bottom:6px;">
                        <?php esc_html_e('Subarea', 'resales-api'); ?>
                    </label>
                    <?php
                    // Mostrar select de subáreas en función de la ubicación seleccionada.
                    $subareas_map = self::subareas_static();
                    $subareas_for_location = [];
                    if ( $current_location && isset( $subareas_map[ $current_location ] ) ) {
                        $subareas_for_location = $subareas_map[ $current_location ];
                    }
                    ?>
                    <select id="resales-filter-subarea" name="subarea" style="min-width:200px;padding:6px 8px;">
                        <option value="">
                            <?php
                            if ( $current_location ) {
                                esc_html_e('All Subareas', 'resales-api');
                            } else {
                                esc_html_e('Select location first', 'resales-api');
                            }
                            ?>
                        </option>
                        <?php if ( ! empty( $subareas_for_location ) ): ?>
                            <?php foreach ( $subareas_for_location as $group => $areas ): ?>
                                <?php if ( $group !== '' ): ?>
                                    <optgroup label="<?php echo esc_attr( $group ); ?>">
                                        <?php foreach ( $areas as $area ): ?>
                                            <option value="<?php echo esc_attr( $area['value'] ); ?>" <?php selected( $current_subarea, $area['value'] ); ?>>
                                                <?php echo esc_html( $area['label'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else: ?>
                                    <?php foreach ( $areas as $area ): ?>
                                        <option value="<?php echo esc_attr( $area['value'] ); ?>" <?php selected( $current_subarea, $area['value'] ); ?>>
                                            <?php echo esc_html( $area['label'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

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

        // Subarea
        if ( isset($_GET['subarea']) && $_GET['subarea'] !== '' ) {
            $val = sanitize_text_field( $_GET['subarea'] );
            // Validar contra la lista estática de subáreas para evitar valores arbitrarios
            $allowed_subareas = [];
            foreach ( self::subareas_static() as $loc_groups ) {
                foreach ( $loc_groups as $group_areas ) {
                    foreach ( $group_areas as $area ) {
                        $allowed_subareas[] = $area['value'];
                    }
                }
            }
            if ( in_array( $val, $allowed_subareas, true ) ) {
                // Se utiliza P_SubArea como parámetro de búsqueda; ajusta según API
                $p['P_SubArea'] = $val;
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

    /**
     * Lista estática de subáreas por localidad y grupo.
     *
     * La clave principal es el valor de la localidad (de locations_static()).
     * Cada localidad contiene un mapa de grupos.  El grupo vacío ('') indica
     * que las subáreas no tienen un nivel intermedio.  Para localidades con
     * grupos (p.ej. Marbella → Golden Mile o Marbella Este) se separan en
     * optgroups.  Cada subárea se define con 'value' y 'label'.
     */
    public static function subareas_static(): array {
        return [
            'Benahavís' => [
                '' => [
                    [ 'value' => 'Benahavís',     'label' => 'Benahavís'     ],
                    [ 'value' => 'El Madroñal',    'label' => 'El Madroñal'    ],
                    [ 'value' => 'La Heredia',      'label' => 'La Heredia'      ],
                    [ 'value' => 'La Quinta',       'label' => 'La Quinta'       ],
                    [ 'value' => 'La Zagaleta',     'label' => 'La Zagaleta'     ],
                    [ 'value' => 'Los Almendros',   'label' => 'Los Almendros'   ],
                    [ 'value' => 'Los Arqueros',    'label' => 'Los Arqueros'    ],
                    [ 'value' => 'Monte Halcones',  'label' => 'Monte Halcones'  ],
                ],
            ],
            'Benalmadena' => [
                '' => [
                    [ 'value' => 'Arroyo de la Miel',  'label' => 'Arroyo de la Miel'  ],
                    [ 'value' => 'Benalmadena',        'label' => 'Benalmádena'        ],
                    [ 'value' => 'Benalmádena Costa',  'label' => 'Benalmádena Costa'  ],
                    [ 'value' => 'Benalmádena Pueblo', 'label' => 'Benalmádena Pueblo' ],
                    [ 'value' => 'La Capellanía',      'label' => 'La Capellanía'      ],
                    [ 'value' => 'Torremar',           'label' => 'Torremar'           ],
                    [ 'value' => 'Torremuelle',        'label' => 'Torremuelle'        ],
                    [ 'value' => 'Torrequebrada',      'label' => 'Torrequebrada'      ],
                ],
            ],
            'Caseres' => [
                '' => [
                    [ 'value' => 'Casares Playa',  'label' => 'Casares Playa'  ],
                    [ 'value' => 'Casares Pueblo', 'label' => 'Casares Pueblo' ],
                    [ 'value' => 'Caseres',        'label' => 'Caseres'        ],
                    [ 'value' => 'Doña Julia',     'label' => 'Doña Julia'     ],
                ],
            ],
            'Estepona & New Golden Mile' => [
                '' => [
                    [ 'value' => 'Atalaya',          'label' => 'Atalaya'          ],
                    [ 'value' => 'Bel Air',          'label' => 'Bel Air'          ],
                    [ 'value' => 'Benamara',         'label' => 'Benamara'         ],
                    [ 'value' => 'Benavista',        'label' => 'Benavista'        ],
                    [ 'value' => 'Cancelada',        'label' => 'Cancelada'        ],
                    [ 'value' => 'Costalita',        'label' => 'Costalita'        ],
                    [ 'value' => 'Diana Park',       'label' => 'Diana Park'       ],
                    [ 'value' => 'El Padrón',        'label' => 'El Padrón'        ],
                    [ 'value' => 'El Paraíso',       'label' => 'El Paraíso'       ],
                    [ 'value' => 'El Presidente',    'label' => 'El Presidente'    ],
                    [ 'value' => 'Estepona',         'label' => 'Estepona'         ],
                    [ 'value' => 'Hacienda del Sol', 'label' => 'Hacienda del Sol' ],
                    [ 'value' => 'Los Flamingos',    'label' => 'Los Flamingos'    ],
                    [ 'value' => 'New Golden Mile',  'label' => 'New Golden Mile'  ],
                    [ 'value' => 'Selwo',            'label' => 'Selwo'            ],
                    [ 'value' => 'Valle Romano',     'label' => 'Valle Romano'     ],
                ],
            ],
            'Fuengirola' => [
                '' => [
                    [ 'value' => 'Carvajal',    'label' => 'Carvajal'    ],
                    [ 'value' => 'Fuengirola',  'label' => 'Fuengirola'  ],
                    [ 'value' => 'Los Boliches','label' => 'Los Boliches'],
                    [ 'value' => 'Los Pacos',   'label' => 'Los Pacos'   ],
                    [ 'value' => 'Torreblanca', 'label' => 'Torreblanca' ],
                ],
            ],
            'Malaga Costa' => [
                '' => [
                    [ 'value' => 'higueron',       'label' => 'Higuerón'       ],
                    [ 'value' => 'Malaga',         'label' => 'Málaga'         ],
                    [ 'value' => 'Málaga Centro',  'label' => 'Málaga Centro'  ],
                    [ 'value' => 'Málaga Este',    'label' => 'Málaga Este'    ],
                    [ 'value' => 'Puerto de la Torre','label' => 'Puerto de la Torre'],
                ],
            ],
            'Manilva' => [
                '' => [
                    [ 'value' => 'Manilva',           'label' => 'Manilva'           ],
                    [ 'value' => 'Manilva Pueblo',    'label' => 'Manilva Pueblo'    ],
                    [ 'value' => 'Puerto de la Duquesa','label' => 'Puerto de la Duquesa'],
                    [ 'value' => 'Punta Chullera',    'label' => 'Punta Chullera'    ],
                    [ 'value' => 'San Diego',         'label' => 'San Diego'         ],
                    [ 'value' => 'San Luis de Sabinillas','label' => 'San Luis de Sabinillas'],
                ],
            ],
            'Marbella' => [
                '' => [
                    [ 'value' => 'Aloha',              'label' => 'Aloha'              ],
                    [ 'value' => 'Cortijo Blanco',     'label' => 'Cortijo Blanco'     ],
                    [ 'value' => 'Guadalmina Alta',    'label' => 'Guadalmina Alta'    ],
                    [ 'value' => 'Guadalmina Baja',    'label' => 'Guadalmina Baja'    ],
                    [ 'value' => 'La Campana',         'label' => 'La Campana'         ],
                    [ 'value' => 'Las Brisas',         'label' => 'Las Brisas'         ],
                    [ 'value' => 'Linda Vista',        'label' => 'Linda Vista'        ],
                    [ 'value' => 'Marbella',           'label' => 'Marbella'           ],
                    [ 'value' => 'Nueva Andalucía',    'label' => 'Nueva Andalucía'    ],
                    [ 'value' => 'Puerto Banús',       'label' => 'Puerto Banús'       ],
                    [ 'value' => 'San Pedro de Alcántara','label' => 'San Pedro de Alcántara'],
                ],
                'Golden Mile / Milla de Oro' => [
                    [ 'value' => 'Nagüeles',       'label' => 'Nagüeles'       ],
                    [ 'value' => 'Sierra Blanca',  'label' => 'Sierra Blanca'  ],
                ],
                'Marbella Este' => [
                    [ 'value' => 'Altos de los Monteros','label' => 'Altos de los Monteros' ],
                    [ 'value' => 'Artola',              'label' => 'Artola'              ],
                    [ 'value' => 'Bahía de Marbella',   'label' => 'Bahía de Marbella'   ],
                    [ 'value' => 'Cabipino',            'label' => 'Cabipino'            ],
                    [ 'value' => 'Carib Playa',         'label' => 'Carib Playa'         ],
                    [ 'value' => 'Costabella',          'label' => 'Costabella'          ],
                    [ 'value' => 'El Rosario',          'label' => 'El Rosario'          ],
                    [ 'value' => 'Elviria',             'label' => 'Elviria'             ],
                    [ 'value' => 'Hacienda Las Chapas','label' => 'Hacienda Las Chapas'],
                    [ 'value' => 'La Mairena',          'label' => 'La Mairena'          ],
                    [ 'value' => 'Las Chapas',          'label' => 'Las Chapas'          ],
                    [ 'value' => 'Los Monteros',        'label' => 'Los Monteros'        ],
                    [ 'value' => 'Marbesa',             'label' => 'Marbesa'             ],
                    [ 'value' => 'Puerto de Cabopino',  'label' => 'Puerto de Cabopino'  ],
                    [ 'value' => 'Reserva de Marbella', 'label' => 'Reserva de Marbella' ],
                    [ 'value' => 'Río Real',            'label' => 'Río Real'            ],
                    [ 'value' => 'Santa Clara',         'label' => 'Santa Clara'         ],
                    [ 'value' => 'Torre Real',          'label' => 'Torre Real'          ],
                ],
            ],
            'Mijas' => [
                '' => [
                    [ 'value' => 'Calahonda',        'label' => 'Calahonda'        ],
                    [ 'value' => 'Calanova Golf',    'label' => 'Calanova Golf'    ],
                    [ 'value' => 'Calypso',          'label' => 'Calypso'          ],
                    [ 'value' => 'Campo Mijas',      'label' => 'Campo Mijas'      ],
                    [ 'value' => 'Cerros del Águila','label' => 'Cerros del Águila'],
                    [ 'value' => 'El Chaparral',     'label' => 'El Chaparral'     ],
                    [ 'value' => 'El Coto',          'label' => 'El Coto'          ],
                    [ 'value' => 'El Faro',          'label' => 'El Faro'          ],
                    [ 'value' => 'La Cala de Mijas', 'label' => 'La Cala de Mijas' ],
                    [ 'value' => 'La Cala Golf',     'label' => 'La Cala Golf'     ],
                    [ 'value' => 'La Cala Hills',    'label' => 'La Cala Hills'    ],
                    [ 'value' => 'Las Lagunas',      'label' => 'Las Lagunas'      ],
                    [ 'value' => 'Mijas',            'label' => 'Mijas'            ],
                    [ 'value' => 'Mijas Costa',      'label' => 'Mijas Costa'      ],
                    [ 'value' => 'Miraflores',       'label' => 'Miraflores'       ],
                    [ 'value' => 'Riviera del Sol',  'label' => 'Riviera del Sol'  ],
                    [ 'value' => 'Sierrezuela',      'label' => 'Sierrezuela'      ],
                    [ 'value' => 'Torrenueva',       'label' => 'Torrenueva'       ],
                    [ 'value' => 'Valtocado',        'label' => 'Valtocado'        ],
                ],
            ],
            'Sotogrande' => [
                '' => [
                    [ 'value' => 'Guadiaro',                'label' => 'Guadiaro'                ],
                    [ 'value' => 'La Alcaidesa',            'label' => 'La Alcaidesa'            ],
                    [ 'value' => 'Los Barrios',             'label' => 'Los Barrios'             ],
                    [ 'value' => 'Pueblo Nuevo de Guadiaro','label' => 'Pueblo Nuevo de Guadiaro'],
                    [ 'value' => 'San Diego',               'label' => 'San Diego'               ],
                    [ 'value' => 'San Enrique',             'label' => 'San Enrique'             ],
                    [ 'value' => 'San Martin de Tesorillo', 'label' => 'San Martin de Tesorillo' ],
                    [ 'value' => 'San Roque',               'label' => 'San Roque'               ],
                    [ 'value' => 'San Roque Club',          'label' => 'San Roque Club'          ],
                    [ 'value' => 'Sotogrande',              'label' => 'Sotogrande'              ],
                    [ 'value' => 'Sotogrande Alto',         'label' => 'Sotogrande Alto'         ],
                    [ 'value' => 'Sotogrande Costa',        'label' => 'Sotogrande Costa'        ],
                    [ 'value' => 'Sotogrande Marina',       'label' => 'Sotogrande Marina'       ],
                    [ 'value' => 'Sotogrande Playa',        'label' => 'Sotogrande Playa'        ],
                    [ 'value' => 'Sotogrande Puerto',       'label' => 'Sotogrande Puerto'       ],
                    [ 'value' => 'Torreguadiaro',           'label' => 'Torreguadiaro'           ],
                ],
            ],
            'Torremolinos' => [
                '' => [
                    [ 'value' => 'Bajondillo',        'label' => 'Bajondillo'        ],
                    [ 'value' => 'El Calvario',       'label' => 'El Calvario'       ],
                    [ 'value' => 'El Pinillo',        'label' => 'El Pinillo'        ],
                    [ 'value' => 'La Carihuela',      'label' => 'La Carihuela'      ],
                    [ 'value' => 'La Colina',         'label' => 'La Colina'         ],
                    [ 'value' => 'Los Álamos',        'label' => 'Los Álamos'        ],
                    [ 'value' => 'Montemar',          'label' => 'Montemar'          ],
                    [ 'value' => 'Playamar',          'label' => 'Playamar'          ],
                    [ 'value' => 'Torremolinos',      'label' => 'Torremolinos'      ],
                    [ 'value' => 'Torremolinos Centro','label' => 'Torremolinos Centro'],
                ],
            ],
        ];
    }

}

// Bootstrap de la clase
new Resales_Filters();

endif;

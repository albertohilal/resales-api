<?php
    if (!defined('ABSPATH')) exit;

    // /properties (buscador principal)
    add_action('rest_api_init', function() {
        register_rest_route('resales/v1', '/properties', [
            // --- V6 API Param Notes ---
            // p_new_devs: exclude | include (default) | only
            // P_Location: "Specific Location or csv list of Locations"
            // P_Beds: 2 (exact) or 2x (at least)
            'methods' => ['POST', 'GET'],
            'callback' => function($request) {

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[REST IN] ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));
                }

                // --- LOG CREDENCIALES Y ARGUMENTOS ENVIADOS ---
                $api_user = get_option('resales_api_p1');
                $api_key = get_option('resales_api_p2');
                $filter_id = defined('RESALES_API_DEFAULT_FILTER_ID') ? RESALES_API_DEFAULT_FILTER_ID : 'NO_DEF';
                $api_key_masked = $api_key ? substr($api_key, 0, 4) . str_repeat('*', max(0, strlen($api_key)-8)) . substr($api_key, -4) : '';
                error_log('[Resales API][CRED] p1=' . $api_user . ' p2=' . $api_key_masked . ' FilterId=' . $filter_id);
                error_log('[Resales API][ARGS] ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));

                $location = isset($_GET['location']) ? sanitize_text_field(wp_unslash($_GET['location'])) : '';
                $typeTxt  = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
                $bedsRaw  = isset($_GET['bedrooms']) ? sanitize_text_field(wp_unslash($_GET['bedrooms'])) : '';


                $params = [];
                // Validar location contra la lista oficial
                $valid_locations = [
                    'Benahavís','Benalmadena','Casares','Estepona','Fuengirola','Málaga','Manilva','Marbella','Mijas','Torremolinos','Sotogrande'
                ];
                if ($location !== '' && in_array($location, $valid_locations, true)) {
                    $params['P_Location'] = $location;
                } else if ($location !== '') {
                    error_log('[REST FILTERS] Localización inválida: ' . $location);
                }

                // bedrooms: exacto vs 1+
                if ($bedsRaw !== '') {
                    // Si termina en +, convertir a 'x'. Si es solo número, también convertir a 'x' (al menos N)
                    if (is_numeric($bedsRaw)) {
                        $params['P_Beds'] = $bedsRaw . 'x';
                    } elseif (substr($bedsRaw, -1) === '+') {
                        $params['P_Beds'] = rtrim($bedsRaw, '+') . 'x';
                    } else {
                        $params['P_Beds'] = $bedsRaw;
                    }
                }

                // Mapear type a IDs reales de Resales Online
                if ($typeTxt !== '') {
                    $map = [
                        'apartment' => '2-1', // ID real de "apartment"
                        'villa'     => '1-1', // ID real de "villa"
                        // añade más tipos según tu catálogo
                    ];
                    if (!empty($map[$typeTxt])) {
                        $params['P_PropertyTypes'] = $map[$typeTxt];
                    } else {
                        // Si el type no está en el mapa, no enviar P_PropertyTypes
                        // (deja la búsqueda abierta por tipo)
                    }
                }

                error_log('[REST FILTERS] Params enviados a la API: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
                require_once __DIR__ . '/class-resales-client.php';
                $client = new Resales_Client();
                $result = $client->search_properties_v6($params);
                if (function_exists('rest_ensure_response')) {
                    $resp = rest_ensure_response($result);
                    $resp->header('X-RO-Query-Trace', 'on');
                    return $resp;
                } else {
                    header('X-RO-Query-Trace: on');
                    return $result;
                }
            },
            'permission_callback' => '__return_true',
        ]);

        // ...resto de endpoints ya existentes...
    });

// Registrar endpoints REST de filtros V6 SIEMPRE, incondicionalmente
add_action('rest_api_init', function() {
    // /resales/v6/locations: devuelve todas las localidades permitidas por el API Filter
    register_rest_route('resales/v6', '/locations', [
        'methods' => 'GET',
        'callback' => function($request) {
            $api_user = get_option('resales_api_p1');
            $api_key = get_option('resales_api_p2');
            $filter_id = defined('RESALES_API_DEFAULT_FILTER_ID') ? RESALES_API_DEFAULT_FILTER_ID : '';
            $agency_id = defined('RESALES_API_DEFAULT_AGENCY_ID') ? RESALES_API_DEFAULT_AGENCY_ID : '';
            $lang = $request->get_param('lang') ? sanitize_text_field($request->get_param('lang')) : '1';
            $pagesize = $request->get_param('pagesize') ? max(1, min(1000, intval($request->get_param('pagesize')))) : 500;
            $params = [
                'P_All' => 'true',
                'P_PageNo' => 1,
                'P_PageSize' => $pagesize,
                'lang' => $lang,
                'p1' => $api_user,
                'p2' => $api_key,
                'p_sandbox' => 'true',
            ];
            // API Filter obligatorio
            if ($filter_id) {
                $params['P_ApiId'] = $filter_id;
            } elseif ($agency_id) {
                $params['P_Agency_FilterId'] = $agency_id;
            }
            // País y área por defecto si no vienen definidos
            if (empty($params['P_Country'])) {
                $params['P_Country'] = 'Spain';
            }
            if (empty($params['P_Area'])) {
                $params['P_Area'] = 'Costa del Sol';
            }
            $url = add_query_arg($params, 'https://webapi.resales-online.com/V6/SearchLocations');
            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => [ 'Accept' => 'application/json' ],
            ]);
            if (is_wp_error($response)) {
                return new WP_Error('rest_api_error', $response->get_error_message(), ['status' => 500]);
            }
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            $api_locations = [];
            if (!empty($json['LocationData']['ProvinceArea'])) {
                foreach ($json['LocationData']['ProvinceArea'] as $province) {
                    if (!empty($province['Locations'])) {
                        foreach ($province['Locations'] as $loc) {
                            if (!empty($loc['Location'])) {
                                $api_locations[] = sanitize_text_field($loc['Location']);
                            }
                        }
                    }
                }
            }
            $api_locations = array_unique($api_locations, SORT_STRING);
            sort($api_locations, SORT_NATURAL | SORT_FLAG_CASE);

            // Static mapping desde class-resales-filters.php
            $static_locations = [];
            if (class_exists('Resales_Filters')) {
                foreach (Resales_Filters::$LOCATIONS as $area => $locs) {
                    foreach ($locs as $item) {
                        if (!empty($item['value'])) {
                            $static_locations[] = sanitize_text_field($item['value']);
                        }
                    }
                }
            }
            $static_locations = array_unique($static_locations, SORT_STRING);
            sort($static_locations, SORT_NATURAL | SORT_FLAG_CASE);

            $result = [ 'static' => $static_locations, 'api' => $api_locations ];
            // Si la API está vacía, solo devuelve static
            if (empty($api_locations)) {
                $result = [ 'static' => $static_locations ];
            }
            return $result;
        },
        'permission_callback' => '__return_true',
    ]);

    // /resales/v6/search: combina API Filter + refinamientos
    register_rest_route('resales/v6', '/search', [
        'methods' => 'GET',
        'callback' => function($request) {
            $api_user = get_option('resales_api_p1');
            $api_key = get_option('resales_api_p2');
            $filter_id = defined('RESALES_API_DEFAULT_FILTER_ID') ? RESALES_API_DEFAULT_FILTER_ID : '';
            $params = [
                'P_ApiId' => $filter_id,
                'p1' => $api_user,
                'p2' => $api_key,
            ];
            $location = $request->get_param('location');
            if ($location) $params['P_Location'] = sanitize_text_field($location);
            $type = $request->get_param('type');
            if ($type) $params['P_PropertyType'] = sanitize_text_field($type);
            $bedrooms = $request->get_param('bedrooms');
            if ($bedrooms) $params['P_Beds'] = sanitize_text_field($bedrooms);
            $minprice = $request->get_param('minprice');
            if ($minprice) $params['P_MinPrice'] = sanitize_text_field($minprice);
            $maxprice = $request->get_param('maxprice');
            if ($maxprice) $params['P_MaxPrice'] = sanitize_text_field($maxprice);
            $url = add_query_arg($params, 'https://webapi.resales-online.com/V6/SearchProperties');
            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => [ 'Accept' => 'application/json' ],
            ]);
            if (is_wp_error($response)) {
                return new WP_Error('rest_api_error', $response->get_error_message(), ['status' => 500]);
            }
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            return $json;
        },
        'permission_callback' => '__return_true',
    ]);
    error_log('[resales-api] rest_api_init fired; routes registered');
    $provider = class_exists('Lusso_Resales_Filters_V6') ? new Lusso_Resales_Filters_V6() : null;

    // /filters/locations
    register_rest_route('resales/v1', '/filters/locations', [
        'methods' => 'GET',
        'callback' => function($request) use ($provider) {
            error_log('[resales-api] filters_v6_enabled=' . var_export(get_option('filters_v6_enabled'), true));
            if (!get_option('filters_v6_enabled')) return ['areas'=>[]];
            if (!$provider) return ['areas'=>[]];
            $area = $request->get_param('area');
            $lang = (int)$request->get_param('lang') ?: 1;
            $data = $provider->get_locations($lang, 1);
            if ($area) {
                $norm = $provider->normalize_slug($area);
                foreach ($data['areas'] as $key => $locations) {
                    if ($provider->normalize_slug($key) === $norm) {
                        return ['areas' => [$key => $locations]];
                    }
                }
                return ['areas' => [$area => []]];
            }
            return $data;
        },
        'permission_callback' => '__return_true',
    ]);

    // /filters/types
    register_rest_route('resales/v1', '/filters/types', [
        'methods' => 'GET',
        'callback' => function($request) use ($provider) {
            error_log('[resales-api] filters_v6_enabled=' . var_export(get_option('filters_v6_enabled'), true));
            if (!get_option('filters_v6_enabled')) return [];
            if (!$provider) return [];
            $lang = (int)$request->get_param('lang') ?: 1;
            return $provider->get_property_types($lang);
        },
        'permission_callback' => '__return_true',
    ]);

    // /filters/bedrooms
    register_rest_route('resales/v1', '/filters/bedrooms', [
        'methods' => 'GET',
        'callback' => function() use ($provider) {
            error_log('[resales-api] filters_v6_enabled=' . var_export(get_option('filters_v6_enabled'), true));
            if (!get_option('filters_v6_enabled')) return [];
            if (!$provider) return [];
            return $provider->get_bedrooms_options();
        },
        'permission_callback' => '__return_true',
    ]);
});

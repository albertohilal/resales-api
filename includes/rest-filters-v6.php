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

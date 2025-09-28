    // /properties (buscador principal)
    register_rest_route('resales/v1', '/properties', [
            // --- V6 API Param Notes ---
            // p_new_devs: exclude | include (default) | only
            // P_Location: "Specific Location or csv list of Locations"
            // P_Beds: 2 (exact) or 2x (at least)
        'methods' => ['POST', 'GET'],
        'callback' => function($request) {
            // 1) Sanitize GET
            $location = isset($_GET['location']) ? sanitize_text_field(wp_unslash($_GET['location'])) : '';
            $type     = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
            $bedsRaw  = isset($_GET['bedrooms']) ? sanitize_text_field(wp_unslash($_GET['bedrooms'])) : '';

            // 2) Base params (only V6-supported keys)
            $params = [
                'p_new_devs' => 'only',
            ];
            // Agency FilterId or ApiId (prefer ApiId if set)
            $apiId = getenv('P_ApiId') ?: get_option('lusso_api_apiid');
            $agencyId = getenv('P_Agency_FilterId') ?: get_option('lusso_api_agency_filterid');
            if ($apiId) {
                $params['P_ApiId'] = $apiId;
            } elseif ($agencyId) {
                $params['P_Agency_FilterId'] = $agencyId;
            }

            // 3) Map UI → API
            if ($location !== '') {
                $params['P_Location'] = $location;
            }
            if ($bedsRaw !== '') {
                $params['P_Beds'] = (substr($bedsRaw, -1) === '+') ? rtrim($bedsRaw, '+') . 'x' : $bedsRaw;
            }
            // (skip type mapping for now if not IDs)

            // 4) Explicitly ignore area/province
            // (do not add to $params)

            // 5) Call client
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
<?php
if (!defined('ABSPATH')) exit;

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

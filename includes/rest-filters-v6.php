    // /properties (buscador principal)
    register_rest_route('resales/v1', '/properties', [
        'methods' => ['POST', 'GET'],
        'callback' => function($request) {
            $allowed = [
                'province', 'location', 'subarea', 'property_types',
                'beds', 'baths', 'price_min', 'price_max', 'sort', 'new_devs_mode', 'page'
            ];
            $input = [];
            foreach ($allowed as $key) {
                $val = $request->get_param($key);
                if ($val !== null && $val !== '' && $val !== []) {
                    // Normalizar tipos
                    if (in_array($key, ['beds','baths','page'])) $val = (int)$val;
                    if (in_array($key, ['price_min','price_max'])) $val = (float)$val;
                    $input[$key] = $val;
                }
            }
            require_once __DIR__ . '/class-resales-client.php';
            $client = new Resales_Client();
            $result = $client->search_properties_v6($input);
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

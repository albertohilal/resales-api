<?php
class Resales_Filters_V6 {
    const API_BASE = 'https://webapi.resales-online.com/V6/';
    const SEARCH_ENDPOINT = 'SearchProperties';
    const CACHE_TTL = 300; // 5 minutos

    private function get_opt_all() {
        $o = [
            'p1'                => get_option('resales_api_p1'),
            'p2'                => get_option('resales_api_p2'),
            'P_ApiId'           => get_option('resales_api_apiid'),
            'P_Agency_FilterId' => get_option('resales_api_agency_filterid'),
            'P_Lang'            => get_option('resales_api_lang', 1),
            'timeout'           => get_option('resales_api_timeout', 20),
        ];
        $o['p1']      = isset($o['p1']) ? trim($o['p1']) : '';
        $o['p2']      = isset($o['p2']) ? trim($o['p2']) : '';
        $o['P_ApiId'] = isset($o['P_ApiId']) ? trim($o['P_ApiId']) : '';
        $o['P_Lang']  = (int) ($o['P_Lang'] ?: 1);
        $o['timeout'] = isset($o['timeout']) ? (int)$o['timeout'] : 20;
        return $o;
    }

    private function http_get($endpoint, $params, $timeout = 20) {
        $url = self::API_BASE . $endpoint . '?' . http_build_query($params, '', '&');
        $res = wp_remote_get($url, [
            'timeout' => $timeout,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        if (is_wp_error($res)) {
            return $res;
        }
        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200) {
            return new WP_Error('resales_http_error', 'HTTP ' . $code, ['body' => $body]);
        }
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('resales_json_error', 'JSON error', ['body' => $body]);
        }
        return $json;
    }

    public function search_with_filters($filters = []) {
        $opts = $this->get_opt_all();

        // Credenciales mínimas necesarias
        if (empty($opts['p1']) || empty($opts['p2'])) {
            return new WP_Error('resales_missing_credentials', 'Missing API credentials p1/p2.');
        }

        $params = [
            'p1'       => $opts['p1'],
            'p2'       => $opts['p2'],
            'p_output' => 'JSON',
            'P_Lang'   => $opts['P_Lang'],
        ];

        // Incluir filtro de agencia si está definido, si no usar api id
        if (!empty($opts['P_Agency_FilterId'])) {
            $params['p_agency_filterid'] = $opts['P_Agency_FilterId'];
        } elseif (!empty($opts['P_ApiId'])) {
            $params['P_ApiId'] = $opts['P_ApiId'];
        }

        // Paginación y orden
        if (isset($filters['page'])) {
            $params['P_PageNo'] = (int)$filters['page'];
        }
        if (isset($filters['page_size'])) {
            $params['P_PageSize'] = (int)$filters['page_size'];
        }
        if (isset($filters['sort'])) {
            $params['P_SortType'] = (int)$filters['sort'];
        }

        // Filtros básicos
        if (!empty($filters['location'])) {
            $params['P_Location'] = sanitize_text_field($filters['location']);
        }
        if (!empty($filters['type'])) {
            $params['P_PropertyType'] = sanitize_text_field($filters['type']);
        }
        if (!empty($filters['bedrooms'])) {
            $params['P_Beds'] = intval($filters['bedrooms']);
        }

        // Filtros de precio
        if (!empty($filters['minprice'])) {
            $params['P_PriceMin'] = intval($filters['minprice']);
        }
        if (!empty($filters['maxprice'])) {
            $params['P_PriceMax'] = intval($filters['maxprice']);
        }

        // Características especiales usando P_MustHaveFeatures y mapeo a parámetros exactos
        if (!empty($filters['features']) && is_array($filters['features'])) {
            $params['P_MustHaveFeatures'] = 1;
            // Mapeo de nombres de “feature” al parámetro exacto en la API V6
            $feature_map = [
                'pool'    => '1Pool1',
                'seaview' => '1SeaView',
                'garage'  => '1Garage',
                'garden'  => '1Garden',
                'terrace' => '1Terrace',
                'lift'    => '1Lift',
                'aircon'  => '1AirConditioning',
                // agrega más según lo que permite tu filtro API
            ];
            foreach ($filters['features'] as $feat) {
                if (isset($feature_map[$feat])) {
                    $params[$feature_map[$feat]] = 1;
                }
            }
        }

        // Caché basado en parámetros
        $cache_key = 'resales_v6_search_' . md5(json_encode($params));
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $resp = $this->http_get(self::SEARCH_ENDPOINT, $params, (int)$opts['timeout']);
        if (!is_wp_error($resp)) {
            set_transient($cache_key, $resp, self::CACHE_TTL);
        }
        return $resp;
    }

    public static function register_rest_endpoint() {
        register_rest_route('resales/v6', '/search', [
            'methods'             => 'GET',
            'callback'            => function($request) {
                $filters = [];
                foreach (['location','type','bedrooms','minprice','maxprice','page','page_size','sort'] as $k) {
                    $v = $request->get_param($k);
                    if ($v !== null) {
                        $filters[$k] = $v;
                    }
                }
                $features = $request->get_param('features');
                if ($features && is_array($features)) {
                    $filters['features'] = $features;
                }
                $self = new self();
                $result = $self->search_with_filters($filters);
                if (is_wp_error($result)) {
                    wp_send_json_error(['error' => $result->get_error_message(), 'data' => $result->get_error_data()]);
                } else {
                    wp_send_json_success($result);
                }
            },
            'permission_callback' => '__return_true',
        ]);
    }
}

// Registrar endpoint REST al inicializar
add_action('rest_api_init', ['Resales_Filters_V6', 'register_rest_endpoint']);

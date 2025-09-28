<?php
if (!defined('ABSPATH')) exit;

class Resales_Client {
    private static $instance = null;
    private $base = 'https://webapi.resales-online.com/V6';

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Busca propiedades usando V6, con lógica de filtro, localización y caché.
     * @param array $input Normalized payload from REST endpoint
     * @return array
     */
    public function search_properties_v6($params) {
        // 1) Base URL
        $base = 'https://webapi.resales-online.com/V6/SearchProperties';

        // 2) Start query with credentials
        $this->api_user = get_option('resales_api_p1');
        $this->api_key  = get_option('resales_api_p2');
        $query = [
            'p1' => $this->api_user,
            'p2' => $this->api_key,
        ];

        // 3) Merge $params from rest-filters-v6.php
        if (!empty($params)) $query = array_merge($query, $params);

        // 4) Enforce ONE filter ID if missing
        if (empty($query['P_Agency_FilterId']) && empty($query['P_ApiId'])) {
            $apiId = getenv('P_ApiId') ?: get_option('lusso_api_apiid');
            $agencyId = getenv('P_Agency_FilterId') ?: get_option('lusso_api_agency_filterid');
            if ($apiId) {
                $query['P_ApiId'] = $apiId;
            } elseif ($agencyId) {
                $query['P_Agency_FilterId'] = $agencyId;
            }
        }

        // 5) Enforce New Developments only
        $query['p_new_devs'] = 'only';

        // 6) p_sandbox for diagnostics
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query['p_sandbox'] = true;
        }

        // 7) Build URL and GET
        $url = $base . '?' . http_build_query($query);
        $timeout = (int) get_option('resales_api_timeout', 20);
        $args = [
            'timeout' => $timeout,
            'headers' => [ 'Accept' => 'application/json' ],
        ];
        $res = wp_remote_get($url, $args);
        if (is_wp_error($res)) return ['success'=>false, 'error'=>$res->get_error_message()];
        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200) {
            return ['success'=>false, 'error'=>'HTTP '.$code, 'body'=>$body];
        }
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success'=>false, 'error'=>'JSON error', 'body'=>$body];
        }
        return $json;
    }
}

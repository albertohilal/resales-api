<?php
// rest-filters-v6.php

class Resales_Filters_V6 {
    const API_BASE = 'https://webapi.resales-online.com/V6/';

    /** Lee y sanitiza las opciones de credenciales/configuración desde WordPress */
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
        $o['P_Lang']  = (int)($o['P_Lang'] ?: 1);
        $o['timeout'] = isset($o['timeout']) ? (int)$o['timeout'] : 20;
        return $o;
    }

    /** Realiza una petición GET a la API y maneja errores HTTP / JSON */
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

    /**
     * Realiza la búsqueda con filtros dados.
     * $filters puede incluir claves como: 'location', 'type', 'bedrooms', etc.
     */
    public function search_with_filters($filters = []) {
        $opts = $this->get_opt_all();

        // Parámetros base
        $params = [
            'p1'       => $opts['p1'],
            'p2'       => $opts['p2'],
            'p_sandbox'=> 'false',             // envía explícitamente sandbox = false
            'p_output' => 'JSON',
            'P_Lang'   => $opts['P_Lang'],
        ];

        // Decide qué parámetro de filtro usar: P_Agency_FilterId o P_ApiId
        if (!empty($opts['P_Agency_FilterId'])) {
            $params['P_Agency_FilterId'] = $opts['P_Agency_FilterId'];
        } elseif (!empty($opts['P_ApiId'])) {
            $params['P_ApiId'] = $opts['P_ApiId'];
        }

        // Opciones de paginación (puedes adaptarlas según tu interfaz)
        if (isset($filters['page'])) {
            $params['P_PageNo'] = (int)$filters['page'];
        }
        if (isset($filters['page_size'])) {
            $params['P_PageSize'] = (int)$filters['page_size'];
        }

        // Añade filtros adicionales si existen
        if (!empty($filters['location'])) {
            $params['P_Location'] = sanitize_text_field($filters['location']);
        }
        if (!empty($filters['type'])) {
            $params['P_PropertyType'] = sanitize_text_field($filters['type']);
        }
        if (!empty($filters['bedrooms'])) {
            $params['P_Beds'] = sanitize_text_field($filters['bedrooms']);
        }
        // Puedes añadir más según lo que soporte la API: precio min/max, baños, características, etc.

        return $this->http_get('SearchProperties', $params, $opts['timeout']);
    }
}

// Ejemplo de uso vía AJAX o endpoint REST
add_action('wp_ajax_resales_search', function () {
    $filters = [];
    if (isset($_GET['location'])) {
        $filters['location'] = sanitize_text_field($_GET['location']);
    }
    if (isset($_GET['type'])) {
        $filters['type'] = sanitize_text_field($_GET['type']);
    }
    if (isset($_GET['bedrooms'])) {
        $filters['bedrooms'] = intval($_GET['bedrooms']);
    }
    if (isset($_GET['page'])) {
        $filters['page'] = intval($_GET['page']);
    }
    if (isset($_GET['page_size'])) {
        $filters['page_size'] = intval($_GET['page_size']);
    }

    $resales = new Resales_Filters_V6();
    $response = $resales->search_with_filters($filters);
    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => $response->get_error_message(),
            'data'    => $response->get_error_data(),
        ]);
    }
    wp_send_json_success($response);
});

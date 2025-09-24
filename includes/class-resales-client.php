<?php
if (!defined('ABSPATH')) exit;

class Resales_Client {
    private $base_url;
    private $p1;
    private $p2;

    public function __construct($p1, $p2) {
        $this->p1 = $p1;
        $this->p2 = $p2;

        // 🔴 ANTES (incorrecto):
        // $this->base_url = 'https://webapi-v6.resales-online.com/';

        // 🟢 AHORA (correcto según documentación):
        $this->base_url = 'https://webapi.resales-online.com/V6/';
    }

    public function request($endpoint, $params = []) {
        $url = $this->base_url . ltrim($endpoint, '/');

        $defaults = [
            'p1' => $this->p1,
            'p2' => $this->p2,
        ];
        $args = [
            'timeout' => 20,
            'sslverify' => true,
        ];

        $url = add_query_arg(array_merge($defaults, $params), $url);

        error_log('[Resales_Client] URL: ' . $url);

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('[Resales_Client] HTTP error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('[Resales_Client] HTTP status: ' . $code);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (!$json) {
            error_log('[Resales_Client] JSON inválido: ' . substr($body, 0, 200));
            return false;
        }

        return $json;
    }

    public function search($params = []) {
        return $this->request('SearchProperties', $params);
    }

    public function list($params = []) {
        return $this->request('ListProperties', $params);
    }

    public function get_property($id, $params = []) {
        $params['propertyId'] = $id;
        return $this->request('GetPropertyDetails', $params);
    }
}

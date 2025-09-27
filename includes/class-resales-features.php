<?php
/**
 * Features dinámicos desde la WebAPI V6 (con caché)
 *
 * @package lusso-resales
 */

if (!defined('ABSPATH')) exit;

/**
 * Obtiene y cachea la lista de features (amenities) desde la WebAPI V6.
 *
 * @param int $lang Código de idioma (por defecto 2)
 * @return array|WP_Error Lista [['ParamName' => ..., 'Label' => ...], ...] o WP_Error
 */
function lusso_fetch_features($lang = 2) {
    $transient_key = 'lusso_features_' . intval($lang);
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        return $cached;
    }
    if (!class_exists('Resales_Client')) {
        require_once __DIR__ . '/class-resales-client.php';
    }
    $client = Resales_Client::instance();
    $params = [ 'P_Lang' => $lang ];
    $response = $client->request('SearchFeatures', $params);
    if (!$response || !isset($response['data']) || !is_array($response['data'])) {
        return new WP_Error('api_error', 'Error al obtener features', $response);
    }
    $features = [];
    foreach ($response['data'] as $item) {
        if (empty($item['ParamName']) || empty($item['Label'])) continue;
        $features[] = [
            'ParamName' => $item['ParamName'],
            'Label' => $item['Label'],
        ];
    }
    set_transient($transient_key, $features, 12 * HOUR_IN_SECONDS);
    return $features;
}

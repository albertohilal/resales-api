<?php
/**
 * Data layer para Locations y Property Types desde WebAPI V6
 *
 * @package lusso-resales
 */

if (!defined('ABSPATH')) exit;

/**
 * Obtiene y cachea las ubicaciones agrupadas por área desde la WebAPI V6.
 *
 * @param int $lang Código de idioma (por defecto 2)
 * @return array|WP_Error ['areas' => ['AreaName' => ['Location A', ...], ...]] o WP_Error
 */
function lusso_fetch_locations($lang = 2) {
    $transient_key = 'lusso_locations_v6_lang_' . intval($lang);
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;
    if (!class_exists('Resales_Client')) {
        require_once plugin_dir_path(__FILE__).'class-resales-client.php';
    }
    $client = new Resales_Client(
        defined('RESALES_API_P1') ? RESALES_API_P1 : '',
        defined('RESALES_API_P2') ? RESALES_API_P2 : ''
    );
    $params = [
        'P_All' => true,
        'P_Lang' => $lang,
    ];
    $response = $client->request('SearchLocations', $params);
    if (!is_array($response) || empty($response['data']) || !is_array($response['data'])) {
        return new WP_Error('api_error', 'Error al obtener locations', $response);
    }
    $areas = [];
    foreach ($response['data'] as $item) {
        if (empty($item['Area']) || empty($item['Location'])) continue;
        $areas[$item['Area']][] = $item['Location'];
    }
    $result = ['areas' => $areas];
    set_transient($transient_key, $result, 12 * HOUR_IN_SECONDS);
    return $result;
}

/**
 * Limpia el caché de locations para un idioma.
 * @param int $lang
 */
function lusso_clear_locations_cache($lang = 2) {
    delete_transient('lusso_locations_v6_lang_' . intval($lang));
}

/**
 * Obtiene y cachea los tipos de propiedad desde la WebAPI V6.
 *
 * @param int $lang Código de idioma (por defecto 2)
 * @return array|WP_Error Lista [['label' => ..., 'value' => ...], ...] o WP_Error
 */
function lusso_fetch_property_types($lang = 2) {
    $transient_key = 'lusso_prop_types_v6_lang_' . intval($lang);
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;
    if (!class_exists('Resales_Client')) {
        require_once plugin_dir_path(__FILE__).'class-resales-client.php';
    }
    $client = new Resales_Client(
        defined('RESALES_API_P1') ? RESALES_API_P1 : '',
        defined('RESALES_API_P2') ? RESALES_API_P2 : ''
    );
    $params = [ 'P_Lang' => $lang ];
    $response = $client->request('SearchPropertyTypes', $params);
    if (!is_array($response) || empty($response['data']) || !is_array($response['data'])) {
        return new WP_Error('api_error', 'Error al obtener property types', $response);
    }
    $types = [];
    foreach ($response['data'] as $item) {
        if (empty($item['Label']) || empty($item['Value'])) continue;
        $types[] = [
            'label' => $item['Label'],
            'value' => $item['Value'],
        ];
    }
    set_transient($transient_key, $types, 12 * HOUR_IN_SECONDS);
    return $types;
}

/**
 * Limpia el caché de property types para un idioma.
 * @param int $lang
 */
function lusso_clear_property_types_cache($lang = 2) {
    delete_transient('lusso_prop_types_v6_lang_' . intval($lang));
}



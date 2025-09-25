<?php
/**
 * Plugin Name:       Resales API
 * Description:       Integración con Resales WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version:           3.2.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/includes/class-resales-filters.php';
require_once __DIR__ . '/includes/class-resales-shortcodes.php';

// Encolar JS de filtros solo si la página contiene los shortcodes
add_action('wp_enqueue_scripts', function () {
    if (!is_singular()) return;
    $post = get_post();
    if (!$post) return;
    $has = has_shortcode($post->post_content, 'lusso_filters') || has_shortcode($post->post_content, 'lusso_properties');
    if (!$has) return;

    wp_enqueue_script(
        'resales-filters-v6',
        plugins_url('assets/js/filters.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );
    wp_localize_script('resales-filters-v6', 'RESALES_FILTERS', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('resales_filters_v6'),
    ));
});


// === Helpers API V6 (una sola vez) ===
if (!function_exists('resales_get_api_creds')) {
    function resales_get_api_creds() {
        return array(
            'p1'   => get_option('resales_api_p1'),
            'p2'   => get_option('resales_api_p2'),
            'fid'  => get_option('resales_agency_filter_id', 1),
            'lang' => get_option('resales_lang', 2), // 2 = Español
        );
    }
}

if (!function_exists('resales_v6_request')) {
    function resales_v6_request($function, $params = array()) {
        $c = resales_get_api_creds();
        $base = 'https://webapi.resales-online.com/V6/' . $function;
        $query = array_merge(array(
            'p1' => $c['p1'],
            'p2' => $c['p2'],
            'P_Agency_FilterId' => $c['fid'],
            'P_Lang' => $c['lang'],
            'P_sandbox' => 'false',
        ), $params);

        $url  = add_query_arg($query, $base);
        $resp = wp_remote_get($url, array('timeout' => 20));
        if (is_wp_error($resp)) return $resp;

        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) return new WP_Error('resales_http', 'HTTP ' . $code);

        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if (!$json) return new WP_Error('resales_json', 'JSON inválido');
        return $json;
    }
}

// === Endpoints AJAX (una sola vez, bien cerrados) ===
if (!function_exists('resales_v6_locations')) {
    function resales_v6_locations() {
        check_ajax_referer('resales_filters_v6', 'nonce');
        $r = resales_v6_request('SearchLocations', array('P_All' => 'TRUE', 'P_SortType' => 1));
        if (is_wp_error($r)) wp_send_json_error($r->get_error_message(), 500);

        $items = array();

        // Caso 1: estructura Location->Locations
        if (!empty($r['Location']['Locations'])) {
            foreach ($r['Location']['Locations'] as $loc) {
                $items[] = array(
                    'province' => $loc['Province'] ?? '',
                    'area'     => $loc['Area'] ?? '',
                    'name'     => $loc['Name'] ?? '',
                );
            }
        }

        // Caso 2: Country->Province->Area (normalizar a la misma salida)
        if (empty($items) && !empty($r['Country']['Province'])) {
            $provList = $r['Country']['Province'];
            if (isset($provList['@attributes'])) $provList = array($provList);
            foreach ($provList as $prov) {
                $pName = $prov['@attributes']['Name'] ?? ($prov['Name'] ?? '');
                $areas = $prov['Area'] ?? array();
                if (isset($areas['Name'])) $areas = array($areas);
                foreach ($areas as $area) {
                    $items[] = array(
                        'province' => $pName,
                        'area'     => $area['Name'] ?? '',
                        'name'     => $area['Name'] ?? '',
                    );
                }
            }
        }

        wp_send_json_success($items);
    }
}

if (!function_exists('resales_v6_types')) {
    function resales_v6_types() {
        check_ajax_referer('resales_filters_v6', 'nonce');
        $r = resales_v6_request('SearchPropertyTypes', array());
        if (is_wp_error($r)) wp_send_json_error($r->get_error_message(), 500);

        $items = array();
        $list  = array();

        // Estructuras posibles en V6
        if (!empty($r['PropertyType']['Type']))          $list = $r['PropertyType']['Type'];
        if (!empty($r['PropertyTypes']['PropertyType'])) $list = $r['PropertyTypes']['PropertyType'];
        if (isset($list['Name'])) $list = array($list);

        foreach ($list as $t) {
            $items[] = array(
                'value' => $t['OptionValue'] ?? '',
                'text'  => $t['Name']        ?? '',
            );
        }
        wp_send_json_success($items);
    }
}

// Registrar hooks una sola vez
if (!has_action('wp_ajax_resales_v6_locations', 'resales_v6_locations')) {
    add_action('wp_ajax_resales_v6_locations', 'resales_v6_locations');
    add_action('wp_ajax_nopriv_resales_v6_locations', 'resales_v6_locations');
}
if (!has_action('wp_ajax_resales_v6_types', 'resales_v6_types')) {
    add_action('wp_ajax_resales_v6_types', 'resales_v6_types');
    add_action('wp_ajax_nopriv_resales_v6_types', 'resales_v6_types');
}

// === Settings API: Flag “Filtros V6 (API) habilitados” en Ajustes → Generales ===
// (usar register_setting + add_settings_field para que el valor se guarde correctamente) :contentReference[oaicite:3]{index=3}
add_action('admin_init', 'resales_register_v6_flag');
function resales_register_v6_flag() {
    register_setting('general', 'resales_filters_v6_enabled', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 0,
        'show_in_rest'      => false,
    ));
    add_settings_field(
        'resales_filters_v6_enabled',
        __('Filtros V6 (API) habilitados', 'resales'),
        'resales_render_v6_flag',
        'general'
    );
}
function resales_render_v6_flag() {
    $val = (int) get_option('resales_filters_v6_enabled', 0);
    echo '<label for="resales_filters_v6_enabled">';
    echo '<input type="checkbox" id="resales_filters_v6_enabled" name="resales_filters_v6_enabled" value="1" ' . checked(1, $val, false) . ' />';
    echo ' ' . esc_html__('Activar filtros V6 desde API', 'resales');
    echo '</label>';
}
// Activation hook: asegurar opción con default 0 (declarado en el archivo principal del plugin)
register_activation_hook(__FILE__, 'resales_v6_flag_activate');
function resales_v6_flag_activate() {
    if (get_option('resales_filters_v6_enabled', null) === null) {
        add_option('resales_filters_v6_enabled', 0);
    }
}

// === Enqueue del JS de filtros solo si el flag está ON y estamos en la página adecuada ===
add_action('wp_enqueue_scripts', function () {
    if (!get_option('resales_filters_v6_enabled')) return;

    $is_properties_page = function_exists('is_page') && is_page('properties');
    $has_shortcode = is_singular() && has_shortcode(get_post_field('post_content', get_the_ID()), 'lusso_properties');
    if (!$is_properties_page && !$has_shortcode) return;

    wp_enqueue_script(
        'resales-filters-v6',
        plugins_url('assets/js/filters.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );
    wp_localize_script('resales-filters-v6', 'RESALES_FILTERS', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('resales_filters_v6'),
    ));
});
// === Helpers / Credenciales API ===




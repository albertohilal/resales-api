<?php
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version: 3.3.0
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

/* ===========================
 *  Constantes globales
 * =========================== */
if (!defined('RESALES_API_HOST'))     define('RESALES_API_HOST', 'webapi.resales-online.com');
if (!defined('RESALES_API_HOST_IP'))  define('RESALES_API_HOST_IP', '');
if (!defined('RESALES_LOG_LEVEL'))    define('RESALES_LOG_LEVEL', 'WARN');
if (!defined('LUSSO_PLUGIN_DIR'))     define('LUSSO_PLUGIN_DIR', plugin_dir_path(__FILE__));

/* ===========================
 *  Helper de logging
 * =========================== */
if (!function_exists('resales_log')) {
    function resales_log($level, $msg, $context = null) {
        $levels = ['DEBUG'=>0,'INFO'=>1,'WARN'=>2,'ERROR'=>3];
        $cfg = defined('RESALES_LOG_LEVEL') ? RESALES_LOG_LEVEL : 'WARN';
        if (!isset($levels[$level]) || $levels[$level] < $levels[$cfg]) return;
        $line = '[resales-api]['.$level.'] '.$msg;
        if ($context !== null) {
            if (is_array($context) || is_object($context)) {
                $ctx = (is_array($context) && count($context) > 3)
                    ? array_slice($context, 0, 3, true) + ['__truncated__' => true]
                    : $context;
                $line .= ' | ' . wp_json_encode($ctx);
            } else {
                $line .= ' | ' . (string)$context;
            }
        }
        error_log($line);
    }
}

/* ===========================
 *  AJAX: búsqueda de propiedades
 * =========================== */
add_action('wp_ajax_lusso_search_properties', 'lusso_search_properties_ajax');
add_action('wp_ajax_nopriv_lusso_search_properties', 'lusso_search_properties_ajax');

function lusso_search_properties_ajax() {
    $p1 = get_option('resales_api_p1');
    $p2 = get_option('resales_api_p2');
    $apiid = get_option('resales_api_apiid');
    $filter_id = get_option('resales_api_filter_id') ?: get_option('lusso_agency_filter_id');

    $api_key_masked = $p2 ? substr($p2, 0, 4) . str_repeat('*', max(0, strlen($p2)-8)) . substr($p2, -4) : '';
    resales_log('DEBUG', '[AJAX][CRED] p1=' . $p1 . ' p2=' . $api_key_masked . ' FilterId=' . ($filter_id ?: $apiid));
    resales_log('DEBUG', '[AJAX][ARGS] ' . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));

    $params = [
        'p1' => $p1,
        'p2' => $p2,
        'P_sandbox' => true,
    ];
    if ($filter_id) $params['P_Agency_FilterId'] = $filter_id;
    elseif ($apiid) $params['P_ApiId'] = $apiid;

    $type_map = [
        'apartment' => '1-1',
        'house'     => '2-1',
        'plot'      => '3-1',
    ];

    $type = isset($_REQUEST['type']) ? strtolower(trim($_REQUEST['type'])) : '';
    if ($type && isset($type_map[$type])) {
        $params['P_PropertyTypes'] = $type_map[$type];
    }

    if (!empty($_REQUEST['location'])) {
        $params['P_Location'] = sanitize_text_field($_REQUEST['location']);
    }

    $min_beds = null;
    if (!empty($_REQUEST['bedrooms']) && is_numeric($_REQUEST['bedrooms'])) {
        $min_beds = intval($_REQUEST['bedrooms']);
        $params['P_Beds'] = $min_beds . 'x';
    }

    $params['p_new_devs'] = 'only';

    $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($params);
    $res = wp_remote_get($url, ['timeout' => (int)get_option('resales_api_timeout', 20), 'headers' => ['Accept' => 'application/json']]);
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    $json = json_decode($body, true);

    if ($code !== 200) wp_send_json_error(['error' => 'HTTP '.$code, 'body' => $body], $code);
    wp_send_json_success($json);
}

/* ===========================
 *  Carga de estilos y scripts
 * =========================== */
add_action('wp_enqueue_scripts', function() {

    // Estilos globales
    wp_enqueue_style('lusso-resales', plugins_url('assets/css/lusso-resales.css', __FILE__), [], '2.1');
    wp_enqueue_style('lusso-resales-filters', plugins_url('assets/css/lusso-resales-filters.css', __FILE__), [], '2.1');
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    wp_enqueue_style('lusso-swiper-gallery', plugins_url('assets/css/swiper-gallery.css', __FILE__), ['swiper-css'], '2.1');
    
    // CSS de detalle solo en páginas que lo necesiten
    global $post;
    $enqueue_detail_css = false;
    
    // Verificar si es template single-property.php
    if (is_page_template('single-property.php')) {
        $enqueue_detail_css = true;
    }
    
    // Verificar si la página contiene el shortcode resales_single
    if ($post && has_shortcode($post->post_content, 'resales_single')) {
        $enqueue_detail_css = true;
    }
    
    // Verificar si es una URL tipo /property/ID/slug/
    if (preg_match('#/property/\d+/#', $_SERVER['REQUEST_URI'])) {
        $enqueue_detail_css = true;
    }
    
    if ($enqueue_detail_css) {
        wp_enqueue_style('lusso-resales-detail', plugins_url('assets/css/lusso-resales-detail.css', __FILE__), [], '2.0');
    }

    // Scripts globales
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_enqueue_script('lusso-swiper-init', plugins_url('assets/js/swiper-init.js', __FILE__), ['swiper-js'], '1.0', true);

    // Eliminar legacy filters si existe
    wp_dequeue_script('lusso-newdevs-filters');
    wp_deregister_script('lusso-newdevs-filters');

    // Cargar solo el script definitivo en /properties/
    if (is_page('properties')) {
        wp_enqueue_script(
            'lusso-filters',
            plugins_url('assets/js/filters.js', __FILE__),
            ['jquery'],
            '1.2',
            true
        );
    }
}, 20);

/* ===========================
 *  Helper de clases
 * =========================== */
function resales_api_require($rel_path){
    $path = plugin_dir_path(__FILE__) . ltrim($rel_path, '/');
    if (file_exists($path)) { require_once $path; return true; }
    error_log('[Resales API] No se pudo cargar: ' . $rel_path);
    return false;
}

/* ===========================
 *  IPv4 pin + diagnóstico DNS
 * =========================== */
add_action('http_api_curl', function($handle){
    if (defined('CURL_IPRESOLVE_V4')) curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    if (defined('RESALES_API_HOST_IP') && RESALES_API_HOST_IP !== '') {
        $url = @curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        if (is_string($url) && stripos($url, RESALES_API_HOST) !== false) {
            curl_setopt($handle, CURLOPT_RESOLVE, [ RESALES_API_HOST . ':443:' . RESALES_API_HOST_IP ]);
            error_log('[Resales API][RESOLVE] Pin aplicado: ' . RESALES_API_HOST . ' -> ' . RESALES_API_HOST_IP);
        }
    }
}, 10, 1);

/* ===========================
 *  Bootstrap del plugin
 * =========================== */
require_once plugin_dir_path(__FILE__).'includes/class-resales-data.php';

add_action('plugins_loaded', function () {
    resales_api_require('includes/class-resales-client.php');
    resales_api_require('includes/class-resales-settings.php');
    resales_api_require('includes/class-resales-shortcodes.php');
    resales_api_require('includes/class-resales-admin.php');
    resales_api_require('includes/class-resales-single.php');
    resales_api_require('includes/class-resales-filters.php');

    if (class_exists('Resales_Settings'))  Resales_Settings::instance();
    if (class_exists('Lusso_Resales_Shortcodes')) new Lusso_Resales_Shortcodes();
    if (class_exists('Resales_Shortcodes')) new Resales_Shortcodes();
    if (class_exists('Resales_Single'))     Resales_Single::instance();
    if (class_exists('Resales_Filters_Shortcode')) new Resales_Filters_Shortcode();
    if (is_admin() && class_exists('Resales_Admin')) Resales_Admin::instance();

    require_once plugin_dir_path(__FILE__).'includes/lusso-ping-endpoint.php';
    require_once plugin_dir_path(__FILE__).'includes/rest-filters-v6.php';
});

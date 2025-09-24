<?php
// ===========================
//  Endpoints AJAX públicos (logueados y no logueados)
// ===========================
if (!defined('LUSSO_PLUGIN_DIR')) {
    define('LUSSO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
add_action('wp_ajax_lusso_ping','lusso_ping');
add_action('wp_ajax_nopriv_lusso_ping','lusso_ping');
add_action('wp_ajax_lusso_debug_locations','lusso_debug_locations');
add_action('wp_ajax_nopriv_lusso_debug_locations','lusso_debug_locations');
add_action('wp_ajax_lusso_debug_types','lusso_debug_types');
add_action('wp_ajax_nopriv_lusso_debug_types','lusso_debug_types');

function lusso_ping(){ wp_send_json_success(['ok'=>1]); }
function lusso_debug_locations(){
    require_once LUSSO_PLUGIN_DIR.'includes/class-resales-data.php';
    $lang = isset($_GET['lang']) ? (int)$_GET['lang'] : 1;
    $data = lusso_fetch_locations($lang);
    is_wp_error($data) ? wp_send_json_error($data->get_error_message(),502) : wp_send_json_success($data);
}
function lusso_debug_types(){
    require_once LUSSO_PLUGIN_DIR.'includes/class-resales-data.php';
    $lang = isset($_GET['lang']) ? (int)$_GET['lang'] : 1;
    $data = lusso_fetch_property_types($lang);
    is_wp_error($data) ? wp_send_json_error($data->get_error_message(),502) : wp_send_json_success($data);
}
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version: 3.2.5
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

/* =======================================
 *  Host oficial WebAPI (NO cambiar)
 *  (Las clases deben usar https://webapi.resales-online.com/V6/…)
 * ======================================= */
if (!defined('RESALES_API_HOST')) {
    define('RESALES_API_HOST', 'webapi.resales-online.com');
}

/* =======================================
 *  Pin DNS opcional (solo si el hosting falla resolviendo DNS)
 *  - Déjalo '' para DESACTIVAR el pin.
 *  - Si necesitas pin, pon una IPv4 válida del host:
 *      dig +short webapi.resales-online.com
 * ======================================= */
if (!defined('RESALES_API_HOST_IP')) {
    define('RESALES_API_HOST_IP', ''); // ej: '34.175.62.143'
}

/* ===========================
 *  Estilos / scripts frontend
 * =========================== */
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('lusso-resales', plugins_url('assets/css/lusso-resales.css', __FILE__), [], '1.0');
    wp_enqueue_style('lusso-resales-filters', plugins_url('assets/css/lusso-resales-filters.css', __FILE__), [], '1.0');
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    wp_enqueue_style('lusso-swiper-gallery', plugins_url('assets/css/swiper-gallery.css', __FILE__), ['swiper-css'], '1.0');
    wp_enqueue_style('lusso-resales-detail', plugins_url('assets/css/lusso-resales-detail.css', __FILE__), [], '1.0');
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_enqueue_script('lusso-swiper-init', plugins_url('assets/js/swiper-init.js', __FILE__), ['swiper-js'], '1.0', true);

    // JS para el formulario de filtros V6 solo en LISTING_PATH y si flag ON
    $listing_path = getenv('LISTING_PATH') ?: '/properties/';
    if (get_option('filters_v6_enabled') && is_page() && untrailingslashit($_SERVER['REQUEST_URI']) === untrailingslashit($listing_path)) {
        wp_enqueue_script(
            'filters-js',
            plugins_url('assets/js/filters.js', __FILE__),
            [],
            '1.0',
            true
        );
        // Inyectar opciones de dormitorios
        if (class_exists('Lusso_Resales_Filters_V6')) {
            $provider = new Lusso_Resales_Filters_V6();
            wp_localize_script('filters-js', 'LUSSO_BEDROOMS', $provider->get_bedrooms_options());
        }
    }

    // JS para el formulario de filtros legacy (si lo usas)
    if (file_exists(plugin_dir_path(__FILE__).'assets/js/lusso-newdevs-filters.js')) {
        wp_enqueue_script(
            'lusso-newdevs-filters',
            plugins_url('assets/js/lusso-newdevs-filters.js', __FILE__),
            [],
            '1.0',
            true
        );
        wp_localize_script('lusso-newdevs-filters', 'LUSSO_NEWDEVS', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('lusso_newdevs_nonce'),
        ]);
    }
});

/* ===========================
 *  Helper de carga de clases
 * =========================== */
function resales_api_require($rel_path){
    $path = plugin_dir_path(__FILE__) . ltrim($rel_path, '/');
    if (file_exists($path)) { require_once $path; return true; }
    error_log('[Resales API] No se pudo cargar: ' . $rel_path);
    return false;
}

/* ==================================================
 *  Forzar IPv4 + PIN DNS (sin romper el certificado)
 *  - Solo aplica si la URL objetivo incluye RESALES_API_HOST
 *  - Mantiene verificación TLS (no tocar sslverify!)
 * ================================================== */
add_action('http_api_curl', function($handle){
    if (defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    if (defined('RESALES_API_HOST_IP') && RESALES_API_HOST_IP !== '') {
        $effective = @curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
        if (is_string($effective) && stripos($effective, RESALES_API_HOST) !== false) {
            curl_setopt($handle, CURLOPT_RESOLVE, [
                RESALES_API_HOST . ':443:' . RESALES_API_HOST_IP
            ]);
            error_log('[Resales API][RESOLVE] Pin aplicado: ' . RESALES_API_HOST . ' -> ' . RESALES_API_HOST_IP);
        }
    }
}, 10, 1);

/* ===========================
 *  Bootstrap (clases del plugin)
 * =========================== */
// Incluir SIEMPRE el data layer para exponer los handlers AJAX
require_once plugin_dir_path(__FILE__).'includes/class-resales-data.php';

add_action('plugins_loaded', function () {
    // Núcleo
    resales_api_require('includes/class-resales-client.php');
    resales_api_require('includes/class-resales-settings.php');
    resales_api_require('includes/class-resales-shortcodes.php');
    resales_api_require('includes/class-resales-admin.php');
    resales_api_require('includes/class-resales-single.php');

    // NUEVO: shortcode SOLO de filtros
    resales_api_require('includes/class-resales-filters.php');

    // Inicializar
    if (class_exists('Resales_Settings'))  Resales_Settings::instance();
    if (class_exists('Lusso_Resales_Shortcodes')) new Lusso_Resales_Shortcodes(); // tarjetas/listados
    if (class_exists('Resales_Shortcodes')) new Resales_Shortcodes();
    if (class_exists('Resales_Single'))     Resales_Single::instance();
    if (class_exists('Resales_Filters_Shortcode')) new Resales_Filters_Shortcode(); // SOLO filtros
    if (is_admin() && class_exists('Resales_Admin')) Resales_Admin::instance();

    // Endpoint AJAX ping (diagnóstico)
    require_once plugin_dir_path(__FILE__).'includes/lusso-ping-endpoint.php';

    // Registrar endpoints REST de filtros V6 SIEMPRE
    require_once plugin_dir_path(__FILE__).'includes/rest-filters-v6.php';
});

/* ===========================
 *  Requisitos mínimos
 * =========================== */
register_activation_hook(__FILE__, function () {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die('Resales API requiere PHP 7.4 o superior.');
    }
});

/* ===========================
 *  Diagnóstico DNS / TLS
 * =========================== */
add_action('init', function () {
    if (!current_user_can('manage_options')) return;
    if (get_transient('resales_dns_diag_ran')) return;
    set_transient('resales_dns_diag_ran', 1, 10 * MINUTE_IN_SECONDS);

    $host = RESALES_API_HOST;

    if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
        error_log('[Resales API][DNS] ATENCIÓN: WP_HTTP_BLOCK_EXTERNAL está ACTIVO.');
    }

    $resolved = @gethostbyname($host);
    if ($resolved && $resolved !== $host) {
        error_log('[Resales API][DNS] gethostbyname(' . $host . ') => ' . $resolved);
    } else {
        error_log('[Resales API][DNS] No se pudo resolver host: ' . $host . ' (cURL error 6 probable). Si el hosting no arregla DNS, usa RESALES_API_HOST_IP con una IPv4 válida del host.');
    }
});

/* ===========================
 *  Aviso visual en Admin
 * =========================== */
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;

    $host     = RESALES_API_HOST;
    $resolved = @gethostbyname($host);
    $blocked  = (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL);

    if ($blocked || !$resolved || $resolved === $host) {
        echo '<div class="notice notice-error"><p><strong>Resales API:</strong> ';
        if ($blocked) {
            echo 'Detectado <code>WP_HTTP_BLOCK_EXTERNAL</code> activo. ';
        }
        if (!$resolved || $resolved === $host) {
            echo 'El servidor no resuelve <code>'.$host.'</code>. Contacta al hosting o configura temporalmente <code>RESALES_API_HOST_IP</code> con una IPv4 válida del host.';
        }
        echo '</p></div>';
    }
});

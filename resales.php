<?php
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version: 3.2.1
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Encolar CSS para el grid (cards + overlay)

add_action('wp_enqueue_scripts', function(){
    // CSS principal del grid
    wp_enqueue_style('lusso-resales', plugins_url('assets/css/lusso-resales.css', __FILE__), [], '1.0');
    // Swiper CSS desde CDN
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
    // Swiper CSS personalizado
    wp_enqueue_style('lusso-swiper-gallery', plugins_url('assets/css/swiper-gallery.css', __FILE__), ['swiper-css'], '1.0');
    // Swiper JS desde CDN
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    // Inicializador personalizado
    wp_enqueue_script('lusso-swiper-init', plugins_url('assets/js/swiper-init.js', __FILE__), ['swiper-js'], '1.0', true);
});

// Helper de carga segura
function resales_api_require($rel_path){
    $path = plugin_dir_path(__FILE__) . ltrim($rel_path, '/');
    if (file_exists($path)) { require_once $path; return true; }
    error_log('[Resales API] No se pudo cargar: ' . $rel_path);
    return false;
}

// Bootstrap
add_action('plugins_loaded', function () {
    // Ajusta rutas si cambias la estructura
    resales_api_require('includes/class-resales-client.php');
    resales_api_require('includes/class-resales-settings.php');
    resales_api_require('includes/class-resales-shortcodes.php');
    resales_api_require('includes/class-resales-admin.php');
    // (NUEVO) Vista detalle
    resales_api_require('includes/class-resales-single.php');

    // Inicializar
    if (class_exists('Resales_Settings'))  Resales_Settings::instance();
    if (class_exists('Lusso_Resales_Shortcodes')) new Lusso_Resales_Shortcodes();
    if (class_exists('Resales_Shortcodes')) new Resales_Shortcodes();
    if (class_exists('Resales_Single'))     Resales_Single::instance();
    if (is_admin() && class_exists('Resales_Admin')) Resales_Admin::instance();
});

// Requisitos mínimos
register_activation_hook(__FILE__, function () {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die('Resales API requiere PHP 7.4 o superior.');
    }
});

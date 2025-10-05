<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('lusso-filters', plugins_url('assets/js/filters.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('lusso-filters', 'myAjax', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
});
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('lusso-filters', plugins_url('assets/js/filters.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('lusso-filters', 'myAjax', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
});
/**
 * Plugin Name: Resales API
 * Description: Integración con Resales Online WebAPI V6 (shortcodes, ajustes, diagnóstico y cliente HTTP).
 * Version: 3.2.5
 * Author: Dev Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
// Handler AJAX para filtro de propiedades con Type seguro (Apartment, House, Plot)
add_action('wp_ajax_lusso_search_properties_type', 'lusso_search_properties_type_ajax');
add_action('wp_ajax_nopriv_lusso_search_properties_type', 'lusso_search_properties_type_ajax');

/**
 * AJAX handler para filtrar propiedades por Type (solo Apartment, House, Plot)
 * Recibe: type (apartment|house|plot), location, bedrooms, etc.
 * Mapea type a OptionValue, llama a SearchProperties y devuelve JSON seguro.
 */
/**
 * AJAX handler para filtrar propiedades por Type (solo Apartment, House, Plot)
 * Recibe: type (apartment|house|plot), location, bedrooms, etc.
 * Mapea type a OptionValue, llama a SearchProperties y devuelve JSON seguro.
 */
// ...duplicado eliminado...
    // Obtener credenciales y filtros
    $p1 = get_option('resales_api_p1');
    $p2 = get_option('resales_api_p2');
    $filter_id = get_option('resales_api_filter_id');
    $sandbox = 'false';

    // Normalizar y validar parámetros recibidos
        $type     = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : '';

    // Mapeo seguro de Type visible a OptionValue API
    $type_map = [
        'apartment' => '1-1', // Apartment
        'house'     => '2-1', // House
        'plot'      => '3-1', // Plot
    ];
    if ($type && !isset($type_map[$type])) {
        wp_send_json_error(['error' => 'Invalid property type'], 400);
    }
    $type_api = $type ? $type_map[$type] : '';

    // Construir parámetros para la API
    $params = [
        'p1' => $p1,
        'p2' => $p2,
        'p_agency_filterid' => $filter_id,
        'p_sandbox' => $sandbox,
        'p_output' => 'JSON',
        'p_new_devs' => 'only',
    ];
    if ($type_api)   $params['P_PropertyTypes'] = $type_api;
    if ($location)   $params['P_Location'] = $location;
        if ($bedrooms)   $params['P_Beds'] = $bedrooms; // Sin sufijo 'x' si la API no lo requiere

    // Construir URL y hacer la petición HTTP
    $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($params);
    $response = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($response)) {
        wp_send_json_error(['error' => 'API request failed', 'details' => $response->get_error_message()], 500);
    }
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    // Manejo de errores y compatibilidad con la estructura de respuesta
    $properties = [];
    if (is_array($json)) {
        if (isset($json['Properties']) && is_array($json['Properties'])) {
            $properties = $json['Properties'];
        } elseif (isset($json['Property']) && is_array($json['Property'])) {
            $properties = $json['Property'];
        }
    }
    if (empty($properties)) {
        wp_send_json_error(['error' => 'No properties found', 'raw' => $json], 200);
    }
    // Devolver solo la parte útil al frontend
    wp_send_json_success([
        'properties' => $properties,
        'raw' => $json,
    ]);
}
// ...existing code...
require_once __DIR__ . '/includes/class-resales-rest.php';
// Helper de logging para Resales API (debe estar disponible para todas las clases)
if (!function_exists('resales_log')) {
    function resales_log($level, $msg, $context = null) {
        $levels = ['DEBUG'=>0,'INFO'=>1,'WARN'=>2,'ERROR'=>3];
        $cfg    = defined('RESALES_LOG_LEVEL') ? RESALES_LOG_LEVEL : 'WARN';
        if (!isset($levels[$level]) || $levels[$level] < $levels[$cfg]) return;
        $line = '[resales-api]['.$level.'] '.$msg;
        if ($context !== null) {
            if (is_array($context) || is_object($context)) {
                $ctx = $context;
                if (is_array($ctx) && count($ctx) > 3) {
                    $ctx = array_slice($ctx, 0, 3, true);
                    $ctx['__truncated__'] = true;
                }
                $line .= ' | ' . wp_json_encode($ctx);
            } else {
                $line .= ' | ' . (string)$context;
            }
        }
        error_log($line);
    }
}
// Handler AJAX para búsqueda de propiedades (V6 SearchProperties)
add_action('wp_ajax_lusso_search_properties', 'lusso_search_properties_ajax');
add_action('wp_ajax_nopriv_lusso_search_properties', 'lusso_search_properties_ajax');
function lusso_search_properties_ajax() {
    // --- LOG CREDENCIALES Y ARGUMENTOS ENVIADOS ---
    $api_key_masked = $p2 ? substr($p2, 0, 4) . str_repeat('*', max(0, strlen($p2)-8)) . substr($p2, -4) : '';
    resales_log('DEBUG', '[AJAX][CRED] p1=' . $p1 . ' p2=' . $api_key_masked . ' FilterId=' . ($filter_id ?: $apiid));
    resales_log('DEBUG', '[AJAX][ARGS] ' . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));
    // 1. Credenciales
    $p1 = get_option('resales_api_p1');
    $p2 = get_option('resales_api_p2');
    // 2. Filtro: usar solo uno
    $filter_id = get_option('resales_api_filter_id') ?: get_option('lusso_agency_filter_id');
    $params = [
        'p1' => $p1,
        'p2' => $p2,
        'P_sandbox' => true,
    ];
    if ($filter_id) {
        $params['P_Agency_FilterId'] = $filter_id;
    } elseif ($apiid = get_option('resales_api_apiid')) {
        $params['P_ApiId'] = $apiid;
    }
    // 3. Mapear argumentos
    if (!empty($_REQUEST['location'])) {
        $params['P_Location'] = sanitize_text_field($_REQUEST['location']);
    }
    if (!empty($_REQUEST['type'])) {
        $params['P_PropertyTypes'] = sanitize_text_field($_REQUEST['type']);
    }
    $min_beds = null;
    if (!empty($_REQUEST['bedrooms']) && is_numeric($_REQUEST['bedrooms'])) {
        $min_beds = intval($_REQUEST['bedrooms']);
        $params['P_Beds'] = $min_beds . 'x';
    }
    $params['p_new_devs'] = 'only';
    error_log('[Resales API][LOG] Params enviados a API: ' . json_encode($params));
    // Nunca enviar "Area"
    // 4. Construir URL y hacer GET
    $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($params);
    $timeout = (int) get_option('resales_api_timeout', 20);
    $args = [
        'timeout' => $timeout,
        'headers' => [ 'Accept' => 'application/json' ],
    ];
    $res = wp_remote_get($url, $args);
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    $json = json_decode($body, true);
    error_log('[Resales API][LOG] Respuesta API: ' . json_encode($json));
    if (isset($json['Property'])) {
        error_log('[Resales API][LOG] Total propiedades recibidas: ' . count($json['Property']));
        foreach ($json['Property'] as $prop) {
            error_log('[Resales API][LOG] Propiedad: Ref=' . $prop['Reference'] . ' | Dormitorios=' . $prop['Bedrooms']);
        }
    }
    // Filtro defensivo antes de enviar al frontend
    if ($min_beds !== null && isset($json['Property'])) {
        $json['Property'] = array_filter($json['Property'], function($prop) use ($min_beds) {
            return cumple_minimo_dormitorios($min_beds, $prop['Bedrooms']);
        });
    }
    // 5. Loguear transaction si existe
    if (isset($json['transaction'])) {
        error_log('[resales-api][DEBUG] transaction=' . wp_json_encode($json['transaction']));
    }
    // 6. Salida JSON estándar
    if ($code !== 200) {
        wp_send_json_error(['error'=>'HTTP '.$code, 'body'=>$body], $code);
    }
    wp_send_json_success($json);
// --- Filtro defensivo mejorado ---
function cumple_minimo_dormitorios($min, $bedrooms_str) {
    $bedrooms_str = trim(str_replace(['–', 'a', ','], ['-', '-', '-'], $bedrooms_str));
    // "2 - 4"
    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $bedrooms_str, $m)) {
        return intval($m[2]) >= $min;
    }
    // "5+"
    if (preg_match('/^(\d+)\+$/', $bedrooms_str, $m)) {
        return intval($m[1]) >= $min;
    }
    // "7"
    if (preg_match('/^(\d+)$/', $bedrooms_str, $m)) {
        return intval($m[1]) >= $min;
    }
    // "4-5" o "4 a 5"
    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $bedrooms_str, $m)) {
        return intval($m[2]) >= $min;
    }
    return false;
}
}


// ================================
// Helper de logging para Resales API
// ================================
if (!defined('RESALES_LOG_LEVEL')) define('RESALES_LOG_LEVEL', 'WARN'); // DEBUG|INFO|WARN|ERROR
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

function lusso_search_properties_type_ajax() {
    // Obtener credenciales y configuración de la API
    $p1 = get_option('resales_api_p1');
    $p2 = get_option('resales_api_p2');
    $filter_id = get_option('resales_api_filter_id');
    $sandbox = 'false';

    // Recoger y normalizar los valores POST
    $type     = isset($_POST['type']) ? strtolower(trim(sanitize_text_field($_POST['type']))) : '';
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : '';

    error_log("AJAX received: type=$type, location=$location, bedrooms=$bedrooms");

    // Mapeo seguro de Type visible a OptionValue API
    $type_map = [
        'apartment' => '1-1',
        'house'     => '2-1',
        'plot'      => '3-1',
    ];
    if ($type && !isset($type_map[$type])) {
        error_log("Invalid property type: $type");
        wp_send_json_error(['error' => 'Invalid property type'], 400);
        wp_die();
    }
    $type_api = $type ? $type_map[$type] : '';

    // Construir parámetros para la API
    $params = [
        'p1' => $p1,
        'p2' => $p2,
        'p_agency_filterid' => $filter_id,
        'p_sandbox' => $sandbox,
        'p_output' => 'JSON',
        'p_new_devs' => 'only',
    ];
    if ($type_api)   $params['P_PropertyTypes'] = $type_api;
    if ($location)   $params['P_Location'] = $location;
    if ($bedrooms)   $params['P_Beds'] = $bedrooms;

    $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($params);
    error_log("API URL: $url");

    // Petición a la API
    $response = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($response)) {
        error_log("API request failed: " . $response->get_error_message());
        wp_send_json_error(['error' => 'API request failed', 'details' => $response->get_error_message()], 500);
        wp_die();
    }
    $body = wp_remote_retrieve_body($response);
    error_log("API Body: " . $body);

    $json = json_decode($body, true);
    error_log("API JSON: " . print_r($json, true));

    // Manejo de errores y compatibilidad con la estructura de respuesta
    $properties = [];
    if (is_array($json)) {
        if (isset($json['Properties']) && is_array($json['Properties'])) {
            $properties = $json['Properties'];
        } elseif (isset($json['Property']) && is_array($json['Property'])) {
            $properties = $json['Property'];
        }
    }
    if (empty($properties)) {
        error_log("No properties found in response.");
        wp_send_json_error(['error' => 'No properties found', 'raw' => $json], 200);
        wp_die();
    }
    // Devolver solo la parte útil al frontend
    error_log("Returning " . count($properties) . " properties.");
    wp_send_json_success([
        'properties' => $properties,
        'raw' => $json,
    ]);
    wp_die();
}

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

    // Incluir siempre el endpoint REST de filtros V6
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

add_action('admin_init', function(){
    if (!current_user_can('manage_options')) return;
    if (!get_option('resales_api_apiid')) update_option('resales_api_apiid', 65503);
    if (!get_option('lusso_agency_filter_id')) update_option('lusso_agency_filter_id', 65503);
});

// Incluir la página de ajustes si existe
if (file_exists(__DIR__ . '/includes/resales-api-settings.php')) {
    require_once __DIR__ . '/includes/resales-api-settings.php';
}

function build_search_properties_params(array $args = []) : array {
    // Obtener el filtro de agencia desde la opción del plugin (ajusta el nombre si es necesario)
    $agency_filter_id = get_option('resales_api_filter_id'); // o 'lusso_agency_filter_id'
    $params = [];

    if ($agency_filter_id) {
        $params['P_Agency_FilterId'] = $agency_filter_id;
    }

    // Tomar location de $_GET si existe y mapear a P_Location
    if (!empty($_GET['location'])) {
        $params['P_Location'] = sanitize_text_field($_GET['location']);
    }

    // Nunca enviar "Area"

    // Activar sandbox temporalmente para logging
    $params['P_sandbox'] = true;

    // ...aquí iría la llamada HTTP a SearchProperties...
    // $response = ...;

    // Loguear el bloque transaction si existe
    if (isset($response['transaction'])) {
        error_log('[resales-api][DEBUG] transaction=' . wp_json_encode($response['transaction']));
    }

    return $params;
}

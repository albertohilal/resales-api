<?php
// Integration-style test for image fallback logic in Resales_Client::search_properties_v6
// Runs outside WordPress; we provide minimal stubs for WP functions the client uses.

define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../includes/class-resales-client.php';

// --- Minimal WP stubs used by the client ---
if (!function_exists('get_option')) {
    function get_option($key, $default = null) {
        $map = [
            'resales_api_p1' => 'TEST_P1',
            'resales_api_p2' => 'TEST_P2',
            'resales_api_timeout' => 5,
            'lusso_agency_filter_id' => 1,
        ];
        return $map[$key] ?? $default;
    }
}
if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        global $TEST_HTTP_RESPONSES;
        if (strpos($url, 'SearchProperties') !== false) return $TEST_HTTP_RESPONSES['search'];
        if (strpos($url, 'PropertyDetails') !== false) return $TEST_HTTP_RESPONSES['details'];
        return ['error' => 'not_found'];
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($res) { return $res['body'] ?? ''; }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($res) { return $res['response']['code'] ?? 200; }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($r) { return is_array($r) && isset($r['error']); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($v) { return trim((string)$v); }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($v) { return $v; }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { $k = strtolower(trim((string)$key)); $k = preg_replace('/[^a-z0-9-_]/', '_', $k); return $k; }
}

// Minimal transient store
$GLOBALS['_TEST_TRANSIENTS'] = [];
if (!function_exists('get_transient')) {
    function get_transient($key) {
        $t = $GLOBALS['_TEST_TRANSIENTS'][$key] ?? null;
        if ($t && isset($t['expires']) && $t['expires'] < time()) { unset($GLOBALS['_TEST_TRANSIENTS'][$key]); return false; }
        return $t['value'] ?? false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $ttl = 0) { $exp = $ttl > 0 ? time() + (int)$ttl : 0; $GLOBALS['_TEST_TRANSIENTS'][$key] = ['value'=>$value,'expires'=>$exp]; return true; }
}

class TestHarness {
    public static function run_case($case_name, $search_body, $details_body) {
        global $TEST_HTTP_RESPONSES;
        $TEST_HTTP_RESPONSES = [
            'search' => ['response'=>['code'=>200],'body'=>json_encode($search_body)],
            'details' => ['response'=>['code'=>200],'body'=>json_encode($details_body)],
        ];
        $client = Resales_Client::instance();
        $res = $client->search_properties_v6([]);
        $prop = isset($res['Property'][0]) ? $res['Property'][0] : ($res['Property'] ?? null);
        $ok = !empty($prop['Pictures']);
        echo $case_name . ': ' . ($ok ? "PASS\n" : "FAIL\n");
        return $ok;
    }
}

$all_ok = true;

// Case A: SearchProperties returns property without Pictures, PropertyDetails has Pictures.Picture
$searchA = ['Property' => ['Reference' => 'REF-A','Title'=>'A']];
$detailsA = ['Property' => [['Reference'=>'REF-A','Pictures'=>['Picture'=>[['PictureURL'=>'https://ex/a1.jpg']]]]]];
$all_ok = $all_ok && TestHarness::run_case('Case A (Pictures.Picture)', $searchA, $detailsA);

// Case B: PropertyDetails returns Images.Image
$searchB = ['Property' => ['Reference' => 'REF-B','Title'=>'B']];
$detailsB = ['Property' => [['Reference'=>'REF-B','Images'=>['Image'=>[['PictureURL'=>'https://ex/b1.jpg']]]]]];
$all_ok = $all_ok && TestHarness::run_case('Case B (Images.Image)', $searchB, $detailsB);

// Case C: PropertyDetails returns MainImage
$searchC = ['Property' => ['Reference' => 'REF-C','Title'=>'C']];
$detailsC = ['Property' => [['Reference'=>'REF-C','MainImage'=>'https://ex/c1.jpg']]];
$all_ok = $all_ok && TestHarness::run_case('Case C (MainImage)', $searchC, $detailsC);

exit($all_ok ? 0 : 1);

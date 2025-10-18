<?php
// Minimal integration test for image fallback in Resales_Client::search_properties_v6
// This script runs outside WordPress: we stub the minimal WP functions used and simulate
// HTTP responses by mocking wp_remote_get via function override.

// Ensure ABSPATH is defined so included plugin files that check it don't exit
define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../includes/class-resales-client.php';

// --- Stubs for WP functions used in the class ---
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
    // We'll override this later by variable
    function wp_remote_get($url, $args = []) {
        global $TEST_HTTP_RESPONSES;
        // simple router by URL path
        if (strpos($url, 'SearchProperties') !== false) {
            return $TEST_HTTP_RESPONSES['search'];
        }
        if (strpos($url, 'PropertyDetails') !== false) {
            return $TEST_HTTP_RESPONSES['details'];
        }
        return new WP_Error('not_found','not found');
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($res) {
        return $res['body'] ?? '';
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($res) {
        return $res['response']['code'] ?? 200;
    }
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

// Minimal sanitize_key used by plugin code
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        $k = strtolower(trim((string)$key));
        // keep only a-z0-9_-
        $k = preg_replace('/[^a-z0-9-_]/', '_', $k);
        return $k;
    }
}

// Minimal WP_Error replacement for tests
class WP_Error {
    public $message;
    public function __construct($code, $message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}

// Simple in-memory transient store for tests
if (!function_exists('get_transient')) {
    $GLOBALS['_TEST_TRANSIENTS'] = [];
    function get_transient($key) {
        $t = $GLOBALS['_TEST_TRANSIENTS'][$key] ?? null;
        if ($t && isset($t['expires']) && $t['expires'] < time()) {
            unset($GLOBALS['_TEST_TRANSIENTS'][$key]);
            return false;
        }
        return $t['value'] ?? false;
    }
    function set_transient($key, $value, $ttl = 0) {
        $exp = $ttl > 0 ? time() + (int)$ttl : 0;
        $GLOBALS['_TEST_TRANSIENTS'][$key] = ['value' => $value, 'expires' => $exp];
        return true;
    }
}

// Prepare mock responses
$search_body = json_encode([
    'QueryInfo' => [ 'QueryId' => 'Q123', 'PropertyCount' => 1, 'PropertiesPerPage' => 1, 'CurrentPage' => 1 ],
    // Property returned WITHOUT Pictures
    'Property' => [
        'Reference' => 'REF-1',
        'Title' => 'Test prop',
        // no Pictures key
    ],
]);
$details_body = json_encode([
    'Property' => [
        [ 'Reference' => 'REF-1', 'Pictures' => [ 'Picture' => [ ['PictureURL' => 'https://example.com/img1.jpg'] ] ] ]
    ]
]);
$TEST_HTTP_RESPONSES = [
    'search' => [ 'response' => ['code' => 200], 'body' => $search_body ],
    'details' => [ 'response' => ['code' => 200], 'body' => $details_body ],
];

// Run the client
$client = Resales_Client::instance();
$result = $client->search_properties_v6([]);

// Assert that the returned structure contains Pictures for the property
$prop = isset($result['Property'][0]) ? $result['Property'][0] : $result['Property'];
if (!empty($prop['Pictures'])) {
    echo "PASS: Pictures present\n";
    exit(0);
} else {
    echo "FAIL: Pictures missing\n";
    exit(1);
}

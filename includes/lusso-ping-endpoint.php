<?php
// AJAX ping endpoint for diagnostics
add_action('wp_ajax_lusso_ping', 'lusso_ping_handler');
add_action('wp_ajax_nopriv_lusso_ping', 'lusso_ping_handler');
function lusso_ping_handler() {
    wp_send_json_success(['ok' => 1]);
    wp_die();
}

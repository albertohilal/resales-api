<?php
// REST API para resales/v1/search y resales/v6/search
add_action('rest_api_init', function () {
    register_rest_route('resales/v1', '/search', [
        'methods' => 'GET',
        'callback' => 'resales_api_search_callback',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('resales/v6', '/search', [
        'methods' => 'GET',
        'callback' => 'resales_api_search_callback',
        'permission_callback' => '__return_true',
    ]);
});

function resales_api_search_callback($request) {
    $params = $request->get_params();
    $query = [];
    // Mapear parámetros
    if (!empty($params['location'])) {
        $query['P_Location'] = sanitize_text_field($params['location']);
    }
    if (!empty($params['bedrooms'])) {
        $query['P_Beds'] = (int)$params['bedrooms'];
    }
    if (!empty($params['type'])) {
        $query['P_PropertyTypes'] = sanitize_text_field($params['type']);
    }
    // Filtro: priorizar P_APId, luego p_agency_filterid, luego settings
    if (!empty($params['P_APId'])) {
        $query['P_ApiId'] = (int)$params['P_APId'];
    } elseif (!empty($params['p_agency_filterid'])) {
        $query['P_Agency_FilterId'] = (int)$params['p_agency_filterid'];
    } else {
        $settings = get_option('resales_api_settings', []);
        if (!empty($settings['api_id']) && $settings['api_id'] > 1000) {
            $query['P_ApiId'] = (int)$settings['api_id'];
        } elseif (!empty($settings['agency_filter_alias']) && $settings['agency_filter_alias'] < 1000) {
            $query['P_Agency_FilterId'] = (int)$settings['agency_filter_alias'];
        }
    }
    // Credenciales y sandbox
    $settings = get_option('resales_api_settings', []);
    $query['p1'] = !empty($settings['p1']) ? $settings['p1'] : '';
    $query['p2'] = !empty($settings['p2']) ? $settings['p2'] : '';
    if (!empty($settings['sandbox'])) {
        $query['p_sandbox'] = true;
    }
    $url = 'https://webapi.resales-online.com/V6/SearchProperties?' . http_build_query($query);
    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [ 'Accept' => 'application/json' ],
    ]);
    if (is_wp_error($response)) {
        return rest_ensure_response(['success' => false, 'error' => $response->get_error_message()]);
    }
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    return rest_ensure_response($json);
}


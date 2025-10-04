<?php
// test_api.php

// — Configuración con tus credenciales reales —
$p1 = '1035049';              // reemplaza con tu valor real
$p2 = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';              // reemplaza con tu valor real
$agencyFilterId = '65503';  // reemplaza con tu ID de agencia o filtro
$sandbox = 'false'; // o "true" según entorno

// Función auxiliar para hacer la solicitud
function callApi($params) {
    $baseUrl = 'https://webapi.resales-online.com/V6/SearchProperties';
    $query = http_build_query($params);

    $url = $baseUrl . '?' . $query;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Si la API requiere headers especiales, agrégalos aquí
    // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Header-Name: value']);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'url' => $url,
        'http_code' => $httpCode,
        'error' => $err,
        'raw' => $response,
        'json' => json_decode($response, true),
    ];
}

// Prueba básica sin filtro
$paramsBasic = [
    'p_agency_filterid' => $agencyFilterId,
    'p1' => $p1,
    'p2' => $p2,
    'p_sandbox' => $sandbox,
    // no incluir P_Location — prueba sin filtro
];
$resultBasic = callApi($paramsBasic);

// Prueba con filtro de localidad (Benalmadena)
$paramsFilter = $paramsBasic;
$paramsFilter['P_Location'] = 'Benalmadena';
$resultFilter = callApi($paramsFilter);

// Mostrar resultados (como JSON legible para inspeccionar)
header('Content-Type: application/json');
echo json_encode([
    'basic' => $resultBasic,
    'with_location_filter' => $resultFilter,
], JSON_PRETTY_PRINT);

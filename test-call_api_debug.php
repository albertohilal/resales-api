<?php
// call_api_debug.php

// Configuración — reemplaza con tus valores reales
$p1 = '1035049';
$p2 = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';
$agencyFilterId = '65503';  // el ID de filtro que corresponde
$sandbox = 'false';

// Función auxiliar para llamar a la API
function callApi($params) {
    $baseUrl = 'https://webapi.resales-online.com/V6/SearchProperties';
    $query = http_build_query($params);
    $url = $baseUrl . '?' . $query;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Si necesitas Headers especiales, agrégalos:
    // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Header-Name: value', ...]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'url' => $url,
        'http_code' => $httpCode,
        'error' => $err,
        'raw_response' => $response,
        'json' => json_decode($response, true),
    ];
}

// Obtener la IP que “ve” este script
$serverIp = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

// Parámetros básicos para invocación
$paramsBasic = [
    'p_agency_filterid' => $agencyFilterId,
    'p1' => $p1,
    'p2' => $p2,
    'p_sandbox' => $sandbox,
    // no incluir P_Location para prueba básica
];
$resultBasic = callApi($paramsBasic);

// Prueba con filtro de localidad (por ejemplo “Benalmadena”)
$paramsFilter = $paramsBasic;
$paramsFilter['P_Location'] = 'Benalmadena';
$resultFilter = callApi($paramsFilter);

// Imprimir lo que se hizo y lo que devolvió
header('Content-Type: application/json');
echo json_encode([
    'server_ip_seen' => $serverIp,
    'basic_call' => $resultBasic,
    'filter_call' => $resultFilter
], JSON_PRETTY_PRINT);

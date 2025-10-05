<?php
// test-propertytypes.php — script independiente para consultar Property Types

// Habilitar visualización de errores (solo en entorno de prueba)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenciales / configuración (reemplaza con tus valores reales)
$p1 = '1035049';
$p2 = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';
$agencyFilterId = '65503';
$sandbox = 'false';

/**
 * Hace la llamada al endpoint SearchPropertyTypes.
 * 
 * @param bool $filter_new_devs Si es true, intenta filtrar para “solo nuevos desarrollos”.
 * @return array Información sobre la petición: url, código HTTP, error, respuesta cruda, JSON decodificado.
 */
function callPropertyTypesApi($filter_new_devs = false) {
    global $p1, $p2, $agencyFilterId, $sandbox;

    $endpoint = 'https://webapi.resales-online.com/V6/SearchPropertyTypes';

    $params = [
        'p1' => $p1,
        'p2' => $p2,
        'p_agency_filterid' => $agencyFilterId,
        'p_sandbox' => $sandbox,
        // A veces es útil especificar formato de salida si la API lo permite
        'p_output' => 'JSON',
    ];

    if ($filter_new_devs) {
        // Si la API soporta este parámetro, lo usamos.
        $params['p_new_devs'] = 'only';
    }

    $url = $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    // Es buena práctica establecer encabezados JSON, aunque algunas APIs no lo requieran
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = null;
    if ($response !== false) {
        $decoded = json_decode($response, true);
    }

    return [
        'url' => $url,
        'http_code' => $httpCode,
        'error' => $err,
        'raw_response' => $response,
        'json' => $decoded,
    ];
}

// Ejecutar la consulta (sin filtro “new devs” por defecto)
$result = callPropertyTypesApi(false);

// Mostrar resultado como JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

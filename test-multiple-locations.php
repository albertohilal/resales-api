<?php
// Ejemplo PHP de prueba para enviar múltiples sub-áreas literales a la API Resales-Online

// Tus parámetros de autenticación
$agency_filter = '1';         // según tu cuenta
$p1 = '1035049';
$p2 = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';
$lang = '1';                   // idioma, por ejemplo 1 = inglés/español según config

// Variables del filtro (múltiples sub-áreas literales separadas por comas)
$subareas = 'Nueva Andalucía,La Heredia,La Zagaleta';

// Montar la URL completa (ejemplo usando SearchProperties endpoint)
$endpoint = 'https://webapi.resales-online.com/V6/SearchProperties';
$params   = [
    'p_agency_filterid' => $agency_filter,
    'p1'                => $p1,
    'p2'                => $p2,
    'P_Lang'            => $lang,
    'P_Location'        => $subareas   // aquí enviamos los literales separados por comas
];

// Construir query string
$query = http_build_query($params);
$url   = $endpoint . '?' . $query;

echo "URL de petición: " . $url . "\n";

// Hacer la petición (GET) y mostrar respuesta
$response = file_get_contents($url);
if ($response === false) {
    echo "Error al llamar a la API.\n";
} else {
    header('Content-Type: application/json');
    echo $response;
}
?>

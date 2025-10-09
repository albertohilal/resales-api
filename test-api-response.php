<?php
// test-api-response.php

// Parámetros de autenticación (reemplazá con los tuyos)
$p1 = '1035049';
$p2 = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';

// Endpoint y payload
$url = 'https://webapi.resales-online.com/V6/Search';
$data = [
    'p1' => $p1,
    'p2' => $p2,
    'p_output' => 'JSON',
    'P_ApiId' => '65503',
    'P_Lang' => 1,
    'P_PageNo' => 1,
    'P_PageSize' => 1,
    'P_SortType' => 3,
    'p_new_devs' => 'include'
];

// Inicializa cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Ejecuta la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Verifica si la respuesta fue exitosa
if ($httpCode === 200) {
    // Decodifica la respuesta JSON y la muestra formateada
    $decoded = json_decode($response, true);
    echo "<pre>" . print_r($decoded, true) . "</pre>";
} else {
    echo "Error al realizar la solicitud (HTTP $httpCode):<br>";
    echo "<pre>$response</pre>";
}
?>

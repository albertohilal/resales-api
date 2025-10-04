<?php
// captura_headers.php

// Mostrar todos los headers recibidos por esta petición (útil para debug)
$allHeaders = getallheaders();

// También capturar parámetros GET (la URL con los parámetros que construyes)
$params = $_GET;

// Imprimir para que veas qué está enviando tu sistema
header('Content-Type: application/json');
echo json_encode([
    'headers' => $allHeaders,
    'params' => $params,
], JSON_PRETTY_PRINT);

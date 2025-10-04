<?php
// test_rest_search.php  (colócalo en la raíz del WP)
// Abre en el navegador: https://tu-dominio.com/test_rest_search.php?location=Sotogrande

// 1) Carga WP para usar wp_remote_get y home_url
require_once __DIR__ . '/wp-load.php';

// 2) Parámetros de prueba (puedes pasarlos por querystring)
$location  = isset($_GET['location'])  ? sanitize_text_field($_GET['location'])  : 'Sotogrande';
$type      = isset($_GET['type'])      ? sanitize_text_field($_GET['type'])      : '';
$bedrooms  = isset($_GET['bedrooms'])  ? (int)$_GET['bedrooms']                  : 0;
$minprice  = isset($_GET['minprice'])  ? (int)$_GET['minprice']                  : 0;
$maxprice  = isset($_GET['maxprice'])  ? (int)$_GET['maxprice']                  : 0;
$page      = isset($_GET['page'])      ? (int)$_GET['page']                      : 1;
$page_size = isset($_GET['page_size']) ? (int)$_GET['page_size']                 : 12;

// 3) Construye la URL del endpoint REST interno
$base = home_url('/wp-json/resales/v6/search');
$query = [
  'location'  => $location,
  'type'      => $type,
  'bedrooms'  => $bedrooms ?: null,
  'minprice'  => $minprice ?: null,
  'maxprice'  => $maxprice ?: null,
  'page'      => $page,
  'page_size' => $page_size,
  // anti-cache para ver el "Calling URL" en logs si tienes transients:
  '_t'        => time(),
];
$query = array_filter($query, fn($v) => $v !== null && $v !== '');
$url = $base . '?' . http_build_query($query);

// 4) Llama desde el servidor (IP del server) → OK para la API
$res = wp_remote_get($url, [
  'timeout' => 25,
  'headers' => ['Accept' => 'application/json'],
]);

header('Content-Type: application/json; charset=utf-8');

$out = [
  'request_url' => $url,
  'http_code'   => is_wp_error($res) ? 0 : wp_remote_retrieve_response_code($res),
];

if (is_wp_error($res)) {
  $out['error'] = $res->get_error_message();
} else {
  $body = wp_remote_retrieve_body($res);
  $out['raw_body'] = $body;
  $decoded = json_decode($body, true);
  $out['json'] = $decoded;
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

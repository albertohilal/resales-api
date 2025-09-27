<?php
/**
 * test-locations.php  —  Prueba directa de WebAPI V6: SearchLocations
 * Colocar en /public_html/test-locations.php
 * Uso: https://TU-DOMINIO/test-locations.php?lang=1
 *      (lang: 1=EN, 2=ES, etc.)
 */

header('Content-Type: application/json; charset=utf-8');

// =====================
// CONFIGURACIÓN RÁPIDA
// =====================
// Cargar variables de entorno desde .env
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$p1 = $_ENV['API_P1'] ?? '';
$p2 = $_ENV['API_P2'] ?? '';
$P_ApiId = $_ENV['API_FILTER_ID'] ?? '';
$P_Agency_FilterId = $_ENV['API_FILTER_ALIAS'] ?? '';

// 3) Idioma (por query ?lang=1; por defecto 1)
$lang = isset($_GET['lang']) ? (int) $_GET['lang'] : 1;
// 4) P_All=1 para traer TODAS las locations dentro del ámbito del filtro
$P_All = 1;

// =====================
//  LLAMADA A LA API V6
// =====================
if (!$p1 || !$p2) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Faltan credenciales p1/p2']);
  exit;
}

$params = [
  'p1'     => $p1,
  'p2'     => $p2,
  'P_Lang' => $lang,
  'P_All'  => $P_All,
];

// Requisito de SearchLocations: enviar P_ApiId O P_Agency_FilterId (uno de los dos).
// Doc oficial V6: ver WebAPI V6 site y guía de filtros. 
// (si no envías ninguno la API devuelve error 003). 
if ($P_Agency_FilterId !== '' && $P_ApiId) {
  // Si por error se configuran ambos, priorizamos el alias
  unset($P_ApiId);
}
if ($P_Agency_FilterId !== '') {
  $params['P_Agency_FilterId'] = $P_Agency_FilterId;
} elseif (!empty($P_ApiId)) {
  $params['P_ApiId'] = $P_ApiId;
} else {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Debes indicar P_ApiId o P_Agency_FilterId']);
  exit;
}

$url = 'https://webapi.resales-online.com/V6/SearchLocations?' . http_build_query($params);

// cURL (preferido); si no está, probamos file_get_contents
$responseBody = null;
$responseCode = 0;

if (function_exists('curl_init')) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
  ]);
  $responseBody = curl_exec($ch);
  $responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlErr      = curl_error($ch);
  curl_close($ch);

  if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['ok'=>false,'error'=>"cURL: $curlErr"]);
    exit;
  }
} else {
  $context = stream_context_create([
    'http' => ['method' => 'GET', 'timeout' => 20, 'header' => "Accept: application/json\r\n"],
  ]);
  $responseBody = @file_get_contents($url, false, $context);
  // Intentamos extraer el código HTTP del wrapper
  if (isset($http_response_header) && is_array($http_response_header)) {
    foreach ($http_response_header as $h) {
      if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) { $responseCode = (int) $m[1]; break; }
    }
  }
  if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['ok'=>false,'error'=>'file_get_contents falló (revisa allow_url_fopen)']);
    exit;
  }
}

// reenviamos el mismo código HTTP que dio la API
if ($responseCode > 0) { http_response_code($responseCode); }

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (stripos($accept, 'application/json') !== false) {
  header('Content-Type: application/json; charset=utf-8');
  echo $responseBody;
  exit;
}

// Mostrar HTML amigable si no se pide JSON
header('Content-Type: text/html; charset=utf-8');
$data = json_decode($responseBody, true, 512, JSON_UNESCAPED_UNICODE);

echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Ubicaciones disponibles</title>';
echo '<style>body{font-family:sans-serif;} ul{margin-left:1em;} strong{color:#1a237e;}</style></head><body>';

if (!is_array($data) || empty($data['LocationData']['ProvinceArea'])) {
  echo '<h2>Error al obtener ubicaciones</h2>';
  if (!empty($data['error'])) {
    echo '<p>' . htmlspecialchars($data['error']) . '</p>';
  } else {
    echo '<pre>' . htmlspecialchars($responseBody) . '</pre>';
  }
  echo '</body></html>';
  exit;
}

echo "<h2>Ubicaciones disponibles</h2>";
echo "<ul>";
foreach ($data['LocationData']['ProvinceArea'] as $area) {
  echo "<li><strong>" . htmlspecialchars($area['ProvinceAreaName'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</strong><ul>";
  foreach ($area['Locations'] as $loc) {
    echo "<li>" . htmlspecialchars($loc['Location'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
  }
  echo "</ul></li>";
}
echo "</ul>";
echo '</body></html>';


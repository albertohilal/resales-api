<?php
/**
 * test-locations-slim.php — SOLO nombres de locations (JSON plano)
 * URL:
 *   - https://TU-DOMINIO/test-locations-slim.php?lang=1
 *   - añade &raw=1 para ver el JSON crudo que devuelve la API V6
 */
header('Content-Type: application/json; charset=utf-8');

// ========= CONFIG =========
// Cargar variables de entorno desde .env
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$p1 = $_ENV['API_P1'] ?? '';
$p2 = $_ENV['API_P2'] ?? '';
$P_ApiId = $_ENV['API_FILTER_ID'] ?? '';
$P_Agency_FilterId = $_ENV['API_FILTER_ALIAS'] ?? '';
// =========================

$lang = isset($_GET['lang']) ? (int)$_GET['lang'] : 1; // 1=EN, 2=ES, etc.
$P_All = 1; // todas las locations dentro del ámbito del filtro

if (!$p1 || !$p2) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Faltan p1/p2']); exit;
}

// Parámetros obligatorios V6: p1/p2 + (P_ApiId o P_Agency_FilterId). (Doc V6) 
// https://webapi-v6.learning.resales-online.com/
$params = ['p1'=>$p1, 'p2'=>$p2, 'P_Lang'=>$lang, 'P_All'=>$P_All];
if ($P_Agency_FilterId !== '') { $params['P_Agency_FilterId'] = $P_Agency_FilterId; }
elseif (!empty($P_ApiId))     { $params['P_ApiId']           = $P_ApiId; }
else {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Debes indicar P_ApiId o P_Agency_FilterId']); exit;
}

$url = 'https://webapi.resales-online.com/V6/SearchLocations?' . http_build_query($params);

// Llamada
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 20,
  CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($body === false) {
  http_response_code(502);
  echo json_encode(['success'=>false,'error'=>'cURL: '.$err]); exit;
}

if (isset($_GET['raw'])) {
  // Muestra el JSON crudo de la API para depurar (mantenlo solo en pruebas)
  http_response_code($code > 0 ? $code : 200);
  echo $body; exit;
}

if ($code !== 200) {
  http_response_code($code);
  echo $body; exit;
}

// ---------- Parseo robusto ----------
// Recolecta cualquier valor string de una clave "Location" en todo el árbol.
$data = json_decode($body, true);
if (!is_array($data)) { echo json_encode(['success'=>false,'error'=>'JSON inválido']); exit; }

$bucket = [];
$collect = function($node) use (&$collect, &$bucket) {
  if (is_array($node)) {
    foreach ($node as $k => $v) {
      if (is_string($k) && strcasecmp($k, 'Location') === 0) {
        // Estructuras conocidas: "Location": "Benalmádena"  ó  "Location": ["A", "B"]
        if (is_string($v)) { $name = trim($v); if ($name !== '') $bucket[$name] = true; }
        elseif (is_array($v)) {
          foreach ($v as $maybe) {
            if (is_string($maybe)) { $name = trim($maybe); if ($name !== '') $bucket[$name] = true; }
            elseif (is_array($maybe) && isset($maybe['Location']) && is_string($maybe['Location'])) {
              $name = trim($maybe['Location']); if ($name !== '') $bucket[$name] = true;
            }
          }
        }
      }
      // Descenso recursivo
      if (is_array($v)) $collect($v);
    }
  }
};
$collect($data);

$items = array_keys($bucket);
sort($items, SORT_NATURAL | SORT_FLAG_CASE);

echo json_encode([
  'success' => true,
  'lang'    => $lang,
  'count'   => count($items),
  'items'   => $items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

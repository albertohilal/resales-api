<?php
/**
 * Test mínimo Resales Online V6 (SearchProperties)
 * - Sólo p1, p2 y P_ApiId como pide soporte.
 * - Sin p_sandbox / p_new_devs / p_images: lo decide el API Filter.
 * - Muestra transaction.incomingIp, QueryInfo, nº de propiedades e imágenes de la 1ª.
 *
 * IMPORTANTE: EJECÚTALO EN EL SERVIDOR DEL CLIENTE (IP autorizada en la API Key).
 */

$p1    = '1035049';                 // TU p1
$p2    = '5df9cb0f7ad59c80f11ec3c2d4c17f105aaf8918';   // TU p2 (API Key) sin espacios
$apiId = '65503';                   // Tu ApiId (o usa el alias como P_Agency_FilterId)

$endpoint = 'https://webapi.resales-online.com/V6/SearchProperties';


// llamada mínima (lo demás lo decide el API Filter)
$query = http_build_query([
  'p1'      => $p1,
  'p2'      => $p2,
  'P_ApiId' => $apiId,
], '', '&', PHP_QUERY_RFC3986);

$url = $endpoint . '?' . $query;

// cURL con timeouts
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT        => 20,
  CURLOPT_HTTPHEADER     => [
    'User-Agent: LussoGroup/ResalesTest',
    'Accept: application/json'
  ],
]);
$body     = curl_exec($ch);
$errNo    = curl_errno($ch);
$errMsg   = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

// salida JSON bonita
header('Content-Type: application/json; charset=utf-8');

$out = [
  'request_url' => $url,
  'http_code'   => $httpCode,
];

if ($errNo) {
  $out['curl_error'] = $errMsg;
  echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  exit;
}

$decoded = json_decode($body, true);
$out['raw'] = $decoded ?? $body;

// extra: transaction, QueryInfo y primeras imágenes
if (is_array($decoded)) {
  $out['transaction']          = $decoded['transaction'] ?? null;
  $out['QueryInfo']            = $decoded['QueryInfo']   ?? null;
  $props                       = isset($decoded['Property']) && is_array($decoded['Property']) ? $decoded['Property'] : [];
  $out['properties_count']     = count($props);

  if (!empty($props)) {
    $first  = (array) reset($props);
    $images = [];

    if (!empty($first['Images']['Image'])) {
      $list = $first['Images']['Image'];
      if (isset($list['Url'])) $list = [ $list ];
      foreach ($list as $im) {
        if (!empty($im['Url'])) $images[] = $im['Url'];
      }
    } elseif (!empty($first['MainImage'])) {
      $images[] = $first['MainImage'];
    }

    $out['first_property_images'] = $images;
  }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);


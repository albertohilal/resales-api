<?php
/**
 * Shortcodes para listado de New Developments (ND)
 * - Usa SearchProperties para el grid.
 * - Fallback a PropertyDetails para imágenes cuando SearchProperties no trae ninguna.
 * - Cachea respuestas en transients para evitar rate-limits y acelerar.
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Lusso_Resales_Shortcodes')) {
  class Lusso_Resales_Shortcodes {

    const API_BASE = 'https://webapi.resales-online.com/V6/';
    const SEARCH_ENDPOINT = 'SearchProperties';
    const DETAILS_ENDPOINT = 'PropertyDetails';

    // TTL cache
    const SEARCH_TTL  = 300;      // 5 min para el grid
    const DETAIL_TTL  = 21600;    // 6 horas para imágenes por referencia

    public function __construct() {
      add_shortcode('lusso_properties', [$this, 'shortcode_properties']);
      // Si necesitas estilos propios del grid, podrías encolar aquí.
    }

    /* ---- Utils de opciones (p1, p2, P_ApiId, idioma, alias, timeout) ---- */

    private function get_opt_all() {
      // Intentos flexibles para encontrar p1/p2 etc. según tu plugin de ajustes.
      // 1) Opción compuesta
      $o = get_option('resales_api');
      if (!is_array($o) || empty($o['p1']) || empty($o['p2'])) {
        // 2) Opción alternativa
        $o = get_option('resales_api_settings');
      }
      if (!is_array($o)) $o = [];

      // 3) Fallbacks por clave plana
      $o = array_merge([
        'p1'                => get_option('resales_api_p1'),
        'p2'                => get_option('resales_api_p2'),
        'P_ApiId'           => get_option('resales_api_P_ApiId'),
        'P_Agency_FilterId' => get_option('resales_api_P_Agency_FilterId'),
        'P_Lang'            => get_option('resales_api_lang', 1),
        'timeout'           => get_option('resales_api_timeout', 20),
      ], $o);

      // Sanitiza
      $o['p1']      = isset($o['p1']) ? trim($o['p1']) : '';
      $o['p2']      = isset($o['p2']) ? trim($o['p2']) : '';
      $o['P_ApiId'] = isset($o['P_ApiId']) ? trim($o['P_ApiId']) : '';
      $o['P_Lang']  = (int)($o['P_Lang'] ?: 1);
      return $o;
    }

    private function http_get($endpoint, $params, $timeout=20) {
      $url = self::API_BASE . $endpoint . '?' . http_build_query($params, '', '&');
      $res = wp_remote_get($url, [
        'timeout' => $timeout,
        'headers' => [
          // Evitamos proxies extraños; forzamos JSON
          'Accept' => 'application/json',
        ],
      ]);
      if (is_wp_error($res)) return $res;

      $code = wp_remote_retrieve_response_code($res);
      $body = wp_remote_retrieve_body($res);
      if ($code !== 200) {
        return new WP_Error('resales_http_error', 'HTTP ' . $code, ['body' => $body]);
      }
      $json = json_decode($body, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('resales_json_error', 'JSON error', ['body' => $body]);
      }
      return $json;
    }

    /* ---- Normalización de imágenes ---- */

    private function normalize_images($property) {
      // Devuelve siempre un array de arrays con ['Url','Size','Order']
      $imgs = [];
      if (!empty($property['Images']['Image'])) {
        $raw = $property['Images']['Image'];
        // Puede venir 1 objeto o una lista
        if (isset($raw['Url'])) {
          $raw = [ $raw ];
        }
        foreach ($raw as $img) {
          if (!empty($img['Url'])) {
            $imgs[] = [
              'Url'   => esc_url_raw($img['Url']),
              'Size'  => isset($img['Size']) ? $img['Size'] : '',
              'Order' => isset($img['Order']) ? (int)$img['Order'] : 0,
            ];
          }
        }
      }
      // Orden por Order asc
      usort($imgs, function($a,$b){ return $a['Order'] <=> $b['Order']; });
      // Dedup por Url
      $seen = [];
      $clean = [];
      foreach ($imgs as $i) {
        if (!isset($seen[$i['Url']])) {
          $clean[] = $i;
          $seen[$i['Url']] = true;
        }
      }
      return $clean;
    }

    private function property_images_with_fallback($opts, $ref, $from_search_property) {
      // 1) Intento con lo que vino en SearchProperties
      $imgs = $this->normalize_images($from_search_property);
      if (count($imgs) > 0) return $imgs;

      // 2) Fallback a PropertyDetails (cacheado)
      $tkey = 'resales_nd_details_' . sanitize_key($ref);
      $cached = get_transient($tkey);
      if ($cached && is_array($cached)) {
        $imgs = $this->normalize_images($cached);
        if (count($imgs) > 0) return $imgs;
      }

      // Llamada puntual a detalles
      $params = [
        'p1'       => $opts['p1'],
        'p2'       => $opts['p2'],
        'p_output' => 'JSON',
        'P_ApiId'  => $opts['P_ApiId'],
        'P_Lang'   => $opts['P_Lang'],
        'P_RefId'  => $ref,             // <- referencia
        // NO enviamos p_sandbox ni p_images aquí: el filtro define tamaños y cantidad
      ];
      $details = $this->http_get(self::DETAILS_ENDPOINT, $params, (int)$opts['timeout']);
      if (!is_wp_error($details) && !empty($details['Property'][0])) {
        set_transient($tkey, $details['Property'][0], self::DETAIL_TTL);
        $imgs = $this->normalize_images($details['Property'][0]);
      }
      return $imgs;
    }

    /* ---- HTML helpers ---- */

    private function render_card($p, $imgs, $debug=false) {
      $has = count($imgs);
      $first = $has ? esc_url($imgs[0]['Url']) : '';
      $title = esc_html(($p['PropertyType']['NameType'] ?? 'New Development') . ' — ' . ($p['PropertyType']['Type'] ?? ''));
      $ref   = esc_html($p['Reference'] ?? '');
      $beds  = esc_html($p['Bedrooms'] ?? '');
      $baths = esc_html($p['Bathrooms'] ?? '');
      $price = esc_html($p['Price'] ?? 'Price on request');

      ob_start(); ?>
      <article class="lusso-card">
        <div class="lusso-card__media">
          <?php if ($has >= 2): ?>
            <div class="lusso-swiper" data-slides="<?php echo (int)$has; ?>">
              <?php foreach ($imgs as $img): ?>
                <div class="lusso-swiper__slide">
                  <img src="<?php echo esc_url($img['Url']); ?>" alt="<?php echo $title; ?>" loading="lazy" decoding="async">
                </div>
              <?php endforeach; ?>
            </div>
          <?php elseif ($has === 1): ?>
            <img class="lusso-card__img" src="<?php echo $first; ?>" alt="<?php echo $title; ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="lusso-card__noimg">No image</div>
          <?php endif; ?>
        </div>
        <div class="lusso-card__body">
          <h3 class="lusso-card__title"><?php echo $title; ?></h3>
          <p class="lusso-card__meta">Ref: <?php echo $ref; ?> · <?php echo $beds ?: '–'; ?> bed · <?php echo $baths ?: '–'; ?> bath</p>
          <p class="lusso-card__price"><?php echo $price; ?></p>
        </div>
        <?php if ($debug): ?>
          <details class="lusso-card__debug"><summary>Debug imágenes (<?php echo (int)$has; ?>)</summary><pre><?php echo esc_html(print_r($imgs, true)); ?></pre></details>
        <?php endif; ?>
      </article>
      <?php
      return ob_get_clean();
    }

    /* ---- Shortcode principal ---- */

    public function shortcode_properties($atts) {
      $atts = shortcode_atts([
        'api_id'           => '',   // opcional: forzar P_ApiId
        'page'             => 1,
        'page_size'        => 12,
        'strict_min'       => '0',  // si "1", no renderiza sliders con <2 imágenes (siempre <img>)
        'debug'            => '0',
      ], $atts, 'lusso_properties');

      $opts = $this->get_opt_all();
      if (!empty($atts['api_id'])) $opts['P_ApiId'] = $atts['api_id'];
      $debug = $atts['debug'] == '1';

      if (empty($opts['p1']) || empty($opts['p2']) || empty($opts['P_ApiId'])) {
        return '<p>[Resales API] Faltan credenciales en Ajustes.</p>';
      }

      // Cache del resultado de SearchProperties
      $tkey = sprintf('resales_nd_search_p%d_s%d', (int)$atts['page'], (int)$atts['page_size']);
      $cached = get_transient($tkey);
      if ($cached) {
        $resp = $cached;
      } else {
        $params = [
          'p1'        => $opts['p1'],
          'p2'        => $opts['p2'],
          'p_output'  => 'JSON',
          'P_ApiId'   => $opts['P_ApiId'],
          'P_Lang'    => $opts['P_Lang'],
          'P_PageNo'  => (int)$atts['page'],
          'P_PageSize'=> (int)$atts['page_size'],
          'P_SortType'=> 3,               // por precio asc/desc según filtro
          'p_new_devs'=> 'only',          // ND
          // No enviamos p_images ni p_sandbox aquí.
        ];
        $resp = $this->http_get(self::SEARCH_ENDPOINT, $params, (int)$opts['timeout']);
        if (!is_wp_error($resp)) {
          set_transient($tkey, $resp, self::SEARCH_TTL);
        }
      }

      if (is_wp_error($resp) || empty($resp['Property'])) {
        if ($debug) {
          $err = is_wp_error($resp) ? $resp->get_error_message() : 'Sin resultados';
          return '<pre>DEBUG SearchProperties error: ' . esc_html($err) . '</pre>';
        }
        return '<p>No hay resultados para mostrar.</p>';
      }

      $props = $resp['Property'];
      if (isset($props['Reference'])) {
        // si viene 1 solo objeto
        $props = [ $props ];
      }

      // Render
      ob_start();

      if ($debug) {
        echo '<details open class="lusso-debug"><summary>DEBUG (mínima + ND)</summary><pre>';
        echo esc_html('HTTP=200');
        echo "\n\nArgs del shortcode\n";
        echo esc_html(print_r($atts, true));
        echo "\n\nQueryInfo\n";
        echo esc_html(print_r($resp['QueryInfo'] ?? [], true));
        echo "\nTotal propiedades recibidas: " . count($props) . "\n";
        echo "</pre></details>";
      }

      echo '<section class="lusso-grid">';

      foreach ($props as $p) {
        $ref  = $p['Reference'] ?? '';
        $imgs = $this->property_images_with_fallback($opts, $ref, $p);

        // strict_min: si es "1", no activamos slider con <2 imágenes (lo resuelve render_card)
        $html = $this->render_card($p, $imgs, $debug);
        echo $html;
      }

      echo '</section>';

      // CSS mínimo para el placeholder / grid
      ?>
      <style>
        .lusso-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
        @media (max-width:900px){.lusso-grid{grid-template-columns:1fr}}
        .lusso-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.06)}
        .lusso-card__media{aspect-ratio:4/3;background:#f2f2f2;position:relative}
        .lusso-card__img,.lusso-swiper__slide img{width:100%;height:100%;object-fit:cover;display:block}
        .lusso-card__noimg{display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-weight:600}
        .lusso-card__body{padding:14px 16px 18px}
        .lusso-card__title{margin:0 0 6px;font-size:16px}
        .lusso-card__meta{margin:0 0 6px;color:#666;font-size:13px}
        .lusso-card__price{margin:0;font-weight:700}
        .lusso-card__debug{padding:8px 12px}
      </style>
      <?php

      return ob_get_clean();
    }
  }

  // bootstrap
  new Lusso_Resales_Shortcodes();
}

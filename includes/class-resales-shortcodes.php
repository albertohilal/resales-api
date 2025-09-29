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
      // Encolar Swiper y estilos/JS de la galería solo en páginas donde se use el shortcode
      add_action('wp_enqueue_scripts', [$this, 'enqueue_gallery_assets']);
    }

    public function enqueue_gallery_assets() {
  // FontAwesome para iconos
  wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [], '4.7.0');
  // Swiper CSS desde CDN
  wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
  // CSS personalizado de la galería
  wp_enqueue_style('lusso-swiper-gallery', plugins_url('../assets/css/swiper-gallery.css', __FILE__), ['swiper'], '1.0.0');
  // Swiper JS desde CDN
  wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
  // JS de inicialización
  wp_enqueue_script('lusso-swiper-init', plugins_url('../assets/js/swiper-init.js', __FILE__), ['swiper'], '1.0.0', true);
    }

    /* ---- Utils de opciones (p1, p2, P_ApiId, idioma, alias, timeout) ---- */

    private function get_opt_all() {
      // Leer opciones individuales según los nombres reales en la base de datos
      $o = [
        'p1'                => get_option('resales_api_p1'),
        'p2'                => get_option('resales_api_p2'),
        'P_ApiId'           => get_option('resales_api_apiid'),
        'P_Agency_FilterId' => get_option('resales_api_agency_filterid'),
        'P_Lang'            => get_option('resales_api_lang', 1),
        'timeout'           => get_option('resales_api_timeout', 20),
      ];
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
      // 1. API V6: Images['Image']
      if (!empty($property['Images']['Image'])) {
        $raw = $property['Images']['Image'];
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
      // 2. Fallback: Pictures['Picture'] (V5 y New Developments)
      if (empty($imgs) && !empty($property['Pictures']['Picture'])) {
        $raw = $property['Pictures']['Picture'];
        if (isset($raw['PictureURL'])) {
          $raw = [ $raw ];
        }
        foreach ($raw as $img) {
          if (!empty($img['PictureURL'])) {
            $imgs[] = [
              'Url'   => esc_url_raw($img['PictureURL']),
              'Size'  => '',
              'Order' => isset($img['Id']) ? (int)$img['Id'] : 0,
            ];
          }
        }
      }
      // Ordenar y deduplicar
      usort($imgs, function($a,$b){ return $a['Order'] <=> $b['Order']; });
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
      if (defined('WP_DEBUG') && WP_DEBUG) {
        resales_log('DEBUG', '[Resales API] Ref ' . $ref . ' imágenes extraídas SearchProperties', $imgs);
      }
      if (count($imgs) > 0) return $imgs;

      // 2) Fallback a PropertyDetails (cacheado)
      $tkey = 'resales_nd_details_' . sanitize_key($ref);
      $cached = get_transient($tkey);
      if ($cached && is_array($cached)) {
        $imgs = $this->normalize_images($cached);
        if (defined('WP_DEBUG') && WP_DEBUG) {
          resales_log('DEBUG', '[Resales API] Ref ' . $ref . ' imágenes extraídas de cache detalles', $imgs);
        }
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
      if (defined('WP_DEBUG') && WP_DEBUG) {
        resales_log('DEBUG', '[Resales API] Ref ' . $ref . ' respuesta cruda detalles', $details);
      }
      if (!is_wp_error($details) && !empty($details['Property'][0])) {
        set_transient($tkey, $details['Property'][0], self::DETAIL_TTL);
        $imgs = $this->normalize_images($details['Property'][0]);
        if (defined('WP_DEBUG') && WP_DEBUG) {
          resales_log('DEBUG', '[Resales API] Ref ' . $ref . ' imágenes extraídas detalles', $imgs);
        }
      }
      return $imgs;
    }

    /* ---- HTML helpers ---- */

    private function render_card($p, $imgs, $debug=false) {
  $has = count($imgs);
  $first = $has ? esc_url($imgs[0]['Url']) : '';
  $ref   = esc_html($p['Reference'] ?? '');
  $detail_url = esc_url( add_query_arg(['ref' => $ref], home_url('/property/')) );
  // Usar la función get_card_place_label si existe, si no, fallback inline
  if (function_exists('get_card_place_label')) {
    $location = get_card_place_label($p);
  } else {
    $province = isset($p['Province']) ? trim((string)$p['Province']) : '';
    $loc = isset($p['Location']) ? trim((string)$p['Location']) : '';
    $sub = isset($p['SubArea']) ? trim((string)$p['SubArea']) : '';
    if ($sub !== '') {
      $location = esc_html($sub . ($loc !== '' ? ', ' . $loc : ''));
    } elseif ($loc !== '') {
      $location = esc_html($loc . ($province !== '' ? ', ' . $province : ''));
    } else {
      $location = esc_html($province);
    }
  }
      // Descripción: primera oración del segundo párrafo
      $desc = '';
      if (isset($p['Description']) && is_string($p['Description'])) {
        $parts = preg_split('/\n+/', trim($p['Description']));
        $ix = count($parts) > 1 ? 1 : 0;
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($parts[$ix]));
        $desc = esc_html($sentences[0]);
      }
      $beds = isset($p['Bedrooms']) ? intval($p['Bedrooms']) : '';
      $baths = isset($p['Bathrooms']) ? intval($p['Bathrooms']) : '';
      $plot = isset($p['PlotSize']) ? intval($p['PlotSize']) : '';
      $built = isset($p['BuiltSize']) ? intval($p['BuiltSize']) : '';
      $terrace = isset($p['TerraceSize']) ? intval($p['TerraceSize']) : '';
      $currency = isset($p['Currency']) ? $p['Currency'] : 'EUR';
      // Rango de precios
      $price_from = isset($p['PriceFrom']) && $p['PriceFrom'] !== '' ? number_format((float)$p['PriceFrom'], 0, ',', '.') : '';
      $price_to = isset($p['PriceTo']) && $p['PriceTo'] !== '' ? number_format((float)$p['PriceTo'], 0, ',', '.') : '';
      $price_single = isset($p['Price']) && $p['Price'] !== '' ? number_format((float)$p['Price'], 0, ',', '.') : '';

      ob_start();
      // Generar un ID único para la galería Swiper de esta tarjeta
      $gallery_id = 'swiper-' . md5($ref . $first);
      ?>
      <article class="lr-card">
        <div class="lr-card__media" style="overflow:hidden;position:relative;">
          <?php if ($has >= 2): ?>
            <div id="<?php echo $gallery_id; ?>" class="property-gallery swiper" data-slides="<?php echo (int)$has; ?>">
              <div class="swiper-wrapper">
                <?php foreach ($imgs as $img): ?>
                  <div class="swiper-slide">
                    <img src="<?php echo esc_url($img['Url']); ?>" alt="<?php echo $location; ?>" loading="lazy" decoding="async">
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="swiper-button-next"></div>
              <div class="swiper-button-prev"></div>
            </div>
          <?php elseif ($has === 1): ?>
            <img class="lr-card__media-img" src="<?php echo $first; ?>" alt="<?php echo $location; ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="lr-card__media-noimg">No image</div>
          <?php endif; ?>
        </div>
        <?php if ($has >= 2): ?>
        <div class="swiper-pagination" style="position:relative;left:0;right:0;margin:-8px auto 0 auto;z-index:10;display:flex;justify-content:center;align-items:center;background:#fff;padding:6px 0 2px 0;border-radius:0 0 8px 8px;width:calc(100% - 0px);"></div>
        <?php endif; ?>
        <a href="<?php echo $detail_url; ?>" class="lr-card__bar" style="display:flex;flex-direction:column;gap:0.5rem;text-decoration:none;" aria-label="Ver detalles de la propiedad <?php echo $ref; ?>">
          <div style="color:#666;font-size:0.875rem;line-height:1.2;">
            <?php echo $location; ?>
          </div>
          <div style="font-size:1rem;font-weight:700;color:var(--color-gold-dark);line-height:1.3;margin:0;">
            <?php echo $desc; ?>
          </div>
          <div style="display:flex;gap:14px;margin:8px 0;justify-content:center;">
            <?php if($beds): ?><span title="Dormitorios"><i class="fa fa-bed" style="color:var(--color-gold-dark);"></i> <?php echo $beds; ?></span><?php endif; ?>
            <?php if($baths): ?><span title="Baños"><i class="fa fa-bath" style="color:var(--color-gold-dark);"></i> <?php echo $baths; ?></span><?php endif; ?>
            <?php if($plot): ?><span title="Parcela"><i class="fa fa-tree" style="color:var(--color-green-dark);"></i> <?php echo $plot; ?> m²</span><?php endif; ?>
            <?php if($built): ?><span title="Construidos"><i class="fa fa-building" style="color:var(--color-gray-dark);"></i> <?php echo $built; ?> m²</span><?php endif; ?>
            <?php if($terrace): ?><span title="Terraza"><i class="fa fa-square" style="color:var(--color-gold-dark);"></i> <?php echo $terrace; ?> m²</span><?php endif; ?>
          </div>
          <div style="font-size:1rem;color:#222;line-height:1.2;">
            <?php
            if ( $price_from && $price_to ) {
                echo 'From ' . $currency . ' ' . $price_from . ' to ' . $currency . ' ' . $price_to;
            } elseif ( $price_from ) {
                echo 'From ' . $currency . ' ' . $price_from;
            } elseif ( $price_single ) {
                echo $currency . ' ' . $price_single;
            } else {
                echo 'Price on request';
            }
            ?>
          </div>
        </a>
        <?php if ($debug): ?>
          <details class="lr-card__debug"><summary>Debug imágenes (<?php echo (int)$has; ?>)</summary><pre><?php echo esc_html(print_r($imgs, true)); ?></pre></details>
        <?php endif; ?>
      </article>
      <?php
      return ob_get_clean();
    }

    /* ---- Shortcode principal ---- */

    public function shortcode_properties($atts) {
      // Logging seguro de location
      $args = [];
      if (isset($_GET['location'])) {
        $args['P_Location'] = sanitize_text_field($_GET['location']);
      }
      resales_safe_log('SHORTCODE ARGS', [
        'location_get' => isset($_GET['location']) ? 'yes' : 'no',
        'args_has_P_Location' => isset($args['P_Location']) ? 'yes' : 'no',
      ]);
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
        if ($debug) {
          resales_log('DEBUG', '[Resales API] Ref ' . $ref . ' imágenes finales para render', $imgs);
        }
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
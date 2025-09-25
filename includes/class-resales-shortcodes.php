<?php
/**
 * Shortcode para mostrar propiedades reales usando SearchProperties (WebAPI V6)
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Properties_Shortcode')) {
  class Resales_Properties_Shortcode {
    public function __construct() {
      add_shortcode('lusso_properties', [$this, 'render']);
    }
    public function render($atts = []) {
      $atts = shortcode_atts([
        'page'   => 1,
        'limit'  => 10,
      ], $atts, 'lusso_properties');

      $params = [
        'P_sandbox' => 'false',
        'P_PageNo'  => (int) max(1, $atts['page']),
      ];
      if (!empty($_GET['location']))    $params['P_Location']       = sanitize_text_field($_GET['location']);
      if (!empty($_GET['area']))        $params['P_Location']       = sanitize_text_field($_GET['area']);
      if (!empty($_GET['beds']))        $params['P_Beds']           = (int) $_GET['beds'];
      if (!empty($_GET['price_from']))  $params['P_PriceMin']       = (int) $_GET['price_from'];
      if (!empty($_GET['price_to']))    $params['P_PriceMax']       = (int) $_GET['price_to'];
      if (!empty($_GET['types']) && is_array($_GET['types'])) {
        $types = array_filter(array_map('sanitize_text_field', $_GET['types']));
        if ($types) $params['P_PropertyTypes'] = implode(',', $types);
      }
      if (!empty($_GET['p_new_devs']))  $params['p_new_devs']       = sanitize_text_field($_GET['p_new_devs']);

      if (!function_exists('resales_v6_request')) return '<div class="lusso-properties-error">Error: helper API no disponible.</div>';
      $res = resales_v6_request('SearchProperties', $params);
      if (is_wp_error($res)) {
        return '<div class="lusso-properties-error">Error API: ' . esc_html($res->get_error_message()) . '</div>';
      }

      $props = [];
      if (!empty($res['PropertyList']['Property'])) {
        $props = $res['PropertyList']['Property'];
      } elseif (!empty($res['Property'])) {
        $props = $res['Property'];
      }
      if (isset($props['Reference'])) $props = [$props];

      if (empty($props) || !is_array($props)) {
        return '<div class="lusso-properties-empty">' . esc_html__('No se encontraron propiedades.', 'resales') . '</div>';
      }

      ob_start();
      echo '<div class="lusso-properties grid">';
      foreach ($props as $p) {
        $ref   = $p['Reference'] ?? '';
        $prov  = $p['Province']  ?? '';
        $area  = $p['Area']      ?? ($p['Location'] ?? '');
        $price = $p['Price']     ?? '';
        $beds  = $p['Bedrooms']  ?? '';
        $type  = $p['PropertyType']['Type'] ?? '';

        $img = '';
        if (!empty($p['Pictures']['Picture'])) {
          $pic = is_array($p['Pictures']['Picture']) ? $p['Pictures']['Picture'][0] : $p['Pictures']['Picture'];
          if (!empty($pic['URL'])) $img = $pic['URL'];
          if (!$img && !empty($pic['Url'])) $img = $pic['Url'];
          if (!$img && is_string($pic))      $img = $pic;
        } elseif (!empty($p['Picture'])) {
          $pic = is_array($p['Picture']) ? $p['Picture'][0] : $p['Picture'];
          if (!empty($pic['URL'])) $img = $pic['URL'];
          if (!$img && !empty($pic['Url'])) $img = $pic['Url'];
          if (!$img && is_string($pic))      $img = $pic;
        }

        $title = trim($type . ($area ? ' · ' . $area : ''));
        $loc   = $area && $prov ? "$area, $prov" : ($area ?: $prov);

        echo '<article class="lusso-card">';
          if ($img) {
            echo '<div class="lusso-card__media"><img loading="lazy" src="' . esc_url($img) . '" alt="' . esc_attr($ref ?: $title) . '"></div>';
          }
          echo '<div class="lusso-card__body">';
            if ($ref)   echo '<div class="lusso-card__ref">Ref: ' . esc_html($ref) . '</div>';
            if ($title) echo '<h3 class="lusso-card__title">' . esc_html($title) . '</h3>';
            if ($loc)   echo '<div class="lusso-card__loc">' . esc_html($loc) . '</div>';
            if ($price) {
              $priceText = is_string($price) && preg_match('/\d$/', $price) ? $price . ' €' : $price;
              echo '<div class="lusso-card__price">' . esc_html($priceText) . '</div>';
            }
            if ($beds)  echo '<div class="lusso-card__meta">' . esc_html($beds) . ' ' . esc_html__('hab.', 'resales') . '</div>';
          echo '</div>';
        echo '</article>';
      }
      echo '</div>';
      return ob_get_clean();
    }
  }
}

add_action('init', function () {
  if (class_exists('Resales_Properties_Shortcode')) new Resales_Properties_Shortcode();
});
  if (!class_exists('Resales_Properties_Shortcode')) {
    class Resales_Properties_Shortcode {
      public function __construct() {
        add_shortcode('lusso_properties', [$this, 'render']);
      }
      public function render($atts = []) {
        $atts = shortcode_atts([
          'page'   => 1,
          'limit'  => 10,
        ], $atts, 'lusso_properties');

        $params = [
          'P_sandbox' => 'false',
          'P_PageNo'  => (int) max(1, $atts['page']),
        ];
        if (!empty($_GET['location']))    $params['P_Location']       = sanitize_text_field($_GET['location']);
        if (!empty($_GET['area']))        $params['P_Location']       = sanitize_text_field($_GET['area']);
        if (!empty($_GET['beds']))        $params['P_Beds']           = (int) $_GET['beds'];
        if (!empty($_GET['price_from']))  $params['P_PriceMin']       = (int) $_GET['price_from'];
        if (!empty($_GET['price_to']))    $params['P_PriceMax']       = (int) $_GET['price_to'];
        if (!empty($_GET['types']) && is_array($_GET['types'])) {
          $types = array_filter(array_map('sanitize_text_field', $_GET['types']));
          if ($types) $params['P_PropertyTypes'] = implode(',', $types);
        }
        if (!empty($_GET['p_new_devs']))  $params['p_new_devs']       = sanitize_text_field($_GET['p_new_devs']);

        if (!function_exists('resales_v6_request')) return '<div class="lusso-properties-error">Error: helper API no disponible.</div>';
        $res = resales_v6_request('SearchProperties', $params);
        if (is_wp_error($res)) {
          return '<div class="lusso-properties-error">Error API: ' . esc_html($res->get_error_message()) . '</div>';
        }

        $props = [];
        if (!empty($res['PropertyList']['Property'])) {
          $props = $res['PropertyList']['Property'];
        } elseif (!empty($res['Property'])) {
          $props = $res['Property'];
        }
        if (isset($props['Reference'])) $props = [$props];

        if (empty($props) || !is_array($props)) {
          return '<div class="lusso-properties-empty">' . esc_html__('No se encontraron propiedades.', 'resales') . '</div>';
        }

        ob_start();
        echo '<div class="lusso-properties grid">';
        foreach ($props as $p) {
          $ref   = $p['Reference'] ?? '';
          $prov  = $p['Province']  ?? '';
          $area  = $p['Area']      ?? ($p['Location'] ?? '');
          $price = $p['Price']     ?? '';
          $beds  = $p['Bedrooms']  ?? '';
          $type  = $p['PropertyType']['Type'] ?? '';

          $img = '';
          if (!empty($p['Pictures']['Picture'])) {
            $pic = is_array($p['Pictures']['Picture']) ? $p['Pictures']['Picture'][0] : $p['Pictures']['Picture'];
            if (!empty($pic['URL'])) $img = $pic['URL'];
            if (!$img && !empty($pic['Url'])) $img = $pic['Url'];
            if (!$img && is_string($pic))      $img = $pic;
          } elseif (!empty($p['Picture'])) {
            $pic = is_array($p['Picture']) ? $p['Picture'][0] : $p['Picture'];
            if (!empty($pic['URL'])) $img = $pic['URL'];
            if (!$img && !empty($pic['Url'])) $img = $pic['Url'];
            if (!$img && is_string($pic))      $img = $pic;
          }

          $title = trim($type . ($area ? ' · ' . $area : ''));
          $loc   = $area && $prov ? "$area, $prov" : ($area ?: $prov);

          echo '<article class="lusso-card">';
            if ($img) {
              echo '<div class="lusso-card__media"><img loading="lazy" src="' . esc_url($img) . '" alt="' . esc_attr($ref ?: $title) . '"></div>';
            }
            echo '<div class="lusso-card__body">';
              if ($ref)   echo '<div class="lusso-card__ref">Ref: ' . esc_html($ref) . '</div>';
              if ($title) echo '<h3 class="lusso-card__title">' . esc_html($title) . '</h3>';
              if ($loc)   echo '<div class="lusso-card__loc">' . esc_html($loc) . '</div>';
              if ($price) {
                $priceText = is_string($price) && preg_match('/\d$/', $price) ? $price . ' €' : $price;
                echo '<div class="lusso-card__price">' . esc_html($priceText) . '</div>';
              }
              if ($beds)  echo '<div class="lusso-card__meta">' . esc_html($beds) . ' ' . esc_html__('hab.', 'resales') . '</div>';
            echo '</div>';
          echo '</article>';
        }
        echo '</div>';
        return ob_get_clean();
      }
    }
  }

  // Instanciar en init para registrar el shortcode
  add_action('init', function () {
    if (class_exists('Resales_Properties_Shortcode')) new Resales_Properties_Shortcode();
  });

<?php

if (!defined('ABSPATH')) exit;

class Resales_Shortcodes {

    public function __construct() {
        $this->register_shortcodes();
    }

    public function register_shortcodes() {
        add_shortcode('lusso_properties', [$this, 'render_properties']);
    }

    public function render_properties() {
        $client = new Resales_Client();

        $params = [
            'p1' => get_option('resales_api_p1'),
            'p2' => get_option('resales_api_p2'),
            'P_ApiId' => get_option('resales_api_id'),
            'p_output' => 'JSON',
            'P_PageSize' => 12,
            'P_SortType' => 3,
            'P_Lang' => 1
        ];

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $params['P_sandbox'] = true;
        }

        $response = $client->search_properties($params);
        $properties = $response['Property'] ?? [];

        ob_start();
        ?>
        <div id="lusso-search-results">
          <?php if (!empty($properties)): ?>
            <div class="lusso-properties-wrapper">
              <?php foreach ($properties as $p): ?>
                <div class="property-card">
                  <strong><?= esc_html($p['Title'] ?? $p['Reference'] ?? 'Property') ?></strong><br>
                  <?php if (!empty($p['Location'])): ?>
                    <span><?= esc_html($p['Location']) ?></span><br>
                  <?php endif; ?>
                  <?php if (!empty($p['Price'])): ?>
                    <span><?= esc_html($p['Price']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="lusso-error">No se encontraron propiedades.</div>
          <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
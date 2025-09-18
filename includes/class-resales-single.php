<?php
/**
 * Plugin file: class-resales-shortcodes.php
 * Shortcode principal: [lusso_properties]
 * Enfocado en New Developments. Independiente del código de single (detalles).
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Shortcodes')):

final class Resales_Shortcodes {

    /** @var array */
    private $settings = [
        'p1'      => '',
        'p2'      => '',
        'api_id'  => '',
        'lang'    => '1', // 1=ES, etc.
    ];

    /** Constructor */
    public function __construct() {
        // Carga creds desde ajustes (ajústalo si tu opción tiene otro nombre/estructura)
        $opt = get_option('resales_api_settings');
        if (is_array($opt)) {
            $this->settings['p1']     = $opt['p1']     ?? '';
            $this->settings['p2']     = $opt['p2']     ?? '';
            $this->settings['api_id'] = $opt['api_id'] ?? ($opt['P_ApiId'] ?? '');
            $this->settings['lang']   = $opt['lang']   ?? '1';
        }

        // Fallback suave si tus claves están guardadas en otra opción
        if (!$this->settings['p1'])     $this->settings['p1']     = get_option('resales_api_p1', '');
        if (!$this->settings['p2'])     $this->settings['p2']     = get_option('resales_api_p2', '');
        if (!$this->settings['api_id']) $this->settings['api_id'] = get_option('resales_api_id', '');

        add_shortcode('lusso_properties', [$this, 'shortcode_properties']);
    }

    /**
     * Shortcode [lusso_properties]
     */
    public function shortcode_properties($atts) {

        $a = shortcode_atts([
            'results'          => '12',
            'page'             => '1',
            'api_id'           => '',     // opcional; si no viene usa ajustes
            'strict_min'       => '0',    // mantiene tu flag si ya lo usabas
            'gallery_fallback' => '1',    // 1 => usa PropertyDetails si Search no trae imágenes
            'max_imgs'         => '6',    // cap de imágenes por card
            'debug'            => '0',
        ], $atts, 'lusso_properties');

        if (!empty($a['api_id'])) {
            $this->settings['api_id'] = $a['api_id'];
        }

        // Comprobaciones mínimas
        if (empty($this->settings['p1']) || empty($this->settings['p2']) || empty($this->settings['api_id'])) {
            return '<div class="resales-error">Resales API: faltan credenciales p1/p2/api_id.</div>';
        }

        // 1) Llamada mínima a SearchProperties (sin p_images aquí; lo gobierna el filtro)
        $page    = max(1, (int)$a['page']);
        $results = max(1, (int)$a['results']);

        $cache_key = sprintf('ros_sp_nd_es_%s_%s_%s_%s',
            $this->settings['api_id'],
            $this->settings['lang'],
            $page,
            $results
        );

        $payload = get_transient($cache_key);
        if ($payload === false) {
            $payload = $this->call_min_search($page, $results);
            // cachea 10 minutos
            set_transient($cache_key, $payload, 10 * MINUTE_IN_SECONDS);
        }

        $html_debug = '';
        if ($a['debug'] == '1') {
            $html_debug .= $this->render_debug_box($payload, $a);
        }

        // 2) Render cards
        $items = $payload['Properties']['Property'] ?? [];
        if (empty($items)) {
            return $html_debug . '<div class="resales-empty">No hay resultados para mostrar.</div>';
        }

        // Normaliza: si trae 1 solo registro como objeto, pásalo a array
        if ($this->is_assoc($items)) {
            $items = [$items];
        }

        ob_start();
        echo $html_debug;
        ?>
        <div class="resales-grid resales-grid--props" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:28px;">
            <?php foreach ($items as $p): ?>
                <?php
                $imgs   = $this->ro_get_property_images($p, $a['gallery_fallback'] === '1');
                if (!empty($imgs)) {
                    $imgs = array_slice($imgs, 0, max(1,(int)$a['max_imgs']));
                }
                $title  = $this->build_title($p);
                $price  = $this->build_price($p);
                $ref    = !empty($p['Reference']) ? esc_html($p['Reference']) : '';
                ?>
                <article class="resales-card" style="border:1px solid #eaeaea;border-radius:14px;overflow:hidden;background:#fff;">
                    <div class="resales-card__media" style="aspect-ratio:16/10;background:#f2f2f2;position:relative;">
                        <?php if (!empty($imgs)): ?>
                            <?php if (count($imgs) > 1): ?>
                                <div class="resales-card__slider" style="position:absolute;inset:0;display:flex;overflow:hidden;">
                                    <?php foreach ($imgs as $img): ?>
                                        <div style="flex:0 0 100%;position:relative;">
                                            <img src="<?php echo esc_url($img['url']); ?>"
                                                 alt="<?php echo esc_attr($title); ?>"
                                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Si usas Swiper, inicialízalo fuera; aquí mantenemos HTML simple -->
                            <?php else: ?>
                                <img src="<?php echo esc_url($imgs[0]['url']); ?>"
                                     alt="<?php echo esc_attr($title); ?>"
                                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo $this->placeholder_svg(); ?>
                        <?php endif; ?>
                    </div>

                    <div class="resales-card__body" style="padding:16px 18px;">
                        <div class="resales-card__title" style="font-weight:600;margin-bottom:6px;">
                            <?php echo esc_html($title); ?>
                        </div>
                        <div class="resales-card__meta" style="color:#666;font-size:13px;margin-bottom:8px;">
                            <?php if ($ref): ?>
                                Ref: <?php echo $ref; ?>
                            <?php endif; ?>
                            <?php echo $this->build_beds_baths($p); ?>
                        </div>
                        <div class="resales-card__price" style="font-weight:700;">
                            <?php echo esc_html($price); ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Llamada mínima a SearchProperties para New Developments (solo p1, p2, P_APIid).
     * El filtro en la consola de Resales controla: imágenes devueltas, tamaños, etc.
     */
    private function call_min_search(int $page, int $results): array {
        $url  = 'https://webapi.resales-online.com/V6/SearchProperties';
        $args = [
            'p1'        => $this->settings['p1'],
            'p2'        => $this->settings['p2'],
            'P_APIid'   => $this->settings['api_id'],
            'P_Lang'    => $this->settings['lang'],
            // Solo New Developments (tal como veníamos trabajando):
            'p_new_devs'=> 'only',
            // paginado y orden (opcional, para consistencia visual):
            'P_PageSize'=> $results,
            'P_PageNo'  => $page,
            'P_SortType'=> 3, // por ejemplo: más recientes
        ];
        $resp = wp_remote_get(add_query_arg($args, $url), ['timeout' => 25]);
        if (is_wp_error($resp)) return [];
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        return is_array($json) ? $json : [];
    }

    /**
     * Normaliza imágenes desde SearchProperties y, si está activado,
     * hace fallback a PropertyDetails cuando no hay ninguna.
     */
    private function ro_get_property_images(array $p, bool $use_fallback = true): array {
        $imgs = [];

        // a) SearchProperties → Images.Image (puede llegar objeto único)
        if (!empty($p['Images']['Image'])) {
            $raw  = $p['Images']['Image'];
            $list = $this->is_assoc($raw) ? [$raw] : $raw;
            foreach ($list as $i) {
                if (empty($i['Url'])) continue;
                $imgs[] = [
                    'url'   => $i['Url'],
                    'order' => isset($i['Order']) ? (int)$i['Order'] : 9999,
                    'size'  => $i['Size'] ?? '',
                ];
            }
        }
        // b) Algunos entornos devuelven MainImage
        elseif (!empty($p['MainImage'])) {
            $imgs[] = ['url' => $p['MainImage'], 'order' => 1, 'size' => 'Main'];
        }

        // c) Fallback a PropertyDetails para ND/propiedades con 0 imágenes en Search
        if ($use_fallback && empty($imgs) && !empty($p['Reference'])) {
            $pd = $this->ro_get_property_details_cached($p['Reference']);
            if (!empty($pd['Property']['Images']['Image'])) {
                $raw  = $pd['Property']['Images']['Image'];
                $list = $this->is_assoc($raw) ? [$raw] : $raw;
                foreach ($list as $i) {
                    if (empty($i['Url'])) continue;
                    $imgs[] = [
                        'url'   => $i['Url'],
                        'order' => isset($i['Order']) ? (int)$i['Order'] : 9999,
                        'size'  => $i['Size'] ?? '',
                    ];
                }
            }
        }

        // Orden por 'Order'
        usort($imgs, fn($a,$b) => $a['order'] <=> $b['order']);

        return $imgs;
    }

    /**
     * Llama PropertyDetails con caché 10 min.
     */
    private function ro_get_property_details_cached(string $reference): array {
        $key = 'ros_pd_' . md5($reference . '|' . $this->settings['api_id']);
        $cached = get_transient($key);
        if ($cached !== false) return $cached;

        $url  = 'https://webapi.resales-online.com/V6/PropertyDetails';
        $args = [
            'p1'        => $this->settings['p1'],
            'p2'        => $this->settings['p2'],
            'P_APIid'   => $this->settings['api_id'],
            'Reference' => $reference,
            'P_Lang'    => $this->settings['lang'],
        ];

        $resp = wp_remote_get(add_query_arg($args, $url), ['timeout' => 25]);
        $json = is_wp_error($resp) ? [] : json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($json)) $json = [];

        set_transient($key, $json, 10 * MINUTE_IN_SECONDS);
        return $json;
    }

    /* ===========================
     * Helpers de Render
     * =========================== */

    private function build_title(array $p): string {
        $type = '';
        if (!empty($p['PropertyType']['NameType'])) {
            $type = $p['PropertyType']['NameType'];
        } elseif (!empty($p['PropertyType']['Type'])) {
            $type = $p['PropertyType']['Type'];
        }
        $cat = '';
        if (!empty($p['PropertyType']['Type'])) {
            $cat = $p['PropertyType']['Type'];
        }
        $parts = array_filter([$type, $cat ? '— '.$cat : '']);
        return trim(implode(' ', $parts));
    }

    private function build_beds_baths(array $p): string {
        // Para ND vienen como rangos: "1 - 3", "1 - 2"
        $beds  = !empty($p['Bedrooms'])  ? ' ' . trim($p['Bedrooms'])  . ' bed'  : '';
        $baths = !empty($p['Bathrooms']) ? ' ' . trim($p['Bathrooms']) . ' bath' : '';
        if ($beds || $baths) {
            return ' · ' . trim($beds . ' ' . $baths);
        }
        return '';
    }

    private function build_price(array $p): string {
        // Price puede llegar como rango (p.ej. "EUR 230000 - 420000")
        if (!empty($p['Price'])) {
            $val = trim($p['Price']);
            // añade moneda si hace falta (habitualmente viene con EUR)
            return $val;
        }
        return __('Price on request', 'resales');
    }

    private function placeholder_svg(): string {
        // Un placeholder liviano con texto “No image”
        return '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#efefef;color:#9a9a9a;font-size:14px;">No image</div>';
    }

    private function render_debug_box(array $payload, array $atts): string {
        ob_start();
        $status = $payload['transaction']['status'] ?? '';
        $incoming = $payload['transaction']['incomingIp'] ?? '';
        $queryInfo = $payload['QueryInfo'] ?? [];
        ?>
        <div style="border:1px dashed #bbb;padding:12px;margin-bottom:18px;border-radius:8px;background:#fafafa;">
            <strong>DEBUG (mínima + ND _PD forzado)</strong><br>
            HTTP: <?php echo esc_html($status ? '200' : '??'); ?><br>
            <details style="margin-top:6px;">
                <summary>Args del shortcode</summary>
                <pre style="white-space:pre-wrap;"><?php echo esc_html(print_r($atts, true)); ?></pre>
            </details>
            <details>
                <summary>transaction</summary>
                <pre style="white-space:pre-wrap;"><?php echo esc_html(print_r($payload['transaction'] ?? [], true)); ?></pre>
            </details>
            <details>
                <summary>QueryInfo</summary>
                <pre style="white-space:pre-wrap;"><?php echo esc_html(print_r($queryInfo, true)); ?></pre>
            </details>
            <small>En ND usaremos SIEMPRE PropertyDetails cuando Search no traiga imágenes.</small>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ===========================
     * Utils
     * =========================== */

    private function is_assoc($arr): bool {
        return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
    }
}

endif;

// Instancia
if (class_exists('Resales_Shortcodes')) {
    new Resales_Shortcodes();
}

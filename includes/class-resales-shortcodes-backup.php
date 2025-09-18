    // Devuelve el valor de imágenes a incluir según el filtro API guardado
    private function get_images_to_include_from_filter() {
        $settings = get_option('resales_api_settings');
        if (isset($settings['images_to_include'])) {
            $val = intval($settings['images_to_include']);
            if ($val === 0 || $val > 1) {
                return 0;
            }
            return $val;
        }
        return null;
    }
<?php
/**
 * Resales – Shortcodes (singleton + robusto + aliases + client factory)
 *
 * Shortcodes:
 *   [lusso_properties]       → listado general (status por defecto: ForSale)
 *   [resales_properties]     → alias del anterior (compat)
 *   [resales_developments]   → fuerza status="NewDevelopments"
 *
 * Atributos:
 *   results (int) : por página (def 12)
 *   page    (int) : página (def 1)
 *   lang    (str) : idioma (def 'es')
 *   status  (str) : ForSale | NewDevelopments | etc. (def 'ForSale')
 */

class Resales_Shortcodes {
    /**
     * Devuelve el valor de imágenes a incluir según la configuración del filtro API en opciones de WordPress.
     * Si no está configurado, retorna null.
     */
    private function get_images_to_include_from_filter() {
        // Cambia 'resales_api_settings' y 'images_to_include' según el nombre real de tu opción y campo
        $options = get_option('resales_api_settings');
        if (is_array($options) && isset($options['images_to_include'])) {
            $val = $options['images_to_include'];
            if ($val === '' || $val === null) return null;
            return $val;
        }
        return null;
    }
    /**
     * Encola Swiper solo una vez en el front.
     */
    public static function maybe_enqueue_swiper() {
        // Encolar solo en front
        if ( is_admin() ) return;
        if ( ! wp_style_is('swiper-css', 'enqueued') ) {
            wp_enqueue_style(
                'swiper-css',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
                array(),
                '11.0.0'
            );
        }
        if ( ! wp_script_is('swiper-js', 'enqueued') ) {
            wp_enqueue_script(
                'swiper-js',
                'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
                array(),
                '11.0.0',
                true
            );
        }
    }

    /** @var self */
    private static $instance = null;

    /** Singleton */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Compat: algunos plugins llaman init() */
    public static function init() { return self::instance(); }

    /** Constructor: registra shortcodes */
    private function __construct() {
    add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_swiper'));
    add_shortcode( 'lusso_properties',      array( $this, 'render_properties' ) );
    add_shortcode( 'resales_properties',    array( $this, 'render_properties' ) );   // alias
    add_shortcode( 'resales_developments',  array( $this, 'render_developments' ) ); // alias (nuevas promociones)
    }

    /** Alias que fuerza NewDevelopments */
    public function render_developments( $atts ) {
        $atts = shortcode_atts( array(
            'results' => 12,
            'page'    => 1,
            'lang'    => 'es',
            'status'  => 'NewDevelopments',
        ), $atts, 'resales_developments' );

        return $this->render_properties( $atts );
    }

    /** Render principal del listado */
    public function render_properties( $atts ) {
        $atts = shortcode_atts( array(
            'results' => 12,
            'page'    => 1,
            'lang'    => 'es',
            'status'  => 'ForSale',
            'ratio'   => '16/9', // Nuevo atributo opcional para aspect-ratio
        ), $atts, 'lusso_properties' );

        $results = max( 1, intval( $atts['results'] ) );
        $page    = max( 1, intval( $atts['page'] ) );
        $lang    = sanitize_text_field( $atts['lang'] );
        $status  = sanitize_text_field( $atts['status'] );

        $error = '';
        $items = array();

        try {
            $items = $this->fetch_items( $results, $page, $lang, $status );
        } catch ( \Throwable $e ) {
            $error = $e->getMessage();
            error_log( '[LUSSO SHORTCODE] ' . $error );
        }

        if ( is_object( $items ) ) $items = (array) $items;
        if ( isset( $items['items'] ) )    $items = $items['items'];        // formatos genéricos
        if ( isset( $items['Property'] ) ) $items = $items['Property'];     // Resales Online V6
        if ( ! is_array( $items ) )        $items = array();

        // Cargar FontAwesome y Swiper.js solo una vez
        static $assets_loaded = false;
        if ( ! $assets_loaded ) {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />';
            echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />';
            echo '<link rel="stylesheet" href="/wp-content/plugins/resales-api/assets/css/swiper-gallery.css" />';
            echo '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
            $assets_loaded = true;
        }

        // Inicializar Swiper en cada tarjeta
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                document.querySelectorAll(".lr-card-swiper").forEach(function(el){
                    new Swiper(el, {
                        navigation: { nextEl: el.querySelector(".swiper-button-next"), prevEl: el.querySelector(".swiper-button-prev") },
                        pagination: { el: el.querySelector(".swiper-pagination"), clickable: true },
                        loop: true,
                        slidesPerView: 1,
                        spaceBetween: 0
                    });
                });
            });
        </script>';

        ob_start();
        echo '<main class="site-main" id="main">';
        echo '<div class="lr-results-wrapper">';

        $count   = count( $items );
        $heading = esc_html__( 'Venta de viviendas en España', 'lusso-resales' );
        $heading .= ' – ' . intval( $count ) . ' ' . esc_html__( 'resultados', 'lusso-resales' );
        echo '<h1 class="lr-results-title" style="margin:0 0 18px;font-weight:700;font-size:20px;color:#404040;">' . $heading . '</h1>';

        if ( $error ) {
            printf('<div class="lr-error" style="color:#B00020;margin:12px 0;">%s</div>', esc_html( $error ));
        }

        echo '<div class="lr-grid">';

        if ( $count ) {
            foreach ( $items as $raw ) $this->render_card( $raw );
        } else {
            echo '<p style="grid-column:1/-1;opacity:.8;">' . esc_html__( 'No hay propiedades para mostrar.', 'lusso-resales' ) . '</p>';
        }

        echo '</div>'; // .lr-grid
        echo '</div>'; // .lr-results-wrapper
        echo '</main>';
        return ob_get_clean();
    }

    /**
     * Obtiene instancia del cliente sin invocar constructor privado.
     * Intenta: Resales_Client::instance()/get_instance()/singleton(), resales_client(), $GLOBALS.
     */
    private function get_client() {
        if ( ! class_exists( 'Resales_Client' ) ) throw new \Exception( 'Resales_Client no disponible.' );
        foreach ( array( 'instance', 'get_instance', 'singleton' ) as $m ) {
            if ( method_exists( 'Resales_Client', $m ) ) {
                $client = call_user_func( array( 'Resales_Client', $m ) );
                if ( is_object( $client ) ) return $client;
            }
        }
        if ( function_exists( 'resales_client' ) ) {
            $client = resales_client();
            if ( is_object( $client ) ) return $client;
        }
        if ( isset( $GLOBALS['resales_client'] ) && is_object( $GLOBALS['resales_client'] ) ) {
            return $GLOBALS['resales_client'];
        }
        throw new \Exception( 'No se pudo obtener Resales_Client (constructor privado / sin factory).' );
    }

    /** Lector de API genérico (V6 y otros) */
    private function fetch_items( $results, $page, $lang, $status ) {
        try {
            $client = $this->get_client();
        } catch ( \Throwable $e ) {
            error_log( '[LUSSO SHORTCODE] get_client(): ' . $e->getMessage() );
            return array();
        }

        // Permitir paso de imágenes y control de New Developments
        global $atts;
        $args = array(
            'lang'             => $lang,
            'results_per_page' => $results,
            'page'             => $page,
            'status'           => $status,
        );

        // Hook para override dinámico desde otros plugins o admin
        $images_override = apply_filters('resales_api_images_to_include', null, $atts, $args);

        if ( isset($atts['images']) ) {
            $args['p_images'] = $atts['images'];
        } elseif ( $images_override !== null ) {
            $args['p_images'] = $images_override;
        } else {
            $filter_val = $this->get_images_to_include_from_filter();
            if ($filter_val !== null) {
                $args['p_images'] = $filter_val;
            }
        }

        // P_New_Devs: 'include' para ForSale, 'only' para solo New Developments
        if ( strtolower($status) === 'forsale' ) {
            $args['p_new_devs'] = 'include';
        } elseif ( strtolower($status) === 'newdevelopments' ) {
            $args['p_new_devs'] = 'only';
        }

        if ( method_exists( $client, 'search' ) ) {
            $response = $client->search( $args );
        } elseif ( method_exists( $client, 'list' ) ) {
            $response = $client->list( $args );
        } elseif ( method_exists( $client, 'get_properties' ) ) {
            $response = $client->get_properties( $args );
        } else {
            error_log( '[LUSSO SHORTCODE] Ningún método de listado encontrado en Resales_Client.' );
            return array();
        }

        // Normalizar formas de respuesta
        if ( is_object( $response ) ) $response = (array) $response;
        if ( isset( $response[0] ) && ( is_array( $response[0] ) || is_object( $response[0] ) ) ) return $response;
        if ( isset( $response['Property'] ) && is_array( $response['Property'] ) ) return $response['Property'];
        if ( isset( $response['items'] ) && is_array( $response['items'] ) )     return $response['items'];
        if ( isset( $response['data'] ) && is_array( $response['data'] ) )       return $response['data'];

        return array();
    }

    /** Pinta una tarjeta */
    private function render_card( $raw ) {
        $item = $this->normalize_item( $raw );
        $alt = isset($item['title']) ? wp_strip_all_tags($item['title']) : '';

        // Galería de imágenes (siempre Swiper, incluso una sola imagen)
        $images = array();
        // 1. Photos (API V6)
        if (!empty($raw['Photos']) && is_array($raw['Photos'])) {
            foreach ($raw['Photos'] as $img) {
                if (!empty($img['Url'])) $images[] = esc_url($img['Url']);
            }
        }
        // 2. Pictures['Picture'] (API V5)
        if (empty($images) && !empty($raw['Pictures']['Picture']) && is_array($raw['Pictures']['Picture'])) {
            foreach ($raw['Pictures']['Picture'] as $img) {
                if (!empty($img['PictureURL'])) $images[] = esc_url($img['PictureURL']);
            }
        }
        // 3. Images/Url
        if (empty($images) && !empty($raw['Images']) && is_array($raw['Images'])) {
            foreach ($raw['Images'] as $img) {
                if (!empty($img['Url'])) $images[] = esc_url($img['Url']);
            }
        }
        // 4. MainImage
        if (empty($images) && !empty($raw['MainImage'])) {
            $images[] = esc_url($raw['MainImage']);
        }
        // 5. ReferenceImage
        if (empty($images) && !empty($raw['ReferenceImage'])) {
            $images[] = esc_url($raw['ReferenceImage']);
        }
        // 6. item['image'] (normalizado)
        if (empty($images) && !empty($item['image'])) {
            $images[] = esc_url($item['image']);
        }
        // Si no hay imágenes, placeholder
        if (empty($images)) {
            $images[] = $this->placeholder_image();
        }

        // Datos principales
        $area = isset($raw['Area']) ? esc_html($raw['Area']) : '';
        $subarea = isset($raw['SubArea']) ? esc_html($raw['SubArea']) : '';
        $location = trim($area . ($subarea ? ', ' . $subarea : ''));
        // Descripción: primera oración del segundo párrafo (igual que detalles)
        $desc = '';
        if (isset($raw['Description']) && is_string($raw['Description'])) {
            $parts = preg_split('/\n+/', trim($raw['Description']));
            $ix = count($parts) > 1 ? 1 : 0;
            $sentences = preg_split('/(?<=[.!?])\s+/', trim($parts[$ix]));
            $desc = esc_html($sentences[0]);
        }
        $bed = isset($raw['Bedrooms']) ? intval($raw['Bedrooms']) : '';
        $bath = isset($raw['Bathrooms']) ? intval($raw['Bathrooms']) : '';
        $plot = isset($raw['PlotSize']) ? intval($raw['PlotSize']) : '';
        $built = isset($raw['BuiltSize']) ? intval($raw['BuiltSize']) : '';
        $terrace = isset($raw['TerraceSize']) ? intval($raw['TerraceSize']) : '';

        // ------- PRECIOS (rango, único y alquiler) -------
        $price_from    = '';
        $price_to      = '';
        $price_single  = '';
        $rental_period = '';
        $currency      = isset($raw['Currency']) ? $raw['Currency'] : 'EUR';

        // Rango declarado PriceFrom/PriceTo (new developments)
        if ( isset($raw['PriceFrom']) && $raw['PriceFrom'] !== '' ) {
            $price_from = number_format((float)$raw['PriceFrom'], 0, ',', '.');
        }
        if ( isset($raw['PriceTo']) && $raw['PriceTo'] !== '' ) {
            $price_to = number_format((float)$raw['PriceTo'], 0, ',', '.');
        }

        // Si no hay PriceFrom/To, intentar con Price y OriginalPrice
        if ( ! $price_from && ! $price_to ) {
            $has_price     = isset($raw['Price']) && $raw['Price'] !== '';
            $has_original  = isset($raw['OriginalPrice']) && $raw['OriginalPrice'] !== '';
            if ( $has_price && $has_original ) {
                $p  = (float) str_replace(array(',', ' '), array('', ''), $raw['Price']);
                $po = (float) str_replace(array(',', ' '), array('', ''), $raw['OriginalPrice']);
                $lo = min($p, $po);
                $hi = max($p, $po);
                if ( $lo && $hi && $hi !== $lo ) {
                    $price_from = number_format($lo, 0, ',', '.');
                    $price_to   = number_format($hi, 0, ',', '.');
                } elseif ( $p ) {
                    $price_single = number_format($p, 0, ',', '.');
                }
            } elseif ( $has_price ) {
                $price_single = number_format((float)$raw['Price'], 0, ',', '.');
            }
        }

        // Si no hay PriceFrom/To, intentar extraer desde la descripción
        if ( ! $price_from && ! $price_to && isset($raw['Description']) && is_string($raw['Description']) ) {
            $parts = preg_split('/\n+/', trim($raw['Description']));
            $first_paragraph = count($parts) > 0 ? $parts[0] : '';
            if (preg_match('/Prices? from \€?([\d,.]+) to \€?([\d,.]+)/i', $first_paragraph, $matches)) {
                $price_from = number_format((float)str_replace([',','.'], ['', ''], $matches[1]), 0, ',', '.');
                $price_to = number_format((float)str_replace([',','.'], ['', ''], $matches[2]), 0, ',', '.');
            }
        }

        // Alquiler: RentalPrice1/2 + RentalPeriod
        if ( isset($raw['RentalPrice1']) || isset($raw['RentalPrice2']) ) {
            $r1 = isset($raw['RentalPrice1']) ? $raw['RentalPrice1'] : '';
            $r2 = isset($raw['RentalPrice2']) ? $raw['RentalPrice2'] : '';
            if ( $r1 !== '' && $r2 !== '' && $r1 != $r2 ) {
                $price_from = number_format((float)$r1, 0, ',', '.');
                $price_to   = number_format((float)$r2, 0, ',', '.');
                $price_single = '';
            } elseif ( $r1 !== '' ) {
                $price_single = number_format((float)$r1, 0, ',', '.');
            } elseif ( $r2 !== '' ) {
                $price_single = number_format((float)$r2, 0, ',', '.');
            }
            if ( isset($raw['RentalPeriod']) && $raw['RentalPeriod'] ) {
                $rental_period = trim($raw['RentalPeriod']);
            }
        }

        // Generar URL al detalle de la propiedad
        $url = '';
        $url = $this->first_non_empty($raw, array(
            'DetailsUrl','details_url',
            'Permalink','permalink',
            'Url','url',
            'Link','link',
            'WebUrl','web_url','WebURL',
            'PropertyUrl','PropertyURL',
            'URLDetails','DetailUrl','DetailURL'
        ), '');
        if ($url === '' && isset($raw['Reference'])) {
            $url = site_url('/property/?ref=' . urlencode($raw['Reference']));
        }

        global $atts;
        ?>
        <article class="lr-card">
            <div class="lr-card__media" style="aspect-ratio: <?php echo esc_attr(isset($atts['ratio']) ? $atts['ratio'] : '16/9'); ?>;">
                <div class="swiper lr-card-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $img): ?>
                            <div class="swiper-slide">
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($alt); ?>" style="width:100%;height:100%;object-fit:cover;" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
            <div class="lr-card__bar" style="display:flex;flex-direction:column;gap:0.5rem;text-decoration:none;">
                <a href="<?php echo esc_url($url); ?>" style="text-decoration:none;color:inherit;display:block;width:100%;height:100%;">
                    <div style="color:#666;font-size:0.875rem;line-height:1.2;"><?php echo $location; ?></div>
                    <h2 style="font-size:1rem;font-weight:700;color:var(--color-gold-dark);line-height:1.3;margin:0;"><?php echo $desc; ?></h2>
                    <div style="display:flex;gap:14px;margin:8px 0;justify-content:center;">
                        <?php if($bed): ?><span title="Bedrooms"><i class="fa fa-bed" style="color:var(--color-gold-dark);"></i> <?php echo $bed; ?></span><?php endif; ?>
                        <?php if($bath): ?><span title="Bathrooms"><i class="fa fa-bath" style="color:var(--color-gold-dark);"></i> <?php echo $bath; ?></span><?php endif; ?>
                        <?php if($plot): ?><span title="Plot size"><i class="fa fa-tree" style="color:var(--color-green-dark);"></i> <?php echo $plot; ?> m²</span><?php endif; ?>
                        <?php if($built): ?><span title="Built size"><i class="fa fa-building" style="color:var(--color-gray-dark);"></i> <?php echo $built; ?> m²</span><?php endif; ?>
                        <?php if($terrace): ?><span title="Terrace"><i class="fa fa-square" style="color:var(--color-gold-dark);"></i> <?php echo $terrace; ?> m²</span><?php endif; ?>
                    </div>

                    <div style="font-size:1rem;color:#222;line-height:1.2;">
                        <?php
                        if ( $price_from && $price_to ) {
                            echo 'From ' . $currency . ' ' . $price_from . ' to ' . $currency . ' ' . $price_to;
                            if ( $rental_period ) echo ' / ' . esc_html($rental_period);
                        } elseif ( $price_from ) {
                            echo 'From ' . $currency . ' ' . $price_from;
                            if ( $rental_period ) echo ' / ' . esc_html($rental_period);
                        } elseif ( $price_single ) {
                            echo $currency . ' ' . $price_single;
                            if ( $rental_period ) echo ' / ' . esc_html($rental_period);
                        } else {
                            echo 'Price on request';
                        }
                        ?>
                    </div>
                </a>
            </div>
        </article>
        <?php
    }

    /** Normaliza claves típicas V6 + alias extra y fallback interno a /property/?ref=xxx */
    private function normalize_item( $raw ) {
        $a = is_object( $raw ) ? (array) $raw : (array) $raw;

        // Imagen
        $image = $this->first_non_empty( $a, array( 'ImageUrl', 'image', 'image_url', 'MainImage', 'PictureURL', 'Image' ) );
        if ( empty( $image ) && isset( $a['Images'] ) && is_array( $a['Images'] ) && ! empty( $a['Images'] ) ) {
            $img0  = is_object($a['Images'][0]) ? (array)$a['Images'][0] : (array)$a['Images'][0];
            $image = $this->first_non_empty( $img0, array( 'UrlImage', 'URL', 'Url', 'ImageUrl' ) );
        }

        // Título
        $title = $this->first_non_empty( $a, array( 'Title','title','Location','location','SubLocation','Urbanization','Name','name' ) );
        if ( empty( $title ) ) {
            $ref  = isset($a['Reference']) ? $a['Reference'] : '';
            $type = '';
            if ( isset($a['PropertyType']) ) {
                $pt   = is_object($a['PropertyType']) ? (array)$a['PropertyType'] : (array)$a['PropertyType'];
                $type = $this->first_non_empty($pt, array('NameType','Type'));
            }
            $title = trim($ref . ' ' . $type);
            if ( $title === '' ) $title = __( 'Sin título', 'lusso-resales' );
        }

        // ...existing code...
    }

    private function first_non_empty( $arr, $keys, $default = '' ) {
        if ( is_object( $arr ) ) $arr = (array) $arr;
        foreach ( $keys as $k ) {
            if ( isset( $arr[ $k ] ) && $arr[ $k ] !== '' && $arr[ $k ] !== null ) {
                return $arr[ $k ];
            }
        }
        return $default;
    }

    private function format_price( $price ) {
        // Si ya contiene divisa o texto (p.ej. "€3,450,000" o "Price on request"), respétalo
        if ( is_string( $price ) && preg_match( '/[A-Za-z€$]/', $price ) ) {
            return trim( $price );
        }
        // Normalizar cadenas con separadores
        if ( is_string( $price ) ) {
            $price = str_replace( array( '.', ' ' ), '', $price ); // quitar miles con puntos/espacios
            $price = str_replace( ',', '.', $price );              // coma decimal → punto
        }
        if ( is_numeric( $price ) ) {
            $formatted = number_format( (float) $price, 0, '.', ',' );
            return '€' . $formatted;
        }
        return '';
    }

    private function placeholder_image() {
        $svg = 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 162 100" preserveAspectRatio="xMidYMid slice">
                <rect width="100%" height="100%" fill="#e5e5e5"/>
                <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#777" font-family="system-ui,Arial,Helvetica,sans-serif" font-size="10">Imagen no disponible</text>
            </svg>'
        );
        return $svg;
    }
}


// Bootstrap
Resales_Shortcodes::instance();

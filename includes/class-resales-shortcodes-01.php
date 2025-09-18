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

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Resales_Shortcodes' ) ) :

class Resales_Shortcodes {

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
				echo '<link rel="stylesheet" href="/assets/css/swiper-gallery.css" />';
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

		$args = array(
			'lang'             => $lang,
			'results_per_page' => $results,
			'page'             => $page,
			'status'           => $status,
		);

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
		$alt = wp_strip_all_tags( $item['title'] );

		// Galería de imágenes (Swiper)
		$images = array();
		if ( !empty($raw['Photos']) && is_array($raw['Photos']) ) {
			foreach ($raw['Photos'] as $img) {
				if (!empty($img['Url'])) $images[] = esc_url($img['Url']);
			}
		} elseif (!empty($item['image'])) {
			$images[] = esc_url($item['image']);
		}
		if (empty($images)) $images[] = $this->placeholder_image();

		// Datos principales
		$area = isset($raw['Area']) ? esc_html($raw['Area']) : '';
		$subarea = isset($raw['SubArea']) ? esc_html($raw['SubArea']) : '';
		$location = trim($area . ($subarea ? ', ' . $subarea : ''));
		$desc = isset($raw['Description']) ? esc_html( preg_split('/\./', $raw['Description'])[0] . '.' ) : '';
		$bed = isset($raw['Bedrooms']) ? intval($raw['Bedrooms']) : '';
		$bath = isset($raw['Bathrooms']) ? intval($raw['Bathrooms']) : '';
		$plot = isset($raw['PlotSize']) ? intval($raw['PlotSize']) : '';
		$built = isset($raw['BuiltSize']) ? intval($raw['BuiltSize']) : '';
		$terrace = isset($raw['TerraceSize']) ? intval($raw['TerraceSize']) : '';
		$price_from = isset($raw['PriceFrom']) ? $this->format_price($raw['PriceFrom']) : '';
		$price_to = isset($raw['PriceTo']) ? $this->format_price($raw['PriceTo']) : '';
		$url = $item['url'];

		?>
		   <article class="lr-card">
			   <div class="lr-card__media">
				   <div class="swiper lr-card-swiper">
					   <div class="swiper-wrapper">
						   <?php foreach($images as $img): ?>
							   <div class="swiper-slide">
								   <img src="<?php echo $img; ?>" alt="<?php echo esc_attr($alt); ?>">
							   </div>
						   <?php endforeach; ?>
					   </div>
				   </div>
			   </div>
			   <a href="<?php echo esc_url($url); ?>" class="lr-card__bar" style="display:flex;flex-direction:column;gap:0.5rem;text-decoration:none;">
				   <div style="color:#666;font-size:0.875rem;line-height:1.2;"> <?php echo $location; ?> </div>
				   <h2 style="font-size:1rem;font-weight:700;color:var(--color-gold-dark);line-height:1.3;margin:0;"> <?php echo $desc; ?> </h2>
				   <div style="display:flex;gap:14px;margin:8px 0;justify-content:center;">
					   <?php if($bed): ?><span title="Bedrooms"><i class="fa fa-bed" style="color:var(--color-gold-dark);"></i> <?php echo $bed; ?></span><?php endif; ?>
					   <?php if($bath): ?><span title="Bathrooms"><i class="fa fa-bath" style="color:var(--color-gold-dark);"></i> <?php echo $bath; ?></span><?php endif; ?>
					   <?php if($plot): ?><span title="Plot size"><i class="fa fa-tree" style="color:var(--color-green-dark);"></i> <?php echo $plot; ?> m²</span><?php endif; ?>
					   <?php if($built): ?><span title="Built size"><i class="fa fa-building" style="color:var(--color-gray-dark);"></i> <?php echo $built; ?> m²</span><?php endif; ?>
					   <?php if($terrace): ?><span title="Terrace"><i class="fa fa-square" style="color:var(--color-gold-dark);"></i> <?php echo $terrace; ?> m²</span><?php endif; ?>
				   </div>
				   <div style="font-size:1rem;color:#222;line-height:1.2;">From</div>
				   <div style="font-size:1.25rem;font-weight:700;color:#222;line-height:1.2;">
					   <?php if($price_from && $price_to): ?><?php echo $price_from; ?> - <?php echo $price_to; ?><?php elseif($price_from): ?><?php echo $price_from; ?><?php endif; ?>
				   </div>
			   </a>
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

		// URL detalle: más alias + fallback
		$url = $this->first_non_empty( $a, array(
			'DetailsUrl','details_url',
			'Permalink','permalink',
			'Url','url',
			'Link','link',
			'WebUrl','web_url','WebURL',
			'PropertyUrl','PropertyURL',
			'URLDetails','DetailUrl','DetailURL'
		), '' );

		if ( $url === '' ) {
			$ref = isset($a['Reference']) ? sanitize_title($a['Reference']) : '';
			$id  = isset($a['PropertyId']) ? intval($a['PropertyId']) : 0;
			if ( $ref || $id ) {
				$args = array_filter(array('ref' => $ref ?: null, 'id' => $id ?: null));
				$url  = add_query_arg($args, site_url('/property/'));
			} else {
				$url = '#';
			}
		}

		// Precio (texto o numérico)
		$price_raw  = $this->first_non_empty( $a, array( 'Price','price','PriceEUR','price_eur','PriceText','price_text' ), '' );
		$price_text = $this->format_price( $price_raw );

		if ( empty($image) ) $image = $this->placeholder_image();

		return array(
			'image'      => $image,
			'title'      => $title,
			'url'        => $url ?: '#',
			'price_text' => $price_text,
		);
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
		// Si ya viene con divisa/texto, respétalo.
		if ( is_string( $price ) && preg_match( '/[A-Za-z€$]/', $price ) ) return trim( $price );
		// Acepta "1234567", "1.234.567", "1.234.567,00"
		if ( is_string( $price ) ) {
			$price = str_replace( array( '.', ' ' ), array( '', '' ), $price );
			$price = str_replace( ',', '.', $price );
		}
		if ( is_numeric( $price ) ) return 'EUR ' . number_format( (float) $price, 0, ',', '.' );
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

endif;

// Bootstrap
Resales_Shortcodes::instance();

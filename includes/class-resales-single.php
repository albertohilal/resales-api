<?php
/**
 * Single Property (Resales Online V6)
 * File: includes/class-resales-single.php
 */

if ( ! defined('ABSPATH') ) { exit; }

if ( ! class_exists('Resales_Single') ) {

class Resales_Single {

	/** @var self */
	private static $instance;

	/** @var array Ajustes normalizados */
	private $opts = [];

	/** Singleton */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Carga ajustes al iniciar (pero sin romper si aún no existen)
		$this->opts = $this->load_options();

		// Shortcodes (alias compatibles)
		add_shortcode('resales_property', [ $this, 'render_shortcode' ]);
		add_shortcode('resales_single',   [ $this, 'render_shortcode' ]);
	}

	/**
	 * Intenta leer los ajustes desde varias opciones posibles del plugin.
	 * También admite CONSTANTES como plan B.
	 */
	private function load_options() {
		$possible_option_names = [
			'resales_api_settings',   // más probable
			'resales_api',            // alternativa común
			'resales_settings',
			'resales_options',
			'resales',                // safety
		];

		$raw = [];
		foreach ( $possible_option_names as $name ) {
			$tmp = get_option($name);
			if ( is_array($tmp) && ! empty($tmp) ) {
				$raw = array_merge($raw, $tmp);
			}
		}

		// Normalizar claves (indistintamente p1/P1, p_apiid/P_ApiId, etc.)
		$norm = function($key) {
			$key = str_replace('-', '_', $key);
			$key = strtolower($key);
			return $key;
		};

		$out = [];
		foreach ( (array) $raw as $k => $v ) {
			$out[ $norm($k) ] = is_string($v) ? trim($v) : $v;
		}

		// Fallback a CONSTANTES (si existen)
		$const_map = [
			'p1'                 => 'RESALES_API_P1',
			'p2'                 => 'RESALES_API_P2',
			'p_apiid'            => 'RESALES_API_APIID',
			'p_agency_filterid'  => 'RESALES_API_AGENCY_FILTERID',
			'p_lang'             => 'RESALES_API_LANG',
			'p_sandbox'          => 'RESALES_API_SANDBOX',
			'timeout'            => 'RESALES_API_TIMEOUT',
		];
		foreach ($const_map as $key => $const) {
			if ( ! isset($out[$key]) && defined($const) ) {
				$out[$key] = constant($const);
			}
		}

		// Valores por defecto razonables
		if ( empty($out['p_lang']) )    { $out['p_lang'] = '2'; }     // 2=ES
		if ( ! isset($out['p_sandbox']) ) { $out['p_sandbox'] = false; }
		if ( empty($out['timeout']) )   { $out['timeout'] = 20; }

		// Log útil (sin exponer p2)
		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			$mask = function($s){ return $s ? substr($s,0,4).'•••' : ''; };
			resales_log('DEBUG', '[Resales API] Opciones detectadas', [
				'p1' => ($out['p1'] ?? ''),
				'p2' => $mask($out['p2'] ?? ''),
				'P_ApiId' => ($out['p_apiid'] ?? ''),
				'P_Agency_FilterId' => ($out['p_agency_filterid'] ?? ''),
				'Lang' => ($out['p_lang'] ?? '')
			]);
		}

		return $out;
	}

	/** Construye URL para la función V6 */
	private function build_url( $function, array $params = [] ) {
		$base = 'https://webapi.resales-online.com/V6/' . $function;

		$commons = [
			'p1'       => $this->opts['p1']     ?? '',
			'p2'       => $this->opts['p2']     ?? '',
			'p_output' => 'JSON',
			'P_Lang'   => $this->opts['p_lang'] ?? '2',
		];

		// Uno de estos dos es obligatorio según la doc (si tu filtro usa alias, deja P_ApiId vacío y pon P_Agency_FilterId)
		if ( ! empty($this->opts['p_apiid']) ) {
			$commons['P_ApiId'] = $this->opts['p_apiid'];
		} elseif ( ! empty($this->opts['p_agency_filterid']) ) {
			$commons['P_Agency_FilterId'] = $this->opts['p_agency_filterid'];
		}

		if ( ! empty($this->opts['p_sandbox']) ) {
			$commons['p_sandbox'] = 'true';
		}

		$q = array_filter( array_merge( $commons, $params ), static function($v){
			return $v !== '' && $v !== null;
		});

		return $base . '?' . http_build_query($q);
	}

	/** Llama a PropertyDetails con P_RefId o P_Id */
	private function fetch_details( $ref = '', $id = '' ) {

		// Validación de credenciales (obligatorias) según la doc: p1 y p2. 
		if ( empty($this->opts['p1']) || empty($this->opts['p2']) ) {
			return $this->error_box('Missing API credentials (p1/p2).');
		}

		$params = [];
		if ( $ref )        { $params['P_RefId'] = $ref; }
		elseif ( $id )     { $params['P_Id']    = $id; }
		else {
			return $this->error_box('Reference or ID is required to show the property.');
		}

		$url = $this->build_url('PropertyDetails', $params);

		// Petición
		$args = [
			'timeout' => intval($this->opts['timeout'] ?? 20),
			'headers' => [ 'Accept' => 'application/json' ],
		];

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error($response) ) {
			return $this->error_box( 'Request error: ' . $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);

		if ( $code !== 200 ) {
			// Mensajes útiles (401 suele ser credenciales/filtro) — ver “Common Parameters List” y requisitos. 
			return $this->error_box( sprintf('Unexpected response (%d) from Resales Online.', $code) );
		}

		$data = json_decode($body, true);
		if ( ! is_array($data) ) {
			return $this->error_box('Invalid JSON returned by API.');
		}

		// Transacción fallida o sin propiedad
		if ( isset($data['transaction']['status']) && $data['transaction']['status'] !== 'success' ) {
			$msg = !empty($data['transaction']['message']) ? $data['transaction']['message'] : 'Transaction not successful.';
			return $this->error_box( esc_html($msg) );
		}

		$prop = $data['Property'] ?? null;
		if ( empty($prop) || ! is_array($prop) ) {
			$apiMsg = '';
			if (isset($data['transaction']['message'])) {
				$apiMsg = esc_html($data['transaction']['message']);
			}
			$fallback = $apiMsg ?: 'Property not found or temporarily unavailable.';
			return $this->error_box($fallback);
		}

		// Render mínimo (estructura: galería + datos). Adapta a tu CSS.
		return $this->render_view($prop);
	}

	/** Shortcode [resales_property ref="Rxxxxx"] o [resales_property id="12345"] */
	public function render_shortcode( $atts = [] ) {
		// Encolar CSS de detalle dinámicamente si no está ya encolado
		if ( ! wp_style_is('lusso-resales-detail', 'enqueued') ) {
			wp_enqueue_style('lusso-resales-detail', plugins_url('../assets/css/lusso-resales-detail.css', __FILE__), [], '1.0');
		}
		
		$atts = shortcode_atts([
			'ref' => '',
			'id'  => '',
		], $atts, 'resales_property');

		// Si no viene por atributo, leemos ?ref o ?id del URL (case-insensitive)
		if ( empty($atts['ref']) ) {
			$try = isset($_GET['ref']) ? $_GET['ref'] : ( $_GET['Ref'] ?? '' );
			if ( is_string($try) ) { $atts['ref'] = trim($try); }
		}
		if ( empty($atts['id']) ) {
			$try = isset($_GET['id']) ? $_GET['id'] : ( $_GET['Id'] ?? '' );
			if ( is_string($try) ) { $atts['id'] = trim($try); }
		}

		return $this->fetch_details( $atts['ref'], $atts['id'] );
	}

	/** Vista mínima (puedes reemplazar por tu plantilla definitiva) */
	private function render_view( array $p ) {
		ob_start();

		// ...existing code...
		$title = esc_html( $p['PropertyType']['NameType'] ?? ($p['Reference'] ?? '') );
		$ref   = esc_html( $p['Reference'] ?? '' );
		$loc   = esc_html( trim( ($p['Location'] ?? '') . ', ' . ($p['Area'] ?? '') ) );
		// Lógica para mostrar el precio como 'From (PriceFrom) to (PriceTo)' si existen
		$price_from = isset($p['PriceFrom']) && $p['PriceFrom'] !== '' ? number_format((float)$p['PriceFrom'], 0, ',', '.') : '';
		$price_to   = isset($p['PriceTo']) && $p['PriceTo'] !== '' ? number_format((float)$p['PriceTo'], 0, ',', '.') : '';
		$currency   = $p['Currency'] ?? 'EUR';
		if ($price_from && $price_to) {
			$price = esc_html("From $currency $price_from to $currency $price_to");
		} elseif ($price_from) {
			$price = esc_html("From $currency $price_from");
		} elseif (isset($p['Price']) && $p['Price'] !== '') {
			$price = esc_html($currency . ' ' . number_format((float)$p['Price'], 0, ',', '.'));
		} else {
			$price = esc_html(__('Price on request', 'resales-api'));
		}

		$imgs = [];
		// Nueva lógica: usar Pictures['Picture'] y PictureURL
		if (!empty($p['Pictures']['Picture']) && is_array($p['Pictures']['Picture'])) {
			foreach ($p['Pictures']['Picture'] as $img) {
				if (!empty($img['PictureURL'])) {
					$imgs[] = esc_url($img['PictureURL']);
				}
			}
		}
		// Fallback: si no hay imágenes, intentar Images/MainImage (por compatibilidad)
		if (empty($imgs)) {
			if (!empty($p['Images']) && is_array($p['Images'])) {
				foreach ($p['Images'] as $img) {
					if (!empty($img['Url'])) { $imgs[] = esc_url($img['Url']); }
				}
			} elseif (!empty($p['MainImage'])) {
				$imgs[] = esc_url($p['MainImage']);
			}
		}

	?>
	<link href="https://fonts.googleapis.com/css?family=Inter:400,500,700&display=swap" rel="stylesheet">
	<div class="property-detail-container" style="width:85vw;max-width:1500px;margin:2em auto;padding:1.2em;background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,0.07);font-family:'Inter',sans-serif;">
		
		<!-- Galería mantenida exactamente como estaba -->
		<div class="property-gallery" style="width:100%; margin-bottom:2em;">
			<?php
				require_once __DIR__ . '/gallery-helper.php';
				render_gallery($imgs, 'detail');
			?>
		</div>
		
		<!-- Nuevos contenedores según especificación -->
		<div class="lusso-detail-container">
			<div class="lusso-detail-left">
				<!-- Contenido principal: título, descripción, tabs -->
				<h1 style="font-size:2.2em;font-weight:700;margin-bottom:0.3em;line-height:1.1;"><?php echo esc_html($title); ?></h1>
				
				<!-- Tabs de descripción y ubicación -->
				<style>
					.property-tabs { display:flex; border-bottom:2px solid var(--c-gray-soft, #EAEAEA); margin-bottom:1.5em; font-family: 'Inter', sans-serif; }
					.property-tab { padding:1em 2em; cursor:pointer; font-weight:600; color:var(--c-gray-intense, #404404); background:none !important; border:none; outline:none; transition:all 0.3s ease; font-family: 'Inter', sans-serif; }
					.property-tab.active { color:var(--c-gold-dark, #B8860B); border-bottom:2px solid var(--c-gold-dark, #B8860B); background:none !important; }
					.property-tab:hover:not(.active) { color:var(--c-gold, #D4AF37); background:none !important; }
					#tab-loc { background-color: transparent !important; background: none !important; }
					.property-tab-content { background:var(--c-white, #FFFFFF); border-radius:8px; box-shadow:0 1px 8px rgba(0,0,0,0.04); padding:2em; min-height:180px; font-family: 'Inter', sans-serif; }
				</style>
				<div class="property-tabs">
					<button class="property-tab active" id="tab-desc" onclick="showTab('desc')"><?php _e('Description', 'resales-api'); ?></button>
					<button class="property-tab" id="tab-loc" onclick="showTab('loc')"><?php _e('Location', 'resales-api'); ?></button>
				</div>
				<div class="property-tab-content" id="tab-content-desc">
					<?php echo wpautop( wp_kses_post( $p['Description'] ?? '' ) ); ?>
				</div>
				<div class="property-tab-content" id="tab-content-loc" style="display:none;">
					<iframe src="https://www.google.com/maps?q=<?php echo urlencode($p['Location'] ?? ''); ?>&output=embed" width="100%" height="220" style="border:0;border-radius:6px;" allowfullscreen="" loading="lazy"></iframe>
					<div style="margin-top:1em;color:#555;font-size:1em;">
						<?php echo esc_html($p['Location'] ?? ''); ?>
					</div>
				</div>
			</div> <!-- cierra .lusso-detail-left -->
			
			<div class="lusso-detail-right">
				<!-- Ficha lateral de resumen -->
				<div class="property-info-table" style="background:#f8f8f8;border-radius:6px;padding:1.5em 2em;margin-bottom:1.5em;">
					<style>
						.property-info-table table { width:100%; border-collapse:collapse; font-size:1.1em; }
						.property-info-table table, .property-info-table td, .property-info-table tr { border:none !important; }
						.property-info-table td { background:#f8f8f8; padding:8px 0; }
						.property-info-table td:first-child { font-weight:600; color:#222; }
					</style>
					<table>
						<tbody>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Ref. no.', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo $ref; ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Price', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo $price; ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Location', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo $loc; ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Area', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['Area'] ?? ''); ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Type', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['Type'] ?? ''); ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Bedrooms', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['Bedrooms'] ?? ''); ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Bathrooms', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['Bathrooms'] ?? ''); ?></td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Plot size', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['PlotSize'] ?? ''); ?> m²</td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Built size', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['BuiltSize'] ?? ''); ?> m²</td></tr>
							<tr><td style="font-weight:600;padding:8px 0;"><?php _e('Terrace', 'resales-api'); ?></td><td style="text-align:right;padding:8px 0;"><?php echo esc_html($p['Terrace'] ?? ''); ?> m²</td></tr>
						</tbody>
					</table>
					<?php if (!empty($p['Features'])): ?>
						<div style="margin-top:1.5em;color:#555;"><strong><?php _e('Features', 'resales-api'); ?>:</strong> <?php echo esc_html($p['Features']); ?></div>
					<?php endif; ?>
				</div>
				
				<!-- Sección de contacto -->
				<div class="property-detail-contact" style="background:#f9f9f9;padding:2em;border-radius:8px;box-shadow:0 1px 8px rgba(0,0,0,0.04);">
					<h2 style="font-size:1.5em;font-weight:600;margin-bottom:1em;"><?php _e('Contact', 'resales-api'); ?></h2>
					<div class="property-contact-placeholder" style="border:1px dashed #aaa; padding:2em; text-align:center; background:#fff; border-radius:6px;">
						<em style="color:#888;font-size:1.1em;"><?php _e('Contact form coming soon.', 'resales-api'); ?></em>
					</div>
				</div>
			</div> <!-- cierra .lusso-detail-right -->
		</div> <!-- cierra .lusso-detail-container -->
		
		<!-- JavaScript para los tabs -->
		<script>
			function showTab(tab) {
				document.getElementById('tab-desc').classList.remove('active');
				document.getElementById('tab-loc').classList.remove('active');
				document.getElementById('tab-content-desc').style.display = 'none';
				document.getElementById('tab-content-loc').style.display = 'none';
				if(tab === 'desc') {
					document.getElementById('tab-desc').classList.add('active');
					document.getElementById('tab-content-desc').style.display = 'block';
				} else {
					document.getElementById('tab-loc').classList.add('active');
					document.getElementById('tab-content-loc').style.display = 'block';
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}

	private function error_box( $msg ) {
		return '<div class="resales-error" style="color:#c00;margin:20px 0;">'. esc_html($msg) .'</div>';
	}
}

} // class_exists

// Bootstrap
add_action('init', function(){
	Resales_Single::instance();
});

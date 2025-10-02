<?php
/**
 * Devuelve la configuración de filtros (provincias, locations y subáreas) para exponer al JS.
 *
 * @return array{
 *   provinces: string[],
 *   locationsByProvince: array<string, string[]>,
 *   subareasByLocation: array<string, string[]>
 * }
 */
function lusso_filters_get_config(): array {
	$provinces = array_keys(Resales_Filters::$LOCATIONS);
	$locationsByProvince = [];
	$subareasByLocation = [];
	foreach (Resales_Filters::$LOCATIONS as $province => $locs) {
		$locationsByProvince[$province] = [];
		foreach ($locs as $item) {
			$loc = $item['value'];
			$locationsByProvince[$province][] = $loc;
			// Si hay subáreas, agregarlas aquí (en este ejemplo no hay, pero estructura lista)
			$subareasByLocation[$loc] = [];
		}
	}
	return [
		'provinces' => $provinces,
		'locationsByProvince' => $locationsByProvince,
		'subareasByLocation' => $subareasByLocation,
	];
}

/**
 * Devuelve la ubicación formateada para mostrar en la tarjeta.
 *
 * @param array $p Array asociativo con claves 'Province', 'Location', 'SubArea'.
 * @return string Ubicación formateada y escapada.
 */
function get_card_place_label(array $p): string {
	$province = isset($p['Province']) ? trim((string)$p['Province']) : '';
	$location = isset($p['Location']) ? trim((string)$p['Location']) : '';
	$subarea  = isset($p['SubArea'])  ? trim((string)$p['SubArea'])  : '';

	if ($subarea !== '') {
		$label = $subarea;
		if ($location !== '') {
			$label .= ', ' . $location;
		}
	} elseif ($location !== '') {
		$label = $location;
		if ($province !== '') {
			$label .= ', ' . $province;
		}
	} else {
		$label = $province;
	}
	return esc_html($label);
}
/**
 * Resales Filters – lista predefinida para Área y Location + shortcode [lusso_filters]
 *
 * - Renderiza selects de Área y Location con <optgroup>.
 * - Los value de <option> coinciden con los valores que entiende la API V6.
 * - Los label pueden tener tildes para UI.
 *
 * @package resales-api
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Resales_Filters' ) ) :

final class Resales_Filters {
	/**
	 * Normaliza un string eliminando etiquetas HTML y convirtiendo a minúsculas.
	 * @param string $s
	 * @return string
	 */
	private function norm($s) {
		$s = wp_strip_all_tags($s);
		$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$s = remove_accents($s);
		$s = strtolower($s);
		$s = preg_replace('/\s+/', ' ', $s);
		return trim($s);
	}

	/**
	 * Singleton.
	 *
	 * @var Resales_Filters|null
	 */
	private static $instance = null;

	/**
	 * Mapa de ubicaciones.
	 *
	 * Clave del array = label del Área (lo que ve el usuario).
	 * Cada localidad = ['value' => <para API>, 'label' => <para UI>].
	 *
	 * Basado en SearchLocations (V6):
	 *   Málaga  → Benahavís, Benalmadena, Casares, Estepona, Fuengirola, Málaga, Manilva, Marbella, Mijas, Torremolinos
	 *   Cádiz   → Sotogrande
	 */
	public static $LOCATIONS = [
		'Málaga' => [
			[ 'value' => 'Benahavís',    'label' => 'Benahavís'    ],
			[ 'value' => 'Benalmadena',  'label' => 'Benalmádena'  ],
			[ 'value' => 'Casares',      'label' => 'Casares'      ],
			[ 'value' => 'Estepona',     'label' => 'Estepona'     ],
			[ 'value' => 'Fuengirola',   'label' => 'Fuengirola'   ],
			[ 'value' => 'Málaga',       'label' => 'Málaga'       ],
			[ 'value' => 'Manilva',      'label' => 'Manilva'      ],
			[ 'value' => 'Marbella',     'label' => 'Marbella'     ],
			[ 'value' => 'Mijas',        'label' => 'Mijas'        ],
			[ 'value' => 'Torremolinos', 'label' => 'Torremolinos' ],
		],
		'Cádiz' => [
			[ 'value' => 'Sotogrande',   'label' => 'Sotogrande'   ],
		],
	];

	private function __construct() {}

	/**
	 * @return Resales_Filters
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Devuelve las áreas disponibles (labels).
	 *
	 * @return string[]
	 */
	public function get_areas() {
		return array_keys( self::$LOCATIONS );
	}

	/**
	 * Devuelve localidades (value/label) por área.
	 *
	 * @param string $area_label Label de área.
	 * @return array<int, array{value:string,label:string}>
	 */
	public function get_locations_by_area( $area_label ) {
		return isset( self::$LOCATIONS[ $area_label ] ) ? self::$LOCATIONS[ $area_label ] : [];
	}

	/**
	 * <select> de Área (labels como value).
	 *
	 * @param string $selected Label de área seleccionada.
	 * @param array  $attrs    Atributos extra para el select.
	 * @return string HTML
	 */
	public function render_area_select( $selected = '', $attrs = [] ) {
		// Si llega vía query string, tiene prioridad.
		if ( isset( $_GET['area'] ) && '' === $selected ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = sanitize_text_field( wp_unslash( (string) $_GET['area'] ) ); // phpcs:ignore
		}

		$attrs = wp_parse_args(
			$attrs,
			[
				'id'    => 'resales-area',
				'name'  => 'area',
				'class' => 'resales-area-filter',
			]
		);

		$attr_html = '';
		foreach ( $attrs as $k => $v ) {
			$attr_html .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
		}

		$html  = '<select' . $attr_html . '>';
		$html .= '<option value="">' . esc_html__( 'Area', 'resales-api' ) . '</option>';

		foreach ( $this->get_areas() as $area_label ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $area_label ),
				selected( $selected, $area_label, false ),
				esc_html( $area_label )
			);
		}

		$html .= '</select>';
		return $html;
	}

	/**
	 * <select> de Location con <optgroup> por Área.
	 *
	 * @param string      $selected       Location (value de API) seleccionado.
	 * @param string|null $selected_area  Área (label) para filtrar (opcional).
	 * @param array       $attrs          Atributos extra del select.
	 * @return string HTML
	 */
	public function render_location_select( $selected = '', $selected_area = null, $attrs = [] ) {
	// Permite inyectar set de localidades de la API para marcar estado visual
	$api_locs = isset($attrs['api_locs']) && is_array($attrs['api_locs']) ? array_map([$this, 'norm'], $attrs['api_locs']) : [];
		if ( isset( $_GET['location'] ) && '' === $selected ) {
			$selected = sanitize_text_field( wp_unslash( (string) $_GET['location'] ) );
		}
		if ( isset( $_GET['area'] ) && null === $selected_area ) {
			$selected_area = sanitize_text_field( wp_unslash( (string) $_GET['area'] ) );
		}

		$attrs = wp_parse_args(
			$attrs,
			[
				'id'    => 'resales-location',
				'name'  => 'location',
				'class' => 'resales-location-filter',
			]
		);

		$attr_html = '';
		foreach ( $attrs as $k => $v ) {
			$attr_html .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
		}

		// Mapeo completo de zonas/localidades de Alejandro
		$zonas_map = [
			'CÁDIZ – CAMPO DE GIBRALTAR' => [ 'Los Barrios', 'Algeciras', 'Zahara', 'San Roque', 'La Línea', 'La Alcaidesa', 'San Roque Club', 'Sotogrande', 'Sotogrande Alto', 'Sotogrande Costa', 'Sotogrande Marina', 'Sotogrande Playa', 'Sotogrande Puerto', 'Guadiaro', 'Torreguadiaro', 'Pueblo Nuevo de Guadiaro', 'San Enrique', 'San Martín de Tesorillo', 'San Diego', 'Punta Chullera' ],
			'MANILVA' => [ 'La Duquesa', 'San Luis de Sabinillas', 'Gaucín', 'Algatocin', 'Benadalid', 'Benarrabá', 'Manilva', 'Casares', 'Casares Playa', 'Casares Pueblo', 'Doña Julia', 'Puerto de la Duquesa', 'San Diego', 'Valle Romano' ],
			'ESTEPONA & NEW GOLDEN MILE' => [ 'Estepona', 'Selwo', 'Genalguacil', 'New Golden Mile', 'Benamara', 'El Padrón', 'El Presidente', 'Bel Air', 'Alpandeire', 'Costalita', 'Los Flamingos', 'El Paraiso', 'Benavista', 'Diana Park', 'Atalaya', 'Hacienda del Sol', 'Valle Romano', 'Valle del Sol', 'Guadalmina Alta', 'Guadalmina Baja' ],
			'BENAHAVÍS' => [ 'Benahavís', 'La Heredia', 'El Madroñal', 'La Zagaleta', 'Los Arqueros', 'Los Almendros', 'Monte Halcones', 'La Quinta' ],
			'MARBELLA' => [ 'San Pedro de Alcántara', 'Cortijo Blanco', 'Los Prados', 'Montejaque', 'Benaoján', 'Júzcar', 'Ronda', 'Arriate', 'Estación de Gaucin', 'Cuevas del Becerro', 'La Campana', 'Aloha', 'Las Brisas', 'Nueva Andalucía', 'Puerto Banús', 'The Golden Mile', 'Marbella', 'Sierra Blanca', 'Nagüeles', 'Istán', 'Ojén', 'Monda', 'Guaro', 'Tolox', 'El Burgo', 'Yunquera', 'Alozaina', 'Coín', 'Torre Real', 'Río Real', 'Bahía de Marbella', 'Santa Clara', 'Los Monteros', 'Altos de los Monteros', 'Las Chapas', 'Hacienda Las Chapas', 'El Rosario', 'Costabella', 'La Mairena', 'Reserva de Marbella', 'Elviria', 'Marbesa', 'Carib Playa', 'Artola', 'Cabopino', 'Puerto de Cabopino', 'Marbella Centro & Casco Antiguo', 'Golden Mile / Milla de Oro', 'Nagüeles', 'Sierra Blanca', 'Casablanca', 'Nueva Andalucía', 'Aloha', 'Las Brisas', 'La Campana', 'Puerto Banús', 'San Pedro de Alcántara', 'Cortijo Blanco', 'Linda Vista', 'Guadalmina Baja', 'Guadalmina Alta' ],
			'MIJAS COSTA' => [ 'Calahonda', 'Calahonda (Sitio de Calahonda)', 'Riviera del Sol', 'Miraflores', 'Torrenueva', 'El Chaparral', 'El Faro', 'La Cala de Mijas', 'La Cala Hills', 'La Cala Golf', 'Calanova Golf', 'Calypso', 'Sierrezuela', 'El Coto', 'Campo Mijas', 'Cerros del Águila', 'Las Lagunas', 'Valtocado', 'Mijas Costa', 'Mijas', 'Mijas Golf' ],
			'FUENGIROLA' => [ 'Fuengirola', 'Centro', 'Los Boliches', 'Los Pacos', 'Torreblanca', 'Carvajal' ],
			'BENALMÁDENA' => [ 'Benalmádena', 'Benalmádena Pueblo', 'Arroyo de la Miel', 'Benalmádena Costa', 'Torremuelle', 'Torrequebrada', 'La Capellanía', 'Torremar' ],
			'TORREMOLINOS' => [ 'Torremolinos', 'Centro', 'Bajondillo', 'La Carihuela', 'Montemar', 'Playamar', 'Los Álamos', 'El Pinillo', 'La Colina', 'El Calvario', 'Torremolinos Centro', 'La Leala' ],
			'MÁLAGA CAPITAL (COSTA)' => [ 'Málaga', 'Málaga Centro', 'Málaga Este', 'Limonar', 'Pedrelejo', 'El Palo', 'Cerrado de Calderon', 'La Magaleta', 'Higueron', 'Puerto de la Torre', 'Churriana' ],
			'INTERIOR (MÁLAGA)' => [ 'Campillos', 'Fuente de Piedra', 'Zalea', 'Ardales', 'Casarabonela', 'El Chorro', 'Alora', 'Pizarra', 'Cártama', 'Estacion de Cartama', 'Gibralgalia', 'Casabermeja', 'Mollina', 'Antequera', 'Almogía', 'Valle de Abdalajis', 'Villanueva De La Concepcion', 'La Atalaya', 'Villanueva del Rosario', 'Archidona', 'Villanueva del Trabuco', 'Alameda', 'Cañete la Real', 'Carratraca', 'Cuevas Bajas', 'Villanueva de Algaidas', 'Estación Archidona', 'Cuevas De San Marcos', 'La Parrilla', 'Jubrique', 'Teba', 'Gobantes' ],
		];
		// Siempre renderiza todas las zonas/localidades del mapping estático
		$agrupadas = $zonas_map;

		$html  = '<select' . $attr_html . '>';
		$html .= '<option value="">' . esc_html__( 'Location', 'resales-api' ) . '</option>';

		foreach ( $agrupadas as $zona => $locs ) {
			// Si se selecciona área, filtra por label de zona
			if ($selected_area && $this->norm($selected_area) !== $this->norm($zona)) {
				continue;
			}
			$html .= sprintf( '<optgroup label="%s">', esc_attr( $zona ) );
			foreach ( $locs as $loc ) {
				$value = (string)$loc;
				$value_norm = $this->norm($value);
				$has_api = empty($api_locs) || in_array($value_norm, $api_locs, true) ? '1' : '0';
				$data_attr = $has_api === '0' ? ' data-has-api="0"' : '';
				$html .= sprintf(
					'<option value="%1$s"%2$s%3$s>%4$s</option>',
					esc_attr( $value ),
					selected( $selected, $value, false ),
					$data_attr,
					esc_html( $value )
				);
			}
			$html .= '</optgroup>';
		}
		if ( ! empty( $otras ) ) {
			$html .= sprintf( '<optgroup label="%s">', esc_html__( 'OTRAS', 'resales-api' ) );
			foreach ( $otras as $item ) {
				$html .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $item['value'] ),
					selected( $selected, $item['value'], false ),
					esc_html( $item['label'] )
				);
			}
			$html .= '</optgroup>';
		}

		$html .= '</select>';
		return $html;
	}
}
endif;

/**
 * Shortcode [lusso_filters]
 *
 * IMPORTANTE: los shortcodes deben **devolver** contenido, no imprimirlo,
 * según la Shortcode API de WordPress. :contentReference[oaicite:2]{index=2}
 */
if ( ! class_exists( 'Resales_Filters_Shortcode' ) ) :



final class Resales_Filters_Shortcode {

	public function __construct() {
		add_shortcode( 'lusso_filters', [ $this, 'render_shortcode' ] ); // registra el shortcode
	}

	/**
	 * Callback del shortcode: devuelve el formulario de filtros.
	 *
	 * @param array  $atts
	 * @param string $content
	 * @param string $tag
	 * @return string HTML
	 */
	public function render_shortcode( $atts = [], $content = '', $tag = '' ) {
		// Logging de banderas GET para location
		if (function_exists('resales_safe_log')) {
			resales_safe_log('SC GET', [
				'has_location' => isset($_GET['location']) ? 'yes' : 'no',
				'location_val' => isset($_GET['location']) && $_GET['location'] !== '' ? '***' : 'empty'
			]);
		}
		// Leer location y area desde GET, sanitizar
		$selected_area = isset($_GET['area']) ? sanitize_text_field(wp_unslash((string)$_GET['area'])) : '';
		$selected_location = isset($_GET['location']) ? sanitize_text_field(wp_unslash((string)$_GET['location'])) : '';

		$area_inferred = 'no';
		// Si no hay área pero sí location, inferir área
		if ($selected_area === '' && $selected_location !== '') {
			foreach (Resales_Filters::$LOCATIONS as $area_label => $locs) {
				foreach ($locs as $item) {
					if (isset($item['value']) && $item['value'] === $selected_location) {
						$selected_area = $area_label;
						$area_inferred = 'yes';
						break 2;
					}
				}
			}
		}

		// Log banderas de GET y área inferida
		if (function_exists('resales_safe_log')) {
			resales_safe_log('SHORTCODE ARGS', [
				'location_get' => $selected_location !== '' ? 'yes' : 'no',
				'area_inferred' => ($selected_area !== '' && !isset($_GET['area'])) ? 'yes' : 'no',
			]);
		}
		$filters = Resales_Filters::instance();

				ob_start();
				?>
								<div class="resales-filters-wrapper">
									<form id="lusso-filters" class="resales-filters-form" method="get" action="">
				    <div class="filter-field">
				      <?php
				        // Área
				        echo $filters->render_area_select(
				          $selected_area,
				          [
							  'id'    => 'resales-area',
							  'name'  => '', // Blindaje: no serializar area
							  'data-name' => 'area',
							  'class' => 'resales-area-filter lusso-area-static',
				          ]
				        );
				      ?>
				    </div>
				    <div class="filter-field">
				      <?php
				        // Location (filtrado por área si corresponde)
				        echo $filters->render_location_select(
				          $selected_location,
				          $selected_area,
				          [
				            'id'    => 'resales-location',
				            'name'  => 'location',
				            'class' => 'resales-location-filter lusso-location-static',
				          ]
				        );
				      ?>
				    </div>
				    <div class="filter-field">
				      <select id="resales-type" name="type" class="resales-type-filter lusso-type-static">
				        <option value=""><?php esc_html_e('Type', 'resales-api'); ?></option>
				        <option value="apartment" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'apartment'); ?>><?php esc_html_e('Apartment', 'resales-api'); ?></option>
				        <option value="villa" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'villa'); ?>><?php esc_html_e('Villa', 'resales-api'); ?></option>
				        <option value="townhouse" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'townhouse'); ?>><?php esc_html_e('Townhouse', 'resales-api'); ?></option>
				        <option value="penthouse" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'penthouse'); ?>><?php esc_html_e('Penthouse', 'resales-api'); ?></option>
				        <option value="plot" <?php selected(isset($_GET['type']) ? $_GET['type'] : '', 'plot'); ?>><?php esc_html_e('Plot', 'resales-api'); ?></option>
				      </select>
				    </div>
				    <div class="filter-field">
				      <select id="resales-bedrooms" name="bedrooms" class="resales-bedrooms-filter lusso-bedrooms-static">
				        <option value=""><?php esc_html_e('Bedrooms', 'resales-api'); ?></option>
				        <option value="1" <?php selected(isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '', '1'); ?>>1+</option>
				        <option value="2" <?php selected(isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '', '2'); ?>>2+</option>
				        <option value="3" <?php selected(isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '', '3'); ?>>3+</option>
				        <option value="4" <?php selected(isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '', '4'); ?>>4+</option>
				        <option value="5" <?php selected(isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '', '5'); ?>>5+</option>
				      </select>
				    </div>
										<div class="filter-field lusso-filters__submit">
	<button type="submit" data-role="search" class="button">
				<?php esc_html_e( 'Search', 'resales-api' ); ?>
			</button>
		</div>
				  </form>
				</div>
				<?php
				  // SHORTCODE: devolver, no echo.
				  return ob_get_clean();
				}
}
endif;

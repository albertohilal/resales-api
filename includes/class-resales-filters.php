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
		if ( isset( $_GET['location'] ) && '' === $selected ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = sanitize_text_field( wp_unslash( (string) $_GET['location'] ) ); // phpcs:ignore
		}
		if ( isset( $_GET['area'] ) && null === $selected_area ) { // phpcs:ignore
			$selected_area = sanitize_text_field( wp_unslash( (string) $_GET['area'] ) ); // phpcs:ignore
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

		$html  = '<select' . $attr_html . '>';
		$html .= '<option value="">' . esc_html__( 'Location', 'resales-api' ) . '</option>';

		foreach ( self::$LOCATIONS as $area_label => $locs ) {
			// Si hay área seleccionada, limitar el grupo.
			if ( ! empty( $selected_area ) && $selected_area !== $area_label ) {
				continue;
			}
			$html .= sprintf( '<optgroup label="%s">', esc_attr( $area_label ) );

			foreach ( $locs as $item ) {
				$value = isset( $item['value'] ) ? (string) $item['value'] : '';
				$label = isset( $item['label'] ) ? (string) $item['label'] : $value;

				$html .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $value ),                          // value EXACTO que espera la API
					selected( $selected, $value, false ),
					esc_html( $label )
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
		$filters = Resales_Filters::instance();

		$selected_area = isset( $_GET['area'] )
			? sanitize_text_field( wp_unslash( (string) $_GET['area'] ) )
			: '';

		$selected_location = isset( $_GET['location'] )
			? sanitize_text_field( wp_unslash( (string) $_GET['location'] ) )
			: '';

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
					  <button type="button" data-role="search" class="button">
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

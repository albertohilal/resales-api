<?php
/**
 * Shortcode de prueba para New Developments con filtros dinámicos.
 * Uso: [lusso_properties_test]  (se puede forzar p1/p2 y filtro por atributos si hace falta)
 * Ej.: [lusso_properties_test p1="1035049" p2="xxxxx" filter="65503"]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Resales_Shortcode_NewDevs {

    private $api_base = 'https://webapi-v6.resales-online.com';
    private $opts = [];

    public function __construct() {
        // Carga el option compuesto si existe
        $this->opts = get_option('resales_api_options', []);
        add_action('init', function () {
            add_shortcode('lusso_properties_test', [ $this, 'render_shortcode' ]);
        });
    }

    public function render_shortcode( $atts = [] ) {
        $atts = shortcode_atts([
            // Permito override por shortcode para testear rápido si el option no está llegando
            'p1'     => '',
            'p2'     => '',
            'filter' => '',   // P_Agency_FilterId o P_ApiId
            'lang'   => '',   // 1=EN, 2=ES, etc.
        ], $atts, 'lusso_properties_test');

        // --- Filtros del front (GET) ---
        $area       = isset($_GET['area'])        ? sanitize_text_field($_GET['area'])        : '';
        $location   = isset($_GET['location'])    ? sanitize_text_field($_GET['location'])    : '';
        $types      = isset($_GET['types'])       ? (array) $_GET['types']                    : [];
        $beds       = isset($_GET['beds'])        ? intval($_GET['beds'])                     : 0;
        $price_from = isset($_GET['price_from'])  ? intval($_GET['price_from'])               : 0;
        $price_to   = isset($_GET['price_to'])    ? intval($_GET['price_to'])                 : 0;
        $page       = isset($_GET['page'])        ? max(1, intval($_GET['page']))             : 1;
        $page_size  = 12;

        // --- CREDENCIALES CON FALLBACKS ---
        // 1) atributos del shortcode (si los pasas explícitamente)
        // 2) option compuesto resales_api_options (claves: p1, p2, P_Agency_FilterId, P_ApiId, Lang)
        // 3) opciones sueltas (por si las tuvieras separadas)
        $p1 = trim($atts['p1']);
        $p2 = trim($atts['p2']);

        if ($p1 === '') { $p1 = isset($this->opts['p1']) ? trim($this->opts['p1']) : ''; }
        if ($p2 === '') { $p2 = isset($this->opts['p2']) ? trim($this->opts['p2']) : ''; }

        if ($p1 === '') { $p1 = trim( (string) get_option('resales_api_p1', '') ); }
        if ($p2 === '') { $p2 = trim( (string) get_option('resales_api_p2', '') ); }

        // Idioma
        $lang = $atts['lang'] !== '' ? intval($atts['lang'])
               : ( isset($this->opts['Lang']) ? intval($this->opts['Lang']) : 1 );

        // FilterId (prioridad Agency, luego ApiId, y por último atributo "filter")
        $filter_id = '';
        if (!empty($this->opts['P_Agency_FilterId'])) {
            $filter_id = trim($this->opts['P_Agency_FilterId']);
        } elseif (!empty($this->opts['P_ApiId'])) {
            $filter_id = trim($this->opts['P_ApiId']);
        }
        if ($filter_id === '' && $atts['filter'] !== '') {
            $filter_id = trim($atts['filter']);
        }

        // Si faltan credenciales, avisamos (ya con fallbacks aplicados)
        if ($p1 === '' || $p2 === '') {
            return '<p style="color:#c00">Faltan credenciales de API (p1/p2) en los ajustes del plugin.</p>';
        }
        if ($filter_id === '') {
            return '<p style="color:#c00">Falta configurar el Filtro API (P_Agency_FilterId o P_ApiId).</p>';
        }

        // --- FORMULARIO ---
        ob_start(); ?>
        <form method="GET" class="lusso-form" style="display:grid;gap:.5rem;grid-template-columns:repeat(6,minmax(0,1fr));align-items:end">
            <div>
                <label>Area</label>
                <select name="area">
                    <option value="">Todas</option>
                    <option value="Costa del Sol" <?php selected($area,'Costa del Sol'); ?>>Costa del Sol</option>
                    <option value="Málaga"        <?php selected($area,'Málaga'); ?>>Málaga</option>
                </select>
            </div>
            <div>
                <label>Location</label>
                <select name="location">
                    <option value="">Todas</option>
                    <option value="Marbella" <?php selected($location,'Marbella'); ?>>Marbella</option>
                    <option value="Manilva"  <?php selected($location,'Manilva');  ?>>Manilva</option>
                    <option value="Estepona" <?php selected($location,'Estepona'); ?>>Estepona</option>
                </select>
            </div>
            <div>
                <label>Tipo</label>
                <select name="types[]" multiple size="4">
                    <option value="Apartment"  <?php echo in_array('Apartment',$types)?'selected':''; ?>>Apartment</option>
                    <option value="Villa"      <?php echo in_array('Villa',$types)?'selected':''; ?>>Villa</option>
                    <option value="Townhouse"  <?php echo in_array('Townhouse',$types)?'selected':''; ?>>Townhouse</option>
                    <option value="Penthouse"  <?php echo in_array('Penthouse',$types)?'selected':''; ?>>Penthouse</option>
                </select>
            </div>
            <div>
                <label>Bedrooms</label>
                <select name="beds">
                    <option value="0">Todos</option>
                    <option value="1" <?php selected($beds,1); ?>>1+</option>
                    <option value="2" <?php selected($beds,2); ?>>2+</option>
                    <option value="3" <?php selected($beds,3); ?>>3+</option>
                    <option value="4" <?php selected($beds,4); ?>>4+</option>
                </select>
            </div>
            <div>
                <label>Price From</label>
                <input type="number" name="price_from" value="<?php echo esc_attr($price_from); ?>" min="0" step="1000" />
            </div>
            <div>
                <label>Price To</label>
                <input type="number" name="price_to" value="<?php echo esc_attr($price_to); ?>" min="0" step="1000" />
            </div>
            <div style="grid-column:1/-1">
                <button type="submit">Search</button>
            </div>
        </form>
        <?php

        // --- QUERY a SearchProperties ---
        $args = [
            'p1'          => $p1,
            'p2'          => $p2,
            'Lang'        => $lang,
            'P_Page'      => $page,
            'P_PageSize'  => $page_size,
        ];

        // Usa Agency Filter si está, si no ApiId
        if (!empty($this->opts['P_Agency_FilterId']) || (isset($this->opts['P_Agency_FilterId']) && $this->opts['P_Agency_FilterId']==='0')) {
            $args['P_Agency_FilterId'] = $filter_id;
        } else {
            $args['P_ApiId'] = $filter_id;
        }

        // Solo añadir si el usuario elige algo (para no restringir tipos por defecto)
        if ($area !== '')         { $args['P_Area']           = $area; }
        if ($location !== '')     { $args['P_Location']       = $location; }
        if (!empty($types))       { $args['P_PropertyTypes']  = implode(',', array_map('sanitize_text_field', $types)); }
        if ($beds > 0)            { $args['P_Beds']           = $beds; }
        if ($price_from > 0)      { $args['P_PriceFrom']      = $price_from; }
        if ($price_to > 0)        { $args['P_PriceTo']        = $price_to; }

        $url = add_query_arg( $args, $this->api_base . '/SearchProperties' );
        $res = wp_remote_get( $url, [ 'timeout' => 20 ] );

        if ( is_wp_error( $res ) ) {
            echo '<p style="color:#c00">Error conectando a WebAPI.</p>';
            return ob_get_clean();
        }

        $code = wp_remote_retrieve_response_code( $res );
        $body = wp_remote_retrieve_body( $res );
        $data = json_decode( $body, true );

        if ( $code !== 200 || ! is_array( $data ) ) {
            echo '<p style="color:#c00">Respuesta inválida de WebAPI.</p>';
            return ob_get_clean();
        }

        echo '<div class="lusso-grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-top:1rem;">';
        if ( ! empty( $data['Results'] ) && is_array($data['Results']) ) {
            foreach ( $data['Results'] as $item ) {
                $title   = esc_html( $item['Title']     ?? ( $item['Ref'] ?? 'Property' ) );
                $price   = esc_html( $item['Price']     ?? '' );
                $bedsOut = esc_html( $item['Bedrooms']  ?? '' );
                $locOut  = esc_html( $item['Location']  ?? '' );
                $img     = $item['Images'][0]['Url']    ?? '';

                echo '<article style="border:1px solid #eee;padding:.75rem;border-radius:.5rem">';
                if ($img) echo '<img src="'.esc_url($img).'" alt="" style="width:100%;height:180px;object-fit:cover;border-radius:.35rem" />';
                echo '<h4 style="margin:.5rem 0 0">'.$title.'</h4>';
                echo '<div>'.$locOut.'</div>';
                echo '<strong>'.$price.'</strong>';
                echo '<div style="opacity:.7">Beds: '.$bedsOut.'</div>';
                echo '</article>';
            }
        } else {
            echo '<p>Sin resultados.</p>';
        }
        echo '</div>';

        return ob_get_clean();
    }
}

new Resales_Shortcode_NewDevs();

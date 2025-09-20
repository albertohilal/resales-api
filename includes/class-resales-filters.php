<?php
if (!defined('ABSPATH')) exit;

class Resales_Filters_Shortcode {
    public function __construct() {
        add_shortcode('lusso_filters', [$this, 'render']);
    }

    public function render($atts = []) {
        // Lee valores actuales de la URL para mantener selección al recargar
        $area       = isset($_GET['area'])        ? sanitize_text_field($_GET['area']) : '';
        $location   = isset($_GET['location'])    ? sanitize_text_field($_GET['location']) : '';
        $beds       = isset($_GET['beds'])        ? intval($_GET['beds']) : 0;
        $price_from = isset($_GET['price_from'])  ? intval($_GET['price_from']) : 0;
        $price_to   = isset($_GET['price_to'])    ? intval($_GET['price_to']) : 0;
        $types      = isset($_GET['types'])       ? (array) $_GET['types'] : [];

        // Acción: enviamos a la misma URL con método GET
        $action = esc_url( remove_query_arg( ['paged'] ) ); // evita paginación estancada

        ob_start(); ?>
        <form action="<?php echo $action; ?>" method="get" class="lusso-filters" autocomplete="off">
            <div class="lusso-filters__row">
                <div class="lusso-filters__col">
                    <label>Area</label>
                    <select name="area">
                        <option value="">Todas</option>
                        <!-- Puedes rellenar dinámicamente con tus áreas -->
                        <option value="Costa del Sol" <?php selected($area, 'Costa del Sol'); ?>>Costa del Sol</option>
                        <option value="Málaga" <?php selected($area, 'Málaga'); ?>>Málaga</option>
                    </select>
                </div>

                <div class="lusso-filters__col">
                    <label>Location</label>
                    <select name="location">
                        <option value="">Todas</option>
                        <!-- Rellena dinámicamente si quieres -->
                        <option value="Manilva" <?php selected($location, 'Manilva'); ?>>Manilva</option>
                        <option value="Estepona" <?php selected($location, 'Estepona'); ?>>Estepona</option>
                    </select>
                </div>

                <div class="lusso-filters__col">
                    <label>Tipo</label>
                    <select name="types[]" multiple size="4">
                        <option value="Apartment"  <?php echo in_array('Apartment',  $types, true) ? 'selected' : ''; ?>>Apartment</option>
                        <option value="Villa"      <?php echo in_array('Villa',      $types, true) ? 'selected' : ''; ?>>Villa</option>
                        <option value="Townhouse" <?php echo in_array('Townhouse', $types, true) ? 'selected' : ''; ?>>Townhouse</option>
                        <option value="Penthouse" <?php echo in_array('Penthouse', $types, true) ? 'selected' : ''; ?>>Penthouse</option>
                    </select>
                </div>

                <div class="lusso-filters__col">
                    <label>Bedrooms</label>
                    <select name="beds">
                        <option value="0" <?php selected($beds, 0); ?>>Todos</option>
                        <option value="1" <?php selected($beds, 1); ?>>1+</option>
                        <option value="2" <?php selected($beds, 2); ?>>2+</option>
                        <option value="3" <?php selected($beds, 3); ?>>3+</option>
                        <option value="4" <?php selected($beds, 4); ?>>4+</option>
                    </select>
                </div>

                <div class="lusso-filters__col">
                    <label>Price From</label>
                    <input type="number" name="price_from" min="0" step="1000" value="<?php echo esc_attr($price_from); ?>" />
                </div>

                <div class="lusso-filters__col">
                    <label>Price To</label>
                    <input type="number" name="price_to" min="0" step="1000" value="<?php echo esc_attr($price_to); ?>" />
                </div>
            </div>

            <!-- Señal para tu shortcode de tarjetas (opcional):
                 Puedes usarla si quieres distinguir búsquedas de primera carga: -->
            <input type="hidden" name="do" value="search" />

            <button type="submit" class="lusso-filters__submit">Search</button>
        </form>
        <?php
        return ob_get_clean();
    }
}

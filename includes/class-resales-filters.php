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
        <form action="<?php echo $action; ?>" method="get" class="lusso-filters-form" autocomplete="off">
            <div class="lusso-filters__row">
                <div class="filter-field">
                    <label for="lusso-filter-area">Area
                        <select id="lusso-filter-area" name="area" class="filter-area">
                            <option value="">Todas</option>
                            <option value="Costa del Sol" <?php selected($area, 'Costa del Sol'); ?>>Costa del Sol</option>
                            <option value="Málaga" <?php selected($area, 'Málaga'); ?>>Málaga</option>
                        </select>
                    </label>
                </div>
                <div class="filter-field">
                    <label for="lusso-filter-location">Location
                        <select id="lusso-filter-location" name="location" class="filter-location">
                            <option value="">Todas</option>
                            <option value="Manilva" <?php selected($location, 'Manilva'); ?>>Manilva</option>
                            <option value="Estepona" <?php selected($location, 'Estepona'); ?>>Estepona</option>
                        </select>
                    </label>
                </div>
                <div class="filter-field">
                    <label for="lusso-filter-types">Tipo
                        <select id="lusso-filter-types" name="types[]" multiple size="4" class="filter-types">
                            <option value="Apartment"  <?php echo in_array('Apartment',  $types, true) ? 'selected' : ''; ?>>Apartment</option>
                            <option value="Villa"      <?php echo in_array('Villa',      $types, true) ? 'selected' : ''; ?>>Villa</option>
                            <option value="Townhouse" <?php echo in_array('Townhouse', $types, true) ? 'selected' : ''; ?>>Townhouse</option>
                            <option value="Penthouse" <?php echo in_array('Penthouse', $types, true) ? 'selected' : ''; ?>>Penthouse</option>
                        </select>
                    </label>
                </div>
                <div class="filter-field">
                    <label for="lusso-filter-beds">Bedrooms
                        <select id="lusso-filter-beds" name="beds" class="filter-beds">
                            <option value="0" <?php selected($beds, 0); ?>>Todos</option>
                            <option value="1" <?php selected($beds, 1); ?>>1+</option>
                            <option value="2" <?php selected($beds, 2); ?>>2+</option>
                            <option value="3" <?php selected($beds, 3); ?>>3+</option>
                            <option value="4" <?php selected($beds, 4); ?>>4+</option>
                        </select>
                    </label>
                </div>
                <div class="filter-field">
                    <label for="lusso-filter-price-from">Price From
                        <input id="lusso-filter-price-from" type="number" name="price_from" min="0" step="1000" value="<?php echo esc_attr($price_from); ?>" class="filter-price-from" />
                    </label>
                </div>
                <div class="filter-field">
                    <label for="lusso-filter-price-to">Price To
                        <input id="lusso-filter-price-to" type="number" name="price_to" min="0" step="1000" value="<?php echo esc_attr($price_to); ?>" class="filter-price-to" />
                    </label>
                </div>
            </div>
            <input type="hidden" name="do" value="search" />
            <button type="submit" class="lusso-filters__submit">Search</button>
        </form>
        <?php
        return ob_get_clean();
    }
}

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
        <form action="<?php echo $action; ?>" method="get" class="lusso-filters lusso-filters--single-row" autocomplete="off">
            <div class="lusso-filters__row lusso-filters__row--single">
                <div class="filter-group">
                    <span class="lusso-filter-tag"><?php echo esc_html__('New Development', 'lusso-resales'); ?></span>
                    <input type="hidden" name="p_new_devs" value="only" />
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-area" name="area" class="filter-area" style="min-width:150px;">
                        <option value="">Area</option>
                        <option value="Costa del Sol" <?php selected($area, 'Costa del Sol'); ?>>Costa del Sol</option>
                        <option value="Málaga" <?php selected($area, 'Málaga'); ?>>Málaga</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-location" name="location" class="filter-location" style="min-width:150px;">
                        <option value="">Location</option>
                        <option value="Manilva" <?php selected($location, 'Manilva'); ?>>Manilva</option>
                        <option value="Estepona" <?php selected($location, 'Estepona'); ?>>Estepona</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-types" name="types[]" class="filter-types" style="min-width:150px;">
                        <option value="">All types</option>
                        <option value="Apartments" <?php echo in_array('Apartments', $types, true) ? 'selected' : ''; ?>>Apartments</option>
                        <option value="Penthouses" <?php echo in_array('Penthouses', $types, true) ? 'selected' : ''; ?>>Penthouses</option>
                        <option value="Villas" <?php echo in_array('Villas', $types, true) ? 'selected' : ''; ?>>Villas</option>
                        <option value="Town Houses" <?php echo in_array('Town Houses', $types, true) ? 'selected' : ''; ?>>Town Houses</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-beds" name="beds" class="filter-beds" style="min-width:120px;">
                        <option value="0" <?php selected($beds, 0); ?>>Bedrooms</option>
                        <option value="1" <?php selected($beds, 1); ?>>1+</option>
                        <option value="2" <?php selected($beds, 2); ?>>2+</option>
                        <option value="3" <?php selected($beds, 3); ?>>3+</option>
                        <option value="4" <?php selected($beds, 4); ?>>4+</option>
                    </select>
                </div>
                
                <div class="filter-group filter-group--submit" style="min-width:150px;">
                    <button type="submit" class="lusso-filters__submit" style="width:100%;height:38px;min-width:150px;">Search</button>
                </div>
            </div>
            <input type="hidden" name="do" value="search" />
        </form>
        <style>
        :root {
            --lusso-gold-dark: #B8860B;
            --lusso-gray-light: #EAEAEA;
            --lusso-white: #FFF;
            --lusso-black: #0D0D0D;
        }
        .lusso-filters--single-row .lusso-filters__row--single {
            display: flex;
            gap: 12px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .lusso-filters__label {
            background: var(--lusso-white);
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 400;
            padding: 8px 12px;
            cursor: default;
            color: #0D0D0D;
            box-shadow: none;
            transition: none;
            min-width: 150px;
            text-align: left;
            display: block;
        }
        .lusso-filters__label:hover,
        .lusso-filters__label:focus {
            background: var(--lusso-white);
            color: var(--lusso-gold-dark);
            border: 2px solid var(--lusso-gray-light);
            box-shadow: none;
        }
        .lusso-filters__submit {
            width: 100%;
            min-width: 120px;
            height: 38px;
            font-size: 1rem;
        }
        .filter-group select, .filter-group input[type="number"] {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            font-size: 1rem;
            background: #fff;
        }
        </style>
        <style>
        .lusso-filters .lusso-filter-tag {
            display: inline-block;
            padding: .5rem .75rem;
            border-radius: 0px;
            line-height: 1;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid var(--color-gray-alt, #dcdcdc);
            background: #fff;
            height: 38px;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

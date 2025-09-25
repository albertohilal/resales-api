
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
    $filters_v6_enabled = get_option('resales_filters_v6_enabled');

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
                        <?php if (empty($filters_v6_enabled)) : ?>
                            <option value="Costa del Sol" <?php selected($area, 'Costa del Sol'); ?>>Costa del Sol</option>
                            <option value="Málaga" <?php selected($area, 'Málaga'); ?>>Málaga</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-location" name="location" class="filter-location" style="min-width:150px;">
                        <option value="">Location</option>
                        <?php if (empty($filters_v6_enabled)) : ?>
                            <option value="Manilva" <?php selected($location, 'Manilva'); ?>>Manilva</option>
                            <option value="Estepona" <?php selected($location, 'Estepona'); ?>>Estepona</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-types" name="types[]" class="filter-types" style="min-width:150px;">
                        <option value="">All types</option>
                        <?php if (empty($filters_v6_enabled)) : ?>
                            <option value="Apartments" <?php echo in_array('Apartments', $types, true) ? 'selected' : ''; ?>>Apartments</option>
                            <option value="Penthouses" <?php echo in_array('Penthouses', $types, true) ? 'selected' : ''; ?>>Penthouses</option>
                            <option value="Villas" <?php echo in_array('Villas', $types, true) ? 'selected' : ''; ?>>Villas</option>
                            <option value="Town Houses" <?php echo in_array('Town Houses', $types, true) ? 'selected' : ''; ?>>Town Houses</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="lusso-filter-bedrooms" name="beds" class="filter-beds" style="min-width:120px;">
                        <option value="0" <?php selected($beds, 0); ?>>Bedrooms</option>
                        <?php if (empty($filters_v6_enabled)) : ?>
                            <option value="1" <?php selected($beds, 1); ?>>1+</option>
                            <option value="2" <?php selected($beds, 2); ?>>2+</option>
                            <option value="3" <?php selected($beds, 3); ?>>3+</option>
                            <option value="4" <?php selected($beds, 4); ?>>4+</option>
                        <?php endif; ?>
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

add_action('admin_init', function() {
    register_setting('general', 'filters_v6_enabled', [
        'type' => 'boolean',
        'sanitize_callback' => function($value) {
            return $value === '1' ? true : false;
        },
        'default' => false
    ]);
    add_settings_field(
        'filters_v6_enabled',
        'Filtros V6 (API) habilitados',
        function() {
            $value = get_option('filters_v6_enabled', false);
            echo '<input type="checkbox" name="filters_v6_enabled" value="1" ' . checked($value, true, false) . '> Activar filtros V6 desde API';
        },
        'general',
        'default'
    );
});

/**
 * Lusso Resales Filters - Provider V6 (Etapa 1)
 * - Whitelist de Áreas con orden fijo y normalización robusta
 * - Lectura segura de credenciales desde .env o get_option
 * - Providers con caché (transients, TTL 12h)
 * - No expone .env al frontend
 * - Feature flag: filters_v6_enabled
 */

if (!defined('LUSSO_AREA_WHITELIST')) {
    define('LUSSO_AREA_WHITELIST', json_encode([
        'Benahavís','Benalmádena','Casares','Estepona','Fuengirola',
        'Manilva','Marbella','Mijas','Torremolinos','Malaga','Sotogrande'
    ]));
}

class Lusso_Resales_Filters_V6 {
    /**
     * Loader de credenciales: primero get_option, luego .env
     * @return array ['p1'=>..., 'p2'=>..., 'P_ApiId'=>...]
     */
    private function get_api_auth() {
        $p1 = get_option('API_P1');
        $p2 = get_option('API_P2');
        $api_id = get_option('API_FILTER_ID');
        if ($p1 && $p2 && $api_id) {
            return ['p1'=>$p1, 'p2'=>$p2, 'P_ApiId'=>$api_id];
        }
        // Si no hay en options, intenta cargar .env
        $env_path = defined('RESALES_API_PLUGIN_DIR') ? RESALES_API_PLUGIN_DIR.'/.env' : dirname(__DIR__,2).'/.env';
        $env = [];
        if (file_exists($env_path)) {
            // Si existe Dotenv, úsalo
            if (class_exists('Dotenv\\Dotenv')) {
                $dotenv = Dotenv\Dotenv::createImmutable(dirname($env_path));
                $dotenv->load();
                $env['API_P1'] = $_ENV['API_P1'] ?? '';
                $env['API_P2'] = $_ENV['API_P2'] ?? '';
                $env['API_FILTER_ID'] = $_ENV['API_FILTER_ID'] ?? '';
            } else {
                // Parser simple
                foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $m)) {
                        $env[$m[1]] = trim($m[2]);
                    }
                }
            }
        }
        return [
            'p1' => $env['API_P1'] ?? '',
            'p2' => $env['API_P2'] ?? '',
            'P_ApiId' => $env['API_FILTER_ID'] ?? ''
        ];
    }

    /**
     * Normaliza nombres para matching robusto (lowercase, sin tildes, sin espacios extra)
     */
    private function normalize_slug($name) {
        // ...existing code...
    }

    private function get_api_auth() {
        // ...existing code...
    }

    // Helpers de caché, si existen
    // ...existing code...
}

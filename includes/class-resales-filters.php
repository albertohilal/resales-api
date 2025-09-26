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
                        <?php foreach ($this->lusso_static_locations() as $loc): ?>
                            <option value="<?php echo esc_attr($loc); ?>" <?php selected($location, $loc); ?>><?php echo esc_html($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
    /**
     * Devuelve la lista fija de localidades para el filtro Location
     * @return array
     */
    private function lusso_static_locations() {
        return [
            'Benahavís','Benalmádena','Casares','Estepona','Fuengirola',
            'Manilva','Marbella','Mijas','Torremolinos','Málaga','Sotogrande'
        ];
    }
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
        $name = strtolower(trim($name));
        $map = [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n'
        ];
        $name = strtr($name, $map);
        $name = preg_replace('/[^a-z0-9 ]/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    /**
     * Provider: Áreas y Locations desde API V6, filtradas y ordenadas por whitelist
     * @param int $lang
     * @param int $sort
     * @return array ['areas'=>[area=>[locations...],...]]
     */
    public function get_locations($lang=1, $sort=1) {
        if (!get_option('filters_v6_enabled')) return ['areas'=>[]];
        $auth = $this->get_api_auth();
        $transient_key = 'lusso_v6_locations_' . intval($lang) . '_' . $auth['P_ApiId'];
        $snapshot = get_transient($transient_key);
        if ($snapshot !== false) return $snapshot;
        // Llamada API V6
        $params = array_merge($auth, [
            'P_Lang' => $lang,
            'P_SortType' => $sort,
            'P_All' => 1
        ]);
        $url = 'https://webapi.resales-online.com/V6/SearchLocations?' . http_build_query($params);
        $response = wp_remote_get($url, ['timeout'=>20, 'sslverify'=>true]);
        $data = is_array($response) && isset($response['body']) ? json_decode($response['body'], true) : null;
        if (!is_array($data) || empty($data['data'])) {
            resales_log('WARN', '[Lusso Filters] API V6 SearchLocations falló, usando snapshot/transient');
            return $snapshot ?: ['areas'=>[]];
        }
        // Filtrar y reordenar por whitelist
        $areas_raw = [];
        foreach ($data['data'] as $item) {
            $area = $item['Area'] ?? '';
            $location = $item['Location'] ?? '';
            $parent_id = $item['ParentAreaId'] ?? null;
            $id = isset($item['LocationId']) ? (int)$item['LocationId'] : null;
            if ($area && $location) {
                $areas_raw[$area][] = [
                    'id' => $id,
                    'name' => $location,
                    'parent_area_id' => $parent_id
                ];
            }
        }
        $whitelist = json_decode(LUSSO_AREA_WHITELIST, true);
        $areas_final = [];
        foreach ($whitelist as $area_name) {
            $norm = $this->normalize_slug($area_name);
            foreach ($areas_raw as $api_area => $locations) {
                if ($this->normalize_slug($api_area) === $norm) {
                    $areas_final[$area_name] = $locations;
                    break;
                }
            }
        }
        $result = ['areas'=>$areas_final];
        set_transient($transient_key, $result, 12 * HOUR_IN_SECONDS);
        return $result;
    }

    /**
     * Provider: Tipos de propiedad desde API V6, cacheado 12h
     * @param int $lang
     * @return array [['id'=>'Type-SubType','label'=>'Type / SubType'], ...]
     */
    public function get_property_types($lang=1) {
        if (!get_option('filters_v6_enabled')) return [];
        $auth = $this->get_api_auth();
        $transient_key = 'lusso_v6_types_' . intval($lang) . '_' . $auth['P_ApiId'];
        $snapshot = get_transient($transient_key);
        if ($snapshot !== false) return $snapshot;
        $params = array_merge($auth, ['P_Lang'=>$lang]);
        $url = 'https://webapi.resales-online.com/V6/SearchPropertyTypes?' . http_build_query($params);
        $response = wp_remote_get($url, ['timeout'=>20, 'sslverify'=>true]);
        $data = is_array($response) && isset($response['body']) ? json_decode($response['body'], true) : null;
        if (!is_array($data) || empty($data['data'])) {
            resales_log('WARN', '[Lusso Filters] API V6 SearchPropertyTypes falló, usando snapshot/transient');
            return $snapshot ?: [];
        }
        $types = [];
        foreach ($data['data'] as $item) {
            $id = $item['TypeId'] ?? '';
            $sub = $item['SubTypeId'] ?? '';
            $label = $item['Type'] ?? '';
            $sublabel = $item['SubType'] ?? '';
            $types[] = [
                'id' => $id . ($sub ? '-' . $sub : ''),
                'label' => $label . ($sublabel ? ' / ' . $sublabel : '')
            ];
        }
        set_transient($transient_key, $types, 12 * HOUR_IN_SECONDS);
        return $types;
    }

    /**
     * Provider: Opciones de dormitorios (estático)
     * @return array
     */
    public function get_bedrooms_options() {
        return [1,2,3,4,'5+'];
    }
}

/**
 * Registrar endpoints REST de solo lectura para filtros V6
 * Solo si filters_v6_enabled está ON
 */
add_action('rest_api_init', function() {
    if (!get_option('filters_v6_enabled')) return;
    $provider = new Lusso_Resales_Filters_V6();

    /**
     * GET /filters/locations
     * @param WP_REST_Request $request
     * @return array ['areas'=>[...]] o ['areas'=>[area=>[]]]
     * Query param opcional: area=<string>
     * Si no existe el área, responde areas:{"<Area>":[]} (no error 4xx)
     * Respeta whitelist y orden.
     */
    register_rest_route('resales/v1', '/filters/locations', [
        'methods' => 'GET',
        'callback' => function($request) use ($provider) {
            $area = $request->get_param('area');
            $lang = (int)$request->get_param('lang') ?: 1;
            $data = $provider->get_locations($lang, 1);
            if ($area) {
                $norm = $provider->normalize_slug($area);
                foreach ($data['areas'] as $key => $locations) {
                    if ($provider->normalize_slug($key) === $norm) {
                        return ['areas' => [$key => $locations]];
                    }
                }
                // Área no encontrada: responde con array vacío
                return ['areas' => [$area => []]];
            }
            return $data;
        },
        'permission_callback' => '__return_true',
    ]);

    /**
     * GET /filters/types
     * @return array [['id'=>...,'label'=>...],...]
     * Devuelve tipos de propiedad desde caché/API
     */
    register_rest_route('resales/v1', '/filters/types', [
        'methods' => 'GET',
        'callback' => function($request) use ($provider) {
            $lang = (int)$request->get_param('lang') ?: 1;
            return $provider->get_property_types($lang);
        },
        'permission_callback' => '__return_true',
    ]);

    /**
     * GET /filters/bedrooms
     * @return array [1,2,3,4,'5+']
     * Devuelve opciones estáticas
     */
    register_rest_route('resales/v1', '/filters/bedrooms', [
        'methods' => 'GET',
        'callback' => function() use ($provider) {
            return $provider->get_bedrooms_options();
        },
        'permission_callback' => '__return_true',
    ]);
});

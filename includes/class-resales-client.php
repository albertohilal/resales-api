<?php
if (!defined('ABSPATH')) exit;

class Resales_Client {
    private static $instance = null;
    private $base = 'https://webapi.resales-online.com/V6';

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /** GET genérico a WebAPI V6 */
    public function request($function, array $params = []) {
        $s = Resales_Settings::instance();
        if (isset($params['P_Agency_FilterId'])) {
            unset($params['P_Agency_FilterId']);
            resales_log('WARN', 'P_Agency_FilterId ignorado; usamos P_ApiId');
        }
        $defaults = [
            'p1'       => $s->get_p1(),
            'p2'       => $s->get_p2(),
            'P_ApiId'  => $s->get_api_id(),
            'P_Lang'   => $s->get_lang(),
            // 'p_sandbox' => true, // habilitar si hace falta
        ];
        $query = array_filter($defaults + $params, static function($v){ return $v !== null && $v !== ''; });
        $url   = trailingslashit($this->base) . $function . '?' . http_build_query($query);

        $resp = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($resp)) {
            resales_log('ERROR', 'HTTP error', ['function'=>$function,'error'=>$resp->get_error_message()]);
            return null;
        }
        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            resales_log('ERROR', 'HTTP status != 200', ['function'=>$function,'status'=>$code]);
            return null;
        }
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);
        if ($json === null) {
            resales_log('ERROR', 'JSON inválido', ['function'=>$function, 'body_sample'=>substr($body,0,200)]);
        }
        return $json;
    }

    /** Cachea SearchLocations 6h */
    public function get_locations($lang = null, $force_refresh = false) {
        $s = Resales_Settings::instance();
        $lang = $lang ?: $s->get_lang();
        $key  = "resales_v6_locations_{$lang}";
        if (!$force_refresh) {
            $cached = get_transient($key);
            if ($cached !== false) return $cached;
        }
        $data = $this->request('SearchLocations', ['P_Lang' => $lang, 'P_All' => 'TRUE']);
        $out = [];
        if (is_array($data) && !empty($data['LocationList'])) {
            foreach ($data['LocationList'] as $row) {
                // Normalizar; claves pueden variar según salida
                $label = $row['Location'] ?? ($row['Name'] ?? '');
                $value = $row['Location'] ?? '';
                $area  = $row['Area'] ?? ($row['Province'] ?? '');
                if ($value) $out[] = ['value'=>$value, 'label'=>$label, 'area'=>$area];
            }
        } else {
            resales_log('WARN', 'SearchLocations vacío o inválido', $data);
        }
        set_transient($key, $out, 6 * HOUR_IN_SECONDS);
        return $out;
    }

    /** Cachea SearchPropertyTypes 6h */
    public function get_property_types($lang = null, $force_refresh = false) {
        $s = Resales_Settings::instance();
        $lang = $lang ?: $s->get_lang();
        $key  = "resales_v6_types_{$lang}";
        if (!$force_refresh) {
            $cached = get_transient($key);
            if ($cached !== false) return $cached;
        }
        $data = $this->request('SearchPropertyTypes', ['P_Lang' => $lang]);
        $out = [];
        if (is_array($data) && !empty($data['PropertyTypes'])) {
            foreach ($data['PropertyTypes'] as $row) {
                $value = $row['OptionValue'] ?? '';
                $label = $row['OptionName'] ?? '';
                if ($value) $out[] = ['value'=>$value, 'label'=>$label];
            }
        } else {
            resales_log('WARN', 'SearchPropertyTypes vacío o inválido', $data);
        }
        set_transient($key, $out, 6 * HOUR_IN_SECONDS);
        return $out;
    }
}

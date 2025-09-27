<?php
if (!defined('ABSPATH')) exit;

class Resales_Client {
    private static $instance = null;
    private $base = 'https://webapi.resales-online.com/V6';

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Busca propiedades usando V6, con lógica de filtro, localización y caché.
     * @param array $input Normalized payload from REST endpoint
     * @return array
     */
    public function search_properties_v6($input) {
        // 1. Leer FilterId del panel (API Filter) desde config/env
        $filterId = getenv('P_ApiId') ?: getenv('P_Agency_FilterId');
            if (!$filterId) {
                // fallback: opción WP
                $filterId = get_option('lusso_api_filter_id') ?: get_option('lusso_agency_filter_id');
            }
            if (!$filterId) return ['success'=>false, 'error'=>'No FilterId'];

            // 2. Localización
            $p_location = null;
            if (!empty($input['subarea'])) {
                $p_location = $input['subarea'];
            } elseif (!empty($input['location'])) {
                $p_location = $input['location'];
            } elseif (!empty($input['province'])) {
                if (function_exists('lusso_filters_get_config')) {
                    $cfg = lusso_filters_get_config();
                    $prov = $input['province'];
                    $locs = isset($cfg['locationsByProvince'][$prov]) ? $cfg['locationsByProvince'][$prov] : [];
                    $p_location = implode(',', $locs);
                }
            }

            // 3. new_devs_mode
            $p_new_devs = null;
            if (!empty($input['new_devs_mode'])) {
                $mode = $input['new_devs_mode'];
                if (in_array($mode, ['only','include','exclude'])) $p_new_devs = $mode;
            }

            // 4. Otros parámetros
            $payload = [
                'P_ApiId' => $filterId,
            ];
            if ($p_location) $payload['P_Location'] = $p_location;
            if ($p_new_devs) $payload['p_new_devs'] = $p_new_devs;
            if (!empty($input['property_types'])) $payload['P_PropertyTypes'] = $input['property_types'];
            if (!empty($input['beds'])) $payload['P_Beds'] = (int)$input['beds'];
            if (!empty($input['baths'])) $payload['P_Baths'] = (int)$input['baths'];
            if (!empty($input['price_min'])) $payload['P_Min'] = (float)$input['price_min'];
            if (!empty($input['price_max'])) $payload['P_Max'] = (float)$input['price_max'];
            if (!empty($input['sort'])) $payload['P_SortType'] = $input['sort'];
            if (!empty($input['page'])) $payload['p_PageNo'] = (int)$input['page'];
            $payload['p_PageSize'] = 20;

            // 5. NUNCA enviar Area ni P_All=TRUE
            unset($payload['Area'], $payload['P_All']);

            // 6. Cache: hash del JSON normalizado + FilterId + page
            $cache_key = 'v6_' . md5(json_encode($payload) . $filterId . ($payload['p_PageNo'] ?? 1));
            $cache = get_transient($cache_key);
            if ($cache) {
                error_log('[V6] Cache hit: ' . $cache_key);
                return $cache;
            }

            // 7. Mostrar en log la URL/params (sin secretos)
            error_log('[V6] SearchProperties payload: ' . json_encode($payload));

            // 8. Llamada real a V6
            $result = $this->request('SearchProperties', $payload);

        // 9. Guardar en caché (TTL 5 min)
        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }
}

<?php
// Helper para logging seguro de parámetros sensibles
if (!function_exists('resales_safe_log')) {
    /**
     * Log seguro para credenciales y parámetros sensibles.
     * @param string $msg
     * @param array $context
     */
    function resales_safe_log($msg, $context = []) {
        $sensitive = ['P1', 'P2', 'Filter', 'FilterId', 'ApiId', 'ApiKey', 'Password', 'Key'];
        $safe = [];
        foreach ($context as $k => $v) {
            if (in_array($k, $sensitive, true)) {
                $safe[$k] = '***REDACTED***';
            } else {
                $safe[$k] = $v;
            }
        }
        error_log('[resales-api][SAFELOG] ' . $msg . ' | ' . json_encode($safe, JSON_UNESCAPED_UNICODE));
    }
}
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
    public function search_properties_v6($params) {
        // --- LOG seguro del payload antes de la petición HTTP ---
        resales_safe_log('REQ PAYLOAD', [
            'P_Location_set' => isset($query['P_Location']) ? 'yes' : 'no',
            'payload_keys'   => array_keys($query),
        ]);
        // 1) Fallback constante para API Filter si no hay opción
        if (!defined('RESALES_API_DEFAULT_FILTER_ID')) {
            define('RESALES_API_DEFAULT_FILTER_ID', 65503); // TODO: reemplazar por el FilterId real
        }
    /*
     * ==== Resales-Online Web API V6 — SearchProperties: Parámetros y ejemplos oficiales ====
     *
     * Common parameters:
     *   p1                    (required) — identificador del agente  
     *   p2                    (required) — API key del agente  
     *   p_sandbox             (optional) — si TRUE devuelve sección “transaction” con diagnóstico  
     *   p_output              (optional) — formato de salida (JSON / XML)  
     *   P_ApiId               (required if P_Agency_FilterId missing) — filtro del panel  
     *   P_Agency_FilterId     (required if P_ApiId missing) — filtro del panel  
     *
     * Filtros específicos de búsqueda:
     *   p_new_devs            include | exclude | only — filtrar nuevos desarrollos  
     *   P_Location            nombre o lista CSV de ubicaciones  
     *   P_PropertyTypes       IDs de tipo de propiedad (CSV)  
     *   P_Beds                número exacto o con sufijo “x” para “al menos”  
     *   P_Baths               número exacto o “x”  
     *   P_MustHaveFeatures    IDs de características obligatorias  
     *   P_MinPrice / P_MaxPrice — rangos de precio  
     *   P_Dimension           tamaño / metros cuadrados  
     *   P_RTA                 número de licencia de alquiler (nuevo parámetro agregado en V6)  
     *   P_QueryId             token de consulta para paginación  
     *   P_PageNo              número de página  
     *   P_PageSize            cantidad por página  
     *   P_RemoveLocation      lista de ubicaciones a excluir si P_Location vacío  
     *   P_SortType            orden de resultados (precio ascendente, descendente, fecha, etc.)  
     *
     * Ejemplos:
     *   /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY&p_sandbox=true
     *   /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY&P_Location=Benalmadena
     *   /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY&P_Location=Benalmadena&P_PropertyTypes=2-1,3-1
     *   /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY&P_Location=Benalmadena&P_Beds=3x&p_new_devs=only
     *   /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY&P_Beds=2&P_PageSize=5
     *
     * Nota: la respuesta siempre está limitada por las condiciones del filtro del panel usado (P_ApiId o P_Agency_FilterId).
     * Para más referencia completa, ver la documentación oficial en el repositorio de Resales-Online Web API V6.  
     */
    /*
     * Resales-Online Web API V6 — SearchProperties parameters:
     *  Required: p1 (agent id), p2 (api key)
     *  Either P_ApiId or P_Agency_FilterId (obligatorio uno)
     *  Optional:
     *    p_sandbox (true = retorna sección “transaction”)
     *    p_output (JSON o XML)
     *    p_new_devs (include | exclude | only) — para filtrar nuevos desarrollos
     *    P_Location (nombre o lista CSV de localizaciones)
     *    P_PropertyTypes (IDs CSV)
     *    P_Beds (número exacto, o con sufijo “x” = al menos)
     *    P_Baths (similar)
     *    P_MustHaveFeatures (filtros por características)
     *    P_Dimension, P_MinPrice, P_MaxPrice, P_RTA, etc.  
     *    P_QueryId, P_PageNo, P_PageSize — para paginación
     *
     *  Ejemplos de uso:
     *    /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY& P_Location=Benalmadena
     *    /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY& P_Location=Benalmadena&P_PropertyTypes=2-1,3-1
     *    /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY& P_Location=Benalmadena&P_Beds=3x&p_new_devs=only
     *    /V6/SearchProperties?p_agency_filterid=1&p1=XXX&p2=YYY& P_Beds=2&P_PageSize=5 
     */
        // 1) Base URL
        $base = 'https://webapi.resales-online.com/V6/SearchProperties';

        // 2) Start query with credentials
        $this->api_user = get_option('resales_api_p1');
        $this->api_key  = get_option('resales_api_p2');
        $query = [
            'p1' => $this->api_user,
            'p2' => $this->api_key,
        ];

        // 3) Mapear filtros del front (location, type, bedrooms)
        // Siempre enviar el filtro fijo
        $query['P_Agency_FilterId'] = RESALES_API_DEFAULT_FILTER_ID;
        // --- LOG seguro de presencia de credenciales y filtro ---
        $resP1 = $query['p1'];
        $resP2 = $query['p2'];
        $resFilter = isset($query['P_Agency_FilterId']) ? $query['P_Agency_FilterId'] : (isset($query['P_ApiId']) ? $query['P_ApiId'] : null);
        $filterType = isset($query['P_Agency_FilterId']) ? 'P_Agency_FilterId' : (isset($query['P_ApiId']) ? 'P_ApiId' : 'none');
        resales_safe_log('CHECK PARAMS', [
            'P1' => !empty($resP1) ? 'set' : 'missing',
            'P2' => !empty($resP2) ? 'set' : 'missing',
            'Filter' => !empty($resFilter) ? 'set' : 'missing',
            'FilterType' => $filterType
        ]);
        // Mapear location
        if (!empty($_GET['location'])) {
            $query['P_Location'] = sanitize_text_field($_GET['location']);
        }
        // Mapear type
        if (!empty($_GET['type'])) {
            $query['P_PropertyTypes'] = sanitize_text_field($_GET['type']);
        }
        // Mapear bedrooms
        if (!empty($_GET['bedrooms'])) {
            $query['P_Beds'] = (int) $_GET['bedrooms'];
        }
        // Nunca enviar "Area"
        // Merge otros params si vienen de la llamada
        if (!empty($params)) $query = array_merge($query, $params);

        // 4) Enforce ONE filter ID if missing
        // Si el fallback está definido y en uso, NO mostrar warning aunque la opción esté vacía
        if (empty($query['P_Agency_FilterId']) && empty($query['P_ApiId'])) {
            $query['P_Agency_FilterId'] = (int) get_option('lusso_agency_filter_id');
            if (empty($query['P_Agency_FilterId']) && empty($query['P_ApiId'])) {
                // Solo mostrar el warning si NO está usando el fallback
                if (!defined('RESALES_API_DEFAULT_FILTER_ID') || RESALES_API_DEFAULT_FILTER_ID === null) {
                    error_log('[resales-api][ERROR] Falta P_ApiId/P_Agency_FilterId');
                }
                return new WP_Error('missing_filter', 'Falta filtro de API.');
            }
        }

        $query['p_new_devs'] = 'only';  // si tu web es solo ND
        // Diagnóstico temporal
        $query['P_sandbox'] = true;
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[V6 OUT] ' . json_encode($query, JSON_UNESCAPED_UNICODE));
        }

        // 7) Build URL and GET
        $url = $base . '?' . http_build_query($query);
        $timeout = (int) get_option('resales_api_timeout', 20);
        $args = [
            'timeout' => $timeout,
            'headers' => [ 'Accept' => 'application/json' ],
        ];
        $res = wp_remote_get($url, $args);
        if (is_wp_error($res)) return ['success'=>false, 'error'=>$res->get_error_message()];
        $code = wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200) {
            return ['success'=>false, 'error'=>'HTTP '.$code, 'body'=>$body];
        }
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success'=>false, 'error'=>'JSON error', 'body'=>$body];
        }
        // Loguear el bloque transaction si existe
        if (isset($json['transaction'])) {
            error_log('[resales-api][DEBUG] transaction=' . wp_json_encode($json['transaction']));
        }
        return $json;
    }
}

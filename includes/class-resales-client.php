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
    public function search_properties_v6($params) {
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

        // 3) Merge $params from rest-filters-v6.php
        if (!empty($params)) $query = array_merge($query, $params);

        // 4) Enforce ONE filter ID if missing
        if (empty($query['P_Agency_FilterId']) && empty($query['P_ApiId'])) {
            $query['P_Agency_FilterId'] = (int) get_option('lusso_agency_filter_id');
            if (empty($query['P_Agency_FilterId']) && empty($query['P_ApiId'])) {
                error_log('[resales-api][ERROR] Falta P_ApiId/P_Agency_FilterId');
                return new WP_Error('missing_filter', 'Falta filtro de API.');
            }
        }

        $query['p_new_devs'] = 'only';  // si tu web es solo ND
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query['p_sandbox'] = 'true';
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
        return $json;
    }
}

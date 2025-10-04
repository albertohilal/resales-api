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
    error_log('[resales-api][TRACE] ENTER search_properties_v6');
        // ... después de decodificar JSON ...
        if (isset($json['transaction']['parameters'])) {
            error_log('[resales-api][SAFELOG] V6 TXN PARAMS | ' . json_encode([
                'accepted' => array_keys((array)$json['transaction']['parameters']),
            ], JSON_UNESCAPED_UNICODE));
        }
        // Normaliza fuente
        $p_location = '';
        if (!empty($params['P_Location'])) $p_location = (string) $params['P_Location'];
        if ($p_location === '' && isset($_GET['location'])) {
            $p_location = sanitize_text_field(wp_unslash((string)$_GET['location']));
        }

        // Copia al contenedor real que envías
        if ($p_location !== '') {
            if (isset($payload) && is_array($payload)) $payload['P_Location'] = $p_location;
            if (isset($query) && is_array($query)) $query['P_Location'] = $p_location;
        }
                // --- LOGS OBLIGATORIOS ANTES DE LA PETICIÓN HTTP ---
                // Aquí ya tienes el array que realmente envías ($payload o $query)
                error_log('[resales-api][SAFELOG] REQ PAYLOAD (FILTER CHECK) | ' . json_encode([
                    'has_P_Agency_FilterId' => isset($payload['P_Agency_FilterId']) ? 'yes' : 'no',
                    'has_P_ApiId'           => isset($payload['P_ApiId'])           ? 'yes' : 'no',
                ], JSON_UNESCAPED_UNICODE));

                error_log('[resales-api][SAFELOG] REQ PAYLOAD | ' . json_encode([
                    'payload_keys'   => array_keys($payload ?? $query ?? []),
                    'P_Location_set' => (isset(($payload ?? $query)['P_Location']) && ($payload ?? $query)['P_Location'] !== '') ? 'yes' : 'no',
                ], JSON_UNESCAPED_UNICODE));
    error_log('[resales-api][TRACE] ENTER search_properties_v6');
        // --- 0) Normaliza y asegura P_Location en el payload ---
        // 0.1) Fuente primaria: $params['P_Location'] (viene del shortcode)
        $p_location = '';
        if (isset($params['P_Location']) && $params['P_Location'] !== '') {
            $p_location = (string) $params['P_Location'];
        }
        // 0.2) (Respaldo) Si no vino en $params pero sí en $_GET['location'] (carga directa con ?location=)
        if ($p_location === '' && isset($_GET['location'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $p_location = sanitize_text_field(wp_unslash((string)$_GET['location'])); // phpcs:ignore
        }

        // 0.3) Copia explícita al contenedor real que se envía (payload o query) ANTES del wp_remote_*.
        //     *No* logs con valores crudos: solo banderas.
        if (!empty($p_location)) {
            // si usas $payload para POST:
            if (isset($payload) && is_array($payload)) {
                $payload['P_Location'] = $p_location;
            }
            // si usas $query para GET/POST:
            if (isset($query) && is_array($query)) {
                $query['P_Location'] = $p_location;
            }
        }

        // 0.4) Logs de control (solo banderas, sin datos sensibles)
        if (function_exists('resales_safe_log')) {
            resales_safe_log('REQ PAYLOAD', [
                'payload_keys'   => array_keys(($payload ?? $query ?? [])),
                'P_Location_set' => (isset(($payload ?? $query)['P_Location']) && ($payload ?? $query)['P_Location'] !== '') ? 'yes' : 'no',
            ]);
        }
        // Log seguro de filtros antes de la petición HTTP
        $has_agency = isset($payload['P_Agency_FilterId']) ? 'yes' : 'no';
        $has_api    = isset($payload['P_ApiId']) ? 'yes' : 'no';
        // Solo uno debe ser 'yes'
        if ($has_agency === $has_api) {
            resales_safe_log('REQ PAYLOAD (FILTER CHECK)', [
                'error' => 'Debe haber exactamente uno en yes',
                'has_P_Agency_FilterId' => $has_agency,
                'has_P_ApiId'           => $has_api,
            ]);
        } else {
            resales_safe_log('REQ PAYLOAD (FILTER CHECK)', [
                'has_P_Agency_FilterId' => $has_agency,
                'has_P_ApiId'           => $has_api,
            ]);
        }
        $payload_keys = array_keys($payload ?? $query ?? []);
        resales_safe_log('REQ PAYLOAD', [
            'payload_keys_includes_P_Location' => in_array('P_Location', $payload_keys, true) ? 'yes' : 'no',
            'P_Location_set' => (isset(($payload ?? $query)['P_Location']) && ($payload ?? $query)['P_Location'] !== '') ? 'yes' : 'no',
        ]);
        // Sandbox si WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (isset($payload)) {
                $payload['p_sandbox'] = true;
            } elseif (isset($query)) {
                $query['p_sandbox'] = true;
            }
        }
        // 1. Determinar P_Location desde $params o $_GET['location']
        $location_source = 'none';
        $p_location = null;
        if (isset($params['P_Location']) && !empty($params['P_Location'])) {
            $p_location = $params['P_Location'];
            $location_source = 'params';
        } elseif (!empty($_GET['location'])) {
            $p_location = sanitize_text_field(wp_unslash($_GET['location']));
            $location_source = 'get';
        }
        // 1b. Log de banderas de presencia
        resales_safe_log('CHECK PARAMS', [
            'P_Location_source' => $location_source,
            'P_Location_set' => $p_location ? 'set' : 'missing',
            'params_keys' => array_keys($params),
            'GET_location' => isset($_GET['location']) ? 'present' : 'absent',
        ]);
        // 1) Fallback constante para API Filter si no hay opción
        if (!defined('RESALES_API_DEFAULT_FILTER_ID')) {
            define('RESALES_API_DEFAULT_FILTER_ID', 1); // Filtro de ejemplo: p_agency_filterid=1
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
        // Mapear location: priorizar $params['P_Location'], luego $p_location
        if (isset($params['P_Location']) && $params['P_Location'] !== '') {
            $query['P_Location'] = $params['P_Location'];
        } elseif (isset($p_location) && $p_location) {
            $query['P_Location'] = $p_location;
        }
        // Log de payload antes de la petición
        resales_safe_log('REQ PAYLOAD', [
            'payload_keys' => array_keys($query),
            'P_Location_set' => (isset($query['P_Location']) && $query['P_Location'] !== '') ? 'yes' : 'no',
        ]);
        // Mapear type
        if (!empty($_GET['type'])) {
            $query['P_PropertyTypes'] = sanitize_text_field(wp_unslash($_GET['type']));
        }
        // Mapear bedrooms
        if (!empty($_GET['bedrooms'])) {
            $beds = (int) $_GET['bedrooms'];
            $query['P_Beds'] = $beds . 'x'; // "5x" para cinco o más dormitorios
        }
        // Nunca enviar "Area"
        // Merge otros params si vienen de la llamada
    if (!empty($params)) $query = array_merge($query, $params);
    // Log de parámetros finales antes de la llamada a la API
    error_log('[Resales API][LOG] Params FINAL antes de API: ' . json_encode($query));

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
        // 2. Añadir p_sandbox solo si WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query['p_sandbox'] = true;
        }
        // 2b. Log de payload antes de la petición

                // --- LOGS OBLIGATORIOS ANTES DE LA PETICIÓN HTTP ---
                error_log('[resales-api][SAFELOG] REQ PAYLOAD (FILTER CHECK) | ' . json_encode([
                    'has_P_Agency_FilterId' => isset($query['P_Agency_FilterId']) ? 'yes' : 'no',
                    'has_P_ApiId'           => isset($query['P_ApiId'])           ? 'yes' : 'no',
                ], JSON_UNESCAPED_UNICODE));

                error_log('[resales-api][SAFELOG] REQ PAYLOAD | ' . json_encode([
                    'payload_keys'   => array_keys($query),
                    'P_Location_set' => (isset($query['P_Location']) && $query['P_Location'] !== '') ? 'yes' : 'no',
                ], JSON_UNESCAPED_UNICODE));

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
        // Loguear parámetros aceptados por la transacción si existen
        if (isset($json['transaction']['parameters'])) {
            resales_safe_log('V6 TXN PARAMS', [
                'accepted' => array_keys((array)$json['transaction']['parameters'])
            ]);
        }
        // 3. Loguear transaction si existe
        if (isset($json['transaction'])) {
            resales_safe_log('V6 TRANSACTION', ['keys' => array_keys($json['transaction'])]);
        }
        return $json;
    }
}

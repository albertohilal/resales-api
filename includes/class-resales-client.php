<?php
/**
 * Cliente para Resales Online WebAPI V6 (GET)
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Client')):

class Resales_Client {
    private static $instance = null;
    private $base = 'https://webapi.resales-online.com/V6/SearchProperties'; // función V6 correcta :contentReference[oaicite:3]{index=3}

    public static function instance(){
        return self::$instance ?: (self::$instance = new self());
    }

    private function __construct(){}

    private function opt($key, $default = ''){
        $v = get_option($key, $default);
        if (is_string($v)) $v = trim($v);
        return $v;
    }

    /** Construye URL con parámetros válidos V6 */
    private function build_url(array $args) : string {
        $params = [
            // credenciales obligatorias :contentReference[oaicite:4]{index=4}
            'p1'       => $this->opt('resales_api_p1'),
            'p2'       => $this->opt('resales_api_p2'),
            // salida / idioma
            'p_output' => 'JSON',
            'P_Lang'   => (int)$this->opt('resales_api_lang', 2), // 2 = ES :contentReference[oaicite:5]{index=5}
        ];

        // Filtro: usar SOLO UNO (ApiId o Agency_FilterId) :contentReference[oaicite:6]{index=6}
        $apiId   = $args['P_ApiId']          ?? $this->opt('resales_api_apiid');
        $afAlias = $args['P_Agency_FilterId']?? $this->opt('resales_api_agency_filterid');

        if (!empty($apiId)) {
            $params['P_ApiId'] = (int)$apiId;
        } elseif (!empty($afAlias)) {
            $params['P_Agency_FilterId'] = (int)$afAlias;
        }

        // Nueva promoción (New Developments) exclude|include|only (include por defecto) :contentReference[oaicite:7]{index=7}
        $newDevs = $args['p_new_devs'] ?? $this->opt('resales_api_newdevs', 'include');
        if (in_array($newDevs, ['exclude','include','only'], true)) {
            $params['p_new_devs'] = $newDevs;
        }

    // --- Mapping de paginación y orden ---
    // per_page → P_PageSize (12–48, fallback 15)
    $pageSize = isset($args['p_PageSize']) ? (int)$args['p_PageSize'] : 15;
    if ($pageSize < 12 || $pageSize > 48) $pageSize = 15;
    $params['P_PageSize'] = $pageSize;
    // page → P_PageNo (>=1, fallback 1)
    $params['P_PageNo']   = isset($args['p_PageNo']) ? max(1, (int)$args['p_PageNo']) : 1;
    // order → P_SortType (numérico V6: 1=asc, 2=desc, 3=recent)
    $sortType = isset($args['P_SortType']) ? (int)$args['P_SortType'] : 3;
    if (!in_array($sortType, [1,2,3])) $sortType = 3;
    $params['P_SortType'] = $sortType;
    // QueryId
    if (!empty($args['P_QueryId'])) $params['P_QueryId']  = sanitize_text_field($args['P_QueryId']);

        // Otros filtros que quieras pasar tal cual (ejemplos comunes)
        foreach ([
            'P_Beds','P_Baths','P_Min','P_Max','P_Location','P_PropertyTypes',
            'p_images','p_show_dev_prices','P_onlydecree218','P_RTA'
        ] as $k){
            if (isset($args[$k]) && $args[$k] !== '') $params[$k] = $args[$k];
        }

    // Diagnóstico cuando WP_DEBUG está activo (solo test)
    if (defined('WP_DEBUG') && WP_DEBUG) $params['p_sandbox'] = 'true';

        $url = esc_url_raw( add_query_arg($params, $this->base) );
        $this->log('URL → ' . preg_replace('/(p2=)[^&]+/','\\1•••', $url)); // oculta p2
        return $url;
    }

    /** Llamada HTTP */
    private function http_get(string $url){
        $timeout = (int) get_option('resales_api_timeout', 20);
        $resp = wp_remote_get($url, ['timeout' => $timeout, 'headers' => ['Accept'=>'application/json']]);
        if (is_wp_error($resp)) {
            $this->log('HTTP ERROR: ' . $resp->get_error_message());
            return ['ok'=>false,'code'=>0,'data'=>null,'error'=>$resp->get_error_message(),'raw'=>null,'url'=>$url];
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = (string) wp_remote_retrieve_body($resp);
        $this->log("HTTP {$code} | BODY: " . substr($body,0,1500));
        if ($code !== 200) {
            return ['ok'=>false,'code'=>$code,'data'=>null,'error'=>'HTTP '.$code,'raw'=>$body,'url'=>$url];
        }
        $data = json_decode($body, true);
        return ['ok'=>true,'code'=>$code,'data'=>$data,'error'=>null,'raw'=>$body,'url'=>$url];
    }

    /** Búsqueda principal (paginada V6) */
    public function search(array $args = []){
        // Si piden página>1 sin QueryId, hacemos una llamada inicial para obtenerlo (recomendado por V6)
        // Doc V6: https://webapi-v6.learning.resales-online.com/#searchproperties
        $page = isset($args['p_PageNo']) ? max(1,(int)$args['p_PageNo']) : 1;
        if ($page > 1 && empty($args['P_QueryId'])) {
            $first = $this->http_get( $this->build_url(array_diff_key($args, ['p_PageNo'=>1])) );
            if (!$first['ok'] || empty($first['data']['QueryInfo']['QueryId'])) return $first;
            $args['P_QueryId'] = $first['data']['QueryInfo']['QueryId'];
        }

        // Solicitar imágenes para developments (cards estilo Promociones)
        if (!isset($args['p_images'])) {
            $args['p_images'] = 1;
        }
        if (!isset($args['p_image_size'])) {
            $args['p_image_size'] = 'medium';
        }
            // Solicitar ambos precios en developments si el API lo soporta
            if (!isset($args['p_show_dev_prices'])) {
                $args['p_show_dev_prices'] = 1;
            }
        // Fallback para orden
        if (isset($args['P_SortType']) && !in_array($args['P_SortType'],['recent','price_asc','price_desc'])) {
            $args['P_SortType'] = 'recent';
        }

        $url = $this->build_url($args);
        $result = $this->http_get($url);

        // Normalizar respuesta: exponer first_image_url en cada elemento
        if ($result['ok'] && !empty($result['data']['Properties']) && is_array($result['data']['Properties'])) {
            foreach ($result['data']['Properties'] as $i => $item) {
                // Doc V6: cada propiedad tiene 'Photos' (array), cada foto tiene 'Url'
                $photos = $item['Photos'] ?? [];
                if (is_array($photos) && count($photos) > 0 && !empty($photos[0]['Url'])) {
                    $result['data']['Properties'][$i]['first_image_url'] = $photos[0]['Url'];
                } else {
                    $result['data']['Properties'][$i]['first_image_url'] = null;
                }
            }
        }
        return $result;
    }

    public function build_title(array $p): string {
        $type = $p['PropertyType']['NameType'] ?? '';
        $loc  = $p['Location'] ?? ($p['Area'] ?? ($p['Province'] ?? ''));
        $ref  = $p['Reference'] ?? '';
        if ($type && $loc) return sprintf('%s en %s', $type, $loc);
        if ($ref && $loc)  return sprintf('Ref. %s — %s', $ref, $loc);
        return $ref ?: 'Propiedad';
    }

    private function log($msg){ if (defined('WP_DEBUG') && WP_DEBUG) error_log('[Resales API] '.$msg); }
}

endif;

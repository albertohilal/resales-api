<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('Resales_Admin')):
class Resales_Admin {
    private static $instance = null;
    public static function instance(){ return self::$instance ?: (self::$instance = new self()); }
    private function __construct(){ add_action('admin_menu', [$this,'menu']); }

    public function menu(){
        add_management_page('Resales API – Test', 'Resales API – Test', 'manage_options', 'resales-api-test', [$this,'page']);
    }

    public function page(){
        $client = Resales_Client::instance();
        $args = [
            'p_PageSize' => 5,
            'p_PageNo'   => 1,
            // usa el filtro configurado en Ajustes; puedes forzarlo aquí:
            // 'P_ApiId' => (int) get_option('resales_api_apiid'),
            'p_new_devs' => get_option('resales_api_newdevs','include'),
        ];
        $res = $client->search($args);
        echo '<div class="wrap"><h1>Diagnóstico WebAPI V6</h1>';
        if (!$res['ok']){
            echo '<p><strong>Error:</strong> '.esc_html($res['error']).'</p>';
            echo '<pre style="white-space:pre-wrap;background:#111;color:#ddd;padding:12px;">'.esc_html($res['raw']).'</pre>';
        } else {
            $qi = $res['data']['QueryInfo'] ?? [];
            echo '<p><strong>SearchType:</strong> '.esc_html($qi['SearchType'] ?? '').'</p>';
            echo '<p><strong>PropertyCount:</strong> '.esc_html($qi['PropertyCount'] ?? '').'</p>';
            echo '<p><strong>QueryId:</strong> '.esc_html($qi['QueryId'] ?? '').'</p>';
            echo '<pre style="white-space:pre-wrap;background:#111;color:#ddd;padding:12px;max-height:420px;overflow:auto;">'.esc_html(json_encode($res['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</pre>';
        }
        echo '</div>';
    }
}
endif;

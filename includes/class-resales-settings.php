<?php
if (!defined('ABSPATH')) exit;

class Resales_Settings {
    private static $instance = null;
    public static function instance(){ return self::$instance ?: (self::$instance = new self()); }

    private function __construct(){
    error_log('[Resales API] Ejecutando constructor de Resales_Settings');
    add_action('admin_menu',        [$this,'add_menu']);
    add_action('admin_init',        [$this,'register']);
    }

    public function add_menu(){
    error_log('[Resales API] Ejecutando add_menu (ajustes)');
    add_options_page('Resales API', 'Resales API', 'manage_options', 'resales-api', [$this,'render']);
    }

    public function register(){
        register_setting('resales_api', 'resales_api_p1');
        register_setting('resales_api', 'resales_api_p2');
        register_setting('resales_api', 'resales_api_apiid');
        register_setting('resales_api', 'resales_api_agency_filterid');
        register_setting('resales_api', 'resales_api_lang');         // 1=EN,2=ES,... :contentReference[oaicite:12]{index=12}
        register_setting('resales_api', 'resales_api_newdevs');      // exclude|include|only :contentReference[oaicite:13]{index=13}
        register_setting('resales_api', 'resales_api_timeout');
    }

    public function render(){ ?>
        <div class="wrap">
            <h1>Resales Online – WebAPI V6</h1>
            <p>Configura tus credenciales y el filtro por defecto. La API requiere <code>p1</code>, <code>p2</code> y <strong>un solo</strong> identificador de filtro: <code>P_ApiId</code> <em>o</em> <code>P_Agency_FilterId</code>. :contentReference[oaicite:14]{index=14}</p>
            <form method="post" action="options.php">
                <?php settings_fields('resales_api'); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="resales_api_p1">p1 (Identifier)</label></th>
                        <td><input name="resales_api_p1" id="resales_api_p1" type="text" value="<?php echo esc_attr(get_option('resales_api_p1','')); ?>" class="regular-text"></td></tr>
                    <tr><th><label for="resales_api_p2">p2 (API Key)</label></th>
                        <td><input name="resales_api_p2" id="resales_api_p2" type="password" value="<?php echo esc_attr(get_option('resales_api_p2','')); ?>" class="regular-text" autocomplete="new-password"></td></tr>
                    <tr><th><label for="resales_api_apiid">P_ApiId (ID de filtro)</label></th>
                        <td><input name="resales_api_apiid" id="resales_api_apiid" type="number" value="<?php echo esc_attr(get_option('resales_api_apiid','')); ?>" class="small-text">
                            <span class="description">Déjalo vacío si prefieres usar el alias <code>P_Agency_FilterId</code>.</span></td></tr>
                    <tr><th><label for="resales_api_agency_filterid">P_Agency_FilterId (alias)</label></th>
                        <td><input name="resales_api_agency_filterid" id="resales_api_agency_filterid" type="number" value="<?php echo esc_attr(get_option('resales_api_agency_filterid','')); ?>" class="small-text"></td></tr>
                    <tr><th><label for="resales_api_lang">P_Lang (idioma)</label></th>
                        <td><input name="resales_api_lang" id="resales_api_lang" type="number" min="1" max="14" value="<?php echo esc_attr(get_option('resales_api_lang','2')); ?>" class="small-text">
                            <span class="description">1=EN, 2=ES, 3=DE, 4=FR, 5=NL, 6=DA, 7=RU, 8=SV, 9=PL, 10=NO, 11=TR, 13=FI, 14=HU.</span></td></tr>
                    <tr><th><label for="resales_api_newdevs">p_new_devs</label></th>
                        <td><select name="resales_api_newdevs" id="resales_api_newdevs">
                            <?php $v = get_option('resales_api_newdevs','include'); ?>
                            <?php foreach (['exclude','include','only'] as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($v,$opt); ?>><?php echo esc_html($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="description">Incluir/excluir “Nueva promoción” en resultados.</span></td></tr>
                    <tr><th><label for="resales_api_timeout">Timeout (seg.)</label></th>
                        <td><input name="resales_api_timeout" id="resales_api_timeout" type="number" min="5" value="<?php echo esc_attr(get_option('resales_api_timeout','20')); ?>" class="small-text"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }
}

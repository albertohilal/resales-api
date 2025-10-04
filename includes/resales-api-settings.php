<?php
/**
 * Página de ajustes para credenciales de Resales API
 * Ubicación: includes/resales-api-settings.php
 */
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_options_page(
        'Resales API',
        'Resales API',
        'manage_options',
        'resales-api-settings',
        'resales_api_settings_page'
    );
});

function resales_api_settings_page() {
    if (!current_user_can('manage_options')) return;
    // Guardar cambios
    if (isset($_POST['resales_api_save'])) {
        check_admin_referer('resales_api_settings');
        update_option('resales_api_p1', sanitize_text_field($_POST['resales_api_p1'] ?? ''));
        update_option('resales_api_p2', sanitize_text_field($_POST['resales_api_p2'] ?? ''));
        update_option('resales_api_apiid', sanitize_text_field($_POST['resales_api_apiid'] ?? ''));
        update_option('resales_api_agency_filterid', sanitize_text_field($_POST['resales_api_agency_filterid'] ?? ''));
        update_option('resales_api_lang', intval($_POST['resales_api_lang'] ?? 1));
        update_option('resales_api_timeout', intval($_POST['resales_api_timeout'] ?? 20));
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }
    // Leer valores actuales
    $p1 = get_option('resales_api_p1', '');
    $p2 = get_option('resales_api_p2', '');
    $apiid = get_option('resales_api_apiid', '');
    $agency_filterid = get_option('resales_api_agency_filterid', '');
    $lang = get_option('resales_api_lang', 1);
    $timeout = get_option('resales_api_timeout', 20);
    ?>
    <div class="wrap">
        <h1>Resales API - Ajustes</h1>
        <form method="post">
            <?php wp_nonce_field('resales_api_settings'); ?>
            <table class="form-table">
                <tr><th scope="row">P1</th><td><input type="text" name="resales_api_p1" value="<?php echo esc_attr($p1); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">P2</th><td><input type="text" name="resales_api_p2" value="<?php echo esc_attr($p2); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">ApiId</th><td><input type="text" name="resales_api_apiid" value="<?php echo esc_attr($apiid); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">Agency FilterId</th><td><input type="text" name="resales_api_agency_filterid" value="<?php echo esc_attr($agency_filterid); ?>" class="regular-text"></td></tr>
                <tr><th scope="row">Idioma (1=ES, 2=EN)</th><td><input type="number" name="resales_api_lang" value="<?php echo esc_attr($lang); ?>" min="1" max="2"></td></tr>
                <tr><th scope="row">Timeout (segundos)</th><td><input type="number" name="resales_api_timeout" value="<?php echo esc_attr($timeout); ?>" min="5" max="60"></td></tr>
            </table>
            <p class="submit"><input type="submit" name="resales_api_save" class="button-primary" value="Guardar cambios"></p>
        </form>
    </div>
    <?php
}

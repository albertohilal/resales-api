<?php
// resales-api-settings.php
// Página de ajustes para credenciales y parámetros de Resales API
add_action('admin_menu', function() {
    add_options_page(
        'Resales API Ajustes',
        'Resales API',
        'manage_options',
        'resales-api-settings',
        'resales_api_settings_page'
    );
});

function resales_api_settings_page() {
    if (!current_user_can('manage_options')) return;
    $fields = [
        'resales_api_p1' => 'API P1',
        'resales_api_p2' => 'API P2',
        'resales_api_apiid' => 'API ID',
        'resales_api_agency_filterid' => 'Agency Filter ID',
        'resales_api_lang' => 'Idioma (1=EN,2=ES,3=DE,4=FR)',
        'resales_api_timeout' => 'Timeout (segundos)',
    ];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('resales_api_settings')) {
        foreach ($fields as $key => $label) {
            if (isset($_POST[$key])) {
                update_option($key, sanitize_text_field($_POST[$key]));
            }
        }
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }
    echo '<div class="wrap"><h1>Resales API Ajustes</h1>';
    echo '<form method="post">';
    wp_nonce_field('resales_api_settings');
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $val = esc_attr(get_option($key, ''));
        echo "<tr><th scope='row'><label for='$key'>$label</label></th><td><input type='text' name='$key' id='$key' value='$val' class='regular-text' /></td></tr>";
    }
    echo '</table>';
    submit_button('Guardar ajustes');
    echo '</form></div>';
}

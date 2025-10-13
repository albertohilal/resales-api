<?php
/**
 * Test real del shortcode con la nueva estructura CSS
 */

// Incluir WordPress (simulado para el test)
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
    
    // Simular funciones básicas de WordPress
    function plugins_url($path, $file) {
        return "/wp-content/plugins/resales-api/$path";
    }
    
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
        echo "<link rel='stylesheet' id='$handle-css' href='$src?ver=$ver' type='text/css' media='$media' />\n";
    }
    
    function wp_style_is($handle, $list = 'enqueued') {
        return false; // Para testing
    }
}

// Incluir la clase
require_once '/home/beto/Documentos/Github/resales-api/includes/class-resales-single.php';

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<title>Test Resales Single</title>\n";

// Instanciar la clase
$single = new Resales_Single();

// Test del shortcode con ref de ejemplo
echo "\n<!-- CSS encolado dinámicamente -->\n";
$result = $single->render_shortcode(['ref' => 'RH-213437']); // Usemos una ref de prueba

echo "</head>\n<body>\n";
echo "<h1>Test del Shortcode Resales Single</h1>\n";
echo "<div class='test-container'>\n";
echo $result;
echo "</div>\n";
echo "</body>\n</html>";
?>
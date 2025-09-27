<?php
/* Template Name: Property Detail */
if (!defined('ABSPATH')) exit;
get_header();

// Obtener ID y slug desde la URL
$property_id = 0;
$slug = '';
if (preg_match('#/property/(\d+)/(.*?)/#', $_SERVER['REQUEST_URI'], $matches)) {
    $property_id = (int)$matches[1];
    $slug = sanitize_title($matches[2]);
}

// Simulación de datos (reemplazar por consulta real a la API o CPT)
$property = [
    'title' => 'Promoción Ejemplo',
    'location' => 'Estepona',
    'price' => '€ 450,000',
    'features' => ['Piscina', 'Garaje', 'Vistas al mar'],
    'images' => [], // Galería futura
];
?>
<main class="property-detail">
    <h1><?php echo esc_html($property['title']); ?></h1>
    <div class="property-meta">
        <span class="property-location">Ubicación: <?php echo esc_html($property['location']); ?></span>
        <span class="property-price">Precio: <?php echo esc_html($property['price']); ?></span>
    </div>
    <ul class="property-features">
        <?php foreach ($property['features'] as $f): ?>
            <li><?php echo esc_html($f); ?></li>
        <?php endforeach; ?>
    </ul>
    <section class="property-gallery">
        <h2>Galería</h2>
        <div class="gallery-placeholder" style="height:220px;background:#f2f2f2;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#888;">Próximamente galería de imágenes</div>
    </section>
</main>
<?php get_footer(); ?>

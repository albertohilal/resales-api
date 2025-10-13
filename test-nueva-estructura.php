<?php
// Test de la nueva estructura con galería
require_once 'includes/class-resales-single.php';

// Datos de prueba
$property = [
    'Reference' => 'R1234567',
    'Price' => '€ 750,000',
    'Location' => 'Marbella',
    'Type' => 'Villa',
    'Area' => 'Costa del Sol',
    'Bedrooms' => '4',
    'Bathrooms' => '3',
    'PlotSize' => '1200',
    'BuiltSize' => '280',
    'Terrace' => '45',
    'Features' => 'Swimming pool, Private parking, Garden, Sea views',
    'Description' => 'Beautiful villa with stunning sea views in the heart of Marbella. This property offers luxury living with spacious rooms, modern amenities, and a beautiful garden with private pool. Perfect for year-round living or holiday home.',
    'Province' => 'Malaga',
    'Gallery' => [
        'https://via.placeholder.com/800x600?text=Property+1',
        'https://via.placeholder.com/800x600?text=Property+2',
        'https://via.placeholder.com/800x600?text=Property+3',
        'https://via.placeholder.com/800x600?text=Property+4'
    ]
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Nueva Estructura</title>
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
    <style>
        body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Nueva Estructura de Detalle de Propiedad</h1>
        <?php
        $single = new Resales_Single();
        echo $single->render_property_detail($property);
        ?>
    </div>
    
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
</body>
</html>
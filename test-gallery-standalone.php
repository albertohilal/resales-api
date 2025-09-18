<?php
// Si mueves este archivo a la raíz pública, ajusta la ruta:
require_once __DIR__ . '/includes/gallery-helper.php';
// Caso 1: varias imágenes
$images = [
    'https://via.placeholder.com/600x400?text=Imagen+1',
    'https://via.placeholder.com/600x400?text=Imagen+2',
    'https://via.placeholder.com/600x400?text=Imagen+3'
];

// Caso 2: una sola imagen
$images_single = [
    'https://via.placeholder.com/600x400?text=Solo+una+imagen'
];

// Caso 3: sin imágenes
$images_empty = [];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Galería Swiper</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="/wp-content/plugins/resales-api/assets/css/swiper-gallery.css" />
    <style>body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 40px; }</style>
</head>
<body>
    <h1>Test Galería Swiper</h1>
    <h2>Varias imágenes</h2>
    <?php render_gallery($images, 'card'); ?>
    <h2>Una sola imagen</h2>
    <?php render_gallery($images_single, 'card'); ?>
    <h2>Sin imágenes</h2>
    <?php render_gallery($images_empty, 'card'); ?>
</body>
</html>

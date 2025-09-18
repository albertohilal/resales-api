<?php
require_once __DIR__ . '/includes/gallery-helper.php';
$images = [
    'https://via.placeholder.com/600x400?text=Imagen+1',
    'https://via.placeholder.com/600x400?text=Imagen+2',
    'https://via.placeholder.com/600x400?text=Imagen+3'
];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Galería Swiper</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="/assets/css/swiper-gallery.css" />
    <style>body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 40px; }</style>
</head>
<body>
    <h1>Test Galería Swiper</h1>
    <?php render_gallery($images, 'card'); ?>
</body>
</html>

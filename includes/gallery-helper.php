<?php
/**
 * gallery-helper.php
 * Helper para renderizar la galería de imágenes en el plugin Resales API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renderiza la galería a partir de un array de URLs de imágenes.
 *
 * @param array  $imgs  Array de URLs de imágenes (strings).
 * @param string $mode  Modo de presentación: 'listing' (tarjetas) o 'detail' (vista individual), etc.
 */
function render_gallery( array $imgs, $mode = 'listing' ) {
    // Si no hay imágenes, no renderizar nada.
    if ( empty( $imgs ) ) {
        return;
    }

    // Limpieza o validación si fuera necesaria
    $imgs = array_filter( $imgs, function( $url ) {
        return is_string( $url ) && $url !== '';
    } );

    // Si tras el filtro sólo queda una imagen, podríamos optar por renderizarla sin slider:
    if ( count( $imgs ) === 1 ) {
        echo '<div class="property-gallery ' . esc_attr( $mode ) . '">';
        echo '<img src="' . esc_url( $imgs[0] ) . '" alt="" />';
        echo '</div>';
        return;
    }

    if ( $mode === 'detail' ) {
        // *** Detalle: slider con Swiper y navegación visible ***
        echo '<!-- Detail mode slider – ensure navigation arrows visible -->';
        echo '<div class="property-gallery detail">';
        echo '  <div class="swiper gallery-swiper detail-swiper">';
        echo '    <div class="swiper-wrapper">';
        foreach ( $imgs as $url ) {
            echo '      <div class="swiper-slide"><img src="' . esc_url( $url ) . '" alt="" /></div>';
        }
        echo '    </div>'; // .swiper-wrapper
        echo '    <div class="swiper-button-prev"></div>';
        echo '    <div class="swiper-button-next"></div>';
        echo '    <div class="swiper-pagination"></div>';
        echo '  </div>'; // .swiper.gallery-swiper.detail-swiper
        echo '</div>';   // .property-gallery.detail

        // Inicialización de Swiper para el slider de detalle
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
          const sliderDetail = new Swiper(".detail-swiper", {
            loop: true,
            slidesPerView: 1,
            navigation: {
              nextEl: ".detail-swiper .swiper-button-next",
              prevEl: ".detail-swiper .swiper-button-prev",
            },
            pagination: {
              el: ".detail-swiper .swiper-pagination",
              clickable: true,
            },
            observer: true,
            observeParents: true
          });
        });
        </script>';

    } else {
        // Modo listado/tarjetas u otro: conservar el comportamiento actual sin afectarlo
        echo '<div class="property-gallery ' . esc_attr( $mode ) . '">';
        echo '  <div class="swiper gallery-swiper">';
        echo '    <div class="swiper-wrapper">';
        foreach ( $imgs as $url ) {
            echo '      <div class="swiper-slide"><img src="' . esc_url( $url ) . '" alt="" /></div>';
        }
        echo '    </div>'; // .swiper-wrapper
        echo '</div>';   // .swiper.gallery-swiper
        echo '</div>';   // .property-gallery
    }
}

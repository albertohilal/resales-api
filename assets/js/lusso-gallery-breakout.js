/**
 * Lusso Gallery Breakout - Forzar galería edge-to-edge
 * File: assets/js/lusso-gallery-breakout.js
 */

(function() {
    'use strict';

    /**
     * Fuerza el breakout completo de la galería
     */
    function forceGalleryBreakout() {
        const gallery = document.querySelector('.property-gallery');
        if (!gallery) return;

        // Calcular el ancho real del viewport
        const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        
        // Encontrar la posición actual de la galería relativa al viewport
        const rect = gallery.getBoundingClientRect();
        const leftOffset = rect.left;
        
        // Aplicar estilos para breakout completo
        gallery.style.cssText = `
            width: ${vw}px !important;
            max-width: ${vw}px !important;
            margin-left: -${leftOffset}px !important;
            margin-right: -${vw - rect.right}px !important;
            position: relative !important;
            left: 0 !important;
            right: 0 !important;
            transform: none !important;
            box-sizing: border-box !important;
        `;

        // Asegurar que el swiper también tenga el ancho completo
        const swiper = gallery.querySelector('.swiper');
        if (swiper) {
            swiper.style.cssText = `
                width: ${vw}px !important;
                max-width: ${vw}px !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            `;
        }

        console.log('Lusso Gallery: Breakout aplicado -', {
            viewportWidth: vw,
            leftOffset: leftOffset,
            galleryWidth: gallery.offsetWidth
        });
    }

    /**
     * Eliminar cualquier contenedor limitante
     */
    function removeContainerLimits() {
        const gallery = document.querySelector('.property-gallery');
        if (!gallery) return;

        // Lista de selectores de contenedores comunes que pueden limitar
        const containerSelectors = [
            '.container', '.content', '.main', '.entry-content', 
            '.post-content', '.page-content', '.site-content',
            '.elementor-container', '.elementor-section',
            '#primary', '#main', '#content'
        ];

        let parent = gallery.parentElement;
        let level = 0;
        
        // Subir por el DOM eliminando limitaciones
        while (parent && parent !== document.body && level < 10) {
            // Verificar si este elemento es un contenedor limitante
            const isLimitingContainer = containerSelectors.some(selector => {
                try {
                    return parent.matches && parent.matches(selector);
                } catch (e) {
                    return false;
                }
            });

            if (isLimitingContainer || parent.style.maxWidth || getComputedStyle(parent).maxWidth !== 'none') {
                // Aplicar override temporal a este contenedor
                parent.style.cssText += `
                    max-width: none !important;
                    width: 100% !important;
                    overflow: visible !important;
                `;
                console.log('Lusso Gallery: Contenedor limitante encontrado y overridden:', parent);
            }

            parent = parent.parentElement;
            level++;
        }
    }

    /**
     * Aplicar breakout con reintentos
     */
    function applyBreakoutWithRetries(attempts = 0) {
        const maxAttempts = 5;
        
        if (attempts >= maxAttempts) {
            console.warn('Lusso Gallery: No se pudo aplicar breakout después de', maxAttempts, 'intentos');
            return;
        }

        const gallery = document.querySelector('.property-gallery');
        
        if (!gallery) {
            // Reintentar en 100ms si la galería no existe aún
            setTimeout(() => applyBreakoutWithRetries(attempts + 1), 100);
            return;
        }

        // Aplicar el breakout
        removeContainerLimits();
        forceGalleryBreakout();

        // Verificar si funcionó
        setTimeout(() => {
            const rect = gallery.getBoundingClientRect();
            const vw = window.innerWidth;
            
            if (Math.abs(rect.left) > 5 || Math.abs(rect.right - vw) > 5) {
                console.log('Lusso Gallery: Breakout no completado, reintentando...', {
                    left: rect.left,
                    right: rect.right,
                    viewport: vw
                });
                applyBreakoutWithRetries(attempts + 1);
            } else {
                console.log('Lusso Gallery: Breakout exitoso!');
            }
        }, 50);
    }

    /**
     * Manejar redimensión de ventana
     */
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            forceGalleryBreakout();
        }, 100);
    }

    /**
     * Inicialización
     */
    function init() {
        // Aplicar breakout cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => applyBreakoutWithRetries(), 100);
            });
        } else {
            setTimeout(() => applyBreakoutWithRetries(), 100);
        }

        // Reaplicar en resize
        window.addEventListener('resize', handleResize);

        // Reaplicar cuando Swiper se inicialice
        document.addEventListener('swiperInit', () => {
            setTimeout(() => applyBreakoutWithRetries(), 200);
        });
    }

    // Iniciar
    init();

})();
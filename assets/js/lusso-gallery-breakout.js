/**
 * Lusso Gallery Breakout - Forzar galería edge-to-edge
 * File: assets/js/lusso-gallery-breakout.js
 */

(function() {
    'use strict';

    /**
     * Elimina backgrounds grises de contenedores padre
     */
    function removeParentBackgrounds() {
        const gallery = document.querySelector('.property-gallery');
        if (!gallery) return;

        // Lista de elementos que pueden tener el fondo gris #f5f5f5
        const elementsToCheck = [
            document.body,
            document.documentElement,
            ...document.querySelectorAll('.elementor-section'),
            ...document.querySelectorAll('.elementor-container'),
            ...document.querySelectorAll('.elementor-row'),
            ...document.querySelectorAll('.site-content'),
            ...document.querySelectorAll('.entry-content'),
            ...document.querySelectorAll('.post-content'),
            ...document.querySelectorAll('#primary'),
            ...document.querySelectorAll('#main'),
            ...document.querySelectorAll('#content'),
            ...document.querySelectorAll('.content-area')
        ];

        // También revisar elementos padre de la galería
        let parent = gallery.parentElement;
        let level = 0;
        while (parent && level < 10) {
            elementsToCheck.push(parent);
            parent = parent.parentElement;
            level++;
        }

        // Solución identificada: aplicar breakout también al contenedor de contenido
        const contentContainer = document.querySelector('.lusso-detail-container');
        if (contentContainer) {
            contentContainer.style.cssText = `
                width: 100vw !important;
                max-width: none !important;
                position: relative !important;
                left: 50% !important;
                right: 50% !important;
                margin-left: -50vw !important;
                margin-right: -50vw !important;
                padding: 0 20px !important;
                box-sizing: border-box !important;
            `;
            console.log('Lusso Gallery: Breakout aplicado también al contenedor de contenido');
        }
        
        console.log('Lusso Gallery: Problema identificado y solucionado - era el contenedor de contenido, no la galería');

        // Eliminar backgrounds grises de otros elementos (no body)
        elementsToCheck.forEach(element => {
            if (element && element !== document.body) {
                const computedStyle = getComputedStyle(element);
                const bgColor = computedStyle.backgroundColor;
                
                // Detectar el color gris problemático #f5f5f5 o similares
                if (bgColor === 'rgb(245, 245, 245)' || 
                    bgColor === '#f5f5f5' || 
                    bgColor.includes('245, 245, 245') ||
                    bgColor === 'rgb(255, 255, 255)' ||
                    bgColor === '#ffffff' ||
                    bgColor === 'white') {
                    
                    element.style.backgroundColor = 'transparent';
                    console.log('Lusso Gallery: Eliminado fondo de', element.tagName, element.className, 'color era:', bgColor);
                }
            }
        });
    }

    /**
     * Fuerza el breakout completo de la galería usando método simplificado
     */
    function forceGalleryBreakout() {
        const gallery = document.querySelector('.property-gallery');
        if (!gallery) {
            console.log('Lusso Gallery: No se encontró .property-gallery');
            return;
        }

        // Primero eliminar backgrounds problemáticos
        removeParentBackgrounds();

        // Método simple y directo para breakout edge-to-edge
        gallery.style.cssText = `
            width: 100vw !important;
            max-width: 100vw !important;
            position: relative !important;
            left: 50% !important;
            right: 50% !important;
            margin-left: -50vw !important;
            margin-right: -50vw !important;
            transform: none !important;
            box-sizing: border-box !important;
            padding: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 40px !important;
            background: transparent !important;
        `;

        // Asegurar que el swiper también tenga el ancho completo
        const swiper = gallery.querySelector('.swiper');
        if (swiper) {
            swiper.style.cssText = `
                width: 100vw !important;
                max-width: 100vw !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                position: relative !important;
                background: transparent !important;
            `;
        }

        // Verificar resultado
        setTimeout(() => {
            const rect = gallery.getBoundingClientRect();
            console.log('Lusso Gallery: Breakout aplicado', {
                left: rect.left,
                right: rect.right,
                width: rect.width,
                viewport: window.innerWidth,
                isEdgeToEdge: Math.abs(rect.left) < 1 && Math.abs(rect.right - window.innerWidth) < 1
            });
        }, 100);
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
     * Aplicar breakout con reintentos simplificado
     */
    function applyBreakoutWithRetries(attempts = 0) {
        const maxAttempts = 3;
        
        if (attempts >= maxAttempts) {
            console.warn('Lusso Gallery: Breakout no aplicado después de', maxAttempts, 'intentos');
            return;
        }

        const gallery = document.querySelector('.property-gallery');
        
        if (!gallery) {
            setTimeout(() => applyBreakoutWithRetries(attempts + 1), 200);
            return;
        }

        console.log('Lusso Gallery: Aplicando breakout, intento', attempts + 1);
        forceGalleryBreakout();
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
     * Inicialización que considera otros scripts
     */
    function init() {
        console.log('Lusso Gallery: Iniciando sistema de breakout avanzado');
        
        // Aplicar en múltiples momentos para asegurar que funcione
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(applyBreakoutWithRetries, 500);
            });
        } else {
            setTimeout(applyBreakoutWithRetries, 500);
        }

        // Después de que todo cargue (incluidos otros JS)
        window.addEventListener('load', () => {
            setTimeout(() => {
                console.log('Lusso Gallery: Aplicando después de load completo');
                forceGalleryBreakout();
            }, 1000);
        });
        
        // Aplicar después de que Elementor termine (si existe)
        if (window.elementorFrontend) {
            document.addEventListener('elementor/frontend/init', () => {
                setTimeout(forceGalleryBreakout, 1500);
            });
        }
        
        // Reaplicar en resize
        window.addEventListener('resize', handleResize);
        
        // Aplicar periódicamente durante los primeros segundos (en caso de JS lentos)
        let attempts = 0;
        const periodicCheck = setInterval(() => {
            attempts++;
            forceGalleryBreakout();
            
            if (attempts >= 5) {
                clearInterval(periodicCheck);
                console.log('Lusso Gallery: Finalizadas aplicaciones periódicas');
            }
        }, 2000);
    }

    // Iniciar
    init();

})();
<?php
/**
 * Renderiza una galería Swiper con todas las imágenes disponibles.
 * @param array $images Array de URLs de imágenes
 * @param string $context 'card' o 'detail' para adaptar el tamaño
 */
function render_gallery($images, $context = 'card') {
    if (empty($images)) {
        echo '<div class="gallery-placeholder" style="height:220px;background:#f2f2f2;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#888;">Imagen no disponible</div>';
        return;
    }
    $height = ($context === 'detail') ? '340px' : '220px';
    $radius = ($context === 'detail') ? '12px' : '8px';
    ?>
    <div class="swiper gallery-swiper" style="width:100%;height:<?php echo $height; ?>;border-radius:<?php echo $radius; ?>;overflow:hidden;">
        <div class="swiper-wrapper">
            <?php foreach($images as $img): ?>
                <div class="swiper-slide">
                    <img src="<?php echo esc_url($img); ?>" alt="Imagen propiedad" style="width:100%;height:<?php echo $height; ?>;object-fit:cover;border-radius:<?php echo $radius; ?>;">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(count($images) > 1): ?>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var swipers = document.querySelectorAll('.gallery-swiper');
        swipers.forEach(function(swiperEl){
            var slides = swiperEl.querySelectorAll('.swiper-slide');
            var loopMode = slides.length > 1;
            new Swiper(swiperEl, {
                loop: loopMode,
                pagination: { el: swiperEl.querySelector('.swiper-pagination'), clickable: true },
                navigation: {
                    nextEl: swiperEl.querySelector('.swiper-button-next'),
                    prevEl: swiperEl.querySelector('.swiper-button-prev')
                },
                slidesPerView: 1,
                spaceBetween: 0
            });
        });
    });
    </script>
    <?php
}

// assets/js/swiper-init.js
// Inicializa Swiper de forma robusta para todas las galerías .property-gallery.swiper

document.addEventListener('DOMContentLoaded', function () {
  if (typeof Swiper === 'undefined') {
    console.warn('Swiper JS no está cargado.');
    return;
  }

  document.querySelectorAll('.property-gallery.swiper').forEach(function (swiperEl, idx) {
    // Asegurar contenedor interno
    const wrapper = swiperEl.querySelector('.swiper-wrapper');
    if (!wrapper) {
      console.warn('Falta .swiper-wrapper dentro de', swiperEl);
      return;
    }

    // Asegurar nodos de UI dentro del contenedor
    let paginationEl = swiperEl.querySelector('.swiper-pagination');
    let nextBtn = swiperEl.querySelector('.swiper-button-next');
    let prevBtn = swiperEl.querySelector('.swiper-button-prev');

    if (!paginationEl) {
      paginationEl = document.createElement('div');
      paginationEl.className = 'swiper-pagination';
      swiperEl.appendChild(paginationEl);
    }
    if (!prevBtn) {
      prevBtn = document.createElement('div');
      prevBtn.className = 'swiper-button-prev';
      swiperEl.appendChild(prevBtn);
    }
    if (!nextBtn) {
      nextBtn = document.createElement('div');
      nextBtn.className = 'swiper-button-next';
      swiperEl.appendChild(nextBtn);
    }

    // Inicializar Swiper
    new Swiper(swiperEl, {
      loop: true,
      slidesPerView: 1,
      spaceBetween: 0,
      preloadImages: true,
      watchOverflow: true,
      navigation: {
        nextEl: nextBtn,
        prevEl: prevBtn,
      },
      pagination: {
        el: paginationEl,
        clickable: true,
      },
    });
  });
});

/* LUSSO — Cambia a estado scrolleado con body.lg-scrolled */
(() => {
  const THRESHOLD = 50; // px
  const body = document.body;

  const apply = () => {
    if (window.scrollY > THRESHOLD) body.classList.add('lg-scrolled');
    else body.classList.remove('lg-scrolled');
  };

  document.addEventListener('DOMContentLoaded', apply);
  window.addEventListener('load', apply);

  let ticking = false;
  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => { apply(); ticking = false; });
      ticking = true;
    }
  }, { passive: true });
})();

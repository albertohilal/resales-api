// Hotfix JS para asegurar que el CTA esté presente en cada tarjeta
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.lr-card').forEach(function(card){
      var cta = card.querySelector('.lr-card__cta');
      if (!cta) {
        // Si no existe, busca href válido y añade el CTA
        var href = card.getAttribute('data-details-url') || '';
        if (href) {
          var a = document.createElement('a');
          a.className = 'lr-card__cta';
          a.setAttribute('data-testid', 'property-cta');
          a.setAttribute('href', href);
          a.setAttribute('rel', 'bookmark');
          a.textContent = 'Ver detalles';
          card.querySelector('.lr-card__body')?.appendChild(a);
        }
      }
    });
  });
})();

// JS para manejar el submit AJAX del formulario de filtros y renderizar resultados sin recargar
jQuery(function($){
    const FORM_SEL = 'form#lusso-filters'; // <-- AJUSTA si tu <form> usa otra id/clase
    const BTN_SEL  = `${FORM_SEL} [data-role="search"], ${FORM_SEL} button[type="submit"], ${FORM_SEL} input[type="submit"]`;

    // 0) Blindaje: nunca serializar "area" en submits nativos
    const $form = $(FORM_SEL);
    if ($form.length) {
        $form.find('select[name="area"]').each(function(){
            // Removemos name para que no salga como ?area=...
            $(this).attr('data-name-backup','area').removeAttr('name');
        });
    }

    // 1) Quitar posibles listeners previos
    $(document).off('submit.lusso', FORM_SEL).off('click.lusso', BTN_SEL);

    // 2) Rebind del click del botón → dispara nuestro submit (sin submit nativo)
    $(document).on('click.lusso', BTN_SEL, function(e){
        e.preventDefault(); e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        $(this).closest(FORM_SEL).trigger('submit');
        return false;
    });

    // 3) Submit controlado: construir URL limpia
    $(document).on('submit.lusso', FORM_SEL, function(e){
        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();

        const $f = $(this);
        // Asegurar que el botón no provoque submit nativo
        $f.find('button[type="submit"], input[type="submit"]').attr('type','button');

        const location = $f.find('select[name="location"]').val()?.trim() || '';
        const type     = $f.find('select[name="type"]').val()?.trim() || '';
        const bedrooms = $f.find('select[name="bedrooms"]').val()?.trim() || '';

        const qs = new URLSearchParams();
        if (location) qs.set('location', location);
        if (type)     qs.set('type', type);
        if (bedrooms) qs.set('bedrooms', bedrooms);

        const url = window.location.pathname + (qs.toString() ? '?' + qs.toString() : '');
        window.location.assign(url);
        return false;
    });
});

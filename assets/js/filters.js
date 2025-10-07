/**
 * LUSSO Filters (GET-only, sin AJAX)
 * - Envía el formulario por GET al cambiar filtros.
 * - Limpia parámetros vacíos y valida valores básicos.
 * - Preserva parámetros ajenos al form (ej: utm_*).
 * - Evita doble envío y hace scroll al grid de resultados.
 *
 * Requisitos:
 * - Form con class="lusso-filters"
 * - Selects: name="location" | name="bedrooms" | name="type"
 * - input[type=hidden] name="newdevs" (opcional, lo respeta)
 * - El shortcode imprime el grid con class="lusso-grid"
 */
jQuery(function ($) {
  'use strict';

  /** -----------------------------------------
   * Utilidades
   * ----------------------------------------- */
  const qs = new URLSearchParams(window.location.search);

  const dom = {
    $form: $('.lusso-filters'),
  };

  if (!dom.$form.length) return;

  // Mapa de campos que maneja el form
  const FIELDS = ['location', 'bedrooms', 'type', 'newdevs'];

  // Selectores de form
  const $location = dom.$form.find('select[name="location"]');
  const $beds     = dom.$form.find('select[name="bedrooms"]');
  const $type     = dom.$form.find('select[name="type"]');
  const $newdevs  = dom.$form.find('input[name="newdevs"], select[name="newdevs"]'); // por si lo expones como select

  // Patrón V6 para OptionValue de property types (p.ej. 2-2, 1-6, 4-1)
  const TYPE_PATTERN = /^\d+-\d+$/;

  // Evita dobles envíos
  let submitting = false;
  const DEBOUNCE_MS = 80;

  // Helper: obtiene valor “limpio” de un control
  function valOf($el) {
    if (!$el.length) return '';
    const v = ($el.val() || '').toString().trim();
    return v;
  }

  // Helper: valida “type” según patrón V6
  function isValidType(v) {
    if (!v) return true; // vacío se permite
    return TYPE_PATTERN.test(v);
  }

  // Helper: bedrooms numérico entero positivo
  function isValidBedrooms(v) {
    if (!v) return true;
    return /^\d+$/.test(v);
  }

  // Helper: deshabilita selects vacíos para no generar ?param=
  function disableEmptyFields($form) {
    $form.find('select').each(function () {
      const v = ( $(this).val() || '' ).toString().trim();
      if (v === '') $(this).prop('disabled', true);
    });
  }

  // Helper: restaura cualquier select deshabilitado (navegador atrás)
  function enableAllFields($form) {
    $form.find('select:disabled,input:disabled').prop('disabled', false);
  }

  // Helper: preserva params ajenos al form
  function appendForeignParams($form) {
    // Construye un Set con nombres que maneja el form
    const FORM_FIELD_NAMES = new Set(
      $form
        .find('input[name],select[name],textarea[name]')
        .map(function () { return this.name; })
        .get()
    );

    // Agrega campos de la URL que NO estén en el form
    qs.forEach((value, key) => {
      if (!FORM_FIELD_NAMES.has(key)) {
        // crear un hidden dinámico
        const $hidden = $('<input>', {
          type: 'hidden',
          name: key,
          value: value
        });
        $form.append($hidden);
      }
    });
  }

  // Helper: hace scroll hacia el grid de resultados si existe
  function scrollToResults() {
    const $grid = $('.lusso-grid').first();
    if ($grid.length) {
      const top = $grid.offset().top - 80; // margen por header fijo
      window.scrollTo({ top: top, behavior: 'smooth' });
    }
  }

  // Helper: submit con seguridad (debounce + validaciones)
  function safeSubmit() {
    if (submitting) return;
    submitting = true;
    setTimeout(() => (submitting = false), DEBOUNCE_MS);

    // Validaciones suaves
    const vType = valOf($type);
    const vBeds = valOf($beds);

    if (!isValidType(vType)) {
      // Si no es válido, lo vaciamos para evitar enviar basura
      $type.val('');
    }
    if (!isValidBedrooms(vBeds)) {
      $beds.val('');
    }

    // Deshabilitar vacíos para limpiar URL
    disableEmptyFields(dom.$form);

    // Preservar parámetros externos (utm, ref, etc.)
    appendForeignParams(dom.$form);

    // En sandbox solemos querer p_sandbox=true; producción no.
    // Si alguna vez agregás el switch en Settings, lo puedes inyectar aquí leyendo una variable global.
    // Por ahora, no agregamos parámetros extra.

    dom.$form.trigger('submit');
  }

  /** -----------------------------------------
   * Eventos
   * ----------------------------------------- */

  // Enviar automáticamente al cambiar cualquiera de los selects
  dom.$form.on('change', 'select[name="location"], select[name="bedrooms"], select[name="type"]', function () {
    safeSubmit();
  });

  // Limpieza y preservación al enviar manualmente (por botón “Search”)
  dom.$form.on('submit', function () {
    // Si el submit no viene de safeSubmit(), aún limpiamos
    disableEmptyFields(dom.$form);
    appendForeignParams(dom.$form);
    // dejamos continuar el envío
  });

  // Restaurar campos deshabilitados al volver con botón “Atrás”
  $(window).on('pageshow', function (e) {
    // pageshow con persisted=true indica bfcache; restauramos controles
    enableAllFields(dom.$form);
  });

  /** -----------------------------------------
   * Mejora UX tras carga si hay resultados
   * ----------------------------------------- */
  // Si hay querystring (búsqueda) y existe el grid, hacemos un scroll suave tras un tiempito
  if (window.location.search && $('.lusso-grid').length) {
    // pequeño delay para evitar saltos si aún está pintando
    setTimeout(scrollToResults, 120);
  }

  /** -----------------------------------------
   * Sincronización defensiva (opcional)
   * - Si el usuario borró a mano un param en la URL y vuelve,
   *   mantenemos el valor visual de los selects (servido por PHP).
   * - Si querés forzar que los selects reflejen exactamente la URL,
   *   descomenta el bloque de abajo.
   * ----------------------------------------- */

  // // Forzar que los selects reflejen parámetros actuales de la URL:
  // if ($location.length && qs.has('location')) $location.val(qs.get('location'));
  // if ($beds.length && qs.has('bedrooms'))    $beds.val(qs.get('bedrooms'));
  // if ($type.length && qs.has('type'))        $type.val(qs.get('type'));
  // if ($newdevs.length && qs.has('newdevs'))  $newdevs.val(qs.get('newdevs'));

  /** -----------------------------------------
   * Depuración (silenciar en producción si quieres)
   * ----------------------------------------- */
  // console.debug('[LUSSO] filters.js cargado. GET=', Object.fromEntries(qs.entries()));
});

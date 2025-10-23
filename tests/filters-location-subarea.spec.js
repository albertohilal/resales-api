const { test, expect } = require('@playwright/test');

test('Filtro Location y Subarea funciona correctamente', async ({ page }) => {
  // Ir a la página de propiedades
  await page.goto('https://lussogroup.es/properties/');

  // Selecciona Location "Benalmadena"
    await page.selectOption('select[name="location"]', 'Benalmadena');
    // Forzar el evento change y esperar el renderizado de checkboxes
    await page.waitForFunction(() => {
      const container = document.querySelector('#subarea-checkboxes');
      return container && container.querySelectorAll('input[type="checkbox"]').length > 1;
    }, { timeout: 30000 });

  // Espera a que los checkboxes de subárea se rendericen
    await page.waitForSelector('#subarea-checkboxes input[type="checkbox"]');

  // Verifica que las opciones "Higueron" y "Torrequebrada" están disponibles
  const subareaLabels = await page.$$eval('#subarea-checkboxes label', labels => labels.map(l => l.textContent));
  expect(subareaLabels).toContain('Higueron');
  expect(subareaLabels).toContain('Torrequebrada');

  // Selecciona múltiples sub-áreas
  await page.check('#subarea-checkboxes input[value="Higueron"]');
  await page.check('#subarea-checkboxes input[value="Torrequebrada"]');

  // Envía el formulario
  await page.click('button.lusso-search-button');

  // Espera a que la página recargue y muestre resultados
  await page.waitForTimeout(2000); // Mejorar con un selector específico si lo tienes

  // Verifica que los resultados contienen ambas sub-áreas seleccionadas
  const pageContent = await page.content();
  expect(pageContent).toContain('Higueron');
  expect(pageContent).toContain('Torrequebrada');
});

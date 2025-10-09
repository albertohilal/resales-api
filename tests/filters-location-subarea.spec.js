const { test, expect } = require('@playwright/test');

test('Filtro Location y Subarea funciona correctamente', async ({ page }) => {
  // Ir a la página de propiedades
  await page.goto('https://lussogroup.es/properties/');

  // Selecciona Location "Benalmadena"
  await page.selectOption('select[name="location"]', 'Benalmadena');

  // Espera a que el filtro de Subarea se repueble y se habilite
  await page.waitForSelector('select[name="zona"]:not([disabled])');

  // Verifica que la opción "Higueron" está disponible en Subarea
  const subareaOptions = await page.$$eval('select[name="zona"] option', opts => opts.map(o => o.textContent));
  expect(subareaOptions).toContain('Higueron');

  // Selecciona Subarea "Higueron"
  await page.selectOption('select[name="zona"]', 'Higueron');

  // Espera a que la página recargue y muestre resultados
  await page.waitForTimeout(2000); // Mejorar con un selector específico si lo tienes

  // Verifica que los resultados contienen la subárea seleccionada
  const pageContent = await page.content();
  expect(pageContent).toContain('Higueron');
});

import { test, expect } from '@playwright/test';

test('Filtro de 2 dormitorios en New Developments', async ({ page }) => {
  // Abre la página de propiedades
  await page.goto('https://lussogroup.es/properties');

  // Selecciona el filtro de 2 dormitorios (ajusta el selector si es necesario)
    await page.selectOption('select[name="bedrooms"]', '2');
  await page.click('button[type="submit"]');

  // Espera la llamada al endpoint y captura la respuesta JSON
  const [response] = await Promise.all([
    page.waitForResponse(resp =>
      resp.url().includes('/wp-json/resales/v6/search') && resp.status() === 200
    ),
    // El submit ya se hizo arriba
  ]);
  const json = await response.json();

  // Verifica que todos los resultados tengan un rango que incluya “2” en Bedrooms
  expect(json.Results.length).toBeGreaterThan(0);
  for (const obj of json.Results) {
    const bedrooms = obj.Bedrooms?.toString() || '';
    expect(
      bedrooms.includes('2') ||
      bedrooms.match(/\b2\b/) ||
      bedrooms.match(/1\s*-\s*2|2\s*-\s*3/)
    ).toBeTruthy();
  }

  // Verifica en las tarjetas del DOM que el texto contenga un rango compatible
  const cards = await page.locator('.lr-card').allTextContents();
  const found = cards.filter(card =>
    /1\s*-\s*2 dormitorios|2\s*-\s*3 dormitorios|2 dormitorios/.test(card)
  );
  expect(found.length).toBeGreaterThan(0);

  // Asegura que haya al menos una tarjeta visible
  await expect(page.locator('.lr-card')).toBeVisible();
});

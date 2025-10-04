import { test, expect } from '@playwright/test';

test.describe('Filtros en lussogroup.es/properties', () => {
  test('Carga inicial y filtro por ubicación', async ({ page }) => {
    await page.goto('https://lussogroup.es/properties');
    // Espera a que cargue el grid de propiedades
    await expect(page.locator('.lusso-grid')).toBeVisible();
    // Aplica filtro de ubicación (ajusta el selector según el HTML real)
    await page.selectOption('select[name="location"]', { label: 'Sotogrande' });
    await page.click('button[type="submit"]');
    // Espera a que se actualicen los resultados
    await expect(page.locator('.lusso-grid')).toBeVisible();
    // Verifica que los resultados correspondan a la ubicación
    const cards = await page.locator('.lr-card').allTextContents();
    expect(cards.some(card => card.includes('Sotogrande'))).toBeTruthy();
  });

  test('Filtro por tipo de propiedad', async ({ page }) => {
    await page.goto('https://lussogroup.es/properties');
    await expect(page.locator('.lusso-grid')).toBeVisible();
    await page.selectOption('select[name="type"]', { label: 'Villa' });
    await page.click('button[type="submit"]');
    await expect(page.locator('.lusso-grid')).toBeVisible();
    const cards = await page.locator('.lr-card').allTextContents();
    expect(cards.some(card => card.includes('Villa'))).toBeTruthy();
  });

  test('Filtro por número de dormitorios', async ({ page }) => {
    await page.goto('https://lussogroup.es/properties');
    await expect(page.locator('.lusso-grid')).toBeVisible();
    await page.selectOption('select[name="bedrooms"]', { label: '3' });
    await page.click('button[type="submit"]');
    await expect(page.locator('.lusso-grid')).toBeVisible();
    const cards = await page.locator('.lr-card').allTextContents();
    expect(cards.some(card => card.includes('3'))).toBeTruthy();
  });
});

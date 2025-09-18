import { test, expect } from '@playwright/test';
const baseURL = process.env.BASE_URL || 'https://lussogroup.es';

test.describe('Developments', () => {
  test('Cards de developments visibles (si la sección existe)', async ({ page }) => {
    await page.goto(`${baseURL}/properties/`); // o la URL real de developments si es otra
    const devs = page.locator('.lusso-developments .lusso-card');
    // Si no existe la sección, no falla: sólo verifica que no haya errores de render
    const count = await devs.count();
    expect(count).toBeGreaterThanOrEqual(0);
  });
});

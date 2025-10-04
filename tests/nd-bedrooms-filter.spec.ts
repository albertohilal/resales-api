import { test, expect } from '@playwright/test';

test.describe('Filtro de dormitorios ND', () => {
  const url = 'https://lussogroup.es/properties?location=Sotogrande';

  for (const beds of [2, 3, 4, 5]) {
    test(`Filtro ${beds}+ solo muestra propiedades con >= ${beds} dormitorios`, async ({ page }) => {
      await page.goto(`${url}&bedrooms=${beds}`);
      await expect(page.locator('.lusso-grid')).toBeVisible();
      const cards = await page.locator('.lr-card').all();
      for (const card of cards) {
  const bedsText = await card.locator('.fa-bed').textContent();
  const numBeds = bedsText ? parseInt(bedsText.trim()) : 0;
  expect(numBeds).toBeGreaterThanOrEqual(beds);
      }
    });
  }
});

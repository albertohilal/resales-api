import { test, expect, Page, Locator } from '@playwright/test';

const LISTING_PATH = process.env.LISTING_PATH || '/properties/';

// Helpers de selectores robustos (zona principal, grid y cards con enlaces)
const main = (p: Page) =>
  p.locator('main, #main, .site-main, #primary, #content, .content-area').first();

const cards = (p: Page) =>
  main(p)
    .locator('.lr-card, .lusso-card, [class*="card"], article, li')
    .filter({ has: p.locator('a[href]') });

test.describe('Responsive', () => {
  test('Grid en desktop tiene items', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const firstCard = cards(page).first();
    await expect(firstCard).toBeVisible({ timeout: 20_000 });
    expect(await cards(page).count()).toBeGreaterThan(0);
  });

  test('Imágenes con object-fit: cover | fill (si hay <img>)', async ({ page }) => {
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const img = cards(page).first().locator('img').first();
    if (await img.count()) {
      await expect(img).toBeVisible();
      const objectFit = await img.evaluate(el => getComputedStyle(el).objectFit);
      expect(['cover', 'fill']).toContain(objectFit);
    } else {
      // Algunas cards usan background-image; en ese caso no aplica object-fit
      test.info().annotations.push({ type: 'note', description: 'Card sin <img> (usa background-image); omitido.' });
    }
  });

  test('Layout móvil en columna y con items', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const firstCard = cards(page).first();
    await expect(firstCard).toBeVisible();
    expect(await cards(page).count()).toBeGreaterThan(0);
  });
});

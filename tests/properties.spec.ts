import { test, expect, request as pwRequest, Page, Locator } from '@playwright/test';

const LISTING_PATH = process.env.LISTING_PATH || '/properties/';
const BASIC_USER   = process.env.BASIC_USER || '';
const BASIC_PASS   = process.env.BASIC_PASS || '';

/** Helpers de selectores robustos (grid principal y cards con enlaces) */
const main = (p: Page) =>
  p.locator('main, #main, .site-main, #primary, #content, .content-area').first();

const cards = (p: Page) =>
  main(p)
    .locator('.lr-card, .lusso-card, [class*="card"], article, li')
    .filter({ has: p.locator('a[href]') });

/** Devuelve URL absoluta a partir del href encontrado en la card */
function absoluteFrom(page: Page, href: string) {
  return href.startsWith('http') ? href : new URL(href, page.url()).toString();
}

test.describe('Properties', () => {
  test('Grid/listado muestra al menos una tarjeta', async ({ page }) => {
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const firstCard = cards(page).first();
    await expect(firstCard).toBeVisible({ timeout: 20_000 });

    const count = await cards(page).count();
    expect(count).toBeGreaterThan(0);
  });

  test('Tarjeta tiene imagen/título y un CTA visible', async ({ page }) => {
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const card = cards(page).first();
    await expect(card).toBeVisible({ timeout: 20_000 });

    // Imagen (o contenedor con background)
    const img = card.locator('img, [style*="background"][style*="url("]').first();
    await expect(img).toBeVisible();

    // Título visible
    await expect(
      card.locator('h2, h3, [aria-label], .lr-card__title, .lusso-card__title').first()
    ).toBeVisible();

    // CTA principal: usar testId estable, con fallback a cualquier <a>
    let cta: Locator = card.getByTestId('property-cta').first();
    if (!(await cta.count())) cta = card.locator('a[href]').first();

    await expect(cta).toBeVisible();
  });

  test('CTA navega al detalle y responde 200', async ({ page }) => {
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const card = cards(page).first();

    // CTA por testId; fallback a <a> si no existe data-testid
    let cta: Locator = card.getByTestId('property-cta').first();
    if (!(await cta.count())) cta = card.locator('a[href]').first();

    const href = await cta.getAttribute('href');
    expect(href).toBeTruthy();

    // Verificar que el destino responde 200 (respetando Basic Auth si aplica)
    const api = await pwRequest.newContext({
      httpCredentials:
        BASIC_USER && BASIC_PASS ? { username: BASIC_USER, password: BASIC_PASS } : undefined,
    });
    const absolute = absoluteFrom(page, href!);
    const resp = await api.get(absolute);
    expect(resp.status()).toBe(200);

    // Navegar y validar que la ficha cargó un heading visible
    await cta.click();
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('h1, h2, [role="heading"]').first()).toBeVisible();
  });

  test('Card muestra galería Swiper con varias imágenes si existen', async ({ page }) => {
    await page.goto(LISTING_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    const card = cards(page).first();
    await expect(card).toBeVisible({ timeout: 20_000 });

    // Buscar el contenedor Swiper en la card
    const swiper = card.locator('.swiper, .gallery-swiper, .lr-card-swiper').first();
    await expect(swiper).toBeVisible();

    // Contar slides
    const slides = swiper.locator('.swiper-slide');
    const slideCount = await slides.count();
    expect(slideCount).toBeGreaterThan(1); // Debe haber más de una imagen si la galería está activa
  });
});

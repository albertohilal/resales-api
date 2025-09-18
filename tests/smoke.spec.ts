import { test, expect, request } from '@playwright/test';

const BASE_URL   = process.env.BASE_URL || 'https://lussogroup.es';
const BASIC_USER = process.env.BASIC_USER || '';
const BASIC_PASS = process.env.BASIC_PASS || '';

test.describe('Smoke', () => {
  test('Home responde 200 y muestra encabezado', async ({ page }) => {
    const api = await request.newContext({
      baseURL: BASE_URL,
      httpCredentials: BASIC_USER && BASIC_PASS ? { username: BASIC_USER, password: BASIC_PASS } : undefined,
    });
    const resp = await api.get('/');
    expect(resp.status()).toBe(200);

    await page.goto('/');
    const title = await page.title();
    expect(title.toLowerCase()).not.toContain('authorization required');
    await expect(page.locator('h1, h2').first()).toBeVisible();
  });

  test('CSS principal responde 200 (href real, con fallback)', async ({ page }) => {
    await page.goto('/');
    const links = page.locator('link[rel="stylesheet"]');
    const hrefs = await links.evaluateAll((nodes: HTMLLinkElement[]) => nodes.map(n => n.href).filter(Boolean));
    const href = hrefs.find(u => /lusso|resales|theme|assets/i.test(u)) || `${BASE_URL}/wp-content/themes/lusso/assets/css/lusso-resales.css`;

    const api = await request.newContext({
      httpCredentials: BASIC_USER && BASIC_PASS ? { username: BASIC_USER, password: BASIC_PASS } : undefined,
    });
    const cssResp = await api.get(href);
    expect(cssResp.status(), `CSS debe responder 200: ${href}`).toBe(200);
  });

  test('Logo responde 200 (si existe)', async ({ page }) => {
    await page.goto('/');
    const img = page.locator('img[src*="logo" i]').first();
    if (await img.count()) {
      const src = await img.getAttribute('src');
      const absolute = src?.startsWith('http') ? src : new URL(src || '', page.url()).toString();
      const api = await request.newContext({
        httpCredentials: BASIC_USER && BASIC_PASS ? { username: BASIC_USER, password: BASIC_PASS } : undefined,
      });
      const r = await api.get(absolute);
      expect(r.status(), `Logo debe responder 200: ${absolute}`).toBe(200);
    } else {
      test.info().annotations.push({ type: 'note', description: 'No se encontró <img> de logo' });
    }
  });
});

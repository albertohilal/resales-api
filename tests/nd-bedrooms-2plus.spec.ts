import { test, expect } from '@playwright/test';

test('Filtro “5+” dormitorios — esperar tarjetas luego del filtro (AJAX o DOM dinámico)', async ({ page }) => {
  await page.goto('https://lussogroup.es/properties');

  const sel = page.locator('select[name="bedrooms"]');
  await expect(sel).toBeVisible({ timeout: 5000 });

  const opts = sel.locator('option');
  const countOpts = await opts.count();
  expect(countOpts).toBeGreaterThanOrEqual(2);

  const opt5plus = opts.filter({ hasText: /5\+/ }).first();
  const val5plus = await opt5plus.getAttribute('value');
  if (!val5plus) {
    throw new Error('No se encontró la opción “5+”');
  }

  // Espera que al menos una tarjeta exista antes de aplicar filtro (para tener base de comparación)
  await page.waitForSelector('.lr-card', { timeout: 5000 });

  // Tomar el texto inicial del primer card (para comparar después)
  const firstBefore = await page.locator('.lr-card').first().innerText();

  // Aplicar filtro
  await sel.selectOption(val5plus);

  // Esperar que cambie la tarjeta: usar waitForFunction
  await page.waitForFunction(
    (oldText) => {
      const el = document.querySelector('.lr-card');
      return el && el.textContent !== oldText;
    },
    firstBefore,
    { timeout: 10000 }
  );

  // Ahora verificar que haya tarjetas
  const cards = page.locator('.lr-card');
  const cardCount = await cards.count();
  expect(cardCount).toBeGreaterThan(0);

  for (let i = 0; i < cardCount; i++) {
    const text = await cards.nth(i).innerText();
    expect(text).not.toMatch(/\b1\s+dormitorio\b/);
  }
});

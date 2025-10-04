import { test, expect } from '@playwright/test';

test('Filtro de dormitorios “2+” solo New Developments — actualiza tarjetas por AJAX', async ({ page }) => {
  // 1. Abrir la página de propiedades
  await page.goto('https://lussogroup.es/properties');

  // 2. Localizar el select de dormitorios y verificar que esté visible
  const sel = page.locator('select[name="bedrooms"]');
  await expect(sel).toBeVisible({ timeout: 5000 });

  // 3. Verificar que tiene opciones
  const opts = sel.locator('option');
  const optCount = await opts.count();
  expect(optCount).toBeGreaterThan(1);

  // 4. Buscar la opción “2+” dinámicamente
  const opt2plus = opts.filter({ hasText: /2\+/ }).first();
  const val2plus = await opt2plus.getAttribute('value');
  expect(val2plus).toBeTruthy();

  // 5. Esperar que haya al menos una tarjeta antes de aplicar el filtro
  await page.waitForSelector('.lr-card', { timeout: 5000 });
  const firstCardBefore = await page.locator('.lr-card').first().innerText();

  // 6. Seleccionar la opción “2+”
  await sel.selectOption(val2plus!);

  // 7. Esperar que cambie el contenido del primer card (AJAX)
  await page.waitForFunction(
    (oldText) => {
      const el = document.querySelector('.lr-card');
      return el && el.textContent !== oldText;
    },
    firstCardBefore,
    { timeout: 10000 }
  );

  // 8. Verificar que hay al menos una tarjeta
  const cards = page.locator('.lr-card');
  const cardCount = await cards.count();
  expect(cardCount).toBeGreaterThan(0);

  // 9. Verificar que ninguna tarjeta muestre “1 dormitorio”
  for (let i = 0; i < cardCount; i++) {
    const text = await cards.nth(i).innerText();
    expect(text).not.toMatch(/\b1\s+dormitorio\b/);
  }
});

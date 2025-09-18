import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const axe = (page: any) =>
  new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa'])
    .disableRules(['html-has-lang']); // Quitar cuando <html> tenga lang

test.describe('Accesibilidad', () => {
  test('Home sin violaciones serias/criticas', async ({ page }) => {
    await page.goto('/');
    const results = await axe(page).analyze();
    const serious = results.violations.filter(v => ['serious', 'critical'].includes(v.impact || ''));
    if (serious.length) console.error('Violaciones en /:', serious);
    expect(serious.length).toBe(0);
  });

  test('/properties sin violaciones serias/criticas', async ({ page }) => {
    await page.goto('/properties/');
    const results = await axe(page).analyze();
    const serious = results.violations.filter(v => ['serious', 'critical'].includes(v.impact || ''));
    if (serious.length) console.error('Violaciones en /properties:', serious);
    expect(serious.length).toBe(0);
  });
});

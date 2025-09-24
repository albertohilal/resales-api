import { test, expect } from '@playwright/test';

// Testea el endpoint de ping
// /wp-admin/admin-ajax.php?action=lusso_ping

test('AJAX ping responde success', async ({ request, baseURL }) => {
  const res = await request.get(`${baseURL}/wp-admin/admin-ajax.php?action=lusso_ping`);
  expect(res.status()).toBe(200);
  const json = await res.json();
  expect(json).toMatchObject({ success: true, data: { ok: 1 } });
});

test('AJAX debug_locations responde success y estructura', async ({ request, baseURL }) => {
  const res = await request.get(`${baseURL}/wp-admin/admin-ajax.php?action=lusso_debug_locations&lang=2`);
  expect(res.status()).toBe(200);
  const json = await res.json();
  expect(json.success).toBe(true);
  expect(json.data).toHaveProperty('areas');
  // Al menos un área y un location
  const areas = Object.values(json.data.areas || {});
  expect(areas.length).toBeGreaterThan(0);
  expect(Array.isArray(areas[0])).toBe(true);
});

test('AJAX debug_types responde success y estructura', async ({ request, baseURL }) => {
  const res = await request.get(`${baseURL}/wp-admin/admin-ajax.php?action=lusso_debug_types&lang=2`);
  expect(res.status()).toBe(200);
  const json = await res.json();
  expect(json.success).toBe(true);
  expect(Array.isArray(json.data)).toBe(true);
  if (json.data.length > 0) {
    expect(json.data[0]).toHaveProperty('label');
    expect(json.data[0]).toHaveProperty('value');
  }
});

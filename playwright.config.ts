import 'dotenv/config';
import { defineConfig, devices } from '@playwright/test';

const BASE_URL   = process.env.BASE_URL || 'https://lussogroup.es';
const BASIC_USER = process.env.BASIC_USER || '';
const BASIC_PASS = process.env.BASIC_PASS || '';

export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  expect: { timeout: 5_000 },
  reporter: [
    ['list'],
    ['html', { open: 'never', host: '127.0.0.1', port: 9324 }],
  ],
  use: {
    baseURL: BASE_URL,
    httpCredentials: BASIC_USER && BASIC_PASS
      ? { username: BASIC_USER, password: BASIC_PASS }
      : undefined,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});

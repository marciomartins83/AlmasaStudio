import { test as setup, expect } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const authFile = path.join(__dirname, '../fixtures/.auth/user.json');

setup('authenticate', async ({ page }) => {
  setup.setTimeout(120000); // 2 min para warm up do cache
  fs.mkdirSync(path.dirname(authFile), { recursive: true });

  // Warm up: primeira requisição pode ser lenta (cache build)
  await page.goto('/login', { timeout: 60000, waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'marcioramos1983@gmail.com');
  await page.fill('input[name="password"]', '123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle', { timeout: 60000 });

  const url = page.url();
  if (url.includes('/login')) {
    throw new Error('Login FAILED — still on /login after submit');
  }

  console.log(`Login OK — redirected to: ${url}`);
  await page.context().storageState({ path: authFile });
});

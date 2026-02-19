import { test as setup, expect } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const authFile = path.join(__dirname, '../fixtures/.auth/user.json');

setup('authenticate', async ({ page }) => {
  // Ensure directory exists
  fs.mkdirSync(path.dirname(authFile), { recursive: true });

  await page.goto('/login');
  await page.fill('input[name="email"]', 'marcioramos1983@gmail.com');
  await page.fill('input[name="password"]', '123');
  await page.click('button[type="submit"]');

  await page.waitForURL('**/dashboard');
  await expect(page.locator('body')).toContainText('Painel');

  await page.context().storageState({ path: authFile });
});

import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test('login page is accessible', async ({ browser }) => {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto('/login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    await context.close();
  });

  test('login with invalid credentials shows error', async ({ browser }) => {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto('/login');
    await page.fill('input[name="email"]', 'invalid@test.com');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    // Should stay on login page with error
    await expect(page).toHaveURL(/\/login/);
    await context.close();
  });

  test('login page has CSRF protection', async ({ browser }) => {
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    await page.goto('/login');
    // CSRF token is present in the form (hidden input or inline value)
    const csrfField = page.locator('input[name="_csrf_token"]');
    await expect(csrfField).toBeAttached();
    await context.close();
  });
});

test.describe('Dashboard (authenticated)', () => {
  test('dashboard loads successfully', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('body')).toContainText('Painel');
  });

  test('dashboard shows user email', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page.locator('body')).toContainText('marcioramos1983@gmail.com');
  });

  test('navigation menu is present', async ({ page }) => {
    await page.goto('/dashboard');
    // Check that main navigation links exist
    const body = page.locator('body');
    await expect(body).toContainText('Imóveis');
    await expect(body).toContainText('Contratos');
    await expect(body).toContainText('Pessoas');
  });

  test('logout works', async ({ browser }) => {
    // Use fresh context with auth
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      storageState: 'e2e/fixtures/.auth/user.json',
    });
    const page = await context.newPage();
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/dashboard/);
    await page.goto('/logout');
    await page.waitForLoadState('networkidle');
    // After logout, going to dashboard should redirect to login
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveURL(/\/login/);
    await context.close();
  });
});

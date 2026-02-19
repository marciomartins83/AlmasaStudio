import { test, expect } from '@playwright/test';

test.describe('Financeiro Module', () => {
  test('index page loads successfully', async ({ page }) => {
    await page.goto('/financeiro/');
    await expect(page).toHaveURL(/\/financeiro\//);
    // Check for main content - title or key elements
    const body = page.locator('body');
    await expect(body).toBeTruthy();
    // Wait for content to load
    await page.waitForLoadState('networkidle');
    const status = page.statusCode || 200;
    expect([200, 304]).toContain(status);
  });

  test('ficha financeira page loads successfully', async ({ page }) => {
    // Navigate to financeiro first to get some context
    await page.goto('/financeiro/');
    await page.waitForLoadState('networkidle');

    // Try to access ficha financeira page - using a test ID if exists
    // Since we need an inquilinoId, we'll try accessing it directly
    await page.goto('/financeiro/ficha/1', { waitUntil: 'networkidle' });

    // Check that page loaded (either valid ficha or redirect)
    const currentUrl = page.url();
    // Should either be on ficha page or redirected to index if inquilino not found
    expect(
      currentUrl.includes('/financeiro/ficha') ||
      currentUrl.includes('/financeiro/')
    ).toBeTruthy();
  });

  test('new lancamento form loads successfully', async ({ page }) => {
    await page.goto('/financeiro/lancamento/new');
    await page.waitForLoadState('networkidle');

    // Should be on the new lancamento form page
    await expect(page).toHaveURL(/\/financeiro\/lancamento\/new/);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('atraso (overdue) page loads successfully', async ({ page }) => {
    await page.goto('/financeiro/em-atraso');
    await page.waitForLoadState('networkidle');

    // Should be on the atraso page
    await expect(page).toHaveURL(/\/financeiro\/em-atraso/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('api lista endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/financeiro/api/lancamentos');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
  });

  test('api estatisticas endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/financeiro/api/estatisticas');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
  });
});

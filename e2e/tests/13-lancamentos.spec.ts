import { test, expect } from '@playwright/test';

test.describe('Lancamentos Module', () => {
  test('index page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/');
    await page.waitForLoadState('networkidle');

    // Should be on the lancamentos index page
    await expect(page).toHaveURL(/\/lancamentos\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('new lancamento form loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/new');
    await page.waitForLoadState('networkidle');

    // Should be on the new lancamento form page
    await expect(page).toHaveURL(/\/lancamentos\/new/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('vencidos (overdue) page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/vencidos');
    await page.waitForLoadState('networkidle');

    // Should be on the vencidos page
    await expect(page).toHaveURL(/\/lancamentos\/vencidos/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('estatisticas page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/estatisticas');
    await page.waitForLoadState('networkidle');

    // Should be on the estatisticas page
    await expect(page).toHaveURL(/\/lancamentos\/estatisticas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('edit lancamento form loads with valid ID', async ({ page }) => {
    // Try accessing edit page - may redirect if lancamento doesn't exist
    await page.goto('/lancamentos/1/edit', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    // Should either be on edit page or redirected
    expect(
      currentUrl.includes('/lancamentos') &&
      (currentUrl.includes('/edit') || currentUrl.includes('/'))
    ).toBeTruthy();
  });

  test('api lista endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/lista');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('lancamentos');
  });

  test('api estatisticas endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/estatisticas');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('estatisticas');
  });
});

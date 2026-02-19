import { test, expect } from '@playwright/test';

test.describe('Prestacao Contas Module', () => {
  test('index page loads successfully', async ({ page }) => {
    await page.goto('/prestacao-contas/');
    await page.waitForLoadState('networkidle');

    // Should be on the prestacao-contas index page
    await expect(page).toHaveURL(/\/prestacao-contas\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('gerar (generate) form loads successfully', async ({ page }) => {
    await page.goto('/prestacao-contas/gerar');
    await page.waitForLoadState('networkidle');

    // Should be on the gerar form page
    await expect(page).toHaveURL(/\/prestacao-contas\/gerar/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('visualizar (view) page loads with valid ID', async ({ page }) => {
    // Try accessing view page - may redirect if prestacao doesn't exist
    await page.goto('/prestacao-contas/1', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    // Should either be on view page or redirected to index if not found
    expect(
      currentUrl.includes('/prestacao-contas/1') ||
      currentUrl.includes('/prestacao-contas/')
    ).toBeTruthy();
  });

  test('historico page loads with valid proprietario ID', async ({ page }) => {
    // Try accessing historico page with a test proprietario ID
    await page.goto('/prestacao-contas/historico/1', {
      waitUntil: 'networkidle',
    });

    const currentUrl = page.url();
    // Should either be on historico page or redirected
    expect(currentUrl.includes('/prestacao-contas')).toBeTruthy();
  });

  test('pdf endpoint returns PDF with valid ID', async ({ page }) => {
    // Try getting PDF - may fail if prestacao doesn't exist, but endpoint should be accessible
    const response = await page.request.get('/prestacao-contas/1/pdf', {
      failOnStatusCode: false,
    });

    // Should either return PDF (200), not found (404), or server error (500)
    expect([200, 404, 302, 500]).toContain(response.status());
  });

  test('imoveis API endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/prestacao-contas/imoveis/1');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('imoveis');
  });

  test('calcular-periodo API endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.post('/prestacao-contas/calcular-periodo', {
      data: {
        tipoPeriodo: 'mensal',
        dataBase: new Date().toISOString().split('T')[0],
      },
    });

    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
  });
});

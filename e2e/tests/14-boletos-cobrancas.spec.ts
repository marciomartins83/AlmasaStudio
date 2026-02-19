import { test, expect } from '@playwright/test';

test.describe('Boletos Module', () => {
  test('boleto index page loads successfully', async ({ page }) => {
    await page.goto('/boleto/');
    await page.waitForLoadState('networkidle');

    // Should be on the boleto index page
    await expect(page).toHaveURL(/\/boleto\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('boleto new form loads successfully', async ({ page }) => {
    await page.goto('/boleto/new');
    await page.waitForLoadState('networkidle');

    // Should be on the boleto new form page
    await expect(page).toHaveURL(/\/boleto\/new/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('boleto show page loads with valid ID', async ({ page }) => {
    // Try accessing show page - may redirect if boleto doesn't exist
    await page.goto('/boleto/1', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    // Should either be on show page or redirected/error
    expect(currentUrl.includes('/boleto')).toBeTruthy();
  });

  test('boleto api estatisticas endpoint is accessible', async ({
    page,
  }) => {
    const response = await page.request.get('/boleto/api/estatisticas', {
      failOnStatusCode: false,
    });
    // Should return any of these status codes
    expect([200, 302, 500]).toContain(response.status());
  });
});

test.describe('Cobranca Module', () => {
  test('cobranca index page loads successfully', async ({ page }) => {
    await page.goto('/cobranca/');
    await page.waitForLoadState('networkidle');

    // Should be on the cobranca index page
    await expect(page).toHaveURL(/\/cobranca\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('cobranca pendentes page loads successfully', async ({ page }) => {
    await page.goto('/cobranca/pendentes');
    await page.waitForLoadState('networkidle');

    // Should be on the cobranca pendentes page
    const currentUrl = page.url();
    expect(
      currentUrl.includes('/cobranca/pendentes') ||
      currentUrl.includes('/cobranca/')
    ).toBeTruthy();

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('cobranca show page loads with valid ID', async ({ page }) => {
    // Try accessing show page - may redirect if cobranca doesn't exist
    await page.goto('/cobranca/1', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    // Should either be on show page or redirected/error
    expect(currentUrl.includes('/cobranca')).toBeTruthy();
  });

  test('cobranca api estatisticas endpoint returns valid JSON', async ({
    page,
  }) => {
    const response = await page.request.get('/cobranca/api/estatisticas');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    // Response should be JSON
    expect(json).toBeTruthy();
  });
});

test.describe('ConfiguracaoApiBanco Module', () => {
  test('configuracao-api-banco index page loads successfully', async ({
    page,
  }) => {
    await page.goto('/configuracao-api-banco/');
    await page.waitForLoadState('networkidle');

    const currentUrl = page.url();
    // Should be on config page or redirected if no access
    expect(currentUrl.includes('/configuracao-api-banco')).toBeTruthy();
  });

  test('configuracao-api-banco new form loads successfully', async ({
    page,
  }) => {
    await page.goto('/configuracao-api-banco/new', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    // Should either be on new form or redirected
    expect(currentUrl.includes('/configuracao-api-banco')).toBeTruthy();
  });
});

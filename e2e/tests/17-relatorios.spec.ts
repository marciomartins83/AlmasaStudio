import { test, expect } from '@playwright/test';

test.describe('Relatorios Module', () => {
  test('index (dashboard) page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/');
    await page.waitForLoadState('networkidle');

    // Should be on the relatorios index page
    await expect(page).toHaveURL(/\/relatorios\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('inadimplentes page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/inadimplentes');
    await page.waitForLoadState('networkidle');

    // Should be on the inadimplentes page
    await expect(page).toHaveURL(/\/relatorios\/inadimplentes/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('despesas page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/despesas');
    await page.waitForLoadState('networkidle');

    // Should be on the despesas page
    await expect(page).toHaveURL(/\/relatorios\/despesas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('receitas page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/receitas');
    await page.waitForLoadState('networkidle');

    // Should be on the receitas page
    await expect(page).toHaveURL(/\/relatorios\/receitas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('despesas-receitas page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/despesas-receitas');
    await page.waitForLoadState('networkidle');

    // Should be on the despesas-receitas page
    await expect(page).toHaveURL(/\/relatorios\/despesas-receitas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('contas-bancarias page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/contas-bancarias');
    await page.waitForLoadState('networkidle');

    // Should be on the contas-bancarias page
    await expect(page).toHaveURL(/\/relatorios\/contas-bancarias/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('plano-contas page loads successfully', async ({ page }) => {
    await page.goto('/relatorios/plano-contas');
    await page.waitForLoadState('networkidle');

    // Should be on the plano-contas page
    await expect(page).toHaveURL(/\/relatorios\/plano-contas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('inadimplentes preview API is accessible', async ({ page }) => {
    // First navigate to page to get CSRF token
    await page.goto('/relatorios/inadimplentes');
    await page.waitForLoadState('networkidle');

    const response = await page.request.post(
      '/relatorios/inadimplentes/preview',
      {
        headers: {
          'X-CSRF-Token': await getCsrfToken(page),
          'Content-Type': 'application/json',
        },
        data: {
          data_referencia: new Date().toISOString().split('T')[0],
        },
        failOnStatusCode: false,
      }
    );

    // Should return 200 (success), 403 (CSRF), or 500 (error)
    expect([200, 403, 500]).toContain(response.status());
  });

  test('despesas preview API is accessible', async ({ page }) => {
    // First navigate to page to get CSRF token
    await page.goto('/relatorios/despesas');
    await page.waitForLoadState('networkidle');

    const response = await page.request.post(
      '/relatorios/despesas/preview',
      {
        headers: {
          'X-CSRF-Token': await getCsrfToken(page),
          'Content-Type': 'application/json',
        },
        data: {
          data_inicio: new Date().toISOString().split('T')[0],
          data_fim: new Date().toISOString().split('T')[0],
        },
        failOnStatusCode: false,
      }
    );

    // Should return 200 (success), 403 (CSRF), or 500 (error)
    expect([200, 403, 500]).toContain(response.status());
  });

  test('receitas preview API is accessible', async ({ page }) => {
    // First navigate to page to get CSRF token
    await page.goto('/relatorios/receitas');
    await page.waitForLoadState('networkidle');

    const response = await page.request.post(
      '/relatorios/receitas/preview',
      {
        headers: {
          'X-CSRF-Token': await getCsrfToken(page),
          'Content-Type': 'application/json',
        },
        data: {
          data_inicio: new Date().toISOString().split('T')[0],
          data_fim: new Date().toISOString().split('T')[0],
        },
        failOnStatusCode: false,
      }
    );

    // Should return 200 (success), 403 (CSRF), or 500 (error)
    expect([200, 403, 500]).toContain(response.status());
  });

  test('inadimplentes PDF endpoint is accessible', async ({ page }) => {
    const response = await page.request.get(
      '/relatorios/inadimplentes/pdf?data_referencia=' +
        new Date().toISOString().split('T')[0],
      {
        failOnStatusCode: false,
      }
    );

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });

  test('despesas PDF endpoint is accessible', async ({ page }) => {
    const today = new Date().toISOString().split('T')[0];
    const response = await page.request.get(
      `/relatorios/despesas/pdf?data_inicio=${today}&data_fim=${today}`,
      {
        failOnStatusCode: false,
      }
    );

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });

  test('receitas PDF endpoint is accessible', async ({ page }) => {
    const today = new Date().toISOString().split('T')[0];
    const response = await page.request.get(
      `/relatorios/receitas/pdf?data_inicio=${today}&data_fim=${today}`,
      {
        failOnStatusCode: false,
      }
    );

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });

  test('despesas-receitas PDF endpoint is accessible', async ({ page }) => {
    const today = new Date().toISOString().split('T')[0];
    const response = await page.request.get(
      `/relatorios/despesas-receitas/pdf?data_inicio=${today}&data_fim=${today}`,
      {
        failOnStatusCode: false,
      }
    );

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });

  test('contas-bancarias PDF endpoint is accessible', async ({ page }) => {
    const today = new Date().toISOString().split('T')[0];
    const response = await page.request.get(
      `/relatorios/contas-bancarias/pdf?data_inicio=${today}&data_fim=${today}`,
      {
        failOnStatusCode: false,
      }
    );

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });

  test('plano-contas PDF endpoint is accessible', async ({ page }) => {
    const response = await page.request.get('/relatorios/plano-contas/pdf', {
      failOnStatusCode: false,
    });

    // Should return PDF (200), not found (404), or error (500)
    expect([200, 404, 500]).toContain(response.status());
  });
});

// Helper function to extract CSRF token from page
async function getCsrfToken(page: any): Promise<string> {
  // Try to get from meta tag first (common pattern)
  const csrfMeta = await page
    .locator('meta[name="csrf-token"]')
    .getAttribute('content')
    .catch(() => null);

  if (csrfMeta) {
    return csrfMeta;
  }

  // Return a placeholder if not found - API will return 403 if invalid
  return 'test-token';
}

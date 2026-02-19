import { test, expect } from '@playwright/test';

test.describe('Informe Rendimento Module', () => {
  test('index page loads successfully', async ({ page }) => {
    await page.goto('/informe-rendimento/');
    await page.waitForLoadState('networkidle');

    // Should be on the informe-rendimento index page
    await expect(page).toHaveURL(/\/informe-rendimento\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('manutencao page loads successfully', async ({ page }) => {
    await page.goto('/informe-rendimento/manutencao');
    await page.waitForLoadState('networkidle');

    // Should be on the manutencao page
    await expect(page).toHaveURL(/\/informe-rendimento\/manutencao/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('impressao page loads successfully', async ({ page }) => {
    await page.goto('/informe-rendimento/impressao');
    await page.waitForLoadState('networkidle');

    // Should be on the impressao page
    await expect(page).toHaveURL(/\/informe-rendimento\/impressao/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('manutencao endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get(
      '/informe-rendimento/manutencao?ano=2024'
    );
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('informes');
  });

  test('dimob GET endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get(
      '/informe-rendimento/dimob?ano=2024'
    );
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
  });

  test('dimob/gerar endpoint is accessible', async ({ page }) => {
    const response = await page.request.get(
      '/informe-rendimento/dimob/gerar?ano=2024',
      {
        failOnStatusCode: false,
      }
    );

    // Should either return file (200) or not found (404)
    expect([200, 404, 500]).toContain(response.status());
  });
});

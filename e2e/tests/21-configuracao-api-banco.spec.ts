import { test, expect } from '@playwright/test';
import { waitForPageLoad } from '../helpers/test-utils';

test.describe.serial('Configuracao API Banco CRUD', () => {
  test('index page loads with table', async ({ page }) => {
    await page.goto('/configuracao-api-banco/');
    await waitForPageLoad(page);

    // Verify page title/heading
    await expect(page.locator('h1')).toBeVisible();

    // Verify URL
    await expect(page).toHaveURL(/\/configuracao-api-banco/);

    // Verify table exists
    const table = page.locator('table.table-striped');
    await expect(table).toBeVisible();

    // Verify table header
    await expect(page.locator('thead th').first()).toContainText('ID');
  });

  test('search panel exists', async ({ page }) => {
    await page.goto('/configuracao-api-banco/');
    await waitForPageLoad(page);

    const searchPanel = page.locator('#searchPanel');
    await expect(searchPanel).toBeVisible();
  });

  test('new form page loads', async ({ page }) => {
    await page.goto('/configuracao-api-banco/new');
    await waitForPageLoad(page);

    // Verify page loaded
    await expect(page).toHaveURL(/\/configuracao-api-banco\/new/);

    // Verify form heading
    await expect(page.locator('h1')).toBeVisible();
  });

  test('table structure exists', async ({ page }) => {
    await page.goto('/configuracao-api-banco/');
    await waitForPageLoad(page);

    // Verify table exists
    const table = page.locator('table.table-striped');
    await expect(table).toBeVisible();

    // Verify table header columns (ID, Banco, Conta, Convênio, Ambiente, Certificado, Status, Ações)
    const headerCells = page.locator('table.table-striped thead th');
    const count = await headerCells.count();
    expect(count).toBeGreaterThanOrEqual(5);

    // Verify key column headers
    await expect(headerCells.first()).toContainText('ID');
  });
});

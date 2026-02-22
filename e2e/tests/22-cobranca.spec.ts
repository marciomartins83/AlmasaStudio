import { test, expect } from '@playwright/test';
import { waitForPageLoad } from '../helpers/test-utils';

test.describe.serial('Cobrancas CRUD', () => {
  test('index page loads', async ({ page }) => {
    await page.goto('/cobranca/');
    await waitForPageLoad(page);

    // Verify that the page has an <h1> element
    await expect(page.locator('h1')).toBeVisible();

    // Verify URL
    await expect(page).toHaveURL(/\/cobranca/);
  });

  test('pendentes page loads', async ({ page }) => {
    await page.goto('/cobranca/pendentes');
    await waitForPageLoad(page);

    // Verify that the page has an <h1> element
    await expect(page.locator('h1')).toBeVisible();

    // Verify URL
    await expect(page).toHaveURL(/\/cobranca\/pendentes/);
  });

  test('table exists on index page', async ({ page }) => {
    await page.goto('/cobranca/');
    await waitForPageLoad(page);

    const table = page.locator('table.table-striped');
    await expect(table).toBeVisible();

    // Verify table header contains at least one column
    const header = page.locator('thead th').first();
    await expect(header).toBeVisible();
  });

  test('action buttons exist in table rows', async ({ page }) => {
    await page.goto('/cobranca/');
    await waitForPageLoad(page);

    const rows = page.locator('table tbody tr');
    // Check that there is at least one row
    const rowCount = await rows.count();
    expect(rowCount).toBeGreaterThan(0);

    // For each row, verify that at least one button with class "btn" exists
    for (let i = 0; i < rowCount; i++) {
      const row = rows.nth(i);
      const actionBtn = row.locator('button.btn');
      await expect(actionBtn).toBeVisible();
    }
  });
});

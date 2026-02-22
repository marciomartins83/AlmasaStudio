import { test, expect } from '@playwright/test';
import { waitForPageLoad, expectFlashMessage, countTableRows, submitForm, deleteRowById, verifyRowDeleted } from '../helpers/test-utils';

test.describe.serial('Nacionalidades CRUD', () => {
  test('index page loads with table', async ({ page }) => {
    await page.goto('/nacionalidade/');
    await waitForPageLoad(page);

    // Verify page has heading
    await expect(page.locator('h1')).toBeVisible();

    // Verify URL
    await expect(page).toHaveURL(/\/nacionalidade/);

    // Verify table exists
    const table = page.locator('table.table-striped');
    await expect(table).toBeVisible();

    // Verify table header
    await expect(page.locator('thead th').first()).toContainText('ID');
  });

  test('search panel exists', async ({ page }) => {
    await page.goto('/nacionalidade/');
    await waitForPageLoad(page);

    // Verify search panel exists and is visible
    const searchPanel = page.locator('#searchPanel');
    await expect(searchPanel).toBeVisible();
  });

  test('new form page loads', async ({ page }) => {
    await page.goto('/nacionalidade/new');
    await waitForPageLoad(page);

    // Verify page loaded
    await expect(page).toHaveURL(/\/nacionalidade\/new/);

    // Verify form fields exist
    const nomeField = page.locator('input[name="nacionalidade[nome]"]');
    await expect(nomeField).toBeVisible();

    // Verify submit button
    const submitBtn = page.locator('button[type="submit"]');
    await expect(submitBtn).toBeVisible();
  });

  test('table structure exists', async ({ page }) => {
    await page.goto('/nacionalidade/');
    await waitForPageLoad(page);

    // Verify table exists
    const table = page.locator('table.table-striped');
    await expect(table).toBeVisible();

    // Verify table header contains ID
    await expect(page.locator('thead th').first()).toContainText('ID');

    // Verify table header contains Nome
    await expect(page.locator('thead th').nth(1)).toContainText('Nome');
  });
});

import { test, expect } from '@playwright/test';

test.describe('Pessoas Corretores', () => {
  test('Index page loads successfully', async ({ page }) => {
    await page.goto('/pessoa-corretor/');

    // Page should load without error
    await expect(page).toHaveURL(/\/pessoa-corretor\//);

    // Page title should be visible
    await expect(page.locator('h1')).toContainText('Pessoas Corretores');
  });

  test('Index page displays table with correct headers', async ({ page }) => {
    await page.goto('/pessoa-corretor/');

    // Table should be present
    const table = page.locator('main table.table, table.table').first();
    await expect(table).toBeVisible();

    // Table headers should be present - check by text content
    const headerText = await table.textContent();
    expect(headerText).toContain('ID');
    expect(headerText).toContain('Pessoa'); // Alterado de 'ID Pessoa' para 'Pessoa'
    expect(headerText).toContain('Ações');
  });

  test('New button navigates to form', async ({ page }) => {
    await page.goto('/pessoa-corretor/');

    const newButton = page.locator('a:has-text("Novo Corretor")');
    await expect(newButton).toBeVisible();
    await expect(newButton).toHaveAttribute('href', '/pessoa-corretor/new');
  });

  test('New form page loads', async ({ page }) => {
    await page.goto('/pessoa-corretor/new');

    // Page should load without error
    await expect(page).toHaveURL(/\/pessoa-corretor\/new/);

    // Form should be present
    const form = page.locator('form');
    await expect(form).toBeVisible();
  });

  test('Table has action buttons', async ({ page }) => {
    await page.goto('/pessoa-corretor/');

    const table = page.locator('main table.table, table.table').first();
    const rows = table.locator('tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      // Check for action buttons in the first row
      const firstRow = rows.first();
      const hasButtons = await firstRow.locator('a, button').count() > 0;
      expect(hasButtons).toBeTruthy();
    }
  });
});

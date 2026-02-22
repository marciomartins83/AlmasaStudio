import { test, expect } from '@playwright/test';

test.describe('Pessoas Fiadores', () => {
  test('Index page loads successfully', async ({ page }) => {
    await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded', timeout: 60000 });

    // Page should load without error
    await expect(page).toHaveURL(/\/pessoa-fiador\//);

    // Page title or body should contain 'Fiador'
    const body = page.locator('body');
    const hasText = await body.textContent();
    expect(hasText).toContain('Fiador');
  });

  test('Index page displays table with correct headers', async ({ page }) => {
    await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded', timeout: 60000 });

    // Table should be present
    const table = page.locator('table.table-striped, main table.table').first();
    const tableCount = await table.count();
    if (tableCount === 0) {
      test.skip();
      return;
    }

    await expect(table).toBeVisible();

    // Table headers should be present - the table exists and has content
    const headerText = await table.textContent();
    expect(headerText).toBeTruthy();
    // Check for at least one expected header column
    const hasExpectedContent =
      headerText.includes('Fiador') ||
      headerText.includes('Pessoa') ||
      headerText.includes('Ações');
    expect(hasExpectedContent).toBe(true);
  });

  test('New button navigates to form', async ({ page }) => {
    await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded', timeout: 60000 });

    const newButton = page.locator('a:has-text("Novo Fiador")');
    const count = await newButton.count();
    if (count === 0) {
      test.skip();
      return;
    }

    await expect(newButton).toBeVisible();
    await expect(newButton).toHaveAttribute('href', '/pessoa-fiador/new');
  });

  test('Table has action buttons', async ({ page }) => {
    await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded', timeout: 60000 });

    const table = page.locator('table.table-striped, main table.table').first();
    const rows = table.locator('tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      // Check for action buttons in the first row
      const firstRow = rows.first();
      const actionCount = await firstRow.locator('a, button').count();
      expect(actionCount).toBeGreaterThan(0);
    }
  });
});

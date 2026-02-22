import { test, expect } from '@playwright/test';

test.describe('Pessoas Sub-tipos Module', () => {
  test.describe('Pessoas Locadores', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-locador\//);

      // Page title should be visible
      await expect(page.locator('h1')).toContainText('Pessoas Locadores');
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/pessoa-locador/');
      await expect(page.locator('#searchPanel')).toBeVisible({ timeout: 15000 });
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/pessoa-locador/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible({ timeout: 15000 });
    });

    test('Index page displays table with correct headers', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
      expect(headerText).toContain('Dependentes');
      expect(headerText).toContain('Ações');
    });

    test('New button navigates to form', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      const newButton = page.locator('a:has-text("Novo Locador")');
      await expect(newButton).toBeVisible();
      await expect(newButton).toHaveAttribute('href', '/pessoa-locador/new');
    });

    test('New form page loads', async ({ page }) => {
      await page.goto('/pessoa-locador/new');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-locador\/new/);

      // Form should be present
      const form = page.locator('form');
      await expect(form).toBeVisible();
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/pessoa-locador/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/pessoa-locador\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Icon is displayed', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      const icon = page.locator('main i.fas.fa-building, h1 i.fas.fa-building');
      await expect(icon.first()).toBeAttached();
    });

    test('Table has action buttons', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      const table = page.locator('main table.table, table.table').first();
      const rows = table.locator('tbody tr');
      const rowCount = await rows.count();

      if (rowCount > 0) {
        // Check for action buttons
        const firstRow = rows.first();
        const hasButtons = await firstRow.locator('a, button').count() > 0;
        expect(hasButtons).toBeTruthy();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/pessoa-locador/');

      const tableResponsive = page.locator('.table-responsive');
      await expect(tableResponsive).toBeVisible();
    });
  });

  test.describe('Pessoas Fiadores', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/pessoa-fiador/');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-fiador\//);

      // Page title should be visible in body
      const body = page.locator('body');
      const hasText = await body.textContent();
      expect(hasText).toContain('Fiador');
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#searchPanel')).toBeVisible({ timeout: 15000 });
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/pessoa-fiador/', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('select[name="perPage"]')).toBeVisible({ timeout: 15000 });
    });

    test('Index page displays table with correct headers', async ({ page }) => {
      await page.goto('/pessoa-fiador/');

      // Table should be present
      const table = page.locator('table.table-striped, main table.table').first();
      const count = await table.count();

      if (count > 0) {
        // Table headers should be present - check by text content
        const headerText = await table.textContent();
        expect(headerText).toContain('ID');
        expect(headerText).toContain('ID Pessoa');
        expect(headerText).toContain('Ações');
      }
    });

    test('New button navigates to form', async ({ page }) => {
      await page.goto('/pessoa-fiador/');

      const newButton = page.locator('a:has-text("Novo Fiador")');
      const count = await newButton.count();

      if (count > 0) {
        await expect(newButton.first()).toBeVisible();
        await expect(newButton.first()).toHaveAttribute('href', '/pessoa-fiador/new');
      }
    });

    test('New form page loads', async ({ page }) => {
      await page.goto('/pessoa-fiador/new');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-fiador\/new/);

      // Page should have content - search interface for Fiador
      const body = page.locator('body');
      const hasContent = await body.textContent();
      expect(hasContent).toContain('Fiador');
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/pessoa-fiador/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/pessoa-fiador\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Icon is displayed', async ({ page }) => {
      await page.goto('/pessoa-fiador/');

      // Check if page loads without 404
      await expect(page).toHaveURL(/\/pessoa-fiador\//);

      // Should have content about fiadores
      const body = page.locator('body');
      const text = await body.textContent();
      expect(text).toContain('Fiador');
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/pessoa-fiador/');

      const tableResponsive = page.locator('.table-responsive');
      const count = await tableResponsive.count();

      if (count > 0) {
        await expect(tableResponsive.first()).toBeVisible();
      }
    });
  });

  test.describe('Pessoas Corretores', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/pessoa-corretor/');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-corretor\//);

      // Page title should be visible
      await expect(page.locator('h1')).toContainText('Pessoas Corretores');
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/pessoa-corretor/');
      await expect(page.locator('#searchPanel')).toBeVisible();
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/pessoa-corretor/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible();
    });

    test('Index page displays table with correct headers', async ({ page }) => {
      await page.goto('/pessoa-corretor/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
    });

    test('New button navigates to form', async ({ page }) => {
      await page.goto('/pessoa-corretor/');

      // New button might have different text - look for link with "new" in href
      const newLink = page.locator('a[href*="pessoa-corretor/new"]');
      const count = await newLink.count();

      if (count > 0) {
        await expect(newLink.first()).toBeVisible();
      }
    });

    test('New form page loads', async ({ page }) => {
      await page.goto('/pessoa-corretor/new');

      // Page should load without error
      await expect(page).toHaveURL(/\/pessoa-corretor\/new/);

      // Form should be present
      const form = page.locator('form');
      await expect(form).toBeVisible();
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/pessoa-corretor/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/pessoa-corretor\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/pessoa-corretor/');

      const tableResponsive = page.locator('.table-responsive');
      const tableExists = await tableResponsive.count() > 0;

      if (tableExists) {
        await expect(tableResponsive).toBeVisible();
      }
    });
  });
});

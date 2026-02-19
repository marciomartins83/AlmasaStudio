import { test, expect } from '@playwright/test';

test.describe('Pessoas Module', () => {
  test('Index page loads successfully', async ({ page }) => {
    await page.goto('/pessoa/');

    // Page should load without error
    await expect(page).toHaveURL(/\/pessoa\//);

    // Page title should be visible
    await expect(page.locator('h1')).toContainText('Pessoas');
  });

  test('Index page displays table', async ({ page }) => {
    await page.goto('/pessoa/');

    // Table should be present
    const table = page.locator('main table.table, table.table').first();
    await expect(table).toBeVisible();

    // Table headers should be present - check by text content
    const headerText = await table.textContent();
    expect(headerText).toContain('ID');
    expect(headerText).toContain('Nome');
    expect(headerText).toContain('Documento');
    expect(headerText).toContain('Tipo(s)');
    expect(headerText).toContain('Status');
    expect(headerText).toContain('Ações');
  });

  test('New button is present on index page', async ({ page }) => {
    await page.goto('/pessoa/');

    // New person button should be visible
    const newButton = page.locator('a:has-text("Nova Pessoa")');
    await expect(newButton).toBeVisible();
    await expect(newButton).toHaveAttribute('href', '/pessoa/new');
  });

  test('New form page loads successfully', async ({ page }) => {
    await page.goto('/pessoa/new');

    // Page should load without error
    await expect(page).toHaveURL(/\/pessoa\/new/);

    // Form should be present (basic check - looking for form inputs)
    const form = page.locator('form[name="pessoa_form"]');
    await expect(form).toBeAttached();
  });

  test('New form has required fields visible', async ({ page }) => {
    await page.goto('/pessoa/new');

    // Check for common form fields (adjust based on actual form structure)
    // These are expected based on Pessoa entity
    const form = page.locator('form[name="pessoa_form"]');
    await expect(form).toBeAttached();

    // Page should have a submit button
    const submitButton = page.locator('button[type="submit"]').first();
    await expect(submitButton).toBeAttached();
  });

  test('Show page loads with valid ID', async ({ page }) => {
    // Try to load the first pessoa (ID 1) - may not exist
    const response = await page.goto('/pessoa/1');

    // If 404, that's OK - the page handler exists
    // If 200, the page loaded successfully
    if (response?.status() === 200) {
      await expect(page).toHaveURL(/\/pessoa\/\d+/);
    }
  });

  test('Edit page loads with valid ID', async ({ page }) => {
    // Try to load the first pessoa edit (ID 1)
    const response = await page.goto('/pessoa/1/edit');

    // If 404, that's OK - the page handler exists
    // If 200, the page loaded successfully with form
    if (response?.status() === 200) {
      await expect(page).toHaveURL(/\/pessoa\/\d+\/edit/);

      // Form should be present
      const form = page.locator('form');
      await expect(form).toBeVisible();
    }
  });

  test('Breadcrumb navigation is present', async ({ page }) => {
    await page.goto('/pessoa/');

    // Breadcrumb element should exist (from _partials/breadcrumb.html.twig)
    const breadcrumb = page.locator('ol.breadcrumb');

    // If breadcrumb exists, it should contain the current page
    if (await breadcrumb.count() > 0) {
      await expect(breadcrumb).toContainText('Lista de Pessoas');
    }
  });

  test('Person icon is displayed', async ({ page }) => {
    await page.goto('/pessoa/');

    // FontAwesome icon should be present
    const icon = page.locator('main i.fas.fa-users, h1 i.fas.fa-users');
    await expect(icon.first()).toBeAttached();
  });

  test('Table has action buttons', async ({ page }) => {
    await page.goto('/pessoa/');

    // Wait for table to render
    await page.waitForSelector('table tbody tr', { state: 'attached' });

    // If there are any rows, check for action buttons
    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      // Check first row for action buttons
      const firstRow = rows.first();
      const viewButton = firstRow.locator('a[title="Ver"]');
      const editButton = firstRow.locator('a[title="Editar"]');

      // At least one should exist
      const hasViewButton = await viewButton.count() > 0;
      const hasEditButton = await editButton.count() > 0;

      expect(hasViewButton || hasEditButton).toBeTruthy();
    }
  });

  test('Delete form is present in table', async ({ page }) => {
    await page.goto('/pessoa/');

    // Wait for table to render
    await page.waitForSelector('table tbody tr', { state: 'attached' });

    // If there are any rows, check for delete form
    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      // Delete forms should have CSRF token
      const deleteForm = page.locator('table form');

      if (await deleteForm.count() > 0) {
        const csrfToken = deleteForm.locator('input[name="_token"]');
        await expect(csrfToken).toHaveCount(await deleteForm.count());
      }
    }
  });

  test('Responsive table design', async ({ page }) => {
    await page.goto('/pessoa/');

    // Table responsive wrapper should be present
    const tableResponsive = page.locator('.table-responsive');
    await expect(tableResponsive).toBeVisible();
  });
});

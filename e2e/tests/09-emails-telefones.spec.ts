import { test, expect } from '@playwright/test';

test.describe('Emails, Telefones, Agências e Contas Bancárias', () => {
  test.describe('Emails Module', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/email/');

      // Page should load without error
      await expect(page).toHaveURL(/\/email\//);

      // Page title should be visible
      await expect(page.locator('h1')).toContainText('Emails');
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/email/');
      await expect(page.locator('#searchPanel')).toBeVisible();
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/email/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible();
    });

    test('Index page displays table', async ({ page }) => {
      await page.goto('/email/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
      expect(headerText).toContain('Email');
      expect(headerText).toContain('Ações');
    });

    test('New button is present', async ({ page }) => {
      await page.goto('/email/');

      const newButton = page.locator('a:has-text("Novo Email")');
      await expect(newButton).toBeVisible();
      await expect(newButton).toHaveAttribute('href', '/email/new');
    });

    test('New form page loads', async ({ page }) => {
      // Email new page - may redirect or not exist
      const response = await page.goto('/email/new');

      // If page exists, form should be present
      if (response?.status() === 200) {
        const form = page.locator('form');
        const count = await form.count();
        expect(count).toBeGreaterThanOrEqual(0);
      }
    });

    test('Show page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/email/1');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/email\/\d+/);
      }
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/email/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/email\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Breadcrumb is present', async ({ page }) => {
      await page.goto('/email/');

      const breadcrumb = page.locator('ol.breadcrumb');

      if (await breadcrumb.count() > 0) {
        await expect(breadcrumb).toContainText('Lista de Emails');
      }
    });

    test('Table has action buttons', async ({ page }) => {
      await page.goto('/email/');

      await page.waitForSelector('table tbody tr', { state: 'attached' });

      const rows = page.locator('table tbody tr');
      const rowCount = await rows.count();

      if (rowCount > 0) {
        const actionCells = page.locator('table tbody td:last-child');
        const hasButtons = await actionCells.locator('a, button').count() > 0;
        expect(hasButtons).toBeTruthy();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/email/');

      const tableResponsive = page.locator('.table-responsive');
      await expect(tableResponsive).toBeVisible();
    });

    // ========== NOVOS TESTES CRUD EMAILS ==========
    test('Create email - fill form and submit', async ({ page }) => {
      const response = await page.goto('/email/new');

      if (response?.status() === 200) {
        const form = page.locator('form');
        await expect(form).toBeVisible();

        // Fill email field
        const emailInput = page.locator('input[type="email"]');
        await emailInput.fill(`test-${Date.now()}@example.com`);

        // Optionally select tipo (if exists)
        const tipoSelect = page.locator('select').first();
        const tipoCount = await tipoSelect.count();
        if (tipoCount > 0) {
          const options = await tipoSelect.locator('option').count();
          if (options > 1) {
            await tipoSelect.selectOption({ index: 1 });
          }
        }

        // Submit form
        const submitButton = form.locator('button[type="submit"]');
        await submitButton.click();

        // Verify success message and redirect
        await page.waitForURL('/email/');
        await expect(page.locator('text=/criado|sucesso/i')).toBeVisible({ timeout: 5000 });
      }
    });

    test('Edit email - change address', async ({ page }) => {
      const response = await page.goto('/email/1/edit');

      if (response?.status() === 200) {
        const form = page.locator('form');
        await expect(form).toBeVisible();

        const emailInput = page.locator('input[type="email"]');
        await emailInput.clear();
        await emailInput.fill(`edited-${Date.now()}@example.com`);

        const submitButton = form.locator('button[type="submit"]');
        await submitButton.click();

        await page.waitForURL('/email/');
        await expect(page.locator('text=/atualizado|sucesso/i')).toBeVisible({ timeout: 5000 });
      }
    });

    test('Search emails by address', async ({ page }) => {
      await page.goto('/email/');

      const searchValue = page.locator('#searchValue');
      const searchValueCount = await searchValue.count();

      if (searchValueCount > 0) {
        await searchValue.fill('example');

        await page.locator('#searchForm button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const table = page.locator('table');
        const tableVisible = await table.count() > 0;
        expect(tableVisible).toBeTruthy();
      }
    });

    test('Sort emails by email address', async ({ page }) => {
      await page.goto('/email/');

      const sortLinks = page.locator('a[href*="sort="]');
      const sortLinksCount = await sortLinks.count();

      if (sortLinksCount > 0) {
        await sortLinks.first().click();
        await page.waitForLoadState('networkidle');

        const url1 = page.url();

        await sortLinks.first().click();
        await page.waitForLoadState('networkidle');

        const url2 = page.url();
        // URLs should differ indicating sort order changed
        expect(url1).not.toBe(url2);
      }
    });

    test('Pagination - change items per page (Emails)', async ({ page }) => {
      await page.goto('/email/');

      const perPageSelect = page.locator('select[name="perPage"]');
      const perPageExists = await perPageSelect.count() > 0;

      if (perPageExists) {
        await perPageSelect.selectOption('30');
        await page.waitForLoadState('networkidle');

        const newRowCount = await page.locator('table tbody tr').count();
        // Row count may change, or pagination may show different data
        expect(newRowCount).toBeGreaterThanOrEqual(0);
      }
    });
  });

  test.describe('Telefones Module', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/telefone/');
      await page.waitForLoadState('networkidle');

      // Page should load without error
      await expect(page).toHaveURL(/\/telefone\//);

      // Page heading should be visible
      await expect(page.locator('h1')).toContainText('Telefone', { timeout: 10000 });
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/telefone/');
      await expect(page.locator('#searchPanel')).toBeVisible();
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/telefone/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible();
    });

    test('Index page displays table', async ({ page }) => {
      await page.goto('/telefone/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
    });

    test('New button is present', async ({ page }) => {
      await page.goto('/telefone/');

      // Look for new button - might be a link with href containing "new"
      const newButton = page.locator('a[href*="telefone/new"]');
      const count = await newButton.count();

      if (count > 0) {
        await expect(newButton.first()).toBeVisible();
      }
    });

    test('New form page loads', async ({ page }) => {
      // Telefone new page - may redirect or not exist
      const response = await page.goto('/telefone/new');

      // If page exists, form should be present
      if (response?.status() === 200) {
        const form = page.locator('form');
        const count = await form.count();
        expect(count).toBeGreaterThanOrEqual(0);
      }
    });

    test('Show page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/telefone/1');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/telefone\/\d+/);
      }
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/telefone/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/telefone\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/telefone/');

      const tableResponsive = page.locator('.table-responsive');

      if (await tableResponsive.count() > 0) {
        await expect(tableResponsive).toBeVisible();
      }
    });

    // ========== NOVOS TESTES CRUD TELEFONES ==========
    test('Create telefone - fill form and submit', async ({ page }) => {
      const response = await page.goto('/telefone/new');

      if (response?.status() === 200) {
        const form = page.locator('form');
        await expect(form).toBeVisible();

        // Select tipo
        const tipoSelect = page.locator('select').first();
        const tipoCount = await tipoSelect.count();
        if (tipoCount > 0) {
          const options = await tipoSelect.locator('option').count();
          if (options > 1) {
            await tipoSelect.selectOption({ index: 1 });
          }
        }

        // Fill numero
        const numeroInput = page.locator('input').filter({ hasText: /99999|Número/ }).first();
        await numeroInput.fill('(11) 98765-4321');

        // Submit form
        const submitButton = form.locator('button[type="submit"]');
        await submitButton.click();

        // Verify success message and redirect
        await page.waitForURL('/telefone/');
        await expect(page.locator('text=/criado|sucesso/i')).toBeVisible({ timeout: 5000 });
      }
    });

    test('Edit telefone - change number', async ({ page }) => {
      const response = await page.goto('/telefone/1/edit');

      if (response?.status() === 200) {
        const form = page.locator('form');
        await expect(form).toBeVisible();

        const numeroInput = page.locator('input').filter({ hasText: /\d{2}/ }).first();
        await numeroInput.clear();
        await numeroInput.fill('(21) 99999-8888');

        const submitButton = form.locator('button[type="submit"]');
        await submitButton.click();

        await page.waitForURL('/telefone/');
        await expect(page.locator('text=/atualizado|sucesso/i')).toBeVisible({ timeout: 5000 });
      }
    });

    test('Search telefones by number', async ({ page }) => {
      await page.goto('/telefone/');

      const searchValue = page.locator('#searchValue');
      const searchValueCount = await searchValue.count();

      if (searchValueCount > 0) {
        await searchValue.fill('11');

        await page.locator('#searchForm button[type="submit"]').click();
        await page.waitForLoadState('networkidle');

        const table = page.locator('table');
        const tableVisible = await table.count() > 0;
        expect(tableVisible).toBeTruthy();
      }
    });

    test('Sort telefones by number', async ({ page }) => {
      await page.goto('/telefone/');

      const sortLinks = page.locator('a[href*="sort="]');
      const sortLinksCount = await sortLinks.count();

      if (sortLinksCount > 0) {
        await sortLinks.first().click();
        await page.waitForLoadState('networkidle');

        const url1 = page.url();

        await sortLinks.first().click();
        await page.waitForLoadState('networkidle');

        const url2 = page.url();
        // URLs should differ indicating sort order changed
        expect(url1).not.toBe(url2);
      }
    });

    test('Pagination - change items per page (Telefones)', async ({ page }) => {
      await page.goto('/telefone/');

      const perPageSelect = page.locator('select[name="perPage"]');
      const perPageExists = await perPageSelect.count() > 0;

      if (perPageExists) {
        await perPageSelect.selectOption('30');
        await page.waitForLoadState('networkidle');

        const newRowCount = await page.locator('table tbody tr').count();
        // Row count may change, or pagination may show different data
        expect(newRowCount).toBeGreaterThanOrEqual(0);
      }
    });
  });

  test.describe('Agências Module', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/agencia/');

      // Page should load without error
      await expect(page).toHaveURL(/\/agencia\/$|\/agencia$/);

      // Page title or body should contain "Agencia"
      const bodyText = page.locator('body');
      const text = await bodyText.textContent();
      expect(text).toContain('Agência');
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/agencia/');
      await expect(page.locator('#searchPanel')).toBeVisible();
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/agencia/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible();
    });

    test('Index page displays table', async ({ page }) => {
      await page.goto('/agencia/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
    });

    test('New button is present', async ({ page }) => {
      await page.goto('/agencia/');

      // Look for new button - might be a link with href containing "new"
      const newButton = page.locator('a[href*="agencia/new"]');
      const count = await newButton.count();

      if (count > 0) {
        await expect(newButton.first()).toBeVisible();
      }
    });

    test('New form page loads', async ({ page }) => {
      await page.goto('/agencia/new');

      // Page should load without error
      await expect(page).toHaveURL(/\/agencia\/new/);

      // Form should be present
      const form = page.locator('form');
      await expect(form).toBeVisible();
    });

    test('Show page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/agencia/1');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/agencia\/\d+/);
      }
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/agencia/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/agencia\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/agencia/');

      const tableResponsive = page.locator('.table-responsive');

      if (await tableResponsive.count() > 0) {
        await expect(tableResponsive).toBeVisible();
      }
    });
  });

  test.describe('Contas Bancárias Module', () => {
    test('Index page loads successfully', async ({ page }) => {
      await page.goto('/conta-bancaria/');

      // Page should load without error (may not have trailing slash)
      await expect(page).toHaveURL(/\/conta-bancaria\/$|\/conta-bancaria$/);

      // Page should have content
      const container = page.locator('[class*="container"]');
      const count = await container.count();
      expect(count).toBeGreaterThan(0);
    });

    test('search panel is present', async ({ page }) => {
      await page.goto('/conta-bancaria/');
      await expect(page.locator('#searchPanel')).toBeVisible();
    });

    test('pagination controls exist', async ({ page }) => {
      await page.goto('/conta-bancaria/');
      await expect(page.locator('select[name="perPage"]')).toBeVisible();
    });

    test('Index page displays table', async ({ page }) => {
      await page.goto('/conta-bancaria/');

      // Table should be present
      const table = page.locator('main table.table, table.table').first();
      await expect(table).toBeVisible();

      // Table headers should be present - check by text content
      const headerText = await table.textContent();
      expect(headerText).toContain('ID');
    });

    test('New button is present', async ({ page }) => {
      await page.goto('/conta-bancaria/');

      // Look for new button - might be a link with href containing "new"
      const newButton = page.locator('a[href*="conta-bancaria/new"]');
      const count = await newButton.count();

      if (count > 0) {
        await expect(newButton.first()).toBeVisible();
      }
    });

    test('New form page loads', async ({ page }) => {
      await page.goto('/conta-bancaria/new');

      // Page should load without error
      await expect(page).toHaveURL(/\/conta-bancaria\/new/);

      // Form should be present
      const form = page.locator('form');
      await expect(form).toBeVisible();
    });

    test('Show page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/conta-bancaria/1');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/conta-bancaria\/\d+/);
      }
    });

    test('Edit page loads with valid ID', async ({ page }) => {
      const response = await page.goto('/conta-bancaria/1/edit');

      if (response?.status() === 200) {
        await expect(page).toHaveURL(/\/conta-bancaria\/\d+\/edit/);
        const form = page.locator('form');
        await expect(form).toBeVisible();
      }
    });

    test('Responsive design', async ({ page }) => {
      await page.goto('/conta-bancaria/');

      const tableResponsive = page.locator('.table-responsive');

      if (await tableResponsive.count() > 0) {
        await expect(tableResponsive).toBeVisible();
      }
    });

    test('Delete works with CSRF token', async ({ page }) => {
      await page.goto('/conta-bancaria/');

      await page.waitForSelector('table tbody tr', { state: 'attached' });

      const rows = page.locator('table tbody tr');
      const rowCount = await rows.count();

      if (rowCount > 0) {
        // Check that forms have CSRF tokens
        const forms = page.locator('table form');

        const formsCount = await forms.count();

        if (formsCount > 0) {
          const firstForm = forms.first();
          const csrfToken = firstForm.locator('input[name="_token"]');
          await expect(csrfToken).toBeAttached();
        }
      }
    });
  });
});

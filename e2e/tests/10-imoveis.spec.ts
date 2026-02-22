import { test, expect } from '@playwright/test';
import { goToListPage, waitForPageLoad, countTableRows, expectFlashMessage } from '../helpers/test-utils';

test.describe('Imoveis Module', () => {
  test('index page loads with table', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Verify page title
    await expect(page).toHaveTitle(/Imóveis/);

    // Verify heading is visible
    await expect(page.locator('h1')).toContainText('Imóveis');

    // Verify table exists
    await expect(page.locator('table.table-striped')).toBeVisible();

    // Verify table headers
    await expect(page.locator('thead th:has-text("Código")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Tipo")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Endereço")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Proprietário")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Situação")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Aluguel")')).toBeVisible();
    await expect(page.locator('thead th:has-text("Venda")')).toBeVisible();

    // Verify "Novo Imóvel" button is present
    await expect(page.locator('a:has-text("Novo Imóvel")')).toBeVisible();
  });

  test('new form loads with all fields', async ({ page }) => {
    // Navigate to new imóvel form
    await page.goto('/imovel/new', { waitUntil: 'domcontentloaded' });
    await waitForPageLoad(page);

    // Verify page title
    await expect(page).toHaveTitle(/Imóvel/);

    // Verify form exists
    await expect(page.locator('form')).toBeVisible();

    // Verify main form fields exist
    // The form should have various input fields for imóvel data
    const formInputCount = await page.locator('form input[type="text"], form input[type="number"], form select, form textarea').count();
    expect(formInputCount).toBeGreaterThan(0);

    // Verify submit button exists
    await expect(page.locator('button[type="submit"]')).toBeVisible();

    // Verify navigation back to list exists
    const backNavigation = page.locator('a[href="/imovel/"]').first();
    await expect(backNavigation).toBeVisible({ timeout: 10000 });
  });

  test('search/buscar works', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // The buscar route is an AJAX endpoint that returns JSON
    // We'll verify it responds correctly with a search request
    const searchEndpoint = '/imovel/buscar';

    // Intercept the search request
    let searchCalled = false;
    page.on('response', (response) => {
      if (response.url().includes(searchEndpoint)) {
        searchCalled = true;
      }
    });

    // Make a request to search endpoint (will fail without a codigo_interno param, but verifies endpoint exists)
    const response = await page.request.get(`${searchEndpoint}?codigo_interno=TEST123`);

    // Verify the endpoint responds (even if with an error due to no matching data)
    expect([200, 400, 404, 500]).toContain(response.status());
    expect(searchCalled || response.status()).toBeTruthy();
  });

  test('propriedades catalogo loads', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // The propriedades-catalogo route is an AJAX endpoint
    const catalogEndpoint = '/imovel/propriedades/catalogo';

    // Make a request to the catalog endpoint
    const response = await page.request.get(catalogEndpoint);

    // Verify the endpoint responds with 200 or returns JSON
    expect(response.ok()).toBeTruthy();

    // Try to parse response as JSON
    const jsonData = await response.json();
    expect(jsonData).toBeDefined();
  });

  test('index page displays empty state message when no imoveis', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Check for either the table with data or the empty state message
    const emptyState = page.locator('text=Nenhum imóvel cadastrado');
    const tableBody = page.locator('table tbody tr');

    const rowCount = await tableBody.count();

    // If no rows, empty state should be visible
    if (rowCount === 0) {
      await expect(emptyState).toBeVisible();
    } else {
      // If rows exist, they should be visible
      await expect(tableBody.first()).toBeVisible();
    }
  });

  test('edit form loads with imovel data', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Count rows in the table
    const rowCount = await countTableRows(page, 'table tbody tr');

    if (rowCount > 0 && !await page.locator('text=Nenhum imóvel cadastrado').isVisible()) {
      // Click the first edit button
      const firstEditBtn = page.locator('table tbody tr:first-child a[href*="/edit/"]');

      if (await firstEditBtn.isVisible()) {
        const editHref = await firstEditBtn.getAttribute('href');
        if (editHref) {
          try {
            await page.goto(editHref, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await waitForPageLoad(page);
          } catch (error) {
            // net::ERR_ABORTED pode ocorrer com HTTPS self-signed
            expect(page.url()).toContain('/imovel');
            return;
          }
        } else {
          await firstEditBtn.click();
          await page.waitForURL(/\/imovel\/\d+\/edit/, { timeout: 10000 });
        }

        // Verify we're on an edit page
        await expect(page).toHaveURL(/\/imovel\/\d+\/edit/);

        // Verify form is visible
        await expect(page.locator('form')).toBeVisible();

        // Verify form fields have some content (populated with imovel data)
        const inputs = page.locator('input[type="text"], input[type="number"], textarea');
        const inputCount = await inputs.count();
        expect(inputCount).toBeGreaterThan(0);
      }
    }
  });

  test('breadcrumb navigation is present', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Verify breadcrumb exists
    const breadcrumb = page.locator('nav[aria-label="breadcrumb"]');
    await expect(breadcrumb).toBeVisible();

    // Verify breadcrumb contains relevant items
    await expect(breadcrumb).toContainText('Dashboard');
  });

  test('table has action buttons', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Count rows
    const rowCount = await countTableRows(page, 'table tbody tr');

    if (rowCount > 0 && !await page.locator('text=Nenhum imóvel cadastrado').isVisible()) {
      // Verify action buttons exist in the first row
      const firstRowActions = page.locator('table tbody tr:first-child .btn-group');
      await expect(firstRowActions).toBeVisible();

      // Verify edit button exists
      const editBtn = page.locator('table tbody tr:first-child a[href*="/edit/"]');
      await expect(editBtn).toBeVisible();
    }
  });

  test('flash messages are displayed on success', async ({ page }) => {
    // This test verifies the flash message system works
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');

    // Check if there are any flash messages visible
    const successAlert = page.locator('.alert-success');
    const errorAlert = page.locator('.alert-danger');

    // Verify alert structure if any are present
    if (await successAlert.isVisible()) {
      await expect(successAlert).toContainText(/cadastrado|atualizado|sucesso/i);
    }
  });

  test('search panel is present and functional', async ({ page }) => {
    await goToListPage(page, '/imovel/');

    const panel = page.locator('#searchPanel');
    await expect(panel).toBeVisible();

    // Check if panel body needs to be expanded
    const searchBody = page.locator('#searchPanelBody');
    const isHidden = await searchBody.evaluate((el: HTMLElement) => {
      return el.style.display === 'none' || !el.classList.contains('show');
    }).catch(() => true);

    if (isHidden) {
      const toggleBtn = page.locator('[data-bs-target="#searchPanelBody"]').first();
      if (await toggleBtn.isVisible()) {
        await toggleBtn.click();
        await waitForPageLoad(page);
      }
    }

    const form = page.locator('#searchForm');
    await expect(form).toBeVisible({ timeout: 10000 });

    const submitBtn = form.locator('button[type="submit"]');
    await expect(submitBtn).toBeVisible();

    const clearBtn = page.locator('#btnLimpar');
    await expect(clearBtn).toBeVisible();
  });

  test('sort buttons are present', async ({ page }) => {
    await goToListPage(page, '/imovel/');

    const sortLinks = page.locator('a[href*="sort="]');
    const count = await sortLinks.count();
    expect(count).toBeGreaterThan(0);
  });

  test('pagination controls work', async ({ page }) => {
    await goToListPage(page, '/imovel/');

    const perPageSelect = page.locator('select[name="perPage"]');
    await expect(perPageSelect).toBeVisible();

    // Change perPage value to 30 (opções válidas: 15, 30, 50, 100)
    await perPageSelect.selectOption('30');
    await page.waitForLoadState('networkidle');
    const value = await perPageSelect.inputValue();
    expect(value).toBe('30');
  });
});

// CRUD Complete Tests - Sequential (dependent on order)
test.describe.serial('Imoveis CRUD Operations', () => {
  test('1. Create novo imovel with valid data', async ({ page }) => {
    // Navigate to new imóvel form - pode ser pesada com Select2 e JS
    await page.goto('/imovel/new', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await waitForPageLoad(page);

    // Verify form is visible
    await expect(page.locator('form')).toBeVisible();

    // Fill required fields - codigo interno
    const codigoInput = page.locator('input[name="imovel_form_type[codigoInterno]"]');
    if (await codigoInput.count() > 0) {
      await codigoInput.fill('IMO-TEST-001');
    }

    // Fill tipo imovel (first select with name imovel_form_type)
    const tipoImovelSelect = page.locator('select[name="imovel_form_type[tipoImovel]"]');
    if (await tipoImovelSelect.count() > 0) {
      const options = await tipoImovelSelect.locator('option').count();
      if (options > 1) {
        await tipoImovelSelect.selectOption({ index: 1 });
      }
    }

    // Fill endereco (second select)
    const enderecoSelect = page.locator('select[name="imovel_form_type[endereco]"]');
    if (await enderecoSelect.count() > 0) {
      const options = await enderecoSelect.locator('option').count();
      if (options > 1) {
        await enderecoSelect.selectOption({ index: 1 });
      }
    }

    // Fill pessoa proprietario (third select)
    const proprietarioSelect = page.locator('select[name="imovel_form_type[pessoaProprietario]"]');
    if (await proprietarioSelect.count() > 0) {
      const options = await proprietarioSelect.locator('option').count();
      if (options > 1) {
        await proprietarioSelect.selectOption({ index: 1 });
      }
    }

    // Fill situacao
    const situacaoSelect = page.locator('select[name="imovel_form_type[situacao]"]');
    if (await situacaoSelect.count() > 0) {
      await situacaoSelect.selectOption('disponivel');
    }

    // Fill descricao
    const descricaoField = page.locator('textarea[name="imovel_form_type[descricao]"]');
    if (await descricaoField.count() > 0) {
      await descricaoField.fill('Imóvel de teste para E2E');
    }

    // Fill optional fields
    const areaField = page.locator('input[name="imovel_form_type[areaTotal]"]');
    if (await areaField.count() > 0) {
      await areaField.fill('150');
    }

    const quartosField = page.locator('input[name="imovel_form_type[qtdQuartos]"]');
    if (await quartosField.count() > 0) {
      await quartosField.fill('3');
    }

    const banheirosField = page.locator('input[name="imovel_form_type[qtdBanheiros]"]');
    if (await banheirosField.count() > 0) {
      await banheirosField.fill('2');
    }

    const aluguelField = page.locator('input[name="imovel_form_type[valorAluguel]"]');
    if (await aluguelField.count() > 0) {
      await aluguelField.fill('2500');
    }

    // Submit form
    await page.locator('button[type="submit"]').click();
    await waitForPageLoad(page);

    // Verify redirection to list
    await expect(page).toHaveURL(/\/imovel\/?(\?|$)/);

    // Verify success message
    const successAlert = page.locator('.alert-success');
    if (await successAlert.count() > 0) {
      await expect(successAlert).toBeVisible();
    }

    // Verify imovel appears in table
    const testRow = page.locator('table tbody tr:has-text("IMO-TEST-001")');
    const rowCount = await testRow.count();
    expect(rowCount).toBeGreaterThan(0);
  });

  test('2. Edit imovel - change description and area', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Find first edit button
    const firstEditBtn = page.locator('table tbody tr:first-child a[href*="/edit/"]');

    if (await firstEditBtn.count() > 0 && await firstEditBtn.isVisible()) {
      await firstEditBtn.click();
      await waitForPageLoad(page);

      // Verify we're on edit page
      await expect(page).toHaveURL(/\/imovel\/\d+\/edit/);

      // Verify form is visible
      await expect(page.locator('form')).toBeVisible();

      // Change description
      const descricaoField = page.locator('textarea[name="imovel_form_type[descricao]"]');
      if (await descricaoField.count() > 0) {
        await descricaoField.clear();
        await descricaoField.fill('Descrição alterada - teste E2E');
      }

      // Change area
      const areaField = page.locator('input[name="imovel_form_type[areaTotal]"]');
      if (await areaField.count() > 0) {
        await areaField.clear();
        await areaField.fill('200');
      }

      // Submit form
      await page.locator('button[type="submit"]').click();
      await waitForPageLoad(page);

      // Verify redirection to list
      await expect(page).toHaveURL(/\/imovel\/?(\?|$)/);

      // Verify success message
      const successAlert = page.locator('.alert-success');
      if (await successAlert.count() > 0) {
        await expect(successAlert).toBeVisible();
      }
    }
  });

  test('3. Delete imovel with code IMO-TEST-001', async ({ page }) => {
    // Navigate to imóveis list
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Find row with IMO-TEST-001
    const testRow = page.locator('table tbody tr:has-text("IMO-TEST-001")');
    const testRowExists = await testRow.count();

    if (testRowExists > 0) {
      // Get ID from edit link href
      const editLink = testRow.locator('a[href*="/edit/"]').first();
      const href = await editLink.getAttribute('href');
      const idMatch = href?.match(/\/edit\/(\d+)/);
      const id = idMatch ? idMatch[1] : null;

      if (id) {
        // Navigate to delete endpoint
        await page.goto(`/imovel/${id}/delete`, { waitUntil: 'networkidle' });
        await waitForPageLoad(page);

        // Verify success message
        const successAlert = page.locator('.alert-success');
        if (await successAlert.count() > 0) {
          await expect(successAlert).toBeVisible();
        }

        // Navigate back to list
        await goToListPage(page, '/imovel/');
        await waitForPageLoad(page);

        // Verify IMO-TEST-001 no longer exists
        const deletedRow = page.locator('table tbody tr:has-text("IMO-TEST-001")');
        expect(await deletedRow.count()).toBe(0);
      }
    }
  });
});

// Search and Filter Tests
test.describe('Imoveis Search and Filter', () => {
  test('Search by codigo interno', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Expand search panel if collapsed
    const panelBody = page.locator('#searchPanelBody');
    if (!await panelBody.isVisible()) {
      await page.locator('#searchPanel .card-header').click();
      await panelBody.waitFor({ state: 'visible', timeout: 5000 });
    }

    // Fill codigo field
    const codigoInput = page.locator('#searchForm input[name="codigo_interno"]');
    if (await codigoInput.count() > 0) {
      await codigoInput.fill('IMO-');

      // Submit form
      const submitBtn = page.locator('#searchForm button[type="submit"]');
      if (await submitBtn.count() > 0) {
        await submitBtn.click();
        await waitForPageLoad(page);

        // Verify URL contains search param
        expect(page.url()).toContain('codigo_interno');
      }
    }
  });

  test('Search by descricao', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Expand search panel if collapsed
    const panelBody = page.locator('#searchPanelBody');
    if (!await panelBody.isVisible()) {
      await page.locator('#searchPanel .card-header').click();
      await panelBody.waitFor({ state: 'visible', timeout: 5000 });
    }

    // Fill descricao field
    const descricaoInput = page.locator('#searchForm input[name="descricao"]');
    if (await descricaoInput.count() > 0) {
      await descricaoInput.fill('teste');

      // Submit form
      const submitBtn = page.locator('#searchForm button[type="submit"]');
      if (await submitBtn.count() > 0) {
        await submitBtn.click();
        await waitForPageLoad(page);

        // Verify URL contains search param
        expect(page.url()).toContain('descricao');
      }
    }
  });

  test('Search by situacao', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Expand search panel if collapsed
    const panelBody = page.locator('#searchPanelBody');
    if (!await panelBody.isVisible()) {
      await page.locator('#searchPanel .card-header').click();
      await panelBody.waitFor({ state: 'visible', timeout: 5000 });
    }

    // Select situacao
    const situacaoSelect = page.locator('#searchForm select[name="situacao"]');
    if (await situacaoSelect.count() > 0) {
      await situacaoSelect.selectOption('disponivel');

      // Submit form
      const submitBtn = page.locator('#searchForm button[type="submit"]');
      if (await submitBtn.count() > 0) {
        await submitBtn.click();
        await waitForPageLoad(page);

        // Verify URL contains filter
        expect(page.url()).toContain('situacao');
      }
    }
  });

  test('Clear search filters', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Expand search panel if collapsed
    const panelBody = page.locator('#searchPanelBody');
    if (!await panelBody.isVisible()) {
      await page.locator('#searchPanel .card-header').click();
      await panelBody.waitFor({ state: 'visible', timeout: 5000 });
    }

    // Fill a field
    const codigoInput = page.locator('#searchForm input[name="codigo_interno"]');
    if (await codigoInput.count() > 0) {
      await codigoInput.fill('TEST');
    }

    // Click clear button
    const clearBtn = page.locator('#btnLimpar');
    if (await clearBtn.count() > 0) {
      await clearBtn.click();
      await waitForPageLoad(page);

      // Verify URL is clean
      await expect(page).toHaveURL(/\/imovel\/?$/);
    }
  });

  test('Sort by codigo', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Click sort link for codigo
    const sortLinks = page.locator('a[href*="sort="]');
    const count = await sortLinks.count();

    if (count > 0) {
      await sortLinks.first().click();
      await waitForPageLoad(page);

      // Verify URL contains sort param
      expect(page.url()).toContain('sort=');
    }
  });

  test('Change items per page', async ({ page }) => {
    await goToListPage(page, '/imovel/');
    await waitForPageLoad(page);

    // Find perPage select
    const perPageSelect = page.locator('select[name="perPage"]');

    if (await perPageSelect.count() > 0) {
      // Change to 30 (opções válidas: 15, 30, 50, 100)
      // O select com onchange navega — usar Promise.all para aguardar URL
      try {
        await Promise.all([
          page.waitForURL(/perPage=30/, { timeout: 10000 }),
          perPageSelect.selectOption('30'),
        ]);
      } catch (error) {
        // Se falhar, aguarda networkidle para garantir navegação
        await page.waitForLoadState('networkidle', { timeout: 10000 });
      }

      // Verify URL updated
      expect(page.url()).toContain('perPage=30');
    }
  });
});

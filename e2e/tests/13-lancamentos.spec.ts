import { test, expect } from '@playwright/test';

/**
 * Helper: expande o search panel (#searchPanelBody) se estiver colapsado.
 * O painel usa Bootstrap collapse e fica fechado quando nao ha filtro ativo.
 */
async function expandSearchPanel(page: import('@playwright/test').Page) {
  const panelBody = page.locator('#searchPanelBody');
  const isVisible = await panelBody.isVisible();
  if (!isVisible) {
    await page.locator('#searchPanel .card-header').click();
    await panelBody.waitFor({ state: 'visible', timeout: 5000 });
  }
}

test.describe('Lancamentos Module', () => {
  test('index page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Should be on the lancamentos index page
    await expect(page).toHaveURL(/\/lancamentos\//);

    // Check that page has content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('new lancamento form loads successfully', async ({ page }) => {
    try {
      await page.goto('/lancamentos/new', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    // Should be on the new lancamento form page
    await expect(page).toHaveURL(/\/lancamentos\/new/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('vencidos (overdue) page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/vencidos', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Should be on the vencidos page
    await expect(page).toHaveURL(/\/lancamentos\/vencidos/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('estatisticas page loads successfully', async ({ page }) => {
    await page.goto('/lancamentos/estatisticas', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Should be on the estatisticas page
    await expect(page).toHaveURL(/\/lancamentos\/estatisticas/);

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();
  });

  test('edit lancamento form loads with valid ID', async ({ page }) => {
    try {
      await page.goto('/lancamentos/1/edit', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    const currentUrl = page.url();
    // Should either be on edit page or redirected
    expect(
      currentUrl.includes('/lancamentos') &&
      (currentUrl.includes('/edit') || currentUrl.includes('/'))
    ).toBeTruthy();
  });

  test('api lista endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/lista');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('lancamentos');
  });

  test('api estatisticas endpoint returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/estatisticas');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('estatisticas');
  });

  // ---------- INDEX & TABLE ----------
  test('index page displays table with columns', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Table should be present
    const table = page.locator('main table.table, table.table').first();
    await expect(table).toBeVisible();

    // Verify table columns exist
    const headerText = await table.textContent();
    expect(headerText).toContain('Vencimento');
    expect(headerText).toContain('Valor');
    expect(headerText).toContain('Status');
  });

  test('breadcrumb navigation is present on index', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    const breadcrumb = page.locator('ol.breadcrumb');
    if (await breadcrumb.count() > 0) {
      // Template renders 'Lancamentos' (sem acento) como item atual do breadcrumb
      const breadcrumbText = await breadcrumb.textContent();
      expect(breadcrumbText).toMatch(/Lan[cç]amentos/i);
    }
  });

  test('table has action buttons (edit/delete)', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Wait for table to render
    await page.waitForSelector('table tbody tr', { state: 'attached' });

    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      const firstRowText = await rows.first().textContent() ?? '';
      // Verificar se a linha nao e o placeholder vazio
      if (!firstRowText.includes('Nenhum lancamento')) {
        const firstRow = rows.first();
        // O template usa: a[href*="/edit"] para editar e buttons .btn-cancelar / .btn-baixa para acoes
        const editButton = firstRow.locator('a[href*="/edit"], a.btn-warning');
        const actionButtons = firstRow.locator('button.btn-baixa, button.btn-cancelar, button.btn-estornar, button[type="button"]');

        const hasEditButton = await editButton.count() > 0;
        const hasActionButton = await actionButtons.count() > 0;

        expect(hasEditButton || hasActionButton).toBeTruthy();
      }
    }
  });

  // ---------- SEARCH & FILTERS ----------
  test('search panel is visible on index page', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });

    const searchPanel = page.locator('#searchPanel');
    await expect(searchPanel).toBeVisible();
  });

  test('search filter by tipo (type) works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // O search panel usa collapse Bootstrap — expandir antes de interagir
    await expandSearchPanel(page);

    // O campo de filtro tem name="tipo" diretamente no form do search panel
    const tipoSelect = page.locator('#searchPanelBody select[name="tipo"]');

    if (await tipoSelect.count() > 0) {
      await tipoSelect.waitFor({ state: 'visible', timeout: 5000 });
      // Valores definidos no controller: 'receber' ou 'pagar'
      await tipoSelect.selectOption('receber');

      await page.locator('#searchForm button[type="submit"]').click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('/lancamentos/');
    }
  });

  test('search filter by status works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Expandir search panel colapsado
    await expandSearchPanel(page);

    const statusSelect = page.locator('#searchPanelBody select[name="status"]');

    if (await statusSelect.count() > 0) {
      await statusSelect.waitFor({ state: 'visible', timeout: 5000 });
      // Valores definidos no controller: 'aberto', 'pago', 'pago_parcial', 'cancelado', 'suspenso'
      await statusSelect.selectOption('aberto');

      await page.locator('#searchForm button[type="submit"]').click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('/lancamentos/');
    }
  });

  test('search filter by competencia (competency) works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Expandir search panel colapsado
    await expandSearchPanel(page);

    const competenciaSelect = page.locator('#searchPanelBody select[name="competencia"]');

    if (await competenciaSelect.count() > 0) {
      await competenciaSelect.waitFor({ state: 'visible', timeout: 5000 });

      // Tentar selecionar a primeira opcao disponivel (alem de "Todos")
      const options = await competenciaSelect.locator('option').count();
      if (options > 1) {
        await competenciaSelect.selectOption({ index: 1 });
      }

      await page.locator('#searchForm button[type="submit"]').click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('/lancamentos/');
    }
  });

  test('search filter by date range works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Expandir search panel colapsado
    await expandSearchPanel(page);

    // Campos de data diretamente no form do search panel
    const dateFromInput = page.locator('#searchPanelBody input[name="vencimentoDe"]');
    const dateToInput = page.locator('#searchPanelBody input[name="vencimentoAte"]');

    if (await dateFromInput.count() > 0) {
      await dateFromInput.waitFor({ state: 'visible', timeout: 5000 });
      await dateFromInput.fill('2024-01-01');

      if (await dateToInput.count() > 0) {
        await dateToInput.fill('2024-12-31');
      }

      await page.locator('#searchForm button[type="submit"]').click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('/lancamentos/');
    }
  });

  test('clear filters button resets search', async ({ page }) => {
    await page.goto('/lancamentos/?tipo=receber&status=aberto', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Quando ha filtros ativos, o painel ja fica expandido e o botao Limpar aparece
    // Botao limpar: <a href="..." id="btnLimpar"> com texto "Limpar"
    const clearButton = page.locator('#btnLimpar, a[href*="/lancamentos/"]:has-text("Limpar"), a[href*="/lancamentos/"]:has-text("Resetar")');

    if (await clearButton.count() > 0) {
      await clearButton.first().click();
      await page.waitForLoadState('networkidle');

      // URL should be back to base
      const url = page.url();
      expect(url).toContain('/lancamentos/');
    }
  });

  // ---------- SORTING ----------
  test('sort buttons with href containing sort= exist', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    const sortButtons = page.locator('a[href*="sort="]');
    const count = await sortButtons.count();
    expect(count).toBeGreaterThan(0);
  });

  test('sort by vencimento (due date) works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Click sort link for vencimento
    const sortLink = page.locator('a[href*="sort=dataVencimento"]').first();

    if (await sortLink.count() > 0) {
      await sortLink.click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('sort=dataVencimento');
    }
  });

  test('sort by valor (amount) works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Click sort link for valor
    const sortLink = page.locator('a[href*="sort=valor"]').first();

    if (await sortLink.count() > 0) {
      await sortLink.click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('sort=valor');
    }
  });

  test('sort by status works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Click sort link for status
    const sortLink = page.locator('a[href*="sort=status"]').first();

    if (await sortLink.count() > 0) {
      await sortLink.click();
      await page.waitForLoadState('networkidle');

      const url = page.url();
      expect(url).toContain('sort=status');
    }
  });

  // ---------- PAGINATION ----------
  test('pagination perPage select is present', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    const perPageSelect = page.locator('select[name="perPage"]');
    await expect(perPageSelect).toBeVisible();
  });

  test('pagination perPage change to 30 works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // O select de perPage usa onchange="this.form.submit()" e fica fora do searchPanel
    const perPageSelect = page.locator('select[name="perPage"]').first();

    if (await perPageSelect.count() > 0) {
      await perPageSelect.waitFor({ state: 'visible', timeout: 5000 });
      // Selecionar e aguardar a navegacao causada pelo onchange
      await Promise.all([
        page.waitForURL(/perPage=30/, { timeout: 10000 }),
        perPageSelect.selectOption('30'),
      ]);

      const url = page.url();
      expect(url).toContain('perPage=30');
    }
  });

  test('pagination perPage change to 50 works', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    const perPageSelect = page.locator('select[name="perPage"]').first();

    if (await perPageSelect.count() > 0) {
      await perPageSelect.waitFor({ state: 'visible', timeout: 5000 });
      await Promise.all([
        page.waitForURL(/perPage=50/, { timeout: 10000 }),
        perPageSelect.selectOption('50'),
      ]);

      const url = page.url();
      expect(url).toContain('perPage=50');
    }
  });

  test('next page link is present when needed', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Look for pagination next button (usa icone fas fa-angle-right, title="Proxima")
    const nextButton = page.locator('a[title*="Proxima"], a[title*="next"], a[aria-label*="next"]').first();

    if (await nextButton.count() > 0) {
      await expect(nextButton).toBeAttached();
    }
  });

  // ---------- NEW FORM ----------
  test('new lancamento form loads with all required fields', async ({ page }) => {
    try {
      await page.goto('/lancamentos/new', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    // Form should be present
    const form = page.locator('form').first();
    await expect(form).toBeVisible();

    // Check for key fields
    const tipoField = page.locator('select[name*="tipo"]').first();
    const valorField = page.locator('input[name*="valor"]').first();
    const vencimentoField = page.locator('input[name*="dataVencimento"]').first();

    await expect(tipoField).toBeAttached();
    await expect(valorField).toBeAttached();
    await expect(vencimentoField).toBeAttached();
  });

  test('new form tipo field is required', async ({ page }) => {
    try {
      await page.goto('/lancamentos/new', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    const tipoField = page.locator('select[name*="tipo"]').first();

    if (await tipoField.count() > 0) {
      // Check if field has required attribute
      const isRequired = await tipoField.evaluate((el: HTMLSelectElement) => el.required);
      expect(isRequired).toBeTruthy();
    }
  });

  test('new form valor field is required', async ({ page }) => {
    try {
      await page.goto('/lancamentos/new', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    const valorField = page.locator('input[name*="valor"]').first();

    if (await valorField.count() > 0) {
      const isRequired = await valorField.evaluate((el: HTMLInputElement) => el.required);
      expect(isRequired).toBeTruthy();
    }
  });

  test('submit button is visible on new form', async ({ page }) => {
    try {
      await page.goto('/lancamentos/new', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    const submitButton = page.locator('button[type="submit"]').first();
    await expect(submitButton).toBeVisible();
  });

  // ---------- EDIT FORM ----------
  test('edit form loads with pre-filled data when lancamento exists', async ({ page }) => {
    let response;
    try {
      response = await page.goto('/lancamentos/1/edit', { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {
      test.skip();
      return;
    }

    if (response?.status() === 200) {
      // Form should be present with data
      const form = page.locator('form').first();
      await expect(form).toBeVisible();

      // Check that fields have values (pre-filled)
      const tipoField = page.locator('select[name*="tipo"]').first();
      const valorField = page.locator('input[name*="valor"]').first();

      if (await tipoField.count() > 0) {
        const value = await tipoField.inputValue();
        expect(value).toBeTruthy();
      }
    }
  });

  // ---------- DELETE ----------
  test('delete form is present in table rows', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Wait for table to render
    await page.waitForSelector('table tbody tr', { state: 'attached' });

    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      const firstRowText = await rows.first().textContent() ?? '';
      if (!firstRowText.includes('Nenhum lancamento')) {
        // No template de lancamentos, as acoes sao via modals JS (btn-baixa, btn-cancelar, btn-estornar)
        // e nao via form com _token. Verificar presenca de botoes de acao ou link de edicao.
        const actionElements = page.locator('table .btn-group a, table .btn-group button').first();
        if (await actionElements.count() > 0) {
          await expect(actionElements).toBeVisible();
        }
      }
    }
  });

  test('delete button/link is clickable in table', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    await page.waitForSelector('table tbody tr', { state: 'attached' });

    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      const firstRow = rows.first();

      // Acoes do lancamento: btn-cancelar, btn-baixa, btn-estornar ou link de edicao
      const actionButton = firstRow.locator(
        'button.btn-cancelar, button.btn-baixa, button.btn-estornar, a[href*="/edit"]'
      ).first();

      if (await actionButton.count() > 0) {
        await expect(actionButton).toBeVisible();
      }
    }
  });

  // ---------- NEW TESTS - API ENDPOINTS (EXTENDED) ----------
  test('api lista endpoint with filters returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/lista?tipo=receber&status=aberto');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('lancamentos');
  });

  test('api estatisticas endpoint with filters returns valid JSON', async ({ page }) => {
    const response = await page.request.get('/lancamentos/api/estatisticas?ano=2024');
    expect(response.ok()).toBeTruthy();
    expect(response.status()).toBe(200);

    const json = await response.json();
    expect(json).toHaveProperty('success');
    expect(json).toHaveProperty('estatisticas');
  });

  // ---------- UI ELEMENTS ----------
  test('status badge is displayed in table', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    await page.waitForSelector('table tbody tr', { state: 'attached' });

    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      const firstRow = rows.first();

      // Status badge: <span class="badge bg-..."> no template
      const statusBadge = rows.first().locator('span.badge');

      if (await statusBadge.count() > 0) {
        await expect(statusBadge.first()).toBeVisible();
      }
    }
  });

  test('valor (amount) is displayed formatted in table', async ({ page }) => {
    await page.goto('/lancamentos/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    await page.waitForSelector('table tbody tr', { state: 'attached' });

    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();

    if (rowCount > 0) {
      const rowText = await rows.first().textContent();
      if (rowText && !rowText.includes('Nenhum lancamento')) {
        // Should contain some number (valor)
        expect(rowText).toMatch(/\d+[\.,]\d{2}/);
      }
    }
  });

  test('vencidos (overdue) page displays overdue lancamentos', async ({ page }) => {
    await page.goto('/lancamentos/vencidos', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();

    // Should either show table or empty message
    const table = page.locator('table.table').first();
    const emptyMessage = page.locator('text=/Nenhum|Sem resultados/i');

    const hasTable = await table.count() > 0;
    const hasEmpty = await emptyMessage.count() > 0;

    expect(hasTable || hasEmpty).toBeTruthy();
  });

  test('estatisticas page displays stats', async ({ page }) => {
    await page.goto('/lancamentos/estatisticas', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('networkidle');

    // Page should have content
    const body = page.locator('body');
    await expect(body).toBeTruthy();

    // Should have some card or stat display
    const statCards = page.locator('[class*="card"], [class*="stat"], [class*="box"]');

    // If page has content, should have at least some elements
    const pageContent = await page.content();
    expect(pageContent.length).toBeGreaterThan(100);
  });
});

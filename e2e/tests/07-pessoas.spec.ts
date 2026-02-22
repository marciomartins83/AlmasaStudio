import { test, expect } from '@playwright/test';

test.describe('Pessoas Module', () => {
  // ... existing tests ...

  test('Buscar pessoa COM conjugue', async ({ page }) => {
    // Use the advanced search endpoint to find a person that has a conjugal relationship
    const response = await page.request.post('/pessoa/search-pessoa-advanced', {
      data: {
        criteria: 'nome',
        value: 'Pessoa Conjuge Teste',
      },
    });

    const json = await response.json();

    expect(json.success).toBe(true);
    expect(json.pessoa).toBeDefined();
    expect(json.pessoa.conjuge).not.toBeNull();

    const conj = json.pessoa.conjuge;
    expect(conj.id).toBeDefined();
    expect(conj.nome).toBeDefined();
    expect(conj.cpf).toBeDefined();
    expect(Array.isArray(conj.telefones)).toBe(true);
    expect(Array.isArray(conj.emails)).toBe(true);
    expect(Array.isArray(conj.profissoes)).toBe(true);
  });
});

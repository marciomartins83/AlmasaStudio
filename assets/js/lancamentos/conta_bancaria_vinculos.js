export function initContaBancariaVinculos() {
  const cfg = window.LANCAMENTOS_PLANO_CONTA;
  const url = window.LANCAMENTOS_VINCULOS_URL;
  const cbUrl = window.LANCAMENTOS_CONTA_BANCARIA_URL;

  const debitoHidden = document.getElementById(cfg?.debito?.hiddenId);
  const creditoHidden = document.getElementById(cfg?.credito?.hiddenId);
  const debitoDisplay = document.getElementById(cfg?.debito?.displayId);
  const creditoDisplay = document.getElementById(cfg?.credito?.displayId);
  const contaBancariaIdHidden = document.getElementById('lancamentos_contaBancariaId');

  const selectDebito = document.getElementById('cb_vinc_debito_select');
  const selectCredito = document.getElementById('cb_vinc_credito_select');
  const wrapperDebito = document.getElementById('cb_vinc_debito_wrapper');
  const wrapperCredito = document.getElementById('cb_vinc_credito_wrapper');
  const msgDebito = document.getElementById('cb_vinc_debito_msg');
  const msgCredito = document.getElementById('cb_vinc_credito_msg');

  const form = document.querySelector('form.needs-validation');
  const modalEl = document.getElementById('modalContaBancaria');
  const btnConfirmar = document.getElementById('btnConfirmarContaBancaria');

  if (!cfg || !url || !debitoHidden || !creditoHidden || !contaBancariaIdHidden) return;

  // === Selects dinâmicos na aba Vínculos ===
  function fetchContas(planoId, lado) {
    const select = lado === 'debito' ? selectDebito : selectCredito;
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    if (!select || !wrapper || !msg) return;
    wrapper.style.display = '';

    fetch(url.replace('__PLANO_ID__', planoId), { headers: { Accept: 'application/json' } })
      .then(r => { if (!r.ok) throw new Error('err'); return r.json(); })
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          select.style.display = 'none';
          msg.textContent = 'Nenhuma conta bancária vinculada. Ao salvar, será solicitada.';
          msg.style.display = '';
          return;
        }
        select.style.display = '';
        msg.style.display = 'none';
        select.innerHTML = '<option value="">Selecione a conta bancária...</option>';
        let defaultSet = false;
        data.forEach(item => {
          const opt = document.createElement('option');
          opt.value = item.id;
          opt.textContent = item.label;
          select.appendChild(opt);
          if (item.padrao && !defaultSet) { opt.selected = true; defaultSet = true; }
        });
      })
      .catch(() => { select.style.display = 'none'; msg.textContent = 'Erro ao obter contas.'; msg.style.display = ''; })
      .finally(() => atualizarHidden());
  }

  function limpar(lado) {
    const select = lado === 'debito' ? selectDebito : selectCredito;
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    if (!select || !wrapper || !msg) return;
    wrapper.style.display = 'none';
    select.innerHTML = '<option value="">Selecione a conta bancária...</option>';
    msg.style.display = 'none';
    atualizarHidden();
  }

  function atualizarHidden() {
    const v1 = selectDebito?.value || '';
    const v2 = selectCredito?.value || '';
    contaBancariaIdHidden.value = v1 || v2 || '';
  }

  debitoHidden.addEventListener('plano-conta-selecionado', e => { if (e.detail?.id) fetchContas(e.detail.id, 'debito'); });
  debitoHidden.addEventListener('plano-conta-limpo', () => limpar('debito'));
  creditoHidden.addEventListener('plano-conta-selecionado', e => { if (e.detail?.id) fetchContas(e.detail.id, 'credito'); });
  creditoHidden.addEventListener('plano-conta-limpo', () => limpar('credito'));
  if (selectDebito) selectDebito.addEventListener('change', atualizarHidden);
  if (selectCredito) selectCredito.addEventListener('change', atualizarHidden);

  // === Modal com autocomplete de busca (restaurado do original) ===
  if (!form || !modalEl || !btnConfirmar || !cbUrl) return;

  function initCbAutocomplete(prefix) {
    const display = document.getElementById(`modal_cb_${prefix}_display`);
    const hidden  = document.getElementById(`modal_cb_${prefix}_hidden`);
    const planoId = document.getElementById(`modal_cb_${prefix}_plano_id`);
    const results = document.getElementById(`modal_cb_${prefix}_results`);
    const clear   = document.getElementById(`modal_cb_${prefix}_clear`);
    if (!display || !hidden || !results) return null;

    let timer = null;

    display.addEventListener('input', () => {
      const q = display.value.trim();
      hidden.value = '';
      if (clear) clear.style.display = 'none';
      clearTimeout(timer);
      if (q.length < 2) { fechar(); return; }
      timer = setTimeout(() => buscar(q), 300);
    });

    display.addEventListener('keydown', (e) => { if (e.key === 'Escape') fechar(); });

    if (clear) {
      clear.addEventListener('click', () => {
        display.value = '';
        hidden.value = '';
        clear.style.display = 'none';
        display.focus();
      });
    }

    async function buscar(q) {
      try {
        const resp = await fetch(`${cbUrl}?q=${encodeURIComponent(q)}`);
        if (!resp.ok) return;
        renderizar(await resp.json());
      } catch (err) { console.error('[cb-autocomplete] erro:', err); }
    }

    function renderizar(contas) {
      results.innerHTML = '';
      if (!contas.length) {
        results.innerHTML = '<div class="list-group-item text-muted fst-italic">Nenhuma conta encontrada</div>';
        results.style.display = 'block';
        return;
      }
      contas.forEach(c => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action';
        const label = c.titular ? `${c.descricao} — ${c.titular}` : (c.descricao || '');
        btn.textContent = label;
        btn.addEventListener('mousedown', (e) => {
          e.preventDefault();
          display.value = label;
          hidden.value = c.id;
          if (clear) clear.style.display = '';
          fechar();
        });
        results.appendChild(btn);
      });
      results.style.display = 'block';
    }

    function fechar() { results.style.display = 'none'; results.innerHTML = ''; }
    function reset() { display.value = ''; hidden.value = ''; if (clear) clear.style.display = 'none'; }

    return { hidden, planoId, display, reset };
  }

  const acDeb  = initCbAutocomplete('deb');
  const acCred = initCbAutocomplete('cred');
  if (!acDeb || !acCred) return;

  // Interceptar submit — abrir modal se falta conta bancária
  form.addEventListener('submit', (e) => {
    const temDebito  = debitoHidden.value && debitoHidden.value !== '';
    const temCredito = creditoHidden.value && creditoHidden.value !== '';
    const temConta   = contaBancariaIdHidden.value && contaBancariaIdHidden.value !== '';

    // Modal abre se tem pelo menos 1 plano preenchido e não tem conta selecionada
    if ((temDebito || temCredito) && !temConta) {
      e.preventDefault();
      document.getElementById('modal_nome_debito').textContent = debitoDisplay?.value || '—';
      document.getElementById('modal_nome_credito').textContent = creditoDisplay?.value || '—';
      acDeb.planoId.value = debitoHidden.value || '';
      acCred.planoId.value = creditoHidden.value || '';
      acDeb.reset();
      acCred.reset();

      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      setTimeout(() => acDeb.display.focus(), 500);
    }
  });

  btnConfirmar.addEventListener('click', async () => {
    const debId  = acDeb.hidden.value;
    const credId = acCred.hidden.value;

    if (!debId && !credId) {
      acDeb.display.classList.add('is-invalid');
      acCred.display.classList.add('is-invalid');
      acDeb.display.focus();
      return;
    }
    acDeb.display.classList.remove('is-invalid');
    acCred.display.classList.remove('is-invalid');

    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    // Criar vínculos bancários automaticamente
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const criarUrl = window.LANCAMENTOS_CRIAR_VINCULO_URL;

    try {
      if (debId && acDeb.planoId.value && criarUrl) {
        await fetch(criarUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ plano_id: acDeb.planoId.value, conta_id: debId })
        });
      }
      if (credId && acCred.planoId.value && criarUrl) {
        await fetch(criarUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ plano_id: acCred.planoId.value, conta_id: credId })
        });
      }
    } catch (err) { console.warn('Vínculo não criado:', err); }

    // Setar conta bancária e submeter
    contaBancariaIdHidden.value = debId || credId;
    bootstrap.Modal.getInstance(modalEl).hide();
    form.submit();
  });
}

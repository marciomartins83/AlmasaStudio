export function initContaBancariaVinculos() {
  const cfg = window.LANCAMENTOS_PLANO_CONTA;
  const url = window.LANCAMENTOS_VINCULOS_URL;
  const cbUrl = window.LANCAMENTOS_CONTA_BANCARIA_URL;

  const debitoHidden = document.getElementById(cfg?.debito?.hiddenId);
  const creditoHidden = document.getElementById(cfg?.credito?.hiddenId);
  const debitoDisplay = document.getElementById(cfg?.debito?.displayId);
  const creditoDisplay = document.getElementById(cfg?.credito?.displayId);
  const contaBancariaIdHidden = document.getElementById('lancamentos_contaBancariaId');

  const wrapperDebito = document.getElementById('cb_vinc_debito_wrapper');
  const wrapperCredito = document.getElementById('cb_vinc_credito_wrapper');
  const msgDebito = document.getElementById('cb_vinc_debito_msg');
  const msgCredito = document.getElementById('cb_vinc_credito_msg');

  const form = document.querySelector('form.needs-validation');
  const modalEl = document.getElementById('modalContaBancaria');
  const btnConfirmar = document.getElementById('btnConfirmarContaBancaria');

  if (!cfg || !url || !debitoHidden || !creditoHidden || !contaBancariaIdHidden || !cbUrl) return;

  // === Autocomplete genérico para busca de conta bancária ===
  function initBuscaConta(prefix) {
    const display = document.getElementById(`${prefix}_display`);
    const hidden  = document.getElementById(`${prefix}_hidden`);
    const results = document.getElementById(`${prefix}_results`);
    const clear   = document.getElementById(`${prefix}_clear`);
    if (!display || !hidden || !results) return null;

    let timer = null;

    display.addEventListener('input', () => {
      const q = display.value.trim();
      hidden.value = '';
      if (clear) clear.style.display = 'none';
      clearTimeout(timer);
      if (q.length < 2) { fechar(); atualizarHidden(); return; }
      timer = setTimeout(() => buscar(q), 300);
    });

    display.addEventListener('keydown', (e) => { if (e.key === 'Escape') fechar(); });

    if (clear) {
      clear.addEventListener('click', () => {
        display.value = '';
        hidden.value = '';
        clear.style.display = 'none';
        display.focus();
        atualizarHidden();
      });
    }

    async function buscar(q) {
      try {
        const flagProp = document.getElementById('filtro_conta_proprietario');
        const propParam = flagProp && !flagProp.checked ? '0' : '1';
        const resp = await fetch(`${cbUrl}?q=${encodeURIComponent(q)}&proprietario=${propParam}`);
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
          atualizarHidden();
        });
        results.appendChild(btn);
      });
      results.style.display = 'block';
    }

    function fechar() { results.style.display = 'none'; results.innerHTML = ''; }
    function reset() { display.value = ''; hidden.value = ''; if (clear) clear.style.display = 'none'; }
    function preload(label, id) { display.value = label; hidden.value = id; if (clear) clear.style.display = ''; }

    // Fechar ao clicar fora
    document.addEventListener('click', (e) => {
      if (!display.closest('.pessoa-autocomplete-wrapper')?.contains(e.target)) fechar();
    });

    return { hidden, display, reset, preload };
  }

  // Autocomplete geral (sempre visível na aba Vínculos)
  const acVincGeral = initBuscaConta('cb_vinc_geral');

  // Autocompletes por plano (débito/crédito) na aba Vínculos
  const acVincDeb = initBuscaConta('cb_vinc_debito');
  const acVincCred = initBuscaConta('cb_vinc_credito');

  // Autocompletes no Modal
  const acModalDeb = initBuscaConta('modal_cb_deb');
  const acModalCred = initBuscaConta('modal_cb_cred');

  function atualizarHidden() {
    // Prioridade: débito > crédito > geral
    const v1 = acVincDeb?.hidden?.value || '';
    const v2 = acVincCred?.hidden?.value || '';
    const v3 = acVincGeral?.hidden?.value || '';
    contaBancariaIdHidden.value = v1 || v2 || v3 || '';
  }

  // === Mostrar/ocultar wrappers conforme plano selecionado ===
  function onPlanoSelecionado(planoId, lado) {
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    const ac = lado === 'debito' ? acVincDeb : acVincCred;
    if (!wrapper || !msg || !ac) return;
    wrapper.style.display = '';

    // Buscar contas vinculadas para pré-selecionar se houver default
    fetch(url.replace('__PLANO_ID__', planoId), { headers: { Accept: 'application/json' } })
      .then(r => r.ok ? r.json() : [])
      .then(data => {
        if (Array.isArray(data) && data.length > 0) {
          msg.style.display = 'none';
          // Auto-preencher com a conta padrão
          const padrao = data.find(d => d.padrao) || data[0];
          ac.preload(padrao.label, padrao.id);
          atualizarHidden();
        } else {
          msg.textContent = 'Nenhuma conta vinculada. Busque uma conta abaixo ou será solicitada ao salvar.';
          msg.style.display = '';
        }
      })
      .catch(() => {});
  }

  function onPlanoLimpo(lado) {
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    const ac = lado === 'debito' ? acVincDeb : acVincCred;
    if (!wrapper || !ac) return;
    wrapper.style.display = 'none';
    if (msg) msg.style.display = 'none';
    ac.reset();
    atualizarHidden();
  }

  debitoHidden.addEventListener('plano-conta-selecionado', e => { if (e.detail?.id) onPlanoSelecionado(e.detail.id, 'debito'); });
  debitoHidden.addEventListener('plano-conta-limpo', () => onPlanoLimpo('debito'));
  creditoHidden.addEventListener('plano-conta-selecionado', e => { if (e.detail?.id) onPlanoSelecionado(e.detail.id, 'credito'); });
  creditoHidden.addEventListener('plano-conta-limpo', () => onPlanoLimpo('credito'));

  // === Modal com autocomplete — intercepta submit se falta conta ===
  if (!form || !modalEl || !btnConfirmar || !acModalDeb || !acModalCred) return;

  form.addEventListener('submit', (e) => {
    const temDebito = debitoHidden.value && debitoHidden.value !== '';
    const temCredito = creditoHidden.value && creditoHidden.value !== '';
    const temConta = contaBancariaIdHidden.value && contaBancariaIdHidden.value !== '';

    // Contas a pagar: salva sem conta bancária (será informada na baixa)
    // Transferências e contas a receber: exige conta bancária
    const tipoSelect = document.getElementById('lancamentos_tipo');
    const tipoPagar = tipoSelect && tipoSelect.value === 'pagar';

    if ((temDebito || temCredito) && !temConta && !tipoPagar) {
      e.preventDefault();
      document.getElementById('modal_nome_debito').textContent = debitoDisplay?.value || '—';
      document.getElementById('modal_nome_credito').textContent = creditoDisplay?.value || '—';
      document.getElementById('modal_cb_deb_plano_id').value = debitoHidden.value || '';
      document.getElementById('modal_cb_cred_plano_id').value = creditoHidden.value || '';
      acModalDeb.reset();
      acModalCred.reset();

      const modal = new bootstrap.Modal(modalEl);
      modal.show();
      setTimeout(() => acModalDeb.display.focus(), 500);
    }
  });

  btnConfirmar.addEventListener('click', async () => {
    const debId = acModalDeb.hidden.value;
    const credId = acModalCred.hidden.value;

    if (!debId && !credId) {
      acModalDeb.display.classList.add('is-invalid');
      acModalCred.display.classList.add('is-invalid');
      acModalDeb.display.focus();
      return;
    }
    acModalDeb.display.classList.remove('is-invalid');
    acModalCred.display.classList.remove('is-invalid');

    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

    // Criar vínculos automaticamente
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const criarUrl = window.LANCAMENTOS_CRIAR_VINCULO_URL;
    const debPlanoId = document.getElementById('modal_cb_deb_plano_id')?.value;
    const credPlanoId = document.getElementById('modal_cb_cred_plano_id')?.value;

    try {
      if (debId && debPlanoId && criarUrl) {
        await fetch(criarUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ plano_id: debPlanoId, conta_id: debId })
        });
      }
      if (credId && credPlanoId && criarUrl) {
        await fetch(criarUrl, {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ plano_id: credPlanoId, conta_id: credId })
        });
      }
    } catch (err) { console.warn('Vínculo não criado:', err); }

    contaBancariaIdHidden.value = debId || credId;
    bootstrap.Modal.getInstance(modalEl).hide();
    form.submit();
  });
}

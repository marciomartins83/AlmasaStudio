export function initContaBancariaVinculos() {
  const cfg = window.LANCAMENTOS_PLANO_CONTA;
  const url = window.LANCAMENTOS_VINCULOS_URL;

  const debitoHidden = document.getElementById(cfg?.debito?.hiddenId);
  const creditoHidden = document.getElementById(cfg?.credito?.hiddenId);
  const contaBancariaIdHidden = document.getElementById('lancamentos_contaBancariaId');

  const selectDebito = document.getElementById('cb_vinc_debito_select');
  const selectCredito = document.getElementById('cb_vinc_credito_select');

  const wrapperDebito = document.getElementById('cb_vinc_debito_wrapper');
  const wrapperCredito = document.getElementById('cb_vinc_credito_wrapper');

  const msgDebito = document.getElementById('cb_vinc_debito_msg');
  const msgCredito = document.getElementById('cb_vinc_credito_msg');

  if (!cfg || !url || !debitoHidden || !creditoHidden || !contaBancariaIdHidden) {
    return;
  }

  function fetchContas(planoId, lado) {
    const select = lado === 'debito' ? selectDebito : selectCredito;
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    if (!select || !wrapper || !msg) return;
    wrapper.style.display = '';

    const endpoint = url.replace('__PLANO_ID__', planoId);
    fetch(endpoint, { headers: { Accept: 'application/json' } })
      .then((resp) => {
        if (!resp.ok) throw new Error('Network response was not ok');
        return resp.json();
      })
      .then((data) => {
        if (!Array.isArray(data) || data.length === 0) {
          select.style.display = 'none';
          msg.textContent = 'Nenhuma conta bancaria vinculada a este plano.';
          msg.style.display = '';
          return;
        }
        select.style.display = '';
        msg.style.display = 'none';

        select.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecione a conta bancaria...';
        select.appendChild(placeholder);

        let defaultSet = false;
        data.forEach((item) => {
          const option = document.createElement('option');
          option.value = item.id;
          option.textContent = item.label;
          select.appendChild(option);
          if (item.padrao && !defaultSet) {
            option.selected = true;
            defaultSet = true;
          }
        });
      })
      .catch((e) => {
        console.error('Error fetching contas:', e);
        select.style.display = 'none';
        msg.textContent = 'Erro ao obter contas bancárias.';
        msg.style.display = '';
      })
      .finally(() => {
        atualizarHidden();
      });
  }

  function limpar(lado) {
    const select = lado === 'debito' ? selectDebito : selectCredito;
    const wrapper = lado === 'debito' ? wrapperDebito : wrapperCredito;
    const msg = lado === 'debito' ? msgDebito : msgCredito;
    if (!select || !wrapper || !msg) return;
    wrapper.style.display = 'none';
    select.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Selecione a conta bancaria...';
    select.appendChild(placeholder);
    msg.style.display = 'none';
    atualizarHidden();
  }

  function atualizarHidden() {
    const valDeb = selectDebito ? selectDebito.value : '';
    const valCred = selectCredito ? selectCredito.value : '';
    const chosen = valDeb !== '' ? valDeb : valCred !== '' ? valCred : '';
    contaBancariaIdHidden.value = chosen;
  }

  debitoHidden.addEventListener('plano-conta-selecionado', (e) => {
    if (e.detail && e.detail.id) {
      fetchContas(e.detail.id, 'debito');
    }
  });

  debitoHidden.addEventListener('plano-conta-limpo', () => {
    limpar('debito');
  });

  creditoHidden.addEventListener('plano-conta-selecionado', (e) => {
    if (e.detail && e.detail.id) {
      fetchContas(e.detail.id, 'credito');
    }
  });

  creditoHidden.addEventListener('plano-conta-limpo', () => {
    limpar('credito');
  });

  if (selectDebito) {
    selectDebito.addEventListener('change', atualizarHidden);
  }

  if (selectCredito) {
    selectCredito.addEventListener('change', atualizarHidden);
  }

  // Interceptar submit — abrir modal para vincular conta se necessário
  const form = document.querySelector('form.needs-validation');
  const modalEl = document.getElementById('modalVincularConta');
  const btnConfirmar = document.getElementById('btnConfirmarVinculo');

  if (form && modalEl && btnConfirmar) {
    let contasCarregadas = false;
    let todasContas = [];

    async function carregarTodasContas() {
      if (contasCarregadas) return todasContas;
      const urlTodas = window.LANCAMENTOS_CONTAS_TODAS_URL;
      if (!urlTodas) return [];
      try {
        const resp = await fetch(urlTodas, { headers: { Accept: 'application/json' } });
        if (resp.ok) todasContas = await resp.json();
        contasCarregadas = true;
      } catch (e) { console.error(e); }
      return todasContas;
    }

    function popularSelect(selectEl, contas) {
      selectEl.innerHTML = '';
      const ph = document.createElement('option');
      ph.value = '';
      ph.textContent = 'Selecione a conta bancária...';
      selectEl.appendChild(ph);
      contas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.titular ? c.descricao + ' — ' + c.titular : c.descricao;
        selectEl.appendChild(opt);
      });
    }

    form.addEventListener('submit', async (e) => {
      const temDebito = debitoHidden.value && debitoHidden.value !== '';
      const temCredito = creditoHidden.value && creditoHidden.value !== '';

      if (!temDebito && !temCredito) return;

      const contaSelecionada = contaBancariaIdHidden.value && contaBancariaIdHidden.value !== '';
      if (contaSelecionada) return;

      // Verificar quais lados faltam conta
      const faltaDebito = temDebito && selectDebito && (!selectDebito.value || selectDebito.style.display === 'none');
      const faltaCredito = temCredito && selectCredito && (!selectCredito.value || selectCredito.style.display === 'none');

      if (!faltaDebito && !faltaCredito) return;

      e.preventDefault();

      // Carregar todas as contas bancárias
      const contas = await carregarTodasContas();

      // Configurar modal
      const debRow = document.getElementById('modal_vinc_debito_row');
      const credRow = document.getElementById('modal_vinc_credito_row');
      const debSelect = document.getElementById('modal_vinc_debito_select');
      const credSelect = document.getElementById('modal_vinc_credito_select');
      const debPlanoId = document.getElementById('modal_vinc_debito_plano_id');
      const credPlanoId = document.getElementById('modal_vinc_credito_plano_id');

      if (faltaDebito) {
        debRow.style.display = '';
        document.getElementById('modal_vinc_debito_nome').textContent =
          document.getElementById('plano_debito_display').value || '—';
        debPlanoId.value = debitoHidden.value;
        popularSelect(debSelect, contas);
      } else {
        debRow.style.display = 'none';
      }

      if (faltaCredito) {
        credRow.style.display = '';
        document.getElementById('modal_vinc_credito_nome').textContent =
          document.getElementById('plano_credito_display').value || '—';
        credPlanoId.value = creditoHidden.value;
        popularSelect(credSelect, contas);
      } else {
        credRow.style.display = 'none';
      }

      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    });

    btnConfirmar.addEventListener('click', async () => {
      const debRow = document.getElementById('modal_vinc_debito_row');
      const credRow = document.getElementById('modal_vinc_credito_row');
      const debSelect = document.getElementById('modal_vinc_debito_select');
      const credSelect = document.getElementById('modal_vinc_credito_select');
      const debPlanoId = document.getElementById('modal_vinc_debito_plano_id');
      const credPlanoId = document.getElementById('modal_vinc_credito_plano_id');

      const debVisible = debRow.style.display !== 'none';
      const credVisible = credRow.style.display !== 'none';

      if (debVisible && !debSelect.value) {
        debSelect.classList.add('is-invalid');
        debSelect.focus();
        return;
      }
      if (credVisible && !credSelect.value) {
        credSelect.classList.add('is-invalid');
        credSelect.focus();
        return;
      }
      debSelect.classList.remove('is-invalid');
      credSelect.classList.remove('is-invalid');

      btnConfirmar.disabled = true;
      btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vinculando...';

      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
      const criarUrl = window.LANCAMENTOS_CRIAR_VINCULO_URL;

      try {
        // Criar vínculos via AJAX
        if (debVisible && debSelect.value && criarUrl) {
          await fetch(criarUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ plano_id: debPlanoId.value, conta_id: debSelect.value })
          });
        }
        if (credVisible && credSelect.value && criarUrl) {
          await fetch(criarUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ plano_id: credPlanoId.value, conta_id: credSelect.value })
          });
        }

        // Setar conta bancária no form (prioridade: débito > crédito)
        const contaId = (debVisible && debSelect.value) ? debSelect.value : credSelect.value;
        contaBancariaIdHidden.value = contaId;

        // Fechar modal e submeter form
        bootstrap.Modal.getInstance(modalEl).hide();
        form.submit();
      } catch (error) {
        console.error('Erro ao criar vínculo:', error);
        alert('Erro ao criar vínculo. Tente novamente.');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Vincular e Salvar';
      }
    });
  }
}

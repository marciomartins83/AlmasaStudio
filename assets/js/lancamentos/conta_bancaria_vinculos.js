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
}

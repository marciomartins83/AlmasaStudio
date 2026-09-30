/**
 * Módulo de Cobranças / Emissão de Boletos em Lote
 *
 * REGRA DE NEGÓCIO: boleto só é emitido com RELATÓRIO DE CONFERÊNCIA APROVADO.
 * Fluxo único de emissão: selecionar -> gerar relatório de conferência -> aprovar
 * (no modal) -> só então emite e envia. Não há envio direto sem aprovação.
 */

// IDs que serão emitidos após a aprovação do relatório (setado antes de abrir o modal).
let idsParaEmitir = [];

document.addEventListener('DOMContentLoaded', function () {
    initCobranca();
});

function initCobranca() {
    const checkAll = document.getElementById('checkAll');
    const cobrancaChecks = document.querySelectorAll('.cobranca-check');
    const btnRelatorio = document.getElementById('btnRelatorioConferencia');
    const contador = document.getElementById('contadorSelecionados');

    if (checkAll) {
        initCheckboxes(checkAll, cobrancaChecks, btnRelatorio, contador);
    }

    initBotoesEnviar();          // agora abrem o relatório (não enviam direto)
    initBotoesCancelar();
    initBotoesMarcarEntregue();
    initBotoesFaixaVencimento();
    initRelatorio();             // botão do lote + botão aprovar do modal
    initGeracaoPeriodo();        // toggle competência / intervalo
    initEdicaoItens();           // modal de edição de itens
    initAvulso();                // modal de boleto avulso
    initVerificacao();           // verificação de pagamento (sob demanda)
}

// --------------------------------------------------------- boleto avulso
function initAvulso() {
    const btn = document.getElementById('btnAvulso');
    if (btn) btn.addEventListener('click', abrirAvulso);
    const add = document.getElementById('btnAvulsoAddItem');
    if (add) add.addEventListener('click', () => avulsoAddLinha());
    const emit = document.getElementById('btnAvulsoEmitir');
    if (emit) emit.addEventListener('click', avulsoEmitir);
}

function abrirAvulso() {
    document.getElementById('avulsoItensBody').innerHTML = '';
    avulsoAddLinha();
    ['avulso_pagador', 'avulso_pagador_display', 'avulsoVenc'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    avulsoTotal();
    new bootstrap.Modal(document.getElementById('avulsoModal')).show();
}

function avulsoAddLinha(item) {
    item = item || { tipo: 'OUTROS', descricao: '', valor: '' };
    const tipos = window.COBRANCA_TIPOS || {};
    const tb = document.getElementById('avulsoItensBody');
    const tr = document.createElement('tr');
    let opts = '';
    Object.keys(tipos).forEach(k => { opts += `<option value="${k}" ${k === item.tipo ? 'selected' : ''}>${tipos[k]}</option>`; });
    tr.innerHTML = `
        <td><select class="form-select form-select-sm av-tipo">${opts}</select></td>
        <td><input type="text" class="form-control form-control-sm av-desc" placeholder="Descrição" value="${(item.descricao || '').replace(/"/g, '&quot;')}"></td>
        <td><input type="text" class="form-control form-control-sm av-valor text-end" placeholder="0,00" inputmode="decimal"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm av-rem"><i class="fas fa-trash"></i></button></td>`;
    tb.appendChild(tr);
    tr.querySelector('.av-rem').addEventListener('click', () => { tr.remove(); avulsoTotal(); });
    tr.querySelector('.av-valor').addEventListener('input', avulsoTotal);
}

function avulsoTotal() {
    let t = 0;
    document.querySelectorAll('#avulsoItensBody .av-valor').forEach(i => { t += parseValor(i.value); });
    document.getElementById('avulsoTotal').textContent = 'R$ ' + t.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function avulsoEmitir() {
    const pagador = parseInt((document.getElementById('avulso_pagador') || {}).value || 0, 10);
    const config = parseInt((document.getElementById('avulsoConfig') || {}).value || 0, 10);
    const venc = (document.getElementById('avulsoVenc') || {}).value;
    if (!pagador) { showToast('warning', 'Selecione o pagador.'); return; }
    if (!venc) { showToast('warning', 'Informe o vencimento.'); return; }
    const itens = [];
    document.querySelectorAll('#avulsoItensBody tr').forEach(tr => {
        const v = parseValor(tr.querySelector('.av-valor').value);
        if (v > 0) itens.push({ tipo: tr.querySelector('.av-tipo').value, descricao: tr.querySelector('.av-desc').value, valor: v });
    });
    if (itens.length === 0) { showToast('warning', 'Informe ao menos um item com valor.'); return; }

    const btn = document.getElementById('btnAvulsoEmitir');
    const o = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Emitindo...';
    fetch(window.COBRANCA_ROUTES.avulso, {
        method: 'POST', headers: jsonHeaders(),
        body: JSON.stringify({ pagador_id: pagador, config_id: config, vencimento: venc, itens: itens, aprovado: true })
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = o;
            if (data.success) {
                showToast('success', data.message || 'Boleto avulso emitido.');
                const m = bootstrap.Modal.getInstance(document.getElementById('avulsoModal')); if (m) m.hide();
            } else { showToast('danger', data.message || 'Erro ao emitir avulso'); }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = o; showToast('danger', 'Erro de comunicação com o servidor'); });
}

// ------------------------------------------------ verificação de pagamento
function initVerificacao() {
    const g = document.getElementById('btnAtualizarEmAberto');
    if (g) g.addEventListener('click', atualizarEmAberto);
    document.querySelectorAll('.btn-verificar-pgto').forEach(b => {
        b.addEventListener('click', function () { verificarPgto(this.dataset.id); });
    });
}

function atualizarEmAberto() {
    const meses = parseInt((document.getElementById('verifMeses') || {}).value || 3, 10);
    const btn = document.getElementById('btnAtualizarEmAberto');
    const o = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verificando...';
    fetch(window.COBRANCA_ROUTES.atualizarEmAberto, { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ meses: meses }) })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = o;
            showToast(data.success ? 'success' : 'danger', data.message || 'Concluído');
            if (data.success) setTimeout(() => location.reload(), 1200);
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = o; showToast('danger', 'Erro de comunicação com o servidor'); });
}

function verificarPgto(id) {
    const btn = document.querySelector(`.btn-verificar-pgto[data-id="${id}"]`);
    const o = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch(window.COBRANCA_ROUTES.verificarPagamento.replace('__ID__', id), { method: 'POST', headers: jsonHeaders() })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = o;
            showToast(data.success ? 'success' : 'warning', data.message || 'Consulta realizada');
            if (data.success && data.statusLabel) {
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    const cell = row.querySelector('td:nth-child(10)');
                    if (cell && data.statusClass) cell.innerHTML = `<span class="badge ${data.statusClass}">${data.statusLabel}</span>`;
                }
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = o; showToast('danger', 'Erro de comunicação com o servidor'); });
}

// ------------------------------------------------------------------ seleção
function initCheckboxes(checkAll, cobrancaChecks, btnRelatorio, contador) {
    checkAll.addEventListener('change', function () {
        cobrancaChecks.forEach(check => { check.checked = this.checked; });
        atualizarContador(cobrancaChecks, btnRelatorio, contador);
    });

    cobrancaChecks.forEach(check => {
        check.addEventListener('change', function () {
            atualizarContador(cobrancaChecks, btnRelatorio, contador);
            const todos = Array.from(cobrancaChecks).every(c => c.checked);
            const alguns = Array.from(cobrancaChecks).some(c => c.checked);
            checkAll.checked = todos;
            checkAll.indeterminate = alguns && !todos;
        });
    });
}

function atualizarContador(checks, btnRelatorio, contador) {
    const qtd = getSelecionados(checks).length;
    if (contador) contador.textContent = qtd;
    if (btnRelatorio) btnRelatorio.disabled = qtd === 0;
}

function getSelecionados(checks) {
    return Array.from(checks).filter(c => c.checked).map(c => parseInt(c.value, 10));
}

// -------------------------------------------------- relatório de conferência
function initRelatorio() {
    const btnRelatorio = document.getElementById('btnRelatorioConferencia');
    const btnAprovar = document.getElementById('btnConfirmarEnvio');

    if (btnRelatorio) {
        btnRelatorio.addEventListener('click', function () {
            const selecionados = getSelecionados(document.querySelectorAll('.cobranca-check'));
            if (selecionados.length > 0) abrirRelatorio(selecionados);
        });
    }

    if (btnAprovar) {
        btnAprovar.addEventListener('click', function () {
            const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
            if (modal) modal.hide();
            if (idsParaEmitir.length > 0) emitirBoletos(idsParaEmitir);
        });
    }
}

// Botões "enviar" por linha: também passam pelo relatório de conferência (1 item).
function initBotoesEnviar() {
    document.querySelectorAll('.btn-enviar').forEach(btn => {
        btn.addEventListener('click', function () {
            abrirRelatorio([parseInt(this.dataset.id, 10)]);
        });
    });
}

function abrirRelatorio(ids) {
    idsParaEmitir = ids;
    carregarPreview(ids);
}

function carregarPreview(ids) {
    const content = document.getElementById('previewContent');
    content.innerHTML = spinner();
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();

    fetch(window.COBRANCA_ROUTES.preview, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ ids: ids })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderPreview(data, content);
            else content.innerHTML = `<div class="alert alert-danger">${data.message || 'Erro ao carregar relatório'}</div>`;
        })
        .catch(() => { content.innerHTML = '<div class="alert alert-danger">Erro de comunicação com o servidor</div>'; });
}

function renderPreview(data, container) {
    let html = `
        <div class="alert alert-info">
            <i class="fas fa-clipboard-check"></i> Relatório de conferência —
            <strong>${data.quantidade}</strong> boleto(s) a emitir.<br>
            <strong>Valor Total: ${data.valor_total_formatado}</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-dark"><tr>
                    <th>Contrato</th><th>Locatário</th><th>Competência</th>
                    <th>Vencimento</th><th class="text-end">Valor</th>
                </tr></thead><tbody>`;
    data.cobrancas.forEach(c => {
        html += `<tr><td>#${c.contrato}</td><td>${c.locatario}</td><td>${c.competencia}</td>
                 <td>${c.vencimento}</td><td class="text-end">${c.valor_formatado}</td></tr>`;
    });
    html += `</tbody></table></div>
        <div class="alert alert-warning mb-0"><i class="fas fa-triangle-exclamation"></i>
        Ao aprovar, os boletos serão gerados e enviados. Confira antes de aprovar.</div>`;
    container.innerHTML = html;
}

// ------------------------------------------------------------------ emissão
function emitirBoletos(ids) {
    const btn = document.getElementById('btnRelatorioConferencia');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Emitindo...'; }

    fetch(window.COBRANCA_ROUTES.enviarLote, {
        method: 'POST',
        headers: jsonHeaders(),
        // aprovado=true: confirma que o relatório de conferência foi aprovado (o servidor exige)
        body: JSON.stringify({ ids: ids, aprovado: true })
    })
        .then(r => r.json())
        .then(data => {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
            if (data.success) mostrarResultadoLote(data);
            else showToast('danger', data.message || 'Erro ao emitir boletos');
        })
        .catch(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
            showToast('danger', 'Erro de comunicação com o servidor');
        });
}

function mostrarResultadoLote(data) {
    const content = document.getElementById('resultadoLoteContent');
    let html = `
        <div class="alert ${data.falha > 0 ? 'alert-warning' : 'alert-success'}"><strong>${data.message}</strong></div>
        <div class="row text-center mb-3">
            <div class="col"><h4 class="text-success">${data.sucesso}</h4><small>Sucesso</small></div>
            <div class="col"><h4 class="text-danger">${data.falha}</h4><small>Falha</small></div>
            <div class="col"><h4 class="text-muted">${data.total}</h4><small>Total</small></div>
        </div>`;
    if (data.detalhes && data.detalhes.length > 0) {
        html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Status</th><th>Mensagem</th></tr></thead><tbody>';
        data.detalhes.forEach(d => {
            const cls = d.sucesso ? 'text-success' : 'text-danger';
            const ico = d.sucesso ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>';
            html += `<tr><td>#${d.id}</td><td class="${cls}">${ico}</td><td>${d.mensagem || '-'}</td></tr>`;
        });
        html += '</tbody></table></div>';
    }
    content.innerHTML = html;
    new bootstrap.Modal(document.getElementById('resultadoLoteModal')).show();
}

// ----------------------------------------------------- gerar relação período
function initGeracaoPeriodo() {
    const modo = document.getElementById('gerModo');
    if (!modo) return;
    const toggle = () => {
        const isComp = modo.value === 'competencia';
        document.querySelectorAll('.modo-competencia').forEach(el => { el.style.display = isComp ? '' : 'none'; });
        document.querySelectorAll('.modo-vencimento').forEach(el => { el.style.display = isComp ? 'none' : ''; });
    };
    modo.addEventListener('change', toggle);
    toggle();
}

// --------------------------------------------------------- edição de itens
function initEdicaoItens() {
    document.querySelectorAll('.btn-editar-itens').forEach(btn => {
        btn.addEventListener('click', function () { abrirEdicaoItens(parseInt(this.dataset.id, 10)); });
    });

    const btnAdd = document.getElementById('btnAddItem');
    if (btnAdd) btnAdd.addEventListener('click', () => adicionarLinhaItem());

    const btnSalvar = document.getElementById('btnSalvarItens');
    if (btnSalvar) btnSalvar.addEventListener('click', salvarItens);
}

let editItensTipos = {};
let editItensCobrancaId = null;

function abrirEdicaoItens(id) {
    editItensCobrancaId = id;
    const url = window.COBRANCA_ROUTES.itensGet.replace('__ID__', id);
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('danger', data.message || 'Erro'); return; }
            editItensTipos = data.tipos || {};
            const corpo = document.getElementById('editItensBody');
            corpo.innerHTML = '';
            (data.itens || []).forEach(it => adicionarLinhaItem(it));
            if ((data.itens || []).length === 0) adicionarLinhaItem();
            document.getElementById('editItensCompetencia').textContent = data.competencia || '';
            document.getElementById('btnSalvarItens').disabled = !data.podeEditar;
            document.getElementById('editItensAviso').style.display = data.podeEditar ? 'none' : '';
            atualizarTotalItens();
            new bootstrap.Modal(document.getElementById('editItensModal')).show();
        })
        .catch(() => showToast('danger', 'Erro de comunicação com o servidor'));
}

function adicionarLinhaItem(item) {
    item = item || { tipo: 'OUTROS', descricao: '', valor: '' };
    const corpo = document.getElementById('editItensBody');
    const tr = document.createElement('tr');
    let opts = '';
    Object.keys(editItensTipos).forEach(k => {
        opts += `<option value="${k}" ${k === item.tipo ? 'selected' : ''}>${editItensTipos[k]}</option>`;
    });
    const valorFmt = item.valor !== '' && item.valor !== undefined
        ? Number(item.valor).toFixed(2).replace('.', ',') : '';
    tr.innerHTML = `
        <td><select class="form-select form-select-sm item-tipo">${opts}</select></td>
        <td><input type="text" class="form-control form-control-sm item-descricao" value="${(item.descricao || '').replace(/"/g, '&quot;')}" placeholder="Descrição"></td>
        <td><input type="text" class="form-control form-control-sm item-valor text-end" value="${valorFmt}" placeholder="0,00" inputmode="decimal"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm item-remover"><i class="fas fa-trash"></i></button></td>`;
    corpo.appendChild(tr);
    tr.querySelector('.item-remover').addEventListener('click', () => { tr.remove(); atualizarTotalItens(); });
    tr.querySelector('.item-valor').addEventListener('input', atualizarTotalItens);
}

function parseValor(str) {
    if (!str) return 0;
    str = String(str).trim().replace(/\./g, '').replace(',', '.');
    const v = parseFloat(str);
    return isNaN(v) ? 0 : v;
}

function atualizarTotalItens() {
    let total = 0;
    document.querySelectorAll('#editItensBody .item-valor').forEach(inp => { total += parseValor(inp.value); });
    document.getElementById('editItensTotal').textContent = 'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function salvarItens() {
    const itens = [];
    document.querySelectorAll('#editItensBody tr').forEach(tr => {
        const tipo = tr.querySelector('.item-tipo').value;
        const descricao = tr.querySelector('.item-descricao').value;
        const valor = parseValor(tr.querySelector('.item-valor').value);
        if (valor > 0) itens.push({ tipo, descricao, valor });
    });
    if (itens.length === 0) { showToast('warning', 'Informe ao menos um item com valor.'); return; }

    const url = window.COBRANCA_ROUTES.itensSave.replace('__ID__', editItensCobrancaId);
    fetch(url, { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ itens: itens }) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Itens atualizados.');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast('danger', data.message || 'Erro ao salvar itens');
            }
        })
        .catch(() => showToast('danger', 'Erro de comunicação com o servidor'));
}

// ------------------------------------------------------------------ cancelar
function initBotoesCancelar() {
    document.querySelectorAll('.btn-cancelar').forEach(btn => {
        btn.addEventListener('click', function () { confirmarCancelamento(this.dataset.id); });
    });
}

function confirmarCancelamento(id) {
    if (!confirm('Remover esta cobrança da relação? (ela é cancelada e sai da lista)')) return;
    const btn = document.querySelector(`.btn-cancelar[data-id="${id}"]`);
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(window.COBRANCA_ROUTES.cancelar.replace('__ID__', id), { method: 'POST', headers: jsonHeaders() })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Cobrança removida da relação.');
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (row) row.remove(); else location.reload();
            } else {
                showToast('danger', data.message || 'Erro ao cancelar');
                btn.disabled = false; btn.innerHTML = original;
            }
        })
        .catch(() => { showToast('danger', 'Erro de comunicação com o servidor'); btn.disabled = false; btn.innerHTML = original; });
}

// ------------------------------------------------------- marcar entregue
function initBotoesMarcarEntregue() {
    document.querySelectorAll('.btn-marcar-entregue').forEach(btn => {
        btn.addEventListener('click', function () { confirmarMarcarEntregue(this.dataset.id); });
    });
}

function confirmarMarcarEntregue(id) {
    if (!confirm('Confirma que o boleto foi impresso e entregue/postado?')) return;
    const btn = document.querySelector(`.btn-marcar-entregue[data-id="${id}"]`);
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(window.COBRANCA_ROUTES.marcarEntregue.replace('__ID__', id), { method: 'POST', headers: jsonHeaders() })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showToast('success', data.message || 'Marcado como entregue!'); location.reload(); }
            else { showToast('danger', data.message || 'Erro'); btn.disabled = false; btn.innerHTML = original; }
        })
        .catch(() => { showToast('danger', 'Erro de comunicação com o servidor'); btn.disabled = false; btn.innerHTML = original; });
}

// -------------------------------------------------- faixas de vencimento
function initBotoesFaixaVencimento() {
    document.querySelectorAll('.btn-faixa-vencimento').forEach(btn => {
        btn.addEventListener('click', function () {
            const hoje = new Date();
            const ano = hoje.getFullYear();
            const mes = hoje.getMonth();
            const ultimoDia = new Date(ano, mes + 1, 0).getDate();
            const diaInicio = parseInt(this.dataset.inicio, 10);
            const diaFim = Math.min(parseInt(this.dataset.fim, 10), ultimoDia);
            const fmt = (a, mIdx, d) => `${a}-${String(mIdx + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dv = document.getElementById('data_vencimento');
            if (dv) dv.value = '';
            document.getElementById('vencimento_inicio').value = fmt(ano, mes, diaInicio);
            document.getElementById('vencimento_fim').value = fmt(ano, mes, diaFim);
            document.getElementById('form-filtro-cobranca').submit();
        });
    });
}

// ------------------------------------------------------------------ helpers
function jsonHeaders() {
    return {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
}

function spinner() {
    return '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
}

function showToast(type, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const toastId = 'toast-' + Date.now();
    const bg = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger'
        : type === 'warning' ? 'bg-warning text-dark' : 'bg-info';
    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center ${bg} text-white border-0" role="alert">
            <div class="d-flex"><div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>`);
    const el = document.getElementById(toastId);
    new bootstrap.Toast(el, { delay: 5000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

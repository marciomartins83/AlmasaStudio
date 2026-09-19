/**
 * Reajustes de Contratos
 * Simula e aplica reajuste de aluguel (IGPM/TJ) por contrato
 */
document.addEventListener('DOMContentLoaded', function () {
    let contratoIdAtual = null;
    const modalEl = document.getElementById('modalReajuste');
    const modal = new bootstrap.Modal(modalEl);

    document.querySelectorAll('.btn-simular').forEach(function (btn) {
        btn.addEventListener('click', function () {
            contratoIdAtual = btn.dataset.contratoId;
            abrirSimulacao(contratoIdAtual);
        });
    });

    document.getElementById('btnAplicarReajuste').addEventListener('click', function () {
        if (contratoIdAtual) {
            aplicarReajuste(contratoIdAtual);
        }
    });

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function formatarMoeda(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function resetarModal() {
        document.getElementById('modalReajusteLoading').classList.remove('d-none');
        document.getElementById('modalReajusteConteudo').classList.add('d-none');
        document.getElementById('modalReajusteErro').classList.add('d-none');
        document.getElementById('btnAplicarReajuste').classList.add('d-none');
    }

    function abrirSimulacao(contratoId) {
        resetarModal();
        modal.show();

        const url = window.ROUTES.simularReajuste.replace('__ID__', contratoId);
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                document.getElementById('modalReajusteLoading').classList.add('d-none');

                if (!data.success) {
                    document.getElementById('modalReajusteErro').textContent = data.message;
                    document.getElementById('modalReajusteErro').classList.remove('d-none');
                    return;
                }

                preencherPreview(data.preview);
                document.getElementById('modalReajusteConteudo').classList.remove('d-none');
                document.getElementById('btnAplicarReajuste').classList.remove('d-none');
            })
            .catch(function (error) {
                document.getElementById('modalReajusteLoading').classList.add('d-none');
                document.getElementById('modalReajusteErro').textContent = 'Erro ao simular reajuste: ' + error.message;
                document.getElementById('modalReajusteErro').classList.remove('d-none');
            });
    }

    function preencherPreview(preview) {
        const label = preview.indice_tipo === 'IGPM' ? 'IGPM' : 'Tribunal de Justiça';
        document.getElementById('modalIndiceTipo').textContent = label + ' (' + (preview.tipo_valor === 'indice' ? 'Índice' : 'Percentual') + ')';
        document.getElementById('modalCompetencias').textContent = preview.competencia_base + ' → ' + preview.competencia_atual;
        document.getElementById('modalFator').textContent = Number(preview.fator_aplicado).toFixed(6);
        document.getElementById('modalValorAnterior').textContent = formatarMoeda(preview.valor_anterior);
        document.getElementById('modalValorNovo').textContent = formatarMoeda(preview.valor_novo);
    }

    function aplicarReajuste(contratoId) {
        const btn = document.getElementById('btnAplicarReajuste');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Aplicando...';

        const url = window.ROUTES.aplicarReajuste.replace('__ID__', contratoId);
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.success) {
                    document.getElementById('modalReajusteErro').textContent = data.message;
                    document.getElementById('modalReajusteErro').classList.remove('d-none');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i> Aplicar Reajuste';
                    return;
                }

                // Atualiza a linha da tabela e remove da lista de pendentes
                const linha = document.querySelector('tr[data-contrato-id="' + contratoId + '"]');
                if (linha) {
                    linha.remove();
                }

                modal.hide();
                window.location.reload();
            })
            .catch(function (error) {
                document.getElementById('modalReajusteErro').textContent = 'Erro ao aplicar reajuste: ' + error.message;
                document.getElementById('modalReajusteErro').classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Aplicar Reajuste';
            });
    }
});

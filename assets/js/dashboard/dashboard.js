/**
 * Dashboard - Lógica dos cards interativos
 */

// Função para mostrar/ocultar detalhes dos cards
window.toggleDetails = function(detailId) {
    const detailElement = document.getElementById(`details-${detailId}`);
    if (detailElement) {
        detailElement.classList.toggle('d-none');
    }
};

console.log('✅ Dashboard carregado com sucesso');

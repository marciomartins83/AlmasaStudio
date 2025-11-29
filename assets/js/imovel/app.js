/**
 * app.js - Arquivo principal do módulo de imóveis
 *
 * Inicializa todos os módulos JavaScript
 * 100% MODULAR - SEM CÓDIGO INLINE
 */

import * as ImovelPropriedades from './imovel_propriedades.js';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando módulo de imóveis...');

    // Verifica se estamos na página de edição de imóvel
    const propriedadesContainer = document.getElementById('propriedades-container');

    if (propriedadesContainer) {
        console.log('📦 Inicializando gerenciamento de propriedades...');
        ImovelPropriedades.init();
    }

    console.log('✅ Módulo de imóveis inicializado');
});

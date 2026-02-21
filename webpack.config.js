const Encore = require('@symfony/webpack-encore');

// Configura o ambiente de execução (dev por padrão)
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Diretório de saída dos assets compilados
    .setOutputPath('public/build/')
    // Caminho público usado pelo servidor web
    // Em produção, o app roda em /almasa, então os assets ficam em /almasa/build
    .setPublicPath(process.env.PUBLIC_PATH || '/build')
    .setManifestKeyPrefix('build/')

    /*
     * ENTRADAS
     * Cada entry gera um arquivo .js e .css correspondente
     */
    .addEntry('app', './assets/app.js')

    // Módulo de Informe de Rendimentos / DIMOB
    .addEntry('js/informe_rendimento/informe_rendimento', './assets/js/informe_rendimento/informe_rendimento.js')

    // Módulo Financeiro - Ficha Financeira / Contas a Receber
    .addEntry('financeiro', './assets/js/financeiro/financeiro.js')
    .addEntry('financeiro_form', './assets/js/financeiro/financeiro_form.js')

    // Módulo Configuração API Bancária
    .addEntry('configuracao_api_banco', './assets/js/configuracao_api_banco/configuracao_api_banco.js')

    // Módulo Boletos
    .addEntry('boleto', './assets/js/boleto/boleto.js')
    .addEntry('boleto_form', './assets/js/boleto/boleto_form.js')

    // Módulo Cobrança Automática
    .addEntry('cobranca', './assets/js/cobranca/cobranca.js')

    // Módulo Lançamentos (Contas a Pagar/Receber)
    .addEntry('lancamentos', './assets/js/lancamentos/app.js')

    // Módulo Prestação de Contas
    .addEntry('prestacao_contas', './assets/js/prestacao_contas/app.js')

    // Módulo Relatórios PDF
    .addEntry('relatorios', './assets/js/relatorios/app.js')

    // Divide os arquivos em pedaços otimizados
    .splitEntryChunks()

    // Cria runtime separado (recomendado)
    .enableSingleRuntimeChunk()

    // Limpa o diretório build antes da compilação
    .cleanupOutputBeforeBuild()

    // Ativa source maps em modo dev
    .enableSourceMaps(!Encore.isProduction())

    // Ativa versionamento (hash nos nomes)
    .enableVersioning(Encore.isProduction())

    /*
     * SUPORTE A SASS/SCSS (Bootstrap e estilos customizados)
     */
    .enableSassLoader()
    ;

module.exports = Encore.getWebpackConfig();

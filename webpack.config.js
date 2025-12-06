const Encore = require('@symfony/webpack-encore');

// Configura o ambiente de execução (dev por padrão)
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Diretório de saída dos assets compilados
    .setOutputPath('public/build/')
    // Caminho público usado pelo servidor web
    .setPublicPath('/build')

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

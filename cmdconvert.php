<?php
// remove_constraints.php - Remove todas as constraints problemáticas

echo "Removendo constraints problemáticas...\n";

$constraints = [
    'contas_vinculadas' => [
        'contas_vinculadas_id_conta_principal_id_conta_vinculada_tip_key'
    ],
    'pessoas_contratantes' => [
        'pessoas_contratantes_id_pessoa_key'
    ],
    'pessoas_corretoras' => [
        'pessoas_corretoras_id_pessoa_key'
    ],
    'chaves_pix' => [
        'chaves_pix_chave_pix_id_tipo_chave_key'
    ],
    'pessoas_corretores' => [
        'pessoas_corretores_creci_key',
        'pessoas_corretores_id_pessoa_key'
    ],
    'relacionamentos_familiares' => [
        'relacionamentos_familiares_id_pessoa_origem_id_pessoa_desti_key'
    ],
    'fiadores_inquilinos' => [
        'fiadores_inquilinos_id_fiador_id_inquilino_data_inicio_key'
    ],
    'pessoas_pretendentes' => [
        'pessoas_pretendentes_id_pessoa_key'
    ],
    'pessoas_emails' => [
        'pessoas_emails_id_pessoa_id_email_key'
    ],
    'users' => [
        'users_email_key'
    ],
    'personal_access_tokens' => [
        'personal_access_tokens_token_key'
    ],
    'permissions' => [
        'permissions_name_guard_name_key'
    ],
    'agencias' => [
        'agencias_codigo_id_banco_key'
    ],
    'failed_jobs' => [
        'failed_jobs_uuid_key'
    ],
    'roles' => [
        'roles_name_guard_name_key'
    ],
    'pessoas_tipos' => [
        'pessoas_tipos_id_pessoa_id_tipo_pessoa_data_inicio_key'
    ],
    'estados' => [
        'estados_uf_key'
    ],
    'pessoas_telefones' => [
        'pessoas_telefones_id_pessoa_id_telefone_key'
    ],
    'pessoas_documentos' => [
        'pessoas_documentos_id_pessoa_id_tipo_documento_numero_docum_key'
    ],
    'pessoas_locadores' => [
        'pessoas_locadores_id_pessoa_key'
    ],
    'pessoas_fiadores' => [
        'pessoas_fiadores_id_pessoa_key'
    ],
    'bancos' => [
        'bancos_numero_key'
    ]
];

// Função para executar SQL
function executeSql($sql) {
    $command = sprintf('php bin/console doctrine:query:sql "%s"', addslashes($sql));
    exec($command, $output, $returnCode);
    return $returnCode === 0;
}

// Remover todas as constraints
foreach ($constraints as $table => $tableConstraints) {
    echo "\nProcessando tabela: $table\n";
    
    foreach ($tableConstraints as $constraint) {
        $sql = "ALTER TABLE $table DROP CONSTRAINT IF EXISTS $constraint;";
        echo "  Removendo constraint: $constraint... ";
        
        if (executeSql($sql)) {
            echo "✅ OK\n";
        } else {
            echo "❌ Erro (pode já estar removida)\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Todas as constraints foram processadas!\n";
echo "Agora execute: php bin/console doctrine:schema:update --force\n";
echo str_repeat("=", 50) . "\n";
?>
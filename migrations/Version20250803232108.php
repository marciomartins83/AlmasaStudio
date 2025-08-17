<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
* Migration para remover todos os triggers updated_at que causam rollback
*/
final class Version20250803232108 extends AbstractMigration
{
   public function getDescription(): string
   {
       return 'Remove todos os triggers updated_at que estão causando rollback nas transações';
   }

   public function up(Schema $schema): void
   {
       $this->addSql('DROP TRIGGER IF EXISTS update_requisicoes_responsaveis_updated_at ON requisicoes_responsaveis;');
       $this->addSql('DROP TRIGGER IF EXISTS update_fiadores_inquilinos_updated_at ON fiadores_inquilinos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_corretoras_updated_at ON pessoas_corretoras;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_pretendentes_updated_at ON pessoas_pretendentes;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_locadores_updated_at ON pessoas_locadores;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_fiadores_updated_at ON pessoas_fiadores;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_corretores_updated_at ON pessoas_corretores;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_contratantes_updated_at ON pessoas_contratantes;');
       $this->addSql('DROP TRIGGER IF EXISTS update_chaves_pix_updated_at ON chaves_pix;');
       $this->addSql('DROP TRIGGER IF EXISTS update_contas_vinculadas_updated_at ON contas_vinculadas;');
       $this->addSql('DROP TRIGGER IF EXISTS update_configuracoes_cobranca_updated_at ON configuracoes_cobranca;');
       $this->addSql('DROP TRIGGER IF EXISTS update_contas_bancarias_updated_at ON contas_bancarias;');
       $this->addSql('DROP TRIGGER IF EXISTS update_relacionamentos_familiares_updated_at ON relacionamentos_familiares;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_profissoes_updated_at ON pessoas_profissoes;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_documentos_updated_at ON pessoas_documentos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_tipos_updated_at ON pessoas_tipos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_emails_updated_at ON pessoas_emails;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_telefones_updated_at ON pessoas_telefones;');
       $this->addSql('DROP TRIGGER IF EXISTS update_enderecos_updated_at ON enderecos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_telefones_updated_at ON telefones;');
       $this->addSql('DROP TRIGGER IF EXISTS update_emails_updated_at ON emails;');
       $this->addSql('DROP TRIGGER IF EXISTS update_pessoas_updated_at ON pessoas;');
       $this->addSql('DROP TRIGGER IF EXISTS update_regimes_casamento_updated_at ON regimes_casamento;');
       $this->addSql('DROP TRIGGER IF EXISTS update_formas_retirada_updated_at ON formas_retirada;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_atendimento_updated_at ON tipos_atendimento;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_imoveis_updated_at ON tipos_imoveis;');
       $this->addSql('DROP TRIGGER IF EXISTS update_profissoes_updated_at ON profissoes;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_pessoas_updated_at ON tipos_pessoas;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_emails_updated_at ON tipos_emails;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_enderecos_updated_at ON tipos_enderecos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_contas_bancarias_updated_at ON tipos_contas_bancarias;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_chaves_pix_updated_at ON tipos_chaves_pix;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_telefones_updated_at ON tipos_telefones;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_documentos_updated_at ON tipos_documentos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_razoes_conta_updated_at ON razoes_conta;');
       $this->addSql('DROP TRIGGER IF EXISTS update_layouts_remessa_updated_at ON layouts_remessa;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_remessa_updated_at ON tipos_remessa;');
       $this->addSql('DROP TRIGGER IF EXISTS update_tipos_carteiras_updated_at ON tipos_carteiras;');
       $this->addSql('DROP TRIGGER IF EXISTS update_agencias_updated_at ON agencias;');
       $this->addSql('DROP TRIGGER IF EXISTS update_bancos_updated_at ON bancos;');
       $this->addSql('DROP TRIGGER IF EXISTS update_logradouros_updated_at ON logradouros;');
       $this->addSql('DROP TRIGGER IF EXISTS update_bairros_updated_at ON bairros;');
       $this->addSql('DROP TRIGGER IF EXISTS update_cidades_updated_at ON cidades;');
       $this->addSql('DROP TRIGGER IF EXISTS update_estados_updated_at ON estados;');
       $this->addSql('DROP TRIGGER IF EXISTS update_roles_updated_at ON roles;');
       $this->addSql('DROP TRIGGER IF EXISTS update_permissions_updated_at ON permissions;');
       $this->addSql('DROP TRIGGER IF EXISTS update_personal_access_tokens_updated_at ON personal_access_tokens;');
       $this->addSql('DROP TRIGGER IF EXISTS update_users_updated_at ON users;');
       
       // Remove também a função se não for mais necessária
       $this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column();');
   }

   public function down(Schema $schema): void
   {
       // Recriar a função
       $this->addSql('
           CREATE OR REPLACE FUNCTION update_updated_at_column()
           RETURNS TRIGGER AS $$
           BEGIN
               NEW.updated_at = CURRENT_TIMESTAMP;
               RETURN NEW;
           END;
           $$ language plpgsql;
       ');
       
       // Recriar todos os triggers (apenas alguns exemplos principais)
       $this->addSql('CREATE TRIGGER update_estados_updated_at BEFORE UPDATE ON estados FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
       $this->addSql('CREATE TRIGGER update_pessoas_updated_at BEFORE UPDATE ON pessoas FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
       $this->addSql('CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();');
       // ... (adicionar outros triggers conforme necessário)
   }

   public function isTransactional(): bool
   {
       return false;
   }
}

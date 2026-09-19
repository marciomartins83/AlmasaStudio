# DataFixtures - Sistema Almasa

## AdminSeeder

Este DataFixture cria um administrador completo para o sistema Almasa com dados realistas.

### Funcionalidades

- ✅ Cria usuário administrador com dados completos
- ✅ Estabelece relacionamento OneToOne entre Users e Pessoas
- ✅ Usa PasswordHasherInterface para hash seguro da senha
- ✅ Implementa transação para garantir integridade dos dados
- ✅ Dados realistas para pessoa física brasileira

### Dados do Administrador

| Campo | Valor |
|-------|-------|
| **Email** | marcioramos1983@gmail.com |
| **Senha** | 123 |
| **Nome** | Marcio Ramos |
| **Tipo Pessoa** | 1 (Administrador) |
| **Status** | true (Ativo) |
| **Física/Jurídica** | fisica |

### Dados Pessoais

| Campo | Valor |
|-------|-------|
| **Data Nascimento** | 15/05/1983 |
| **Estado Civil** | casado |
| **Nacionalidade** | Brasileira |
| **Naturalidade** | São Paulo |
| **Nome Pai** | João Carlos Ramos |
| **Nome Mãe** | Maria Aparecida Silva |
| **Renda** | R$ 8.500,00 |
| **Observações** | Administrador do sistema |
| **Theme Light** | true |

### Como Executar

```bash
# Executar o seeder
php bin/console doctrine:fixtures:load --no-interaction

# Ou executar apenas o AdminSeeder
php bin/console doctrine:fixtures:load --group=AdminSeeder --no-interaction
```

### Estrutura das Entidades

#### Users
- `id` (auto)
- `name`: Marcio Ramos
- `email`: marcioramos1983@gmail.com
- `email_verified_at`: now()
- `password`: hash de '123'

#### Pessoas
- `idpessoa` (auto)
- `nome`: Marcio Ramos
- `dt_cadastro`: now()
- `tipo_pessoa`: 1
- `status`: true
- `fisica_juridica`: fisica
- `data_nascimento`: 1983-05-15
- `estado_civil`: casado
- `nacionalidade`: Brasileira
- `naturalidade`: São Paulo
- `nome_pai`: João Carlos Ramos
- `nome_mae`: Maria Aparecida Silva
- `renda`: 8500.00
- `observacoes`: Administrador do sistema
- `theme_light`: true
- `user_id`: relacionamento com Users.id

### Relacionamento

O seeder estabelece um relacionamento OneToOne entre:
- `Users` → `Pessoas` (via `user_id`)

### Segurança

- ✅ Senha hashada com PasswordHasherInterface
- ✅ Transação para garantir integridade
- ✅ Rollback automático em caso de erro
- ✅ Validação de dados antes da persistência

### Logs

O seeder exibe informações de sucesso:
```
✅ Administrador criado com sucesso!
📧 Email: marcioramos1983@gmail.com
🔑 Senha: 123
👤 Nome: Marcio Ramos
🏢 Tipo: Administrador
``` 
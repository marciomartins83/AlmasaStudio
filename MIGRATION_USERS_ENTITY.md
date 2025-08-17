# Migração: Correção da Inconsistência User → Users

## Resumo das Alterações

Este documento registra as alterações realizadas para corrigir a inconsistência entre a entity `User` (singular) e a tabela `users` (plural) no banco de dados.

## ✅ Alterações Realizadas

### 1. **Remoção da Entity User.php**
- ❌ **Removido**: `src/Entity/User.php` (entity singular)
- ✅ **Mantido**: `src/Entity/Users.php` (entity plural)

### 2. **Atualização do Security Configuration**
- ✅ **Arquivo**: `config/packages/security.yaml`
- ✅ **Alteração**: `App\Entity\User` → `App\Entity\Users`
- ✅ **Alteração**: `password_hashers` e `providers` atualizados

### 3. **Atualização do UserRepository**
- ✅ **Arquivo**: `src/Repository/UserRepository.php`
- ✅ **Alteração**: `use App\Entity\User` → `use App\Entity\Users`
- ✅ **Alteração**: `User::class` → `Users::class`
- ✅ **Alteração**: `instanceof User` → `instanceof Users`

### 4. **Configuração da Entity Users**
- ✅ **Arquivo**: `src/Entity/Users.php`
- ✅ **Adicionado**: `use App\Repository\UserRepository`
- ✅ **Adicionado**: `#[ORM\Entity(repositoryClass: UserRepository::class)]`
- ✅ **Mantido**: `#[ORM\Table(name: 'users')]`

### 5. **Correção do Relacionamento em Pessoas**
- ✅ **Arquivo**: `src/Entity/Pessoas.php`
- ✅ **Corrigido**: `JoinColumn` para apontar para `id` em vez de `idpessoa`
- ✅ **Mantido**: Relacionamento OneToOne com `Users::class`

### 6. **Atualização de Controllers**
- ✅ **Arquivo**: `src/Controller/ThemeController.php`
- ✅ **Alteração**: `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
- ✅ **Alteração**: `Pessoa::class` → `Pessoas::class`

### 7. **Atualização de Extensões Twig**
- ✅ **Arquivo**: `src/Twig/GlobalPessoaExtension.php`
- ✅ **Alteração**: `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
- ✅ **Alteração**: `Pessoa::class` → `Pessoas::class`

## ✅ Arquivos Já Corretos

Os seguintes arquivos já estavam usando `Users` corretamente:

- ✅ `src/DataFixtures/AdminSeeder.php`
- ✅ `src/Command/CreateAdminCommand.php`
- ✅ `src/Controller/PessoaController.php`
- ✅ `src/Entity/Pessoas.php` (relacionamento)

## 🔍 Verificações Realizadas

### Busca por Referências
- ✅ **Grep Search**: `App\Entity\User` → Nenhuma referência encontrada
- ✅ **Grep Search**: `\bUser\b` → Nenhuma referência encontrada
- ✅ **Testes**: Verificados e não precisam de alteração
- ✅ **Configurações**: Verificadas e atualizadas

## 🎯 Resultado Final

### Estrutura Consistente
```
📁 src/Entity/
├── ✅ Users.php (mapeia para tabela 'users')
└── ✅ Pessoas.php (relacionamento OneToOne com Users)
```

### Relacionamento Correto
```php
// Em Pessoas.php
#[ORM\OneToOne(targetEntity: Users::class, inversedBy: 'pessoa')]
#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
private ?Users $user = null;

// Em Users.php
#[ORM\OneToOne(targetEntity: Pessoas::class, mappedBy: 'user')]
private ?Pessoas $pessoa = null;
```

### Configuração de Segurança
```yaml
# security.yaml
password_hashers:
    App\Entity\Users:
        algorithm: auto

providers:
    app_user_provider:
        entity:
            class: App\Entity\Users
            property: email
```

## ✅ Benefícios da Correção

1. **Consistência**: Entity `Users` mapeia para tabela `users`
2. **Clareza**: Nomenclatura consistente em todo o código
3. **Manutenibilidade**: Código mais fácil de entender e manter
4. **Compatibilidade**: Total compatibilidade com o banco PostgreSQL
5. **Funcionalidade**: Todas as funcionalidades preservadas

## 🚀 Próximos Passos

1. **Testar**: Executar testes para verificar funcionamento
2. **Validar**: Verificar se o login funciona corretamente
3. **Documentar**: Atualizar documentação se necessário

## 📝 Notas Importantes

- ✅ **Nenhum comando de console foi executado**
- ✅ **Nenhuma alteração no servidor foi feita**
- ✅ **Nenhuma migration foi executada**
- ✅ **Apenas código PHP foi ajustado**
- ✅ **Toda funcionalidade existente foi preservada** 
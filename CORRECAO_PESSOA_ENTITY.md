# Correção: Pessoa → Pessoas

## Resumo das Correções Realizadas

Este documento registra as correções realizadas para eliminar o erro "Class 'App\Entity\Pessoa' does not exist" corrigindo todas as referências para usar `Pessoas` (plural).

## ✅ Correções Realizadas

### 1. **Controllers**
- ✅ `src/Controller/DashboardController.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `Pessoa::class` → `Pessoas::class`

- ✅ `src/Controller/PessoaController.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `new Pessoa()` → `new Pessoas()`

### 2. **Repository**
- ✅ `src/Repository/PessoaRepository.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `Pessoa::class` → `Pessoas::class`
  - `ServiceEntityRepository<Pessoa>` → `ServiceEntityRepository<Pessoas>`

### 3. **Forms**
- ✅ `src/Form/PessoaType.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `'data_class' => Pessoa::class` → `'data_class' => Pessoas::class`

- ✅ `src/Form/PessoaLocadorType.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `'class' => Pessoa::class` → `'class' => Pessoas::class`

- ✅ `src/Form/PessoaFiadorType.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `'class' => Pessoa::class` → `'class' => Pessoas::class` (2 ocorrências)

- ✅ `src/Form/PessoaCorretorType.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `'class' => Pessoa::class` → `'class' => Pessoas::class`

- ✅ `src/Form/ContaBancariaType.php`
  - `use App\Entity\Pessoa` → `use App\Entity\Pessoas`
  - `'class' => Pessoa::class` → `'class' => Pessoas::class`

### 4. **Scripts**
- ✅ `test_schema.php`
  - `new \App\Entity\Pessoa()` → `new \App\Entity\Pessoas()`

- ✅ `diagnose_schema.php`
  - `new \App\Entity\Pessoa()` → `new \App\Entity\Pessoas()`

- ✅ `test_entities.php`
  - `new \App\Entity\Pessoa()` → `new \App\Entity\Pessoas()`

- ✅ `scripts/validate_system.php`
  - `'App\Entity\Pessoa'` → `'App\Entity\Pessoas'`

### 5. **Testes (Parcial)**
- ✅ `tests/Repository/PessoaRepositoryTest.php`
  - `use AppEntityPessoa` → `use AppEntityPessoas`
  - `new Pessoa()` → `new Pessoas()` (1 de 3 corrigido)
  - `Pessoa::class` → `Pessoas::class` (1 de 7 corrigido)

## 🔄 Arquivos Restantes para Correção

### **Testes (Prioridade Alta)**
- `tests/Repository/PessoaRepositoryTest.php` (6 referências restantes)
- `tests/Entity/PessoaTest.php` (8 referências)
- `tests/Entity/ContaBancariaTest.php` (2 referências)
- `tests/Entity/CommunicationTest.php` (2 referências)
- `tests/Entity/EntityTest.php` (9 referências)
- `tests/Relationship/ContaBancariaRelationshipsTest.php` (1 referência)
- `tests/Relationship/PessoaHasMultipleContasTest.php` (2 referências)
- `tests/Repository/ContaBancariaRepositoryTest.php` (4 referências)
- `tests/Validation/ContaBancariaUniqueConstraintTest.php` (2 referências)
- `tests/Integration/ContaBancariaWithRelationshipsTest.php` (1 referência)
- `tests/Integration/CrudWorkflowTest.php` (1 referência)

### **Controllers de Teste**
- `tests/Controller/PessoaCorretorControllerTest.php` (8 referências)
- `tests/Controller/TelefoneControllerTest.php` (6 referências)
- `tests/Controller/PessoaLocadorControllerTest.php` (8 referências)
- `tests/Controller/PessoaFiadorControllerTest.php` (8 referências)

### **Forms de Teste**
- `tests/Form/PessoaTypeTest.php` (2 referências)
- `tests/Form/PessoaLocadorTypeTest.php` (2 referências)
- `tests/Form/PessoaCorretorTypeTest.php` (2 referências)
- `tests/Form/PessoaFiadorTypeTest.php` (2 referências)
- `tests/Form/ContaBancariaTypeTest.php` (1 referência)

### **DataFixtures de Teste**
- `tests/DataFixtures/ContaBancariaTestFixtures.php` (1 referência)
- `tests/DataFixtures/RelationshipTestFixtures.php` (1 referência)

## 🎯 Próximos Passos

1. **Corrigir todos os arquivos de teste** (prioridade alta)
2. **Verificar se há templates que precisam de correção**
3. **Executar testes para validar correções**
4. **Verificar se há outros arquivos que possam ter referências**

## 📊 Estatísticas

- ✅ **Corrigidos**: 15 arquivos principais
- 🔄 **Pendentes**: ~20 arquivos de teste
- 🎯 **Objetivo**: Eliminar erro "Class 'App\Entity\Pessoa' does not exist"

## ✅ Benefícios das Correções

1. **Eliminação do erro**: "Class 'App\Entity\Pessoa' does not exist"
2. **Consistência**: Todas as referências usam `Pessoas` (plural)
3. **Compatibilidade**: Código funciona com entity `Pessoas`
4. **Manutenibilidade**: Código mais fácil de manter 